<?php

/**
 * Validator dimensi lama yang disesuaikan untuk v1.2 (deterministik, ringan):
 * Struktur, Keterlacakan, KecocokanStack, Kejujuran, Konsistensi, Keterukuran. Keterbacaan belum dinilai otomatis.
 */
final class Dimensi
{
    /** Validator => dimensi skor. */
    public const MAP = [
        'Struktur' => 'struktur', 'Keterlacakan' => 'keterlacakan', 'KecocokanStack' => 'kecocokan_stack',
        'Kejujuran' => 'kejujuran', 'Konsistensi' => 'konsistensi', 'Keterukuran' => 'keterukuran',
    ];

    /** StrukturLengkap: bab 1–13 ada sesuai profil (mode hanya-UI tanpa bab 6, 8, 12). */
    public static function Struktur(array $c): array
    {
        $p = $c['prd'];
        $checks = [
            1 => ['Overview', ($p['overview']['masalah'] ?? '') !== ''],
            2 => ['Requirements', !empty($p['requirements']['fungsional'])],
            3 => ['Core Features', count($p['fitur'] ?? []) >= count($c['features'])],
            4 => ['User Flow', !empty($p['user_flow'])],
            5 => ['Architecture', ($p['arsitektur']['ringkasan'] ?? '') !== '' || !empty($p['arsitektur']['komponen'])],
            6 => ['Database Schema', !empty($p['database']['tabel'])],
            7 => ['Tech Stack', !empty($p['tech_stack'])],
            8 => ['Keamanan & Privasi', !empty($p['keamanan']['persyaratan'])],
            9 => ['Design System & UI/UX', !empty($p['design']['warna']['terang'])],
            10 => ['Peta Rute & Layout per Peran', !empty($p['rute']['rute'])],
            11 => ['Data Dummy & Copywriting', !empty($p['dummy']['dataset'])],
            12 => ['Layanan Pihak Ketiga & Variabel Lingkungan', !empty($p['layanan']['env_example']) || !empty($p['layanan']['layanan'])],
            13 => ['Milestone Pembangunan & Master Prompt', !empty($p['milestone']['daftar'])],
        ];
        $out = [];
        foreach ($checks as $bab => [$name, $ok]) {
            if ($c['flags']['uiOnly'] && in_array($bab, [6, 8, 12], true)) {
                continue;
            }
            if (!$ok) {
                $out[] = QV::finding('Struktur', 'M', $bab, "Bab $bab $name belum ada atau kosong.");
            }
        }
        return $out;
    }

    /** Keterlacakan: aturan dari Catatan Tambahan muncul sebagai FR/NFR/OQ beridentitas; gaya desain dan stack pilihan pengguna tercermin. */
    public static function Keterlacakan(array $c): array
    {
        $b = $c['brief'];
        $p = $c['prd'];
        $out = [];
        $ids = array_filter(array_column(array_merge(QV::reqs($p['requirements']['fungsional'] ?? []), QV::reqs($p['requirements']['non_fungsional'] ?? []),
            QV::reqs($p['requirements']['pertanyaan_terbuka'] ?? [])), 'id'));
        foreach ($b->noteItems() as $rule) {
            $words = QV::words($rule);
            $found = false;
            foreach ($p['requirements']['catatan_diterapkan'] ?? [] as $row) {
                $hit = array_intersect($words, QV::words($row['aturan']));
                $found = $found || ($words && count($hit) >= max(1, (int) ceil(count($words) / 2)) && array_intersect($row['ref'], $ids));
            }
            if (!$found) {
                $out[] = QV::finding('Keterlacakan', 'M', 2, "Aturan dari catatan tambahan belum dipetakan ke FR/NFR/OQ: \"$rule\".", 'Tambahkan FR/NFR beridentitas atau Open Question, lalu catat di "catatan diterapkan".');
            }
        }
        $design = mb_strtolower(json_encode($p['design'] ?? [], JSON_UNESCAPED_UNICODE));
        $styleWords = ['bersih_modern' => '/bersih|modern/u', 'dark_premium' => '/gelap|dark/u', 'minimalis' => '/minimalis/u', 'korporat' => '/korporat|profesional/u'];
        $ok = match (true) {
            isset($styleWords[$b->designStyle]) => (bool) preg_match($styleWords[$b->designStyle], $design),
            $b->designStyle === 'tulis_sendiri' => (bool) array_filter(QV::words($b->designCustom), fn ($w) => str_contains($design, $w)),
            default => true,
        };
        if (!$ok && !empty($p['design'])) {
            $out[] = QV::finding('Keterlacakan', 'M', 9, 'Gaya desain pilihan pengguna ("' . $b->designLabel() . '") tidak tercermin di bab 9.');
        }
        $stackText = mb_strtolower(json_encode([$p['tech_stack'] ?? [], $p['keputusan_teknologi'] ?? []], JSON_UNESCAPED_UNICODE));
        $keys = $b->stackChoice === 'tulis_sendiri' ? QV::words($b->stackCustom) : array_map('mb_strtolower', $c['stack']['kata_kunci'] ?? []);
        if ($b->stackChoice !== 'bebas' && $keys && !array_filter($keys, fn ($k) => str_contains($stackText, mb_strtolower($k)))) {
            $out[] = QV::finding('Keterlacakan', 'M', 7, 'Tech stack pilihan pengguna tidak tercermin di bab 7.');
        }
        return $out;
    }

    /** KecocokanStack: stack terkunci sesuai stacks.json, bahasa skema sesuai, tidak menjanjikan RLS bila tidak didukung. */
    public static function KecocokanStack(array $c): array
    {
        $p = $c['prd'];
        $stack = $c['stack'];
        $out = [];
        if (empty($p['profil']['stack'])) {
            $out[] = QV::finding('KecocokanStack', 'M', 7, 'Stack belum dikunci ke profil di stacks.json.');
        }
        $code = (string) ($p['database']['kode_skema'] ?? '');
        if (!$c['flags']['uiOnly'] && $code !== '' && ($lang = QV::schemaLanguage($code)) !== $stack['bahasa_skema']) {
            $out[] = QV::finding('KecocokanStack', 'M', 6, "Bahasa kode skema ($lang) tidak sesuai stack ({$stack['bahasa_skema']}).");
        }
        if (!$stack['rls']) {
            $sentences = array_merge([], ...array_map([QV::class, 'sentences'], QV::texts([$p['keamanan'] ?? [], $p['database']['kebijakan_akses'] ?? [], $code])));
            $hits = array_filter($sentences,fn ($s) => preg_match('/row level security|\brls\b|create policy/iu', $s) && !QV::negated($s));
            if ($hits) {
                $out[] = QV::finding('KecocokanStack', 'K', 8, 'Menjanjikan Row Level Security, tetapi database stack ini tidak mendukungnya.', 'Ganti dengan pemeriksaan kepemilikan di server.');
            }
        }
        $words = array_map('mb_strtolower', $stack['kata_kunci'] ?? []);
        $text = mb_strtolower(json_encode($p['tech_stack'] ?? [], JSON_UNESCAPED_UNICODE));
        if (!empty($p['profil']['stack']) && !$c['flags']['uiOnly'] && $words && !str_contains($text, $words[0])) {
            $out[] = QV::finding('KecocokanStack', 'M', 7, "Tabel Tech Stack tidak menyebut {$stack['kata_kunci'][0]} dari stack terkunci.");
        }
        return $out;
    }

    /** Kejujuran: tanpa klaim berlebih atau klaim kepatuhan hukum; klaim "gratis" konsisten dengan biaya di bab 12. */
    public static function Kejujuran(array $c): array
    {
        $std = $c['standard'];
        $words = implode('|', array_map(fn ($w) => preg_quote($w, '/'), $std['kata_klaim_berlebih']));
        $denial = '/tidak (boleh )?(meng)?klaim|bukan (sebuah )?klaim|tidak menjamin|tanpa klaim|dilarang (meng)?klaim|hindari klaim/iu';
        $out = [];
        foreach (QV::sentences($c['markdown']) as $s) {
            // Kalimat yang justru melarang/menghindari klaim (mis. "tidak memakai klaim berlebihan seperti 'aman 100%'") bukan klaim.
            $denies = preg_match($denial, $s) || (preg_match('/klaim|hindari|menghindari/iu', $s) && QV::negated($s)) || preg_match('/\b(hindari|menghindari|jangan)\b/iu', $s);
            if (preg_match("/($words)/iu", $s) && !$denies) {
                $out[] = QV::finding('Kejujuran', 'K', null, 'Klaim berlebih: "' . mb_substr($s, 0, 160) . '"');
            }
            if (preg_match('/\b(patuh|sesuai|memenuhi)\s+(sepenuhnya\s+)?(uu|undang-undang|hukum|regulasi|gdpr|pdp)\b/iu', $s) && !preg_match('/tinjau|ahli|perlu dikonfirmasi|belum/iu', $s)) {
                $out[] = QV::finding('Kejujuran', 'K', null, 'Klaim kepatuhan hukum tanpa label tinjauan ahli: "' . mb_substr($s, 0, 160) . '"');
            }
        }
        $paid = array_filter($c['prd']['layanan']['layanan'] ?? [], fn ($r) => preg_match('/berbayar|per transaksi|per percakapan|per pesan|\$|rp\s?\d/iu', $r['biaya']));
        $claims = json_encode([$c['prd']['overview'] ?? [], $c['prd']['tech_stack'] ?? [], $c['prd']['keputusan_teknologi'] ?? []], JSON_UNESCAPED_UNICODE);
        if ($paid && preg_match('/(sepenuhnya|100%|total|selalu)\s+gratis|tanpa biaya (apa pun|sama sekali)|gratis selamanya/iu', $claims)) {
            $out[] = QV::finding('Kejujuran', 'M', 12, 'Ada klaim "gratis" padahal bab 12 mencantumkan layanan berbayar.');
        }
        return $out;
    }

    /** Konsistensi lintas bab: peran sama di bab 2/6/8/10, FR merujuk fitur yang ada, ID FR unik. */
    public static function Konsistensi(array $c): array
    {
        $p = $c['prd'];
        $out = [];
        $roles = array_map([QV::class, 'lower'], $c['roles']);
        $access = array_unique(array_map([QV::class, 'lower'], array_column($p['keamanan']['hak_akses'] ?? [], 'peran')));
        if (!$c['flags']['uiOnly'] && $access) {
            if ($unknown = array_diff($access, $roles)) {
                $out[] = QV::finding('Konsistensi', 'M', 8, 'Peran di hak akses tidak ada di daftar peran: ' . implode(', ', $unknown) . '.');
            }
            if ($absent = array_diff($roles, $access)) {
                $out[] = QV::finding('Konsistensi', 'M', 8, 'Peran tidak punya baris hak akses: ' . implode(', ', $absent) . '.');
            }
        }
        $names = array_map(fn ($f) => QV::lower($f['nama']), $c['features']);
        $fr = QV::reqs($p['requirements']['fungsional'] ?? []);
        $badRef = [];
        foreach ($fr as $x) {
            foreach ($x['fitur'] as $name) {
                if (!in_array(QV::lower($name), $names, true)) {
                    $badRef[] = $name;
                }
            }
        }
        if ($badRef) {
            $out[] = QV::finding('Konsistensi', 'M', 2, 'FR merujuk fitur yang tidak ada di peta fitur: ' . implode(', ', array_unique($badRef)) . '.');
        }
        $ids = array_column($fr, 'id');
        if (in_array('', $ids, true) || count($ids) !== count(array_unique($ids))) {
            $out[] = QV::finding('Konsistensi', 'M', 2, 'Persyaratan fungsional harus punya ID unik (FR-01, FR-02, ...).');
        }
        return $out;
    }

    /** Keterukuran: NFR punya kriteria terukur, milestone punya Selesai bila, sukses produk dirumuskan. */
    public static function Keterukuran(array $c): array
    {
        $p = $c['prd'];
        $out = [];
        $nfr = QV::reqs($p['requirements']['non_fungsional'] ?? []);
        if (!array_filter($nfr, fn ($x) => preg_match('/\d/', $x['teks']))) {
            $out[] = QV::finding('Keterukuran', 'M', 2, 'Belum ada persyaratan non-fungsional dengan kriteria terukur (angka atau ambang yang bisa diperiksa).');
        }
        $weak = array_column(array_filter($p['milestone']['daftar'] ?? [], fn ($x) => count($x['selesai_bila']) < 2), 'id');
        if ($weak) {
            $out[] = QV::finding('Keterukuran', 'M', 13, 'Milestone dengan kurang dari 2 kriteria Selesai bila: ' . implode(', ', $weak) . '.');
        }
        if (($p['overview']['sukses'] ?? '') === '') {
            $out[] = QV::finding('Keterukuran', 'm', 1, 'Kriteria sukses produk belum ditulis.');
        }
        return $out;
    }
}
