<?php

/**
 * Laporan Kualitas Standar PlanKu v1.2.1: menjalankan semua validator deterministik, menghitung skor 10 dimensi
 * (termasuk Paritas P1–P10), menyimpan, dan merender QUALITY.md (daftar periksa tujuh kekurangan + tabel P1–P10).
 */
final class QualityReport
{
    public static function standard(): array
    {
        static $std = null;
        return $std ??= json_decode(file_get_contents(__DIR__ . '/standard.json'), true);
    }

    /**
     * Konteks validator. PRD format lama (v1.1) dilengkapi kunci kosong supaya bisa dinilai (dan gagal di bab baru).
     * @param array $tasks daftar task: [fitur, title, milestone, status], urut sesuai sort_order
     */
    public static function context(Brief $b, array $prd, array $tasks = []): array
    {
        $prd = self::complete($prd, count($b->features));
        $profil = $prd['profil'] ?? [];
        $stack = $profil['stack'] ?? $b->guessStack(json_encode($prd['tech_stack'], JSON_UNESCAPED_UNICODE));
        $roles = $profil['peran'] ?? array_values(array_unique(array_merge(['pengunjung anonim'],
            array_map([QV::class, 'lower'], array_column($prd['keamanan']['hak_akses'] ?? [], 'peran')))));
        $flags = Triggers::flags($b, $profil['bendera_ai'] ?? [], $stack, $roles, $profil['invarian_ai'] ?? []);
        $security = SecurityTriggers::detect(['ide_awal' => $b->ide], $b->features, $b->qa, $prd);
        return [
            'brief' => $b,
            'prd' => $prd,
            'features' => $b->features,
            'tasks' => $tasks,
            'stack' => $stack,
            'roles' => $roles,
            'flags' => $flags,
            'security' => $security,
            'controls' => SecurityTriggers::tiers($security),
            'markdown' => Documents::renderPrd($b->features, $prd),
            'standard' => self::standard(),
        ];
    }

    /** PRD dengan semua kunci v1.2 (yang tidak ada diisi kosong); isi yang sudah ada tidak diubah. */
    public static function complete(array $prd, int $featureCount): array
    {
        $empty = Validator::aiPrd([], $featureCount, true);
        unset($empty['versi']);
        foreach ($empty as $key => $default) {
            if (!isset($prd[$key])) {
                $prd[$key] = $default;
            } elseif (is_array($default) && is_array($prd[$key]) && $default && array_keys($default) !== range(0, count($default) - 1)) {
                $prd[$key] += $default; // objek: tambah sub-kunci yang belum ada
            }
        }
        return $prd;
    }

    /** Jalankan semua validator dan hitung skor. @param array $auditAi temuan Pass 4 (hanya laporan). */
    public static function evaluate(Brief $b, array $prd, array $tasks = [], array $auditAi = []): array
    {
        $c = self::context($b, $prd, $tasks);
        $std = $c['standard'];
        $uiOnly = $c['flags']['uiOnly'];

        $groups = array_fill_keys(array_keys($std['bobot']), []);
        $skipped = $uiOnly ? ['V15SkemaKode', 'V16LayananPihakKetiga', 'V17BebanKeamanan'] : [];
        foreach ($std['kesiapan_bangun'] as $class) {
            if (!in_array($class, $skipped, true)) {
                $groups['kesiapan_bangun'] = array_merge($groups['kesiapan_bangun'], $class::check($c));
            }
        }
        if (!$uiOnly) {
            $groups['keamanan'] = SecurityValidators::run($c);
        }
        foreach (Dimensi::MAP as $method => $dim) {
            $groups[$dim] = Dimensi::$method($c);
        }
        // Paritas v1.2.1: PRD dirakit menjadi paket dokumen yang sama dengan ZIP, lalu dinilai P1–P10.
        $paritas = Paritas::evaluate($b, Paket::fromPrd($b, $c['prd'], $tasks));
        $groups['paritas'] = $paritas['temuan'];

        // Dimensi yang tidak dinilai otomatis atau tidak berlaku dikeluarkan; bobot sisanya dinormalisasi.
        $active = array_values(array_diff($std['dinilai_otomatis'], $uiOnly ? ['keamanan'] : []));
        $scores = [];
        foreach ($std['bobot'] as $dim => $_) {
            $scores[$dim] = in_array($dim, $active, true) ? self::score($groups[$dim], $std['penalti']) : null;
        }
        $weight = array_sum(array_intersect_key($std['bobot'], array_flip($active)));
        $total = (int) round(array_sum(array_map(fn ($d) => $std['bobot'][$d] * $scores[$d], $active)) / max(1, $weight));

        $findings = [];
        foreach ($groups as $dim => $list) {
            foreach ($list as $f) {
                $findings[] = $f + ['dimensi' => $dim];
            }
        }
        $critical = count(array_filter($findings, fn ($f) => $f['sev'] === 'K'));
        $lulus = $total >= $std['lulus']['skor_minimal'] && !$critical
            && ($scores['keamanan'] === null || $scores['keamanan'] >= $std['lulus']['keamanan_minimal'])
            && $scores['kesiapan_bangun'] >= $std['lulus']['kesiapan_bangun_minimal'];

        $checklist = [];
        foreach ($std['kekurangan'] as $k) {
            $na = $uiOnly && in_array($k['validator'], ['V15', 'V16', 'V17'], true);
            $checklist[] = $k + ['status' => $na ? 'tidak berlaku' : (array_filter($findings, fn ($f) => $f['id'] === $k['validator']) ? 'gagal' : 'lulus')];
        }

        return [
            'standar_versi' => $std['versi'],
            'skor' => $total,
            'skor_dimensi' => $scores,
            'skor_keamanan' => $scores['keamanan'],
            'skor_kesiapan' => $scores['kesiapan_bangun'],
            'lulus' => $lulus,
            'temuan' => $findings,
            'checklist' => $checklist,
            'paritas' => array_values(array_map(fn ($a) => ['id' => $a['id'], 'judul' => $a['judul'], 'status' => $a['status']], $paritas['aturan'])),
            'audit_ai' => $auditAi,
            'profil' => [
                'stack' => $c['stack']['nama'] . ' — ' . $c['stack']['ringkas'],
                'mode' => $uiOnly ? 'hanya UI' : 'aplikasi lengkap',
                'cara_kerja' => $b->workflowLabel(),
                'bendera' => array_keys(array_filter($c['flags'])),
            ],
        ];
    }

    private static function score(array $findings, array $penalty): int
    {
        return max(0, 100 - array_sum(array_map(fn ($f) => $penalty[$f['sev']], $findings)));
    }

    /**
     * Potongan PRD (nama potongan di Prompts::PRD_PARTS) yang perlu ditulis ulang karena temuannya.
     * Temuan batas task (P8, `tugas`) diperbaiki lewat bagian khusus "task" (penggabungan task, bukan penulisan ulang PRD).
     */
    public static function partsToRepair(array $report): array
    {
        $parts = [];
        foreach ($report['temuan'] as $f) {
            if ($f['sev'] === 'm') {
                continue;
            }
            if (!empty($f['tugas'])) {
                $parts['task'][] = $f;
            } elseif ($f['bab'] && ($part = Prompts::partOfBab((int) $f['bab']))) {
                $parts[$part][] = $f;
            }
        }
        return $parts;
    }

    public static function save(int $projectId, string $hash, array $report): void
    {
        Database::run(
            'INSERT INTO quality_reports (project_id, peta_hash, standar_versi, skor_keamanan, lulus, data) VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE peta_hash = VALUES(peta_hash), standar_versi = VALUES(standar_versi),
             skor_keamanan = VALUES(skor_keamanan), lulus = VALUES(lulus), data = VALUES(data), created_at = CURRENT_TIMESTAMP',
            [$projectId, $hash, $report['standar_versi'], $report['skor_keamanan'] ?? 100, $report['lulus'] ? 1 : 0, json_encode($report, JSON_UNESCAPED_UNICODE)]
        );
    }

    /** Laporan yang masih sesuai sidik PRD dan versi standar sekarang, atau null. */
    public static function load(int $projectId, string $hash): ?array
    {
        $row = Database::run(
            'SELECT data FROM quality_reports WHERE project_id = ? AND peta_hash = ? AND standar_versi = ?',
            [$projectId, $hash, self::standard()['versi']]
        )->fetchColumn();
        return $row ? json_decode($row, true) : null;
    }

    public static function render(array $report): string
    {
        $std = self::standard();
        $labels = ['konsistensi' => 'Konsistensi', 'keterukuran' => 'Keterukuran', 'kejujuran' => 'Kejujuran', 'keamanan' => 'Keamanan (Wajib MVP)',
            'kecocokan_stack' => 'Kecocokan Stack', 'keterlacakan' => 'Keterlacakan', 'struktur' => 'Struktur', 'keterbacaan' => 'Keterbacaan', 'kesiapan_bangun' => 'Kesiapan Bangun', 'paritas' => 'Paritas (P1–P10)'];
        $cell = fn ($s) => str_replace(['|', "\n"], ['\|', ' '], (string) $s);
        $l = $std['lulus'];
        $md = [
            "# Laporan Kualitas — {$std['nama']} v{$report['standar_versi']}",
            'Status: **' . ($report['lulus'] ? 'Lulus' : 'Belum lulus') . "** · Skor total **{$report['skor']}/100**",
            "Syarat lulus: skor ≥ {$l['skor_minimal']}, tanpa temuan kritis, Keamanan ≥ {$l['keamanan_minimal']}, Kesiapan Bangun ≥ {$l['kesiapan_bangun_minimal']}.",
            "Profil: {$report['profil']['stack']} · mode {$report['profil']['mode']} · cara kerja AI {$report['profil']['cara_kerja']}",
        ];
        $rows = [];
        foreach ($std['bobot'] as $dim => $w) {
            $score = $report['skor_dimensi'][$dim];
            $rows[] = "| {$labels[$dim]} | $w | " . ($score === null ? ($dim === 'keterbacaan' ? 'belum dinilai otomatis' : 'tidak berlaku') : "$score/100") . ' |';
        }
        $md[] = "## Skor per Dimensi\n\n| Dimensi | Bobot | Skor |\n|---|---|---|\n" . implode("\n", $rows)
            . "\n\n*Dimensi yang tidak dinilai atau tidak berlaku dikeluarkan dan bobot sisanya dinormalisasi.*";

        $icon = ['lulus' => '✅ Lulus', 'gagal' => '❌ Gagal', 'tidak berlaku' => '➖ Tidak berlaku'];
        $md[] = "## Daftar Periksa Tujuh Kekurangan\n\n| # | Kekurangan | Validator | Status |\n|---|---|---|---|\n"
            . implode("\n", array_map(fn ($k) => "| {$k['no']} | {$k['judul']} | {$k['validator']} | {$icon[$k['status']]} |", $report['checklist']));

        if (!empty($report['paritas'])) {
            $pIcon = $icon + ['sebagian' => '🟡 Sebagian'];
            $md[] = "## Paritas v1.2.1 (P1–P10)

| Aturan | Judul | Status |
|---|---|---|
"
                . implode("
", array_map(fn ($p) => "| {$p['id']} | {$p['judul']} | {$pIcon[$p['status']]} |", $report['paritas']))
                . "

*Sebagian = hanya temuan minor. Rincian temuan di tabel Temuan (validator P1–P10, PK = kelengkapan).*";
        }
        if ($report['temuan']) {
            $sev = ['K' => 'Kritis', 'M' => 'Mayor', 'm' => 'Minor'];
            usort($report['temuan'], fn ($a, $b) => [array_search($a['sev'], ['K', 'M', 'm']), $a['bab'] ?? 99] <=> [array_search($b['sev'], ['K', 'M', 'm']), $b['bab'] ?? 99]);
            $md[] = "## Temuan\n\n| Tingkat | Validator | Bab | Temuan | Saran |\n|---|---|---|---|---|\n" . implode("\n", array_map(
                fn ($t) => "| {$sev[$t['sev']]} | {$t['id']} | " . ($t['bab'] ?? '–') . ' | ' . $cell($t['pesan']) . ' | ' . $cell($t['saran']) . ' |', $report['temuan']));
        } else {
            $md[] = "## Temuan\n\nTidak ada temuan dari validator.";
        }
        if ($report['audit_ai']) {
            $md[] = "## Catatan Audit AI\n\nHal yang tidak bisa diperiksa mesin (kelayakan vendor, kewajaran dataset). Hanya laporan, tidak memengaruhi skor.\n\n"
                . implode("\n", array_map(fn ($t) => '- **Bab ' . ($t['bab'] ?: '–') . "** — {$t['pesan']}", $report['audit_ai']));
        }
        $md[] = '*Laporan ini hasil pemeriksaan otomatis atas dokumen perencanaan, bukan hasil uji penetrasi atau tinjauan hukum.*';
        return implode("\n\n", $md) . "\n";
    }
}
