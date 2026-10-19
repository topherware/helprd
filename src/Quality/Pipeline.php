<?php

/** Langkah pipeline PRD v1.2 yang dipakai bersama oleh streaming, audit, tes, dan benchmark. */
final class Pipeline
{
    /** Kunci profil dari hasil potongan "profil" (Validator::aiProfil): stack terkunci, peran, bendera final. */
    public static function lockProfil(Brief $b, array $raw): array
    {
        $declared = $raw['stack_kustom'] + ['stack_id' => $raw['stack_id']];
        $stack = $b->resolveStack(array_filter($declared, fn ($v) => $v !== null && $v !== ''));
        $roles = $raw['peran'];
        $declaredInv = $raw['invarian'] ?? [];
        return [
            'bendera_ai' => $raw['bendera_ai'],
            'bendera' => Triggers::flags($b, $raw['bendera_ai'], $stack, $roles, $declaredInv),
            'stack' => $stack,
            'peran' => $roles,
            'peran_asal' => $raw['peran_asal'] ?? [],
            // Invarian final = deklarasi AI + kalimat masukan pengguna yang cocok dengan Triggers::INVARIAN.
            'invarian_ai' => $declaredInv,
            'invarian' => Triggers::invariants($b, $declaredInv),
            'alasan_stack' => $raw['alasan_stack'],
        ];
    }

    /**
     * Penegakan deterministik v1.2.1 setelah PRD dirakit (AI kadang mengabaikan aturan prompt, terbukti di uji AI sungguhan G6):
     * - fitur usulan yang belum disertakan dikeluarkan dari rute, navigasi, cakupan milestone, dan rencana tugas (P2);
     * - rencana tugas dipadatkan ≤ 10 per milestone dan ≤ 30 total dengan menggabung tugas bertetangga (P8, isi tidak dibuang);
     * - Master Prompt dilengkapi butir gerbang yang belum ada (P10).
     */
    public static function finalize(Brief $b, array $prd): array
    {
        $names = array_values(array_filter(array_map(fn ($u) => mb_strtolower(trim($u['nama'])), $b->usulan)));
        $isUsulan = fn (string $s) => (bool) array_filter($names, fn ($n) => mb_stripos($s, $n) !== false);
        if ($names && isset($prd['rute']['rute'])) {
            $kept = [];
            foreach ($prd['rute']['rute'] as $r) {
                $own = array_values(array_filter($r['sumber'], fn ($s) => !$isUsulan($s)));
                if ($r['sumber'] && !$own && $isUsulan(implode(' ', $r['sumber']))) {
                    continue; // rute yang hanya melayani fitur usulan
                }
                $kept[] = ['sumber' => $own] + $r;
            }
            $prd['rute']['rute'] = $kept;
            foreach ($prd['rute']['navigasi'] ?? [] as $i => $nav) {
                $prd['rute']['navigasi'][$i]['item'] = array_values(array_filter($nav['item'], fn ($x) => !$isUsulan($x)));
            }
        }
        if (isset($prd['milestone'])) {
            $m = &$prd['milestone'];
            if ($names) {
                foreach ($m['daftar'] as $i => $x) {
                    $m['daftar'][$i]['cakupan'] = array_values(array_filter($x['cakupan'], fn ($c) => !$isUsulan($c)));
                }
                $m['rencana'] = array_values(array_filter($m['rencana'] ?? [], fn ($t) => !$isUsulan($t['judul'] . ' ' . $t['perilaku'])));
            }
            $m['rencana'] = self::compactPlan($m['rencana'] ?? []);
            $m['master_prompt'] = self::gatePrompt($b, $m['master_prompt'] ?? '');
            unset($m);
        }
        return $prd;
    }

    /** Gabung tugas rencana bertetangga dalam milestone yang sama sampai ≤ 10 per milestone dan ≤ 30 total; ID dinomori ulang. */
    public static function compactPlan(array $plan): array
    {
        if (!$plan) {
            return $plan;
        }
        $groups = [];
        foreach ($plan as $t) {
            $groups[preg_match('/^M(\d+)\./', $t['id'], $mm) ? (int) $mm[1] : 9][] = $t;
        }
        ksort($groups);
        $merge = function (array $a, array $c): array {
            $out = $a;
            $out['judul'] = mb_substr($a['judul'] . ' + ' . $c['judul'], 0, 150);
            foreach (['berkas', 'dataset', 'sec', 'fr', 'tabel'] as $k) {
                $out[$k] = array_values(array_unique(array_merge($a[$k], $c[$k])));
            }
            $out['perilaku'] = mb_substr(trim($a['perilaku'] . ' ' . $c['perilaku']), 0, 800);
            $out['selesai_bila'] = mb_substr(trim($a['selesai_bila'] . ' ' . $c['selesai_bila']), 0, 400);
            return $out;
        };
        foreach ($groups as $ms => $list) {
            while (count($list) > P08Batas::PER_MILESTONE) {
                $list = self::mergeSmallest($list, $merge);
            }
            $groups[$ms] = $list;
        }
        while (array_sum(array_map('count', $groups)) > P08Batas::RENCANA) {
            $ms = array_search(max(array_map('count', $groups)), array_map('count', $groups), true);
            $groups[$ms] = self::mergeSmallest($groups[$ms], $merge);
        }
        $out = [];
        foreach ($groups as $ms => $list) {
            foreach (array_values($list) as $k => $t) {
                $out[] = ['id' => ($ms === 9 ? 'M9' : "M$ms") . '.' . ($k + 1)] + $t;
            }
        }
        return $out;
    }

    /** Gabung pasangan bertetangga dengan teks terpendek (paling sedikit kehilangan keterbacaan). */
    private static function mergeSmallest(array $list, callable $merge): array
    {
        $best = 0;
        $size = PHP_INT_MAX;
        for ($i = 0; $i < count($list) - 1; $i++) {
            $n = mb_strlen($list[$i]['perilaku'] . $list[$i + 1]['perilaku']);
            if ($n < $size) {
                [$best, $size] = [$i, $n];
            }
        }
        array_splice($list, $best, 2, [$merge($list[$best], $list[$best + 1])]);
        return $list;
    }

    /** Lengkapi Master Prompt dengan butir gerbang P10 yang belum ada, sesuai Cara Kerja AI. */
    public static function gatePrompt(Brief $b, string $prompt): string
    {
        if (trim($prompt) === '') {
            return $prompt;
        }
        $lines = [];
        $has = fn (string $key) => (bool) preg_match(P10MasterPrompt::BUTIR[$key], $prompt);
        if (!$has('ringkasan pemahaman maks. 10 poin')) {
            $lines[] = 'Sebelum menulis kode, berikan ringkasan pemahamanmu maksimal 10 poin.';
        }
        if (!$has('rencana berkas milestone pertama')) {
            $lines[] = 'Sebutkan rencana berkas untuk milestone pertama.';
        }
        if (!$has('pertanyaan kesiapan')) {
            $lines[] = 'Lalu tanyakan apakah saya siap kamu mulai, dan tunggu jawaban saya.';
        }
        if ($b->aiWorkflow !== 'sekaligus' && !preg_match(P10MasterPrompt::GERBANG, $prompt)) {
            $lines[] = $b->aiWorkflow === 'task'
                ? 'Kerjakan satu task per giliran; task berikutnya hanya dimulai setelah saya memberi izin.'
                : 'Milestone berikutnya hanya dimulai setelah saya memberi izin.';
        }
        if ($b->aiWorkflow === 'task' && !preg_match('/(setiap|tiap|per|satu)\s+task/iu', $prompt)) {
            $lines[] = 'Berhenti di setiap task untuk konfirmasi.';
        }
        if (!$has('larangan mengarang data')) {
            $lines[] = 'Jangan mengarang data di luar bab 11.';
        }
        return $lines ? rtrim($prompt) . "\n\nGERBANG KERJA:\n- " . implode("\n- ", $lines) : $prompt;
    }

    /** Konteks prompt untuk potongan berikutnya; kontrol keamanan dihitung dari PRD yang sudah ditulis (tabel & tech stack). */
    public static function context(Brief $b, array $profil, array $prd): array
    {
        $security = SecurityTriggers::detect(['ide_awal' => $b->ide], $b->features, $b->qa, $prd);
        return [
            'stack' => $profil['stack'],
            'flags' => $profil['bendera'],
            'roles' => $profil['peran'],
            'invariants' => $profil['invarian'] ?? Triggers::invariants($b),
            'security' => $security,
            'controls' => SecurityTriggers::tiers($security),
        ];
    }

    /** PRD lengkap mode tiruan (tanpa AI), sudah dirapikan seperti hasil pipeline. */
    public static function mockPrd(Brief $b): array
    {
        $profil = self::lockProfil($b, Validator::aiProfil(MockData::profil($b)));
        $raw = MockData::prd($b, self::context($b, $profil, []));
        $prd = Validator::aiPrd(json_decode(json_encode($raw), true), count($b->features));
        $prd['profil'] = $profil;
        return self::finalize($b, $prd);
    }

    /** Task tiruan untuk semua fitur: [fitur, title, milestone, status]. */
    public static function mockTasks(Brief $b, array $prd): array
    {
        $refs = self::taskRefs($prd);
        $chunk = array_map(fn ($f, $i) => ['id' => $i + 1, 'nama' => $f['nama'], 'fase' => $f['fase'],
            'subs' => array_map(fn ($s, $j) => ['id' => ($i + 1) * 100 + $j, 'nama' => $s], $f['sub_fitur'], array_keys($f['sub_fitur']))], $b->features, array_keys($b->features));
        $result = Validator::aiTasks(MockData::tasks($chunk, $refs), $chunk, $refs['ui_only']);
        $tasks = [];
        foreach ($chunk as $f) {
            foreach ($result[$f['id']]['tasks'] as $t) {
                $tasks[] = ['fitur' => $f['nama'], 'title' => $t['title'], 'milestone' => $t['milestone'], 'koordinasi' => $t['koordinasi'], 'status' => 'belum_mulai'];
            }
        }
        return $tasks;
    }

    /** Rujukan untuk Bikin Task: ID dataset, SEC Wajib MVP beserta fiturnya, mode hanya-UI. */
    public static function taskRefs(array $prd): array
    {
        $sec = $prd['keamanan']['persyaratan'] ?? [];
        return [
            'dataset' => array_column($prd['dummy']['dataset'] ?? [], 'id'),
            'sec' => array_column($sec, 'teks', 'id'),
            'sec_fitur' => array_column($sec, 'fitur', 'id'),
            'ui_only' => !empty($prd['profil']['bendera']['uiOnly']),
            'rencana' => array_map(fn ($r) => ['id' => $r['id'], 'judul' => $r['judul']], $prd['milestone']['rencana'] ?? []),
        ];
    }

    /**
     * Pass 5 untuk batas task (P8): gabungkan task bertetangga dengan milestone yang sama (rujukan Task M<n>.<k> sama lebih dulu)
     * sampai ≤ $max. Tidak menambah dan tidak membuang teks: judul digabung "A; b" selama ≤ 200 karakter (batas kolom).
     * @param array $tasks baris urut sort_order: id, title, milestone, koordinasi
     * @return array{judul: array<int, string>, hapus: int[]} judul baru per id yang dipertahankan, id yang diserap
     */
    public static function mergeTasks(array $tasks, int $max = P08Batas::PER_FITUR): array
    {
        $rows = array_values($tasks);
        $absorbed = [];
        while (count($rows) > $max) {
            $pick = null;
            foreach ([true, false] as $sameRef) {
                for ($i = 0; $i < count($rows) - 1 && $pick === null; $i++) {
                    [$a, $b] = [$rows[$i], $rows[$i + 1]];
                    $title = $a['title'] . '; ' . mb_strtolower(mb_substr($b['title'], 0, 1)) . mb_substr($b['title'], 1);
                    if (($a['milestone'] ?? '') === ($b['milestone'] ?? '') && (!$sameRef || ($a['koordinasi'] ?? '') === ($b['koordinasi'] ?? ''))
                        && mb_strlen($title) <= 200) {
                        $pick = [$i, $title];
                    }
                }
                if ($pick) {
                    break;
                }
            }
            if (!$pick) {
                break;
            }
            [$i, $title] = $pick;
            $absorbed[] = (int) $rows[$i + 1]['id'];
            $rows[$i]['title'] = $title;
            array_splice($rows, $i + 1, 1);
        }
        $changed = [];
        foreach ($rows as $r) {
            $orig = array_values(array_filter($tasks, fn ($t) => $t['id'] === $r['id']))[0];
            if ($orig['title'] !== $r['title']) {
                $changed[(int) $r['id']] = $r['title'];
            }
        }
        return ['judul' => $changed, 'hapus' => $absorbed];
    }

    /** Task project dari database dalam bentuk untuk validator. */
    public static function projectTasks(int $projectId): array
    {
        return Database::run(
            'SELECT f.nama AS fitur, t.title, t.milestone, t.koordinasi, t.status FROM tasks t JOIN features f ON f.id = t.feature_id
             WHERE t.project_id = ? ORDER BY f.fase, f.urutan, f.id, t.sort_order, t.id',
            [$projectId]
        )->fetchAll();
    }
}
