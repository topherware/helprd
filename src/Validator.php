<?php

final class Validator
{
    public const ICONS = [
        'bintang', 'rumah', 'keranjang', 'pengguna', 'dokumen', 'kalender', 'chat', 'grafik',
        'kunci', 'lokasi', 'uang', 'gambar', 'pengaturan', 'notifikasi', 'pencarian',
    ];
    public const STATUSES = ['direncanakan', 'dikerjakan', 'selesai'];
    /** Asal fitur/peran/entitas (v1.2.1): dari input pengguna, atau usulan AI yang belum diminta. */
    public const ASAL = ['ide', 'jawaban', 'catatan', 'keputusan', 'usulan_ai'];

    /** Teks wajib dengan batas panjang (dalam karakter). */
    public static function text($value, string $label, int $min, int $max): string
    {
        $value = is_string($value) ? trim(preg_replace('/[^\P{C}\n\t]/u', '', $value) ?? '') : '';
        $length = mb_strlen($value);
        if ($length < $min) {
            throw new UserError("$label minimal $min karakter.", 422);
        }
        if ($length > $max) {
            throw new UserError("$label maksimal $max karakter.", 422);
        }
        return $value;
    }

    /** Teks opsional (boleh kosong) dengan batas panjang. */
    public static function optional($value, string $label, int $max): string
    {
        return is_string($value) && trim($value) !== '' ? self::text($value, $label, 1, $max) : '';
    }

    public static function ide($value): string
    {
        return self::text($value, 'Ide', 10, 3000);
    }

    public static function techStack($value): string
    {
        return self::text($value, 'Tech stack', 2, 255);
    }

    /**
     * Isian Bagian B (langkah Teknologi). Nilai pilihan harus dari daftar tetap; teks bebas dibersihkan
     * (tag HTML dan karakter kontrol dibuang) dan dibatasi panjangnya di server.
     * @return array kolom projects yang disimpan
     */
    public static function brief(array $in): array
    {
        $design = $in['design_style'] ?? '';
        $stack = $in['stack_choice'] ?? '';
        $workflow = $in['ai_workflow'] ?? '';
        if (!isset(Brief::DESIGN[$design])) {
            throw new UserError('Pilih salah satu gaya desain.', 422);
        }
        if (!in_array($stack, array_merge(['bebas', 'tulis_sendiri'], Brief::stackIds()), true)) {
            throw new UserError('Pilih salah satu tech stack.', 422);
        }
        if (!isset(Brief::WORKFLOW[$workflow])) {
            throw new UserError('Pilih cara kerja AI coding assistant.', 422);
        }
        $clean = fn ($v) => is_string($v) ? trim(preg_replace('/[ \t]+/u', ' ', strip_tags($v))) : '';
        $designCustom = $design === 'tulis_sendiri' ? self::text($clean($in['design_custom'] ?? ''), 'Deskripsi gaya desain', 3, 1000) : null;
        $stackCustom = $stack === 'tulis_sendiri' ? self::text($clean($in['stack_custom'] ?? ''), 'Tech stack pilihan sendiri', 2, 500) : null;
        $notes = self::optional($clean($in['extra_notes'] ?? ''), 'Catatan tambahan', Brief::NOTES_MAX);

        $fixed = Brief::stack($stack);
        return [
            'design_style' => $design,
            'design_custom' => $designCustom,
            'stack_choice' => $stack,
            'stack_custom' => $stackCustom,
            'ui_only' => !empty($in['ui_only']) ? 1 : 0,
            'ai_workflow' => $workflow,
            'extra_notes' => $notes === '' ? null : $notes,
            // Kolom lama tetap diisi supaya langkah pertanyaan & peta fitur memakai teknologi yang sama.
            'tech_mode' => $stack === 'bebas' ? 'ai' : 'manual',
            'tech_stack_pilihan' => $stack === 'bebas' ? null : mb_substr($stackCustom ?? "{$fixed['nama']}: {$fixed['ringkas']}", 0, 255),
        ];
    }

    /**
     * Jawaban dari langkah 3. Format masuk: [{question_id, jawaban, dilewati}].
     * Jawaban pilihan berupa array string, disimpan sebagai JSON.
     * @param array<int, array> $questions pertanyaan milik project, di-key dengan id
     * @return array<int, array{jawaban: ?string, dilewati: int}>
     */
    public static function answers($items, array $questions): array
    {
        if (!is_array($items)) {
            throw new UserError('Format jawaban tidak valid.', 422);
        }
        $result = [];
        foreach ($items as $item) {
            $id = (int) ($item['question_id'] ?? 0);
            if (!isset($questions[$id])) {
                throw new UserError('Pertanyaan tidak ditemukan.', 422);
            }
            $raw = $item['jawaban'] ?? null;
            if ($questions[$id]['tipe'] === 'pilihan') {
                $choices = [];
                foreach (is_array($raw) ? array_slice($raw, 0, 12) : [] as $choice) {
                    if (is_string($choice) && trim($choice) !== '') {
                        $choices[] = self::text($choice, 'Pilihan', 1, 200);
                    }
                }
                $jawaban = $choices ? json_encode(array_values(array_unique($choices)), JSON_UNESCAPED_UNICODE) : null;
            } else {
                $jawaban = is_string($raw) && trim($raw) !== '' ? self::text($raw, 'Jawaban', 1, 1500) : null;
            }
            $skipped = !empty($item['dilewati']) || $jawaban === null;
            $result[$id] = ['jawaban' => $skipped ? null : $jawaban, 'dilewati' => $skipped ? 1 : 0];
        }
        foreach ($questions as $id => $_) {
            $result[$id] ??= ['jawaban' => null, 'dilewati' => 1];
        }
        return $result;
    }

    /** Field yang boleh diubah lewat update_feature. */
    public static function featureUpdate(array $body): array
    {
        $fields = [];
        if (array_key_exists('nama', $body)) {
            $fields['nama'] = self::text($body['nama'], 'Nama fitur', 1, 100);
        }
        if (array_key_exists('fase', $body)) {
            $fase = filter_var($body['fase'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 4]]);
            if ($fase === false) {
                throw new UserError('Fase harus 1 sampai 4.', 422);
            }
            $fields['fase'] = $fase;
        }
        if (array_key_exists('status', $body)) {
            if (!in_array($body['status'], self::STATUSES, true)) {
                throw new UserError('Status tidak valid.', 422);
            }
            $fields['status'] = $body['status'];
        }
        if (array_key_exists('deskripsi', $body)) {
            $fields['deskripsi'] = self::optional($body['deskripsi'], 'Deskripsi', 500);
        }
        if (array_key_exists('disertakan', $body)) {
            $fields['disertakan'] = $body['disertakan'] ? 1 : 0; // usulan AI dinyalakan/dimatikan dari peta fitur
        }
        if (array_key_exists('tujuan', $body)) {
            $fields['tujuan'] = self::optional($body['tujuan'], 'Tujuan', 800);
        }
        if (array_key_exists('selesai_bila', $body)) {
            // Boleh array atau teks satu kriteria per baris.
            $items = is_array($body['selesai_bila']) ? $body['selesai_bila'] : preg_split('/\R/u', (string) $body['selesai_bila']);
            $items = array_values(array_filter(array_map(fn ($i) => self::optional($i, 'Kriteria Selesai bila', 500), $items), 'strlen'));
            $fields['selesai_bila'] = json_encode(array_slice($items, 0, 12), JSON_UNESCAPED_UNICODE);
        }
        if (!$fields) {
            throw new UserError('Tidak ada perubahan.', 422);
        }
        return $fields;
    }

    /** Rapikan hasil AI untuk pertanyaan klarifikasi. */
    public static function aiQuestions($data): array
    {
        $list = is_array($data['pertanyaan'] ?? null) ? $data['pertanyaan'] : [];
        $result = [];
        foreach (array_slice($list, 0, 7) as $q) {
            $teks = self::clip($q['teks'] ?? '', 500);
            if ($teks === '') {
                continue;
            }
            $opsi = [];
            foreach (is_array($q['opsi'] ?? null) ? $q['opsi'] : [] as $o) {
                if (($o = self::clip($o, 80)) !== '') {
                    $opsi[] = $o;
                }
            }
            $opsi = array_slice(array_values(array_unique($opsi)), 0, 8);
            $tipe = ($q['tipe'] ?? '') === 'pilihan' && count($opsi) >= 2 ? 'pilihan' : 'teks_bebas';
            $result[] = [
                'teks' => $teks,
                'tipe' => $tipe,
                'opsi' => $tipe === 'pilihan' ? $opsi : [],
                'boleh_pilih_banyak' => $tipe === 'pilihan' && !empty($q['boleh_pilih_banyak']) ? 1 : 0,
            ];
        }
        if (count($result) < 3) {
            throw new AiException('Hasil AI: pertanyaan kurang dari 3. Data: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        return $result;
    }

    /** Rapikan hasil AI untuk peta fitur. */
    public static function aiFeatures($data): array
    {
        $features = [];
        foreach (array_slice(is_array($data['fitur'] ?? null) ? $data['fitur'] : [], 0, 14) as $f) {
            $nama = self::clip($f['nama'] ?? '', 100);
            if ($nama === '') {
                continue;
            }
            $subs = [];
            foreach (is_array($f['sub_fitur'] ?? null) ? $f['sub_fitur'] : [] as $s) {
                if (($s = self::clip($s, 100)) !== '') {
                    $subs[] = $s;
                }
            }
            $fase = (int) ($f['fase'] ?? 1);
            $features[] = [
                'nama' => $nama,
                'fase' => min(4, max(1, $fase)),
                'ikon' => in_array($f['ikon'] ?? '', self::ICONS, true) ? $f['ikon'] : 'bintang',
                'asal' => in_array($f['asal'] ?? '', self::ASAL, true) ? $f['asal'] : 'ide',
                'asal_alasan' => self::clip($f['alasan'] ?? '', 300),
                'sub_fitur' => array_slice($subs, 0, 8),
            ];
        }
        // Maksimal 3 usulan AI; usulan selebihnya dibuang supaya peta fitur tetap berasal dari input pengguna.
        $kept = [];
        $usulan = 0;
        foreach ($features as $f) {
            if ($f['asal'] !== 'usulan_ai' || ++$usulan <= 3) {
                $kept[] = $f;
            }
        }
        $features = $kept;
        if (count($features) < 3) {
            throw new AiException('Hasil AI: fitur kurang dari 3. Data: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
        }
        $stack = self::clip($data['rekomendasi_stack'] ?? '', 2000);
        return [
            'judul' => self::clip($data['judul'] ?? '', 150),
            'rekomendasi_stack' => $stack === '' ? null : $stack,
            'fitur' => $features,
        ];
    }

    /** Potongan "profil": bendera (hanya boolean asli), id stack, profil stack kustom, dan peran. */
    public static function aiProfil($data): array
    {
        $p = self::arr($data['profil'] ?? $data, true);
        $flags = [];
        foreach (self::arr($p['bendera'] ?? null, true) as $flag => $value) {
            if (in_array($flag, Triggers::FLAGS, true) && is_bool($value)) {
                $flags[$flag] = $value;
            }
        }
        $k = self::arr($p['stack_kustom'] ?? null, true);
        // Peran boleh string (v1.2) atau {nama, asal} (v1.2.1); asal disimpan terpisah supaya daftar peran tetap string.
        $roleAsal = [];
        foreach (self::arr($p['peran'] ?? null) as $r) {
            $name = mb_strtolower(self::clip(is_array($r) ? ($r['nama'] ?? '') : $r, 40));
            if ($name !== '' && $name !== 'pengunjung anonim' && !isset($roleAsal[$name]) && count($roleAsal) < 4) {
                $roleAsal[$name] = is_array($r) && in_array($r['asal'] ?? '', self::ASAL, true) ? $r['asal'] : 'ide';
            }
        }
        $roles = array_keys($roleAsal);
        $invariants = array_map(fn ($i) => [
            'jenis' => in_array($i['jenis'] ?? '', array_keys(Triggers::INVARIAN), true) ? $i['jenis'] : 'unik',
            'aturan' => self::clip($i['aturan'] ?? '', 300),
            'sumber_daya' => self::clip($i['sumber_daya'] ?? '', 80),
            'asal' => in_array($i['asal'] ?? '', self::ASAL, true) ? $i['asal'] : 'ide',
        ], self::objects($p['invarian'] ?? null, 8, 'aturan'));
        return [
            'bendera_ai' => $flags,
            'stack_id' => self::clip($p['stack_id'] ?? '', 40),
            'stack_kustom' => [
                'mirip' => self::clip($k['mirip'] ?? '', 40),
                'db' => self::clip($k['db'] ?? '', 20),
                'bahasa_skema' => self::clip($k['bahasa_skema'] ?? '', 20),
                'rls' => is_bool($k['rls'] ?? null) ? $k['rls'] : null,
                'serverless' => is_bool($k['serverless'] ?? null) ? $k['serverless'] : null,
            ],
            'peran' => array_merge(['pengunjung anonim'], $roles),
            'peran_asal' => ['pengunjung anonim' => 'ide'] + $roleAsal,
            'invarian' => $invariants,
            'alasan_stack' => self::clip($p['alasan_stack'] ?? '', 400),
        ];
    }

    /**
     * Rapikan hasil AI untuk PRD v1.2. Semua kunci selalu ada (kosong bila belum ditulis) supaya renderer dan validator
     * tidak perlu memeriksa satu per satu. $partial = true saat streaming (tidak ada pemeriksaan kelengkapan).
     */
    public static function aiPrd($data, int $featureCount, bool $partial = false): array
    {
        $data = self::arr($data, true);
        $o = self::arr($data['overview'] ?? null, true);
        $r = self::arr($data['requirements'] ?? null, true);
        $a = self::arr($data['arsitektur'] ?? null, true);
        $sequence = trim(preg_replace('/^```(mermaid)?|```$/m', '', self::clip($a['sequence_mermaid'] ?? '', 8000)));
        $ided = fn ($rows, int $max, string $prefix) => array_map(fn ($row) => [
            'id' => self::refId(is_array($row) ? ($row['id'] ?? '') : '', $prefix),
            'teks' => self::clip(is_array($row) ? ($row['teks'] ?? '') : $row, 500),
            'fitur' => is_array($row) ? self::strings($row['fitur'] ?? null, 10, 100) : [],
        ], array_slice(array_values(array_filter(self::arr($rows), fn ($x) => self::clip(is_array($x) ? ($x['teks'] ?? '') : $x, 500) !== '')), 0, $max));

        $result = [
            'versi' => '1.2',
            'overview' => [
                'deskripsi' => self::clip($o['deskripsi'] ?? '', 600),
                'masalah' => self::clip($o['masalah'] ?? '', 2000),
                'tujuan' => self::strings($o['tujuan'] ?? null, 8, 400),
                'prinsip' => self::strings($o['prinsip'] ?? null, 8, 300),
                'sukses' => self::clip($o['sukses'] ?? '', 800),
            ],
            'requirements' => [
                'fungsional' => $ided($r['fungsional'] ?? null, 40, 'FR'),
                'non_fungsional' => $ided($r['non_fungsional'] ?? null, 20, 'NFR'),
                'di_luar_cakupan' => self::strings($r['di_luar_cakupan'] ?? null, 10, 300),
                'pertanyaan_terbuka' => $ided($r['pertanyaan_terbuka'] ?? null, 15, 'OQ'),
                'catatan_diterapkan' => array_map(fn ($c) => [
                    'aturan' => self::clip($c['aturan'] ?? '', 400),
                    'ref' => self::strings($c['ref'] ?? null, 6, 20),
                ], self::objects($r['catatan_diterapkan'] ?? null, 30, 'aturan')),
            ],
            'fitur' => array_map(fn ($f) => [
                'deskripsi' => self::clip(self::arr($f, true)['deskripsi'] ?? '', 400),
                'sub_fitur' => self::strings(self::arr($f, true)['sub_fitur'] ?? null, 10, 300),
            ], array_slice(self::arr($data['fitur'] ?? null), 0, $featureCount)),
            'user_flow' => self::rows($data['user_flow'] ?? null, 12, ['judul' => 150, 'fase' => 20, 'langkah' => 1500]),
            'arsitektur' => [
                'ringkasan' => self::clip($a['ringkasan'] ?? '', 1000),
                'komponen' => self::rows($a['komponen'] ?? null, 10, ['nama' => 100, 'deskripsi' => 800]),
                'sequence_mermaid' => str_starts_with($sequence, 'sequenceDiagram') ? $sequence : '',
            ],
            'database' => self::database($data['database'] ?? null),
            'tech_stack' => self::rows($data['tech_stack'] ?? null, 14, ['lapisan' => 60, 'pilihan' => 200, 'alasan' => 400]),
            'keputusan_teknologi' => [
                'stack' => self::clip(self::arr($data['keputusan_teknologi'] ?? null, true)['stack'] ?? '', 200),
                'alasan' => self::clip(self::arr($data['keputusan_teknologi'] ?? null, true)['alasan'] ?? '', 500),
            ],
            'keamanan' => self::keamanan($data['keamanan'] ?? null),
            'design' => self::design($data['design'] ?? null),
            'rute' => self::rute($data['rute'] ?? null),
            'dummy' => self::dummy($data['dummy'] ?? null),
            'layanan' => self::layanan($data['layanan'] ?? null),
            'milestone' => self::milestone($data['milestone'] ?? null),
        ];
        if (isset($data['profil']) && is_array($data['profil'])) {
            $result['profil'] = $data['profil']; // sudah dirapikan & dikunci oleh pipeline
        }
        if (!$partial) {
            self::requireKeys($result, array_keys(array_intersect_key(Prompts::BAB, $data)), $featureCount);
        }
        return $result;
    }

    /** Pastikan kunci yang ditulis sebuah potongan tidak kosong; bila kosong, potongan dianggap gagal (AI diminta ulang). */
    public static function requireKeys(array $prd, array $keys, int $featureCount): void
    {
        $empty = [
            'overview' => $prd['overview']['masalah'] === '',
            'requirements' => !$prd['requirements']['fungsional'],
            'fitur' => count($prd['fitur']) < $featureCount,
            'user_flow' => !$prd['user_flow'],
            'arsitektur' => $prd['arsitektur']['ringkasan'] === '' && !$prd['arsitektur']['komponen'],
            'database' => !$prd['database']['tabel'],
            'tech_stack' => !$prd['tech_stack'],
            'keputusan_teknologi' => $prd['keputusan_teknologi']['stack'] === '',
            'keamanan' => !$prd['keamanan']['persyaratan'],
            'design' => !$prd['design']['warna']['terang'],
            'rute' => !$prd['rute']['rute'],
            'dummy' => !$prd['dummy']['dataset'],
            'layanan' => !$prd['layanan']['env_example'],
            'milestone' => !$prd['milestone']['daftar'],
        ];
        $missing = array_filter($keys, fn ($k) => $empty[$k] ?? false);
        if ($missing) {
            throw new AiException('Hasil AI: bagian PRD kosong: ' . implode(', ', $missing));
        }
    }

    private static function database($value): array
    {
        $db = self::arr($value, true);
        $rules = self::arr($db['aturan_pemicu'] ?? null, true);
        return [
            'catatan' => self::clip($db['catatan'] ?? '', 600),
            'tabel' => array_map(fn ($t) => [
                'nama' => self::clip($t['nama'] ?? '', 60),
                'deskripsi' => self::clip($t['deskripsi'] ?? '', 200),
                'tunggal' => !empty($t['tunggal']),
                'teknis' => !empty($t['teknis']),
                'kolom' => self::rows($t['kolom'] ?? null, 40, ['nama' => 60, 'tipe' => 160]),
            ], self::objects($db['tabel'] ?? null, 30, 'nama')),
            'relasi' => self::rows($db['relasi'] ?? null, 60, ['dari' => 60, 'ke' => 60, 'jenis' => 30, 'label' => 40]),
            'kode_skema' => trim(preg_replace('/^```\w*|```$/m', '', is_string($db['kode_skema'] ?? null) ? mb_substr($db['kode_skema'], 0, 40000) : '')),
            'aturan_pemicu' => [
                'snapshot' => self::clip($rules['snapshot'] ?? '', 600),
                'log_audit' => self::clip($rules['log_audit'] ?? '', 800),
                'tamu' => self::clip($rules['tamu'] ?? '', 600),
            ],
            'kebijakan_akses' => self::rows($db['kebijakan_akses'] ?? null, 20, ['tabel' => 60, 'aturan' => 500]),
        ];
    }

    private static function keamanan($value): array
    {
        $k = self::arr($value, true);
        $levels = ['publik', 'internal', 'pribadi', 'sensitif'];
        $owasp = self::arr($k['owasp'] ?? null, true);
        return [
            'klasifikasi_data' => array_map(fn ($r) => [
                'data' => self::clip($r['data'] ?? '', 150),
                'sensitivitas' => in_array($r['sensitivitas'] ?? '', $levels, true) ? $r['sensitivitas'] : 'pribadi',
                'lokasi' => self::clip($r['lokasi'] ?? '', 200),
                'akses' => self::clip($r['akses'] ?? '', 300),
                'retensi' => self::clip($r['retensi'] ?? '', 200),
                'boleh_masuk_log' => !empty($r['boleh_masuk_log']),
            ], self::objects($k['klasifikasi_data'] ?? null, 30, 'data')),
            'prinsip_akses' => self::clip($k['prinsip_akses'] ?? '', 500),
            'hak_akses' => array_map(fn ($r) => [
                'peran' => mb_strtolower(self::clip($r['peran'] ?? '', 60)),
                'sumber_daya' => self::clip($r['sumber_daya'] ?? '', 150),
                'aksi' => array_values(array_intersect(['baca', 'tulis', 'hapus', 'setujui'], array_map('strtolower', self::strings($r['aksi'] ?? null, 4, 10)))),
            ], self::objects($k['hak_akses'] ?? null, 40, 'peran')),
            'persyaratan' => array_map(fn ($r) => [
                'id' => implode('', self::secIds($r['id'] ?? '')) ?: 'SEC-??',
                'kelompok' => self::clip($r['kelompok'] ?? '', 60),
                'teks' => self::clip($r['teks'] ?? '', 500),
                'kontrol' => array_values(array_filter(self::strings($r['kontrol'] ?? null, 12, 60), fn ($code) => preg_match('/^[a-z]+\.[a-z_]+$/', $code))),
                'fitur' => self::strings($r['fitur'] ?? null, 15, 100),
            ], self::objects($k['persyaratan'] ?? null, 40, 'teks')),
            'hardening' => self::strings($k['hardening'] ?? null, 20, 300),
            'owasp' => [
                'edisi' => self::clip($owasp['edisi'] ?? '', 60),
                'kategori' => array_map(fn ($r) => [
                    'kode' => self::clip($r['kode'] ?? '', 20),
                    'nama' => self::clip($r['nama'] ?? '', 100),
                    'sec' => self::secIds($r['sec'] ?? null),
                    'alasan' => self::clip($r['alasan'] ?? '', 300),
                ], self::objects($owasp['kategori'] ?? null, 10, 'kode')),
            ],
            'risiko_tersisa' => self::strings($k['risiko_tersisa'] ?? null, 15, 500),
        ];
    }

    private static function design($value): array
    {
        $d = self::arr($value, true);
        $w = self::arr($d['warna'] ?? null, true);
        $tokens = fn ($set) => array_map(fn ($v) => self::clip($v, 60), array_filter(self::arr($set, true), fn ($v, $k) => is_string($k) && is_string($v), ARRAY_FILTER_USE_BOTH));
        $t = self::arr($d['tipografi'] ?? null, true);
        $u = self::arr($d['ukuran'] ?? null, true);
        $n = self::arr($d['nuansa'] ?? null, true);
        $s = self::arr($d['keadaan'] ?? null, true);
        $nums = fn ($v) => array_slice(array_values(array_map('intval', array_filter(self::arr($v), 'is_numeric'))), 0, 12);
        return [
            'alasan' => self::clip($d['alasan'] ?? '', 500),
            'warna' => ['terang' => $tokens($w['terang'] ?? null), 'gelap' => $tokens($w['gelap'] ?? null)],
            'tipografi' => [
                'font_judul' => self::clip($t['font_judul'] ?? '', 80),
                'font_isi' => self::clip($t['font_isi'] ?? '', 80),
                'skala' => $nums($t['skala'] ?? null),
                'tinggi_baris' => self::clip($t['tinggi_baris'] ?? '', 60),
                'sumber' => self::clip($t['sumber'] ?? '', 120),
            ],
            'ukuran' => [
                'ketuk_min' => is_numeric($u['ketuk_min'] ?? null) ? (int) $u['ketuk_min'] : (int) preg_replace('/\D.*/', '', (string) ($u['ketuk_min'] ?? '0')),
                'spasi' => $nums($u['spasi'] ?? null),
                'radius' => self::clip($u['radius'] ?? '', 120),
                'bayangan' => self::clip($u['bayangan'] ?? '', 200),
                'lebar_maks' => self::clip($u['lebar_maks'] ?? '', 60),
                'breakpoint' => self::strings($u['breakpoint'] ?? null, 6, 40),
            ],
            'komponen' => self::rows($d['komponen'] ?? null, 16, ['nama' => 60, 'gaya' => 400]),
            'nuansa' => ['sifat' => self::strings($n['sifat'] ?? null, 6, 40), 'hindari' => self::strings($n['hindari'] ?? null, 4, 120)],
            'keadaan' => ['loading' => self::clip($s['loading'] ?? '', 300), 'kosong' => self::clip($s['kosong'] ?? '', 300), 'error' => self::clip($s['error'] ?? '', 300)],
            'aksesibilitas' => self::strings($d['aksesibilitas'] ?? null, 8, 300),
        ];
    }

    private static function rute($value): array
    {
        $r = self::arr($value, true);
        return [
            'rute' => array_map(fn ($x) => [
                'path' => self::clip($x['path'] ?? '', 120),
                'halaman' => self::clip($x['halaman'] ?? '', 120),
                'peran' => array_map('mb_strtolower', self::strings($x['peran'] ?? null, 6, 40)),
                'layout' => self::clip($x['layout'] ?? '', 80),
                'sumber' => self::strings($x['sumber'] ?? null, 8, 100),
            ], self::objects($r['rute'] ?? null, 60, 'path')),
            'navigasi' => array_map(fn ($x) => [
                'peran' => mb_strtolower(self::clip($x['peran'] ?? '', 40)),
                'komponen' => self::clip($x['komponen'] ?? '', 200),
                'item' => self::strings($x['item'] ?? null, 12, 60),
            ], self::objects($r['navigasi'] ?? null, 8, 'peran')),
            'rute_khusus' => self::rows($r['rute_khusus'] ?? null, 10, ['jenis' => 20, 'path' => 120, 'halaman' => 200]),
            'penjaga_akses' => self::strings($r['penjaga_akses'] ?? null, 12, 400),
        ];
    }

    private static function dummy($value): array
    {
        $d = self::arr($value, true);
        $c = self::arr($d['copywriting'] ?? null, true);
        $m = self::arr($c['microcopy'] ?? null, true);
        $cell = fn ($v) => is_int($v) || is_float($v) ? $v : (is_bool($v) ? $v : self::clip($v, 200));
        return [
            'dataset' => array_map(fn ($x) => [
                'id' => strtoupper(self::clip($x['id'] ?? '', 60)),
                'tabel' => self::clip($x['tabel'] ?? '', 60),
                'kolom' => self::strings($x['kolom'] ?? null, 20, 60),
                'baris' => array_map(fn ($row) => array_map($cell, array_slice(self::arr($row), 0, 20)), array_slice(self::arr($x['baris'] ?? null), 0, 12)),
            ], self::objects($d['dataset'] ?? null, 20, 'id')),
            'template' => self::rows($d['template'] ?? null, 12, ['nama' => 100, 'isi' => 1000, 'contoh' => 1000]),
            'copywriting' => [
                'nada' => self::clip($c['nada'] ?? '', 300),
                'sapaan' => self::clip($c['sapaan'] ?? '', 200),
                'istilah' => self::strings($c['istilah'] ?? null, 20, 200),
                'microcopy' => [
                    'kosong' => self::clip($m['kosong'] ?? '', 300), 'error' => self::clip($m['error'] ?? '', 300),
                    'sukses' => self::clip($m['sukses'] ?? '', 300), 'konfirmasi' => self::clip($m['konfirmasi'] ?? '', 300),
                ],
            ],
        ];
    }

    private static function layanan($value): array
    {
        $l = self::arr($value, true);
        return [
            'layanan' => array_map(fn ($x) => [
                'kebutuhan' => mb_strtolower(self::clip($x['kebutuhan'] ?? '', 60)),
                'vendor' => self::clip($x['vendor'] ?? '', 120),
                'mock' => !empty($x['mock']),
                'alasan' => self::clip($x['alasan'] ?? '', 400),
                'biaya' => self::clip($x['biaya'] ?? '', 200),
                'env' => array_values(array_filter(self::strings($x['env'] ?? null, 10, 60), fn ($v) => preg_match('/^[A-Z][A-Z0-9_]*$/', $v))),
                'mode_mvp' => self::clip($x['mode_mvp'] ?? '', 200),
            ], self::objects($l['layanan'] ?? null, 20, 'kebutuhan')),
            'rate_limit' => self::clip($l['rate_limit'] ?? '', 600),
            'env_example' => array_map(fn ($g) => [
                'grup' => self::clip($g['grup'] ?? '', 60),
                'komentar' => self::clip($g['komentar'] ?? '', 300),
                'variabel' => array_values(array_filter(self::rows($g['variabel'] ?? null, 20, ['nama' => 60, 'nilai' => 200, 'komentar' => 300]), fn ($v) => preg_match('/^[A-Z][A-Z0-9_]*$/', $v['nama']))),
            ], self::objects($l['env_example'] ?? null, 12, 'grup')),
        ];
    }

    private static function milestone($value): array
    {
        $m = self::arr($value, true);
        $c = self::arr($m['claude_md'] ?? null, true);
        $cmd = self::arr($c['perintah'] ?? null, true);
        return [
            'daftar' => array_map(fn ($x) => [
                'id' => strtoupper(self::clip($x['id'] ?? '', 4)),
                'nama' => self::clip($x['nama'] ?? '', 80),
                'tujuan' => self::clip($x['tujuan'] ?? '', 500),
                'selesai_bila' => self::strings($x['selesai_bila'] ?? null, 8, 300),
                'cakupan' => self::strings($x['cakupan'] ?? null, 20, 100),
                'checkpoint' => self::clip($x['checkpoint'] ?? '', 600),
            ], self::objects($m['daftar'] ?? null, 6, 'id')),
            'aturan_checkpoint' => self::clip($m['aturan_checkpoint'] ?? '', 800),
            'master_prompt' => is_string($m['master_prompt'] ?? null) ? mb_substr(trim($m['master_prompt']), 0, 8000) : '',
            'claude_md' => [
                'struktur_folder' => is_string($c['struktur_folder'] ?? null) ? mb_substr(trim($c['struktur_folder']), 0, 3000) : '',
                'konvensi' => self::strings($c['konvensi'] ?? null, 15, 300),
                'perintah' => ['jalan' => self::clip($cmd['jalan'] ?? '', 200), 'uji' => self::clip($cmd['uji'] ?? '', 200)],
            ],
        ];
    }

    /** "fr-1", "FR 01" → "FR-01"; kosong bila tidak sesuai awalan. */
    private static function refId($value, string $prefix): string
    {
        return is_string($value) && preg_match('/^' . $prefix . '[-\s]?(\d+)$/i', trim($value), $m) ? sprintf('%s-%02d', $prefix, $m[1]) : '';
    }

    /**
     * Rapikan hasil Bikin Task untuk satu kelompok fitur. Fitur & sub fitur dicocokkan lewat nama, lalu urutan.
     * @param array $chunk fitur dengan 'subs' => [[id, nama], ...]
     * @return array<int, array> feature_id => deskripsi, tujuan, selesai_bila, subs (sub_id => tujuan, selesai_bila), tasks
     */
    public static function aiTasks($data, array $chunk, bool $uiOnly = false): array
    {
        $key = fn ($name) => mb_strtolower(trim(is_string($name) ? $name : ''));
        $items = self::objects($data['fitur'] ?? null, 50, 'nama');
        $byName = array_column(array_map(fn ($it) => [$key($it['nama']), $it], $items), 1, 0);
        $priorities = ['utama', 'penting', 'opsional'];
        $out = [];
        foreach ($chunk as $i => $f) {
            $it = $byName[$key($f['nama'])] ?? ($items[$i] ?? null);
            if (!$it) {
                throw new AiException("Hasil AI: fitur \"{$f['nama']}\" tidak ada. Data: " . mb_substr(json_encode($data, JSON_UNESCAPED_UNICODE), 0, 500));
            }
            $subItems = self::objects($it['sub_fitur'] ?? null, 30, 'nama');
            $subByName = array_column(array_map(fn ($s) => [$key($s['nama']), $s], $subItems), 1, 0);
            $subIds = [];
            $subs = [];
            foreach ($f['subs'] as $j => $s) {
                $si = $subByName[$key($s['nama'])] ?? ($subItems[$j] ?? []);
                $subs[$s['id']] = ['tujuan' => self::clip($si['tujuan'] ?? '', 600), 'selesai_bila' => self::strings($si['selesai_bila'] ?? null, 6, 400)];
                $subIds[$key($s['nama'])] = $s['id'];
            }
            $tasks = [];
            foreach (self::arr($it['tasks'] ?? null) as $t) {
                $t = is_array($t) ? $t : ['judul' => $t];
                $title = self::clip($t['judul'] ?? '', 200);
                if ($title === '') {
                    continue;
                }
                // Awalan "[M1]" di judul dipindah ke kolom milestone.
                if (preg_match('/^\[?(M[123])\]?\s*[:\-–]?\s*/u', $title, $m)) {
                    $title = trim(mb_substr($title, mb_strlen($m[0])));
                    $t['milestone'] ??= $m[1];
                }
                $milestone = strtoupper(is_string($t['milestone'] ?? null) ? trim($t['milestone'], '[] ') : '');
                $tasks[] = [
                    'title' => $title,
                    'milestone' => $uiOnly ? 'M1' : (in_array($milestone, ['M1', 'M2', 'M3'], true) ? $milestone : 'M2'),
                    'sub_feature_id' => $subIds[$key($t['sub_fitur'] ?? '')] ?? null,
                    'priority' => in_array($t['prioritas'] ?? '', $priorities, true) ? $t['prioritas'] : 'penting',
                ];
            }
            // Urut M1 → M2 → M3, urutan asli dipertahankan di dalam milestone yang sama.
            $order = array_keys($tasks);
            usort($order, fn ($x, $y) => [$tasks[$x]['milestone'], $x] <=> [$tasks[$y]['milestone'], $y]);
            $tasks = array_map(fn ($i) => $tasks[$i], $order);
            if (count($tasks) < 3) {
                throw new AiException("Hasil AI: task untuk fitur \"{$f['nama']}\" kurang dari 3.");
            }
            $out[$f['id']] = [
                'deskripsi' => self::clip($it['deskripsi'] ?? '', 500),
                'tujuan' => self::clip($it['tujuan'] ?? '', 800),
                'selesai_bila' => self::strings($it['selesai_bila'] ?? null, 8, 500),
                'subs' => $subs,
                'tasks' => array_slice($tasks, 0, 20),
            ];
        }
        return $out;
    }

    /** Rapikan temuan Audit AI (Pass 4): bab + pesan. */
    public static function aiAudit($data): array
    {
        return array_map(fn ($t) => ['bab' => (int) ($t['bab'] ?? 0), 'pesan' => self::clip($t['pesan'] ?? '', 500)],
            self::objects($data['temuan'] ?? null, 8, 'pesan'));
    }

    /** "SEC-1, sec-02" atau ["SEC-01"] menjadi ["SEC-01", "SEC-02"]. */
    private static function secIds($value): array
    {
        $text = is_array($value) ? implode(' ', array_filter($value, 'is_string')) : (is_string($value) ? $value : '');
        preg_match_all('/SEC-?(\d+)/i', $text, $m);
        return array_values(array_unique(array_map(fn ($n) => sprintf('SEC-%02d', $n), $m[1])));
    }

    /** Daftar objek (array asosiatif) yang field $required-nya tidak kosong. */
    private static function objects($value, int $max, string $required): array
    {
        $out = array_filter(self::arr($value), fn ($r) => is_array($r) && self::clip($r[$required] ?? '', 1000) !== '');
        return array_slice(array_values($out), 0, $max);
    }

    private static function clip($value, int $max): string
    {
        return is_string($value) || is_int($value) ? mb_substr(trim((string) $value), 0, $max) : '';
    }

    /** Nilai sebagai array; $assoc = true untuk objek, false untuk daftar. */
    private static function arr($value, bool $assoc = false): array
    {
        if (!is_array($value)) {
            return [];
        }
        return $assoc ? $value : array_values($value);
    }

    /** Daftar string tidak kosong, dipotong ke $max item dan $len karakter. */
    private static function strings($value, int $max, int $len): array
    {
        $out = [];
        foreach (self::arr($value) as $item) {
            if (($item = self::clip($item, $len)) !== '') {
                $out[] = $item;
            }
        }
        return array_slice($out, 0, $max);
    }

    /** Daftar objek dengan field tertentu (field => panjang maks); baris tanpa field pertama dibuang. */
    private static function rows($value, int $max, array $fields): array
    {
        $out = [];
        $first = array_key_first($fields);
        foreach (self::arr($value) as $item) {
            $row = [];
            foreach ($fields as $field => $len) {
                $row[$field] = self::clip(is_array($item) ? ($item[$field] ?? '') : '', $len);
            }
            if ($row[$first] !== '') {
                $out[] = $row;
            }
        }
        return array_slice($out, 0, $max);
    }
}
