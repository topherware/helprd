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
                $tasks[] = ['fitur' => $f['nama'], 'title' => $t['title'], 'milestone' => $t['milestone'], 'status' => 'belum_mulai'];
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
        ];
    }

    /** Task project dari database dalam bentuk untuk validator. */
    public static function projectTasks(int $projectId): array
    {
        return Database::run(
            'SELECT f.nama AS fitur, t.title, t.milestone, t.status FROM tasks t JOIN features f ON f.id = t.feature_id
             WHERE t.project_id = ? ORDER BY f.fase, f.urutan, f.id, t.sort_order, t.id',
            [$projectId]
        )->fetchAll();
    }
}
