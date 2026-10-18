<?php

/**
 * Dokumen langkah 5: PRD.md dan features/NN-nama.md.
 * AI mengisi data terstruktur (JSON); markdown dirakit di sini supaya formatnya selalu sama.
 */
final class Documents
{
    public const PRD_MAX_TOKENS = 32000;
    /** Endpoint api_logs yang dihitung ke batas DOC_MAX_CALLS_PER_PROJECT. */
    public const ENDPOINTS = ['generate_prd', 'audit_kualitas', 'perbaikan_prd'];

    /** Jumlah panggilan AI untuk dokumen sejak dokumen versi sekarang mulai dibuat. */
    public static function callsUsed(int $projectId): int
    {
        $in = implode(',', array_fill(0, count(self::ENDPOINTS), '?'));
        return (int) Database::run(
            "SELECT COUNT(*) FROM api_logs WHERE project_id = ? AND endpoint IN ($in)
             AND created_at >= COALESCE((SELECT created_at FROM documents WHERE project_id = ? AND feature_id = 0), NOW() + INTERVAL 1 DAY)",
            array_merge([$projectId], self::ENDPOINTS, [$projectId])
        )->fetchColumn();
    }

    public static function callsLeft(int $projectId): int
    {
        return (int) env('DOC_MAX_CALLS_PER_PROJECT', 36) - self::callsUsed($projectId);
    }

    /**
     * Sidik PRD: berubah bila fitur, fase, atau sub fitur berubah (status tidak dihitung), atau bila isian Bagian B
     * (gaya desain, stack, mode hanya-UI, cara kerja AI, catatan) berubah.
     */
    public static function hash(array $features, array $project): string
    {
        return sha1(json_encode([
            array_map(fn ($f) => [$f['id'], $f['nama'], $f['fase'], $f['sub_fitur']], $features),
            Brief::fingerprint($project),
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * Dokumen yang masih sesuai peta fitur, di-key feature_id (0 = PRD).
     * Dokumen basi tidak dihapus (supaya mengedit fitur tidak menghilangkan PRD); ia tertimpa saat dibuat ulang.
     */
    public static function current(int $projectId, string $hash): array
    {
        $rows = Database::run('SELECT feature_id, data FROM documents WHERE project_id = ? AND peta_hash = ?', [$projectId, $hash])
            ->fetchAll(PDO::FETCH_KEY_PAIR);
        return array_map(fn ($json) => json_decode($json, true), $rows);
    }

    /** PRD JSON terakhir apa pun versinya: [data, peta_hash] atau null. */
    public static function latestPrd(int $projectId): ?array
    {
        $row = Database::run('SELECT data, peta_hash FROM documents WHERE project_id = ? AND feature_id = 0', [$projectId])->fetch();
        return $row ? [json_decode($row['data'], true), $row['peta_hash']] : null;
    }

    public static function save(int $projectId, int $featureId, string $hash, array $data): void
    {
        Database::run(
            // created_at diperbarui saat versi peta berganti, supaya callsUsed() menghitung dari awal versi baru.
            'INSERT INTO documents (project_id, feature_id, peta_hash, data) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE created_at = IF(peta_hash = VALUES(peta_hash), created_at, CURRENT_TIMESTAMP),
             peta_hash = VALUES(peta_hash), data = VALUES(data)',
            [$projectId, $featureId, $hash, json_encode($data, JSON_UNESCAPED_UNICODE)]
        );
    }

    public static function slug(string $text): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii)), '-');
    }

    /** Nama file fitur di dalam zip, mis. features/01-beranda-katalog.md */
    public static function featureFilename(int $index, array $feature): string
    {
        return sprintf('features/%02d-%s.md', $index + 1, self::slug($feature['nama']) ?: 'fitur');
    }

    /* ---------- Markdown ---------- */

    /**
     * PRD.md. Format v1.2 (bab 1–13) dan format lama v1.1 (bab 1–8) sama-sama bisa dirender.
     * @param array|null $present kunci JSON yang sudah mulai di-stream (null = PRD lengkap). Bagian yang belum
     *                            muncul tidak dirender, dan diagram di bagian yang masih ditulis diganti placeholder.
     */
    public static function renderPrd(array $features, array $prd, ?array $present = null): string
    {
        $show = fn (string $key) => $present === null ? isset($prd[$key]) : in_array($key, $present, true);
        $writing = $present === null ? null : $present[count($present) - 1] ?? null;
        $pending = "```diagram-pending\n```";
        $uiOnly = !empty($prd['profil']['bendera']['uiOnly']);
        $v12 = ($prd['versi'] ?? '') === '1.2';
        $na = 'Tidak berlaku (mode hanya-UI).';
        $md = ['# PRD — Project Requirements Document'];

        if ($show('overview')) {
            $o = $prd['overview'];
            $md[] = '## 1. Overview';
            if ($o['deskripsi'] !== '') {
                $md[] = $o['deskripsi'];
            }
            $md[] = "**Latar Belakang & Masalah**\n\n" . $o['masalah'];
            if ($o['tujuan']) {
                $md[] = "**Tujuan Utama**\n\n" . self::numbered($o['tujuan']);
            }
            if ($o['prinsip']) {
                $md[] = "**Prinsip Utama**\n\n" . self::bullets($o['prinsip']);
            }
            if ($o['sukses'] !== '') {
                $md[] = "**Sukses seperti apa yang diharapkan**\n\n" . $o['sukses'];
            }
        }

        if ($show('requirements')) {
            $r = $prd['requirements'];
            $line = fn ($x) => is_array($x) ? ($x['id'] !== '' ? "**{$x['id']}** — " : '') . $x['teks'] . (!empty($x['fitur']) ? ' _(Fitur: ' . implode(', ', $x['fitur']) . ')_' : '') : $x;
            $md[] = "---\n\n## 2. Requirements";
            $md[] = "### 2.1 Persyaratan Fungsional\n\n" . self::bullets(array_map($line, $r['fungsional']));
            if ($r['non_fungsional'] || $writing !== 'requirements') {
                $md[] = "### 2.2 Persyaratan Non-Fungsional\n\n" . self::bullets(array_map($line, $r['non_fungsional']));
            }
            if ($r['di_luar_cakupan']) {
                $md[] = "### 2.3 Di Luar Cakupan (untuk versi ini)\n\n" . self::bullets($r['di_luar_cakupan']);
            }
            if (!empty($r['pertanyaan_terbuka'])) {
                $md[] = "### 2.4 Pertanyaan Terbuka\n\n" . self::bullets(array_map($line, $r['pertanyaan_terbuka']));
            }
            if (!empty($r['catatan_diterapkan'])) {
                $md[] = "### 2.5 Catatan Tambahan Pengguna yang Diterapkan\n\n" . self::table(['Aturan dari catatan', 'Dipenuhi oleh'],
                    array_map(fn ($c) => [$c['aturan'], implode(', ', $c['ref'])], $r['catatan_diterapkan']));
            }
        }

        if ($show('fitur')) {
            $md[] = "---\n\n## 3. Core Features";
            $md[] = '*(Disusun sesuai peta fitur yang telah disetujui, mengikuti urutan fase.)*';
            foreach ($features as $i => $f) {
                $desc = $prd['fitur'][$i] ?? ['deskripsi' => '', 'sub_fitur' => []];
                $block = "### Fase {$f['fase']} — {$f['nama']}";
                if ($desc['deskripsi'] !== '') {
                    $block .= "\n\n" . $desc['deskripsi'];
                }
                $lines = [];
                foreach ($f['sub_fitur'] as $j => $sub) {
                    $lines[] = "- **$sub**" . (($desc['sub_fitur'][$j] ?? '') !== '' ? ' — ' . $desc['sub_fitur'][$j] : '');
                }
                $md[] = $block . ($lines ? "\n\n" . implode("\n", $lines) : '');
            }
        }

        if ($show('user_flow')) {
            $md[] = "---\n\n## 4. User Flow";
            foreach ($prd['user_flow'] as $i => $flow) {
                $fase = $flow['fase'] !== '' ? " (Fase {$flow['fase']})" : '';
                $md[] = '**' . ($i + 1) . ") {$flow['judul']}{$fase}**\n" . $flow['langkah'];
            }
        }

        if ($show('arsitektur')) {
            $a = $prd['arsitektur'];
            $md[] = "---\n\n## 5. Architecture";
            if ($a['ringkasan'] !== '') {
                $md[] = $a['ringkasan'];
            }
            if ($a['komponen']) {
                $md[] = "**Komponen utama:**\n" . implode("\n", array_map(fn ($k) => "- **{$k['nama']}:** {$k['deskripsi']}", $a['komponen']));
            }
            if ($writing === 'arsitektur') {
                $md[] = $pending;
            } elseif ($a['sequence_mermaid'] !== '') {
                $md[] = "```mermaid\n{$a['sequence_mermaid']}\n```";
            }
        }

        if ($uiOnly && $v12 && $show('tech_stack')) {
            $md[] = "---\n\n## 6. Database Schema\n\n$na";
        } elseif ($show('database')) {
            $md[] = self::renderDatabase($prd['database'], $writing === 'database', $prd['profil']['stack']['bahasa_skema'] ?? 'sql');
        }

        if ($show('tech_stack')) {
            $md[] = "---\n\n## 7. Tech Stack";
            $k = $prd['keputusan_teknologi'] ?? null;
            if ($k && $k['stack'] !== '') {
                $md[] = "**Keputusan teknologi:** {$k['stack']}" . ($k['alasan'] !== '' ? " — {$k['alasan']}" : '');
            }
            $md[] = self::table(['Lapisan', 'Pilihan', 'Alasan'], array_map(fn ($t) => [$t['lapisan'], $t['pilihan'], $t['alasan']], $prd['tech_stack']));
        }

        if ($uiOnly && $v12 && $show('design')) {
            $md[] = "---\n\n## 8. Keamanan & Privasi\n\n$na";
        } elseif (!empty($prd['keamanan']) && $show('keamanan')) {
            $md[] = self::renderSecurity($prd['keamanan']);
        }

        if ($show('design')) {
            $md[] = self::renderDesign($prd['design']);
        }
        if ($show('rute')) {
            $md[] = self::renderRoutes($prd['rute']);
        }
        if ($show('dummy')) {
            $md[] = self::renderDummy($prd['dummy']);
        }
        if ($uiOnly && $v12 && $show('milestone')) {
            $md[] = "---\n\n## 12. Layanan Pihak Ketiga & Variabel Lingkungan\n\n$na";
        } elseif ($show('layanan')) {
            $md[] = self::renderServices($prd['layanan']);
        }
        if ($show('milestone')) {
            $md[] = self::renderMilestones($prd['milestone']);
        }

        return implode("\n\n", $md) . "\n";
    }

    private static function renderDatabase(array $db, bool $writing, string $lang): string
    {
        $md = ["---\n\n## 6. Database Schema"];
        if ($db['catatan'] !== '') {
            $md[] = $db['catatan'];
        }
        foreach ($db['tabel'] as $t) {
            $cols = implode(', ', array_map(fn ($c) => "`{$c['nama']}`" . ($c['tipe'] !== '' ? " ({$c['tipe']})" : ''), $t['kolom']));
            $md[] = "**{$t['nama']}**" . ($t['deskripsi'] !== '' ? " — {$t['deskripsi']}" : '') . ($cols ? "\n- $cols" : '');
        }
        if ($writing) {
            $md[] = $pending = "```diagram-pending\n```";
        } elseif ($er = self::erDiagram($db['relasi'])) {
            $md[] = $er;
        }
        if (($db['kode_skema'] ?? '') !== '') {
            $fence = ['sql' => 'sql', 'ts' => 'ts', 'prisma' => 'prisma'][$lang] ?? 'sql';
            $md[] = "### 6.1 Kode Skema (" . self::schemaPath($lang) . ")\n\n```$fence\n{$db['kode_skema']}\n```";
        }
        $rules = array_filter($db['aturan_pemicu'] ?? []);
        if ($rules) {
            $labels = ['snapshot' => 'Snapshot nilai transaksi', 'log_audit' => 'Log audit', 'tamu' => 'Transaksi tamu'];
            $md[] = "### 6.2 Aturan Data\n\n" . self::bullets(array_map(fn ($k, $v) => "**{$labels[$k]}:** $v", array_keys($rules), $rules));
        }
        if (!empty($db['kebijakan_akses'])) {
            $md[] = "### 6.3 Kebijakan Akses Data\n\n" . self::table(['Tabel', 'Aturan'], array_map(fn ($r) => [$r['tabel'], $r['aturan']], $db['kebijakan_akses']));
        }
        return implode("\n\n", $md);
    }

    /** PRD bab 8 Keamanan & Privasi (v1.2: Wajib MVP + Hardening; tabel ancaman/pihak ketiga v1.1 tetap dirender bila ada). */
    private static function renderSecurity(array $k): string
    {
        $md = ["---\n\n## 8. Keamanan & Privasi"];

        $md[] = "### 8a. Klasifikasi Data\n\n" . self::table(
            ['Data', 'Sensitivitas', 'Tabel & kolom', 'Siapa yang boleh akses', 'Retensi usulan'],
            array_map(fn ($r) => [$r['data'], $r['sensitivitas'], $r['lokasi'], $r['akses'], $r['retensi'] . ($r['boleh_masuk_log'] ? '' : '; tidak boleh masuk log')], $k['klasifikasi_data'])
        );

        $mark = fn ($row, $action) => in_array($action, $row['aksi'], true) ? '✓' : '–';
        $md[] = "### 8b. Aktor & Hak Akses\n\n" . ($k['prinsip_akses'] !== '' ? $k['prinsip_akses'] . "\n\n" : '') . self::table(
            ['Peran', 'Sumber daya', 'Baca', 'Tulis', 'Hapus', 'Setujui'],
            array_map(fn ($r) => [$r['peran'], $r['sumber_daya'], $mark($r, 'baca'), $mark($r, 'tulis'), $mark($r, 'hapus'), $mark($r, 'setujui')], $k['hak_akses'])
        );

        if (!empty($k['ancaman'])) {
            $md[] = "### Model Ancaman Ringkas\n\n" . self::table(
                ['Aset', 'Ancaman', 'Dampak', 'Mitigasi', 'ID SEC', 'Fitur terkait'],
                array_map(fn ($r) => [$r['aset'], $r['ancaman'], $r['dampak'], $r['mitigasi'], $r['sec'], $r['fitur']], $k['ancaman'])
            );
        }

        // Persyaratan dikelompokkan sesuai urutan kelompok di standar; kelompok lain di akhir.
        $groups = [];
        foreach ($k['persyaratan'] as $sec) {
            $groups[$sec['kelompok'] ?: 'Lainnya'][] = $sec;
        }
        $order = array_unique(array_merge(QualityReport::standard()['kelompok_sec'], array_keys($groups)));
        $blocks = ['### 8c. Persyaratan Keamanan — Wajib MVP'];
        foreach ($order as $group) {
            $lines = array_map(fn ($s) => "- **{$s['id']}** — {$s['teks']}" . ($s['fitur'] ? ' _(Fitur: ' . implode(', ', $s['fitur']) . ')_' : ''), $groups[$group] ?? []);
            if ($group === 'Pihak Ketiga & Dependensi' && !empty($k['pihak_ketiga'])) {
                $lines[] = "\n" . self::table(['Layanan', 'Data yang dikirim', 'Tujuan'], array_map(fn ($r) => [$r['layanan'], $r['data'], $r['tujuan']], $k['pihak_ketiga']));
            }
            if ($lines) {
                $blocks[] = "#### $group\n\n" . implode("\n", $lines);
            }
        }
        $md[] = implode("\n\n", $blocks);

        if (!empty($k['hardening'])) {
            $md[] = "### 8d. Hardening setelah MVP\n\n" . self::bullets($k['hardening']);
        }

        $md[] = "### 8e. Pemetaan ke OWASP Top 10 ({$k['owasp']['edisi']})\n\n" . self::table(
            ['Kategori', 'SEC'],
            array_map(fn ($r) => ["{$r['kode']} {$r['nama']}", $r['sec'] ? implode(', ', $r['sec']) : 'tidak relevan — ' . $r['alasan']], $k['owasp']['kategori'])
        );

        $md[] = "### 8f. Risiko Tersisa & Hal yang Perlu Ditinjau Ahli\n\n" . self::bullets($k['risiko_tersisa']);
        return implode("\n\n", $md);
    }

    private static function renderDesign(array $d): string
    {
        $md = ["---\n\n## 9. Design System & UI/UX"];
        if ($d['alasan'] !== '') {
            $md[] = "**Arah desain:** {$d['alasan']}";
        }
        $light = $d['warna']['terang'];
        $dark = $d['warna']['gelap'];
        if ($light) {
            $md[] = "### 9.1 Token Warna\n\n" . self::table($dark ? ['Token', 'Tema terang', 'Tema gelap'] : ['Token', 'Nilai'],
                array_map(fn ($k) => $dark ? ["`$k`", $light[$k], $dark[$k] ?? '–'] : ["`$k`", $light[$k]], array_keys($light)));
        }
        $t = $d['tipografi'];
        $md[] = "### 9.2 Tipografi\n\n" . self::bullets(array_filter([
            $t['font_judul'] !== '' ? "Font judul: **{$t['font_judul']}**; font isi: **{$t['font_isi']}**" . ($t['sumber'] !== '' ? " (sumber: {$t['sumber']})" : '') : null,
            $t['skala'] ? 'Skala ukuran: ' . implode(' / ', $t['skala']) . ' px' : null,
            $t['tinggi_baris'] !== '' ? "Tinggi baris: {$t['tinggi_baris']}" : null,
        ]));
        $u = $d['ukuran'];
        $md[] = "### 9.3 Ukuran & Jarak\n\n" . self::bullets(array_filter([
            $u['ketuk_min'] ? "Area ketuk minimal {$u['ketuk_min']}×{$u['ketuk_min']} px" : null,
            $u['spasi'] ? 'Skala spasi: ' . implode(' / ', $u['spasi']) . ' px' : null,
            $u['radius'] !== '' ? "Radius: {$u['radius']}" : null,
            $u['bayangan'] !== '' ? "Bayangan: {$u['bayangan']}" : null,
            $u['lebar_maks'] !== '' ? "Lebar maksimum konten: {$u['lebar_maks']}" : null,
            $u['breakpoint'] ? 'Titik putus responsif: ' . implode(', ', $u['breakpoint']) : null,
        ]));
        if ($d['komponen']) {
            $md[] = "### 9.4 Gaya Komponen\n\n" . self::table(['Komponen', 'Gaya'], array_map(fn ($c) => [$c['nama'], $c['gaya']], $d['komponen']));
        }
        $n = $d['nuansa'];
        $s = $d['keadaan'];
        $md[] = "### 9.5 Nuansa, Keadaan Antarmuka, & Aksesibilitas\n\n" . self::bullets(array_filter([
            $n['sifat'] ? 'Nuansa: ' . implode(', ', $n['sifat']) : null,
            $n['hindari'] ? 'Hindari: ' . implode('; ', $n['hindari']) : null,
            $s['loading'] !== '' ? "Loading: {$s['loading']}" : null,
            $s['kosong'] !== '' ? "Kosong: {$s['kosong']}" : null,
            $s['error'] !== '' ? "Error: {$s['error']}" : null,
        ])) . ($d['aksesibilitas'] ? "\n\n**Aksesibilitas dasar:**\n" . self::bullets($d['aksesibilitas']) : '');
        return implode("\n\n", $md);
    }

    private static function renderRoutes(array $r): string
    {
        $md = ["---\n\n## 10. Peta Rute & Layout per Peran"];
        $md[] = self::table(['Path', 'Halaman', 'Peran', 'Layout', 'Fitur sumber'],
            array_map(fn ($x) => ["`{$x['path']}`", $x['halaman'], implode(', ', $x['peran']), $x['layout'], implode(', ', $x['sumber'])], $r['rute']));
        if ($r['navigasi']) {
            $md[] = "### 10.1 Navigasi per Peran\n\n" . self::bullets(array_map(fn ($n) => "**{$n['peran']}** — {$n['komponen']}: " . implode(', ', $n['item']), $r['navigasi']));
        }
        if ($r['rute_khusus']) {
            $md[] = "### 10.2 Rute Khusus\n\n" . self::bullets(array_map(fn ($x) => "{$x['jenis']}: `{$x['path']}` — {$x['halaman']}", $r['rute_khusus']));
        }
        if ($r['penjaga_akses']) {
            $md[] = "### 10.3 Penjaga Akses\n\n" . self::bullets($r['penjaga_akses']);
        }
        return implode("\n\n", $md);
    }

    private static function renderDummy(array $d): string
    {
        $md = ["---\n\n## 11. Data Dummy & Copywriting"];
        $cell = fn ($v) => is_bool($v) ? ($v ? 'ya' : 'tidak') : (string) $v;
        foreach ($d['dataset'] as $set) {
            $md[] = "### {$set['id']} (`{$set['tabel']}`)\n\n" . self::table($set['kolom'], array_map(fn ($row) => array_map($cell, $row), $set['baris']));
        }
        if ($d['template']) {
            $md[] = "### Template Teks\n\n" . implode("\n\n", array_map(fn ($t) => "**{$t['nama']}**\n- Template: `{$t['isi']}`\n- Contoh: {$t['contoh']}", $d['template']));
        }
        $c = $d['copywriting'];
        $m = $c['microcopy'];
        $md[] = "### Panduan Copywriting\n\n" . self::bullets(array_filter([
            $c['nada'] !== '' ? "Nada: {$c['nada']}" : null,
            $c['sapaan'] !== '' ? "Sapaan: {$c['sapaan']}" : null,
            $c['istilah'] ? 'Istilah baku: ' . implode('; ', $c['istilah']) : null,
            $m['kosong'] !== '' ? "Microcopy kosong: \"{$m['kosong']}\"" : null,
            $m['error'] !== '' ? "Microcopy error: \"{$m['error']}\"" : null,
            $m['sukses'] !== '' ? "Microcopy sukses: \"{$m['sukses']}\"" : null,
            $m['konfirmasi'] !== '' ? "Microcopy konfirmasi: \"{$m['konfirmasi']}\"" : null,
        ]));
        return implode("\n\n", $md);
    }

    private static function renderServices(array $l): string
    {
        $md = ["---\n\n## 12. Layanan Pihak Ketiga & Variabel Lingkungan"];
        $md[] = $l['layanan']
            ? self::table(['Kebutuhan', 'Vendor / mock', 'Alasan', 'Biaya', 'Variabel lingkungan', 'Mode MVP'],
                array_map(fn ($x) => [$x['kebutuhan'], $x['vendor'] . ($x['mock'] ? ' (adapter tiruan di MVP)' : ''), $x['alasan'], $x['biaya'], implode(', ', $x['env']), $x['mode_mvp']], $l['layanan']))
            : 'Tidak ada layanan pihak ketiga yang dibutuhkan untuk MVP.';
        if ($l['rate_limit'] !== '') {
            $md[] = "**Pembatasan laju:** {$l['rate_limit']}";
        }
        $md[] = "### .env.example\n\n```bash\n" . self::envExample($l) . "```";
        return implode("\n\n", $md);
    }

    private static function renderMilestones(array $m): string
    {
        $md = ["---\n\n## 13. Milestone Pembangunan & Master Prompt"];
        foreach ($m['daftar'] as $x) {
            $md[] = "### {$x['id']} — {$x['nama']}\n\n{$x['tujuan']}\n\n**Selesai bila:**\n" . self::bullets($x['selesai_bila'])
                . ($x['cakupan'] ? "\n\n**Cakupan:** " . implode(', ', $x['cakupan']) : '') . ($x['checkpoint'] !== '' ? "\n\n> **{$x['checkpoint']}**" : '');
        }
        if ($m['aturan_checkpoint'] !== '') {
            $md[] = "**Aturan checkpoint:** {$m['aturan_checkpoint']}";
        }
        if ($m['master_prompt'] !== '') {
            $md[] = "### Master Prompt\n\n```text\n{$m['master_prompt']}\n```";
        }
        return implode("\n\n", $md);
    }

    /* ---------- Berkas pendamping ZIP v1.2 ---------- */

    public static function schemaPath(string $lang): string
    {
        return ['ts' => 'db/schema.ts', 'prisma' => 'db/schema.prisma'][$lang] ?? 'db/schema.sql';
    }

    public static function envExample(array $layanan): string
    {
        $out = '';
        foreach ($layanan['env_example'] as $g) {
            $out .= "# --- {$g['grup']}" . ($g['komentar'] !== '' ? ": {$g['komentar']}" : '') . "\n";
            foreach ($g['variabel'] as $v) {
                $out .= ($v['komentar'] !== '' ? "# {$v['komentar']}\n" : '') . "{$v['nama']}={$v['nilai']}\n";
            }
            $out .= "\n";
        }
        return $out;
    }

    public static function claudeMd(string $judul, array $prd): string
    {
        $m = $prd['milestone'];
        $c = $m['claude_md'];
        $stack = $prd['profil']['stack'] ?? null;
        $md = [
            "# CLAUDE.md — $judul",
            'Panduan untuk AI coding assistant. Baca berkas ini sebelum mengerjakan apa pun.',
            "## Stack\n\n" . ($stack ? "{$stack['nama']}: {$stack['ringkas']}" : implode(', ', array_column($prd['tech_stack'], 'pilihan'))),
            "## Struktur Folder\n\n```text\n{$c['struktur_folder']}\n```",
            "## Konvensi\n\n" . self::bullets($c['konvensi']),
            "## Perintah\n\n- Jalankan: `{$c['perintah']['jalan']}`\n- Uji: `{$c['perintah']['uji']}`",
            "## Aturan Checkpoint\n\n{$m['aturan_checkpoint']}\n\nDi setiap CHECKPOINT: berhenti, perbarui PROGRESS.md, ringkas hasil, tunggu persetujuan, dan sarankan percakapan baru bila konteks sudah panjang.",
            "## Rujukan di PRD.md\n\n- Design system: bab 9\n- Peta rute & layout: bab 10\n- Data dummy & copywriting: bab 11 (jangan mengarang data di luar ini)\n- Keamanan Wajib MVP: bab 8\n- Layanan & variabel lingkungan: bab 12 dan `.env.example`",
        ];
        return implode("\n\n", $md) . "\n";
    }

    public static function progressMd(string $judul, array $prd): string
    {
        $rows = array_map(fn ($x) => [$x['id'], $x['nama'], 'Belum mulai', ''], $prd['milestone']['daftar']);
        return "# PROGRESS — $judul\n\nPerbarui berkas ini di setiap checkpoint.\n\n" . self::table(['Milestone', 'Nama', 'Status', 'Catatan'], $rows)
            . "\n\n## Catatan Keputusan\n\n- _(tulis keputusan penting di sini beserta tanggalnya)_\n";
    }

    /** Bagian Keamanan per fitur: maksimal 3 rujukan SEC, yang khusus fitur ini lebih dulu. */
    public static function featureSecurity(array $feature, array $prd): array
    {
        $mine = array_values(array_filter($prd['keamanan']['persyaratan'] ?? [],
            fn ($s) => in_array(mb_strtolower($feature['nama']), array_map('mb_strtolower', $s['fitur']), true)));
        usort($mine, fn ($a, $b) => count($a['fitur']) <=> count($b['fitur']));
        return array_slice($mine, 0, 3);
    }

    /* ---------- Ekspor workspace (Implemen) ---------- */

    /** Fitur (urut fase) beserta sub fitur dan task dari database, untuk ZIP, prompt agent, dan validator. */
    public static function exportData(int $projectId): array
    {
        $features = Database::run(
            'SELECT id, nama, fase, ikon, deskripsi, tujuan, selesai_bila FROM features WHERE project_id = ? AND disertakan = 1 ORDER BY fase, urutan, id',
            [$projectId]
        )->fetchAll();
        $subs = Database::run(
            'SELECT s.feature_id, s.id, s.nama, s.tujuan, s.selesai_bila FROM sub_features s JOIN features f ON f.id = s.feature_id
             WHERE f.project_id = ? ORDER BY s.urutan, s.id',
            [$projectId]
        )->fetchAll(PDO::FETCH_GROUP);
        $tasks = Database::run(
            'SELECT t.feature_id, t.title, t.status, t.priority, t.phase, t.milestone, s.nama AS sub_nama
             FROM tasks t LEFT JOIN sub_features s ON s.id = t.sub_feature_id
             WHERE t.project_id = ? ORDER BY t.sort_order, t.id',
            [$projectId]
        )->fetchAll(PDO::FETCH_GROUP);
        $list = fn (?string $json) => $json ? (json_decode($json, true) ?: []) : [];
        foreach ($features as &$f) {
            $f['selesai_bila'] = $list($f['selesai_bila']);
            $f['subs'] = array_map(fn ($s) => ['nama' => $s['nama'], 'tujuan' => $s['tujuan'] ?? '', 'selesai_bila' => $list($s['selesai_bila'])], $subs[$f['id']] ?? []);
            $f['tasks'] = $tasks[$f['id']] ?? [];
        }
        return $features;
    }

    /** features/NN-slug.md: isi dari database (seperti yang tampil di workspace), status task terkini, dan maksimal 3 rujukan SEC. */
    public static function renderFeatureExport(array $f, array $secs): string
    {
        $md = ["# {$f['nama']}"];
        if ($f['deskripsi']) {
            $md[] = $f['deskripsi'];
        }
        $md[] = "## Spesifikasi\n\n### Tujuan\n" . ($f['tujuan'] ?: '_Belum diisi._') . "\n\n### Selesai bila\n"
            . ($f['selesai_bila'] ? self::bullets($f['selesai_bila']) : '_Belum diisi._');
        if ($secs) {
            $md[] = "### Keamanan\nPersyaratan Wajib MVP terkait (detail di PRD bab 8):\n" . self::bullets(array_map(fn ($s) => "**{$s['id']}** — {$s['teks']}", $secs));
        }

        foreach ($f['subs'] as $s) {
            $block = "## Sub-fitur: {$s['nama']}";
            if ($s['tujuan']) {
                $block .= "\n\n### Tujuan\n" . $s['tujuan'];
            }
            if ($s['selesai_bila']) {
                $block .= "\n\n### Selesai bila\n" . self::bullets($s['selesai_bila']);
            }
            $md[] = $block;
        }

        $done = count(array_filter($f['tasks'], fn ($t) => $t['status'] === 'selesai'));
        $md[] = "## Task\n\n" . ($f['tasks'] ? "Progres: $done/" . count($f['tasks']) . " selesai\n\n" . implode("\n", array_map(fn ($t) => self::taskLine($t, false), $f['tasks']))
            : '_Belum ada task. Klik "Bikin Task" di PlanKu._');

        return implode("\n\n", $md) . "\n";
    }

    /** TASKS.md: semua task bercentang dengan awalan milestone, dikelompokkan per fase lalu per fitur. */
    public static function renderTasksMd(string $judul, array $features): string
    {
        $all = array_merge(...array_map(fn ($f) => $f['tasks'], $features ?: [['tasks' => []]]));
        $done = count(array_filter($all, fn ($t) => $t['status'] === 'selesai'));
        $md = [
            "# TASKS — $judul",
            'Kerjakan berurutan sesuai milestone ([M1] prototipe UI dengan data tiruan, [M2] backend & data, [M3] keamanan & uji) dan aturan checkpoint di CLAUDE.md. Ubah `[ ]` menjadi `[x]` setelah task selesai dan diuji.',
            $all ? "Progres: $done/" . count($all) . ' selesai' : '_Belum ada task. Klik "Bikin Task" di PlanKu._',
        ];
        $phases = [1 => 'Inti', 2 => 'Penting', 3 => 'Pengembangan', 4 => 'Tambahan'];
        foreach ($phases as $fase => $label) {
            $group = array_filter($features, fn ($f) => (int) $f['fase'] === $fase && $f['tasks']);
            if (!$group) {
                continue;
            }
            $md[] = "## Fase $fase · $label";
            foreach ($group as $f) {
                $md[] = "### {$f['nama']}\n" . implode("\n", array_map(fn ($t) => self::taskLine($t, true), $f['tasks']));
            }
        }
        return implode("\n\n", $md) . "\n";
    }

    private static function taskLine(array $t, bool $withSub): string
    {
        $status = ['belum_mulai' => '', 'dikerjakan' => 'sedang dikerjakan', 'selesai' => '', 'gagal' => 'gagal'][$t['status']];
        $meta = array_filter([$withSub ? $t['sub_nama'] : null, ucfirst($t['priority']), $status]);
        $prefix = !empty($t['milestone']) ? "[{$t['milestone']}] " : '';
        return '- [' . ($t['status'] === 'selesai' ? 'x' : ' ') . "] $prefix{$t['title']}" . ($meta ? ' _(' . implode(' · ', $meta) . ')_' : '');
    }

    /**
     * ZIP Implemen: PRD.md (versi terbaru), TASKS.md, features/*.md, dan untuk PRD v1.2: CLAUDE.md, PROGRESS.md,
     * .env.example, db/schema.* (bila ada database). QUALITY.md ikut bila audit sudah dijalankan.
     */
    public static function exportZip(array $project, string $prdMarkdown, bool $manualEdit): string
    {
        $id = (int) $project['id'];
        $features = self::exportData($id);
        $hash = self::hash(project_features($id), $project);
        $report = QualityReport::load($id, $hash);
        $prdJson = self::latestPrd($id)[0] ?? [];

        $path = tempnam(sys_get_temp_dir(), 'planku');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat file zip.');
        }
        $zip->addFromString('PRD.md', $prdMarkdown);
        $zip->addFromString('TASKS.md', self::renderTasksMd($project['judul'], $features));
        foreach ($features as $i => $f) {
            $zip->addFromString(self::featureFilename($i, $f), self::renderFeatureExport($f, self::featureSecurity($f, $prdJson)));
        }
        if (($prdJson['versi'] ?? '') === '1.2') {
            $zip->addFromString('CLAUDE.md', self::claudeMd($project['judul'], $prdJson));
            $zip->addFromString('PROGRESS.md', self::progressMd($project['judul'], $prdJson));
            if (empty($prdJson['profil']['bendera']['uiOnly'])) {
                $zip->addFromString('.env.example', self::envExample($prdJson['layanan']));
                if ($prdJson['database']['kode_skema'] !== '') {
                    $zip->addFromString(self::schemaPath($prdJson['profil']['stack']['bahasa_skema'] ?? 'sql'), $prdJson['database']['kode_skema'] . "\n");
                }
            }
        }
        if ($report) {
            $quality = QualityReport::render($report);
            if ($manualEdit) {
                $quality .= "\n*Catatan: PRD diedit manual setelah audit; laporan ini merujuk versi PRD yang dibuat AI.*\n";
            }
            $zip->addFromString('QUALITY.md', $quality);
        }
        $zip->close();
        return $path;
    }

    private static function table(array $head, array $rows): string
    {
        $line = fn ($cells) => '| ' . implode(' | ', array_map(fn ($c) => self::cell((string) $c), $cells)) . ' |';
        return implode("\n", array_merge([$line($head), '|' . str_repeat('---|', count($head))], array_map($line, $rows)));
    }

    private static function bullets(array $items): string
    {
        return implode("\n", array_map(fn ($item) => "- $item", $items));
    }

    private static function numbered(array $items): string
    {
        return implode("\n", array_map(fn ($item, $i) => ($i + 1) . ". $item", $items, array_keys($items)));
    }

    private static function cell(string $text): string
    {
        return str_replace(['|', "\n"], ['\|', ' '], $text);
    }

    private static function erDiagram(array $relations): string
    {
        $arrows = ['satu-ke-banyak' => '||--o{', 'satu-ke-satu' => '||--||', 'banyak-ke-banyak' => '}o--o{'];
        $lines = [];
        foreach ($relations as $rel) {
            $from = strtoupper(preg_replace('/\W+/', '_', $rel['dari']));
            $to = strtoupper(preg_replace('/\W+/', '_', $rel['ke']));
            if ($from === '' || $to === '') {
                continue;
            }
            $label = str_replace('"', '', $rel['label']) ?: 'terkait';
            $lines[] = "    $from " . ($arrows[$rel['jenis']] ?? '||--o{') . " $to : \"$label\"";
        }
        return $lines ? "```mermaid\nerDiagram\n" . implode("\n", $lines) . "\n```" : '';
    }
}
