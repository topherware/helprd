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
        return $prd;
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
