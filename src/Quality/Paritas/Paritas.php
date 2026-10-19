<?php

/**
 * Aturan paritas v1.2.1 (P1–P10) di atas Paket dokumen. Status per aturan: lulus, sebagian (hanya temuan minor), gagal, tidak berlaku.
 * Dipakai QualityReport (PRD PlanKu) dan tools/parity.php (PRD acuan).
 */
final class Paritas
{
    /** id => [kelas validator, judul] */
    public const ATURAN = [
        'P1' => ['P01Invarian', 'Invarian bisnis dijaga di database (termasuk rentang)'],
        'P2' => ['P02Lingkup', 'Penjaga lingkup: tidak ada fitur/peran tanpa asal'],
        'P3' => ['P03KepemilikanData', 'Data milik pengguna butuh bukti kepemilikan'],
        'P4' => ['P04KekhususanTugas', 'Tugas spesifik dan bisa dikerjakan'],
        'P5' => ['P05KedalamanStack', 'Kedalaman teknis stack'],
        'P6' => ['P06PublikSEO', 'SEO & performa halaman publik'],
        'P7' => ['P07Konfigurasi', 'Angka dari input menjadi pengaturan'],
        'P8' => ['P08Batas', 'Batas ditegakkan'],
        'P9' => ['P09KunciPilihan', 'Pilihan stack dikunci'],
        'P10' => ['P10MasterPrompt', 'Master Prompt dengan gerbang izin'],
    ];

    public const TEKNIS = '/tech stack|fondasi teknis|teknologi|technical/i';

    /** Konteks validator dari Brief + Paket. Data terstruktur PlanKu (profil) dipakai lebih dulu bila ada. */
    public static function context(Brief $b, Paket $p): array
    {
        $profil = $p->data['profil'] ?? [];
        $tech = $p->section(self::TEKNIS);
        $stack = $profil['stack'] ?? Brief::stack($b->stackChoice) ?? $b->guessStack($tech !== '' ? $tech : $p->prd());
        $roles = $profil['peran'] ?? self::roles($p);
        $declared = $profil['invarian_ai'] ?? [];
        // Bendera terkunci di profil dipakai lebih dulu; profil lama (sebelum v1.2.1) dilengkapi bendera baru dari Triggers.
        $flags = ($profil['bendera'] ?? []) + Triggers::flags($b, $profil['bendera_ai'] ?? [], $stack, $roles, $declared);
        return [
            'brief' => $b,
            'paket' => $p,
            'stack' => $stack,
            'db' => $p->data ? ($stack['db'] ?? 'postgresql') : self::dbType($p->schemaCode(), $tech),
            'roles' => $roles,
            'flags' => $flags,
            'invariants' => $profil['invarian'] ?? Triggers::invariants($b, $declared),
            'features' => $b->features,
        ];
    }

    /** Jalankan P1–P10 (+ Kelengkapan). */
    public static function run(array $c): array
    {
        $rules = [];
        $all = [];
        foreach (self::ATURAN as $id => [$class, $judul]) {
            $applies = $class::berlaku($c);
            $found = $applies ? $class::check($c) : [];
            $status = !$applies ? 'tidak berlaku' : (!$found ? 'lulus' : (array_filter($found, fn ($f) => $f['sev'] !== 'm') ? 'gagal' : 'sebagian'));
            $rules[$id] = ['id' => $id, 'judul' => $judul, 'status' => $status, 'temuan' => $found];
            $all = array_merge($all, $found);
        }
        $extra = PKelengkapan::check($c);
        return ['aturan' => $rules, 'kelengkapan' => $extra, 'temuan' => array_merge($all, $extra)];
    }

    /** Evaluasi satu paket (folder/berkas PRD acuan atau PRD PlanKu). */
    public static function evaluate(Brief $b, Paket $p): array
    {
        return self::run(self::context($b, $p));
    }

    /** Jenis database dari kode skema, lalu dari teks bab teknis. */
    public static function dbType(string $code, string $tech): string
    {
        foreach ([[$code, true], [$tech, false]] as [$text, $isCode]) {
            $hit = match (true) {
                $isCode && (bool) preg_match('/pgTable|pgEnum|timestamptz|tstzrange|jsonb/i', $text) => 'postgresql',
                $isCode && (bool) preg_match('/sqliteTable/i', $text) => 'sqlite',
                $isCode && (bool) preg_match('/mysqlTable|AUTO_INCREMENT|ENGINE\s*=/i', $text) => 'mysql',
                !$isCode && (bool) preg_match('/\bD1\b|cloudflare d1/i', $text) => 'd1',
                !$isCode && (bool) preg_match('/sqlite|turso|libsql/i', $text) => 'sqlite',
                !$isCode && (bool) preg_match('/mysql|mariadb|planetscale/i', $text) => 'mysql',
                !$isCode && (bool) preg_match('/mongo/i', $text) => 'mongodb',
                !$isCode && (bool) preg_match('/postgres|neon|supabase/i', $text) => 'postgresql',
                default => '',
            };
            if ($hit !== '') {
                return $hit;
            }
        }
        return 'postgresql';
    }

    /**
     * Peran dari bab hak akses: tabel berkolom "Peran/Aktor" (satu baris per peran) atau matriks (peran di judul kolom).
     */
    public static function roles(Paket $p): array
    {
        $section = $p->section('/hak akses|aktor|peran pengguna/i');
        $roles = [];
        foreach (array_filter(explode("\n\n", $section), fn ($chunk) => str_contains($chunk, '|')) as $table) {
            $rows = Paket::tableRows($table);
            if (!$rows) {
                continue;
            }
            $clean = fn ($s) => QV::lower(trim(preg_replace('/\([^)]*\)|\*|`/u', '', $s)));
            if (preg_match('/^(peran|aktor|role|pengguna)$/iu', $clean($rows[0][0]))) {
                foreach (array_slice($rows, 1) as $row) {
                    $roles[] = $clean($row[0]);
                }
            } elseif (count($rows[0]) > 2) {
                foreach (array_slice($rows[0], 1) as $cell) {
                    $roles[] = $clean($cell);
                }
            }
            if ($roles) {
                break;
            }
        }
        return array_values(array_unique(array_filter($roles)));
    }

    /** Teks tugas: rencana terkoordinasi, bab milestone, TASKS.md, dan butir per fitur. */
    public static function taskText(Paket $p): string
    {
        return implode("\n", array_column($p->coordinatedTasks(), 'teks')) . "\n" . $p->section('/milestone|tahapan|task breakdown|rencana pengerjaan/i')
            . "\n" . $p->file('TASKS.md') . "\n" . implode("\n", array_merge([], ...array_values($p->featureChecklist())));
    }
}
