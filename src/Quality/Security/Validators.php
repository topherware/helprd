<?php

/**
 * Validator keamanan (v1.1 yang disederhanakan di v1.2): hanya menilai tingkat Wajib MVP.
 * Keparahan diambil dari standard.json (K kritis, M mayor). Klaim berlebih pindah ke dimensi Kejujuran (Dimensi::Kejujuran),
 * konsistensi RLS ke KecocokanStack, dan pihak ketiga ke V16 (bab 12).
 *
 * Konteks ($c): prd, features, security (SecurityTriggers::detect), controls (SecurityTriggers::tiers), markdown, standard.
 */
final class SecurityValidators
{
    public static function run(array $c): array
    {
        $out = [];
        foreach ($c['standard']['validator_keamanan'] as $rule => $sev) {
            foreach (self::$rule($c) as [$bab, $message]) {
                $out[] = QV::finding($rule, $sev, $bab, $message);
            }
        }
        return $out;
    }

    public static function SecBagianLengkap(array $c): array
    {
        $k = $c['prd']['keamanan'] ?? [];
        if (empty($k['persyaratan'])) {
            return [[8, 'Bab 8 Keamanan & Privasi belum ada.']];
        }
        $out = [];
        $parts = ['klasifikasi_data' => 'Klasifikasi Data', 'hak_akses' => 'Aktor & Hak Akses', 'risiko_tersisa' => 'Risiko Tersisa'];
        foreach ($parts as $key => $label) {
            if (empty($k[$key])) {
                $out[] = [8, "Sub-bagian $label kosong."];
            }
        }
        if (empty($k['owasp']['kategori'])) {
            $out[] = [8, 'Pemetaan OWASP kosong.'];
        }
        if (!preg_match('/UU|Pelindungan Data Pribadi|PDP/u', implode(' ', $k['risiko_tersisa'] ?? []))) {
            $out[] = [8, 'Risiko tersisa belum memuat catatan "perlu ditinjau ahli hukum" untuk UU Pelindungan Data Pribadi.'];
        }
        foreach (SecurityTriggers::personalTables($c['prd']) as $table) {
            if (!self::classified($k, $table)) {
                $out[] = [8, "Tabel `$table` berisi data pribadi tetapi belum ada di klasifikasi data."];
            }
        }
        return $out;
    }

    public static function SecPemicuTerpenuhi(array $c): array
    {
        $covered = [];
        foreach ($c['prd']['keamanan']['persyaratan'] ?? [] as $sec) {
            foreach ($sec['kontrol'] ?? [] as $code) {
                $covered[$code] = true;
            }
        }
        $out = [];
        foreach ($c['controls']['mvp'] as $code => [, $text]) {
            if (!isset($covered[$code])) {
                $out[] = [8, "Kontrol Wajib MVP [$code] belum punya SEC ($text)"];
            }
        }
        return $out;
    }

    public static function SecMatriksAkses(array $c): array
    {
        $k = $c['prd']['keamanan'] ?? [];
        if (empty($k['hak_akses'])) {
            return [];
        }
        $known = array_map([QV::class, 'lower'], $c['roles']);
        $db = mb_strtolower(json_encode($c['prd']['database']['tabel'] ?? [], JSON_UNESCAPED_UNICODE));
        $out = [];
        foreach (array_unique(array_column($k['hak_akses'], 'peran')) as $role) {
            $first = mb_strtolower(preg_split('/[\s\/(]+/u', trim($role))[0]);
            if (!Triggers::isAnonymous($role) && !in_array(QV::lower($role), $known, true) && !str_contains($db, $first)) {
                $out[] = [8, "Peran \"$role\" tidak ada di daftar peran atau tabel database dan tidak dinyatakan anonim."];
            }
        }
        foreach (SecurityTriggers::personalTables($c['prd']) as $table) {
            $ruled = false;
            foreach ($k['klasifikasi_data'] ?? [] as $row) {
                $ruled = $ruled || (self::mentions($row['lokasi'], $table) && $row['akses'] !== '');
            }
            foreach ($k['hak_akses'] as $row) {
                $ruled = $ruled || self::mentions($row['sumber_daya'], $table);
            }
            if (!$ruled) {
                $out[] = [8, "Tabel `$table` berisi data pribadi tetapi tidak punya aturan akses."];
            }
        }
        return $out;
    }

    public static function SecKeterlacakan(array $c): array
    {
        $names = array_map('mb_strtolower', array_column($c['features'], 'nama'));
        $out = [];
        foreach ($c['prd']['keamanan']['persyaratan'] ?? [] as $sec) {
            if (!array_intersect(array_map('mb_strtolower', $sec['fitur'] ?? []), $names)) {
                $out[] = [8, "{$sec['id']} belum dipetakan ke fitur yang ada di peta fitur."];
            }
        }
        return $out;
    }

    public static function SecRahasia(array $c): array
    {
        $out = [];
        foreach (QV::sentences($c['markdown']) as $s) {
            if (preg_match('/(kunci|key|secret|rahasia|token|kredensial)[^.\n]{0,80}\b(di|ke|dalam|pada)\s+(sisi\s+)?(klien|client|frontend|browser|repositori|repository|repo|github|kode sumber)/iu', $s)
                && !QV::negated($s)
                // Kunci publik (mis. anon/publishable key) memang dirancang untuk klien.
                && !preg_match('/\b(publik|public|anon|publishable)\b/iu', $s)) {
                $out[] = [7, 'Menyebut rahasia diletakkan di klien/repositori: "' . mb_substr($s, 0, 160) . '"'];
            }
        }
        $stack = json_encode($c['prd']['tech_stack'] ?? [], JSON_UNESCAPED_UNICODE);
        if (!preg_match('/variabel lingkungan|environment variable|env var|\.env|pengelola rahasia|secrets? manager|vault|secrets/iu', $stack)) {
            $out[] = [7, 'Tech Stack belum menyebut tempat penyimpanan rahasia (variabel lingkungan atau pengelola rahasia platform).'];
        }
        return $out;
    }

    public static function SecDataSensitifTerlindungi(array $c): array
    {
        $rows = $c['prd']['keamanan']['klasifikasi_data'] ?? [];
        $out = [];
        foreach ($rows as $row) {
            if ($row['sensitivitas'] === 'sensitif' && ($row['akses'] === '' || $row['retensi'] === '' || $row['boleh_masuk_log'])) {
                $out[] = [8, "Data sensitif \"{$row['data']}\" wajib punya aturan akses, retensi usulan, dan larangan masuk log."];
            }
        }
        foreach ($c['security'] as $t) {
            if (empty($t['data_sensitif'])) {
                continue;
            }
            $found = false;
            foreach ($rows as $row) {
                $found = $found || ($row['sensitivitas'] === 'sensitif' && preg_match($t['data_sensitif'], $row['data'] . ' ' . $row['lokasi']));
            }
            if (!$found) {
                $out[] = [8, "Pemicu \"{$t['label']}\": datanya belum diklasifikasikan sebagai sensitif."];
            }
        }
        return $out;
    }

    public static function SecOwasp(array $c): array
    {
        $o = $c['prd']['keamanan']['owasp'] ?? ['edisi' => '', 'kategori' => []];
        $out = ($o['edisi'] ?? '') === '' ? [[8, 'Nama edisi OWASP belum ditulis.']] : [];
        $have = [];
        foreach ($o['kategori'] ?? [] as $cat) {
            $have[$cat['kode']] = $cat;
        }
        foreach ($c['standard']['owasp']['kategori'] as $std) {
            $cat = $have[$std['kode']] ?? null;
            if (!$cat) {
                $out[] = [8, "Kategori OWASP {$std['kode']} {$std['nama']} belum dipetakan."];
            } elseif (!$cat['sec'] && $cat['alasan'] === '') {
                $out[] = [8, "Kategori OWASP {$std['kode']} tidak punya SEC atau alasan \"tidak relevan\"."];
            }
        }
        return $out;
    }

    public static function SecLogBersih(array $c): array
    {
        $out = [];
        foreach ($c['prd']['keamanan']['persyaratan'] ?? [] as $sec) {
            if (preg_match('/\b(catat|mencatat|dicatat|rekam|merekam|direkam|log)\b[^.]{0,60}(kata sandi|sandi|password|token|otp|nomor kartu|cvv|data pribadi mentah|\bnik\b)/iu', $sec['teks'])
                && !preg_match('/\b(tanpa|tidak|jangan|dilarang|bukan|kecuali|disamarkan|masking|hash|di-hash)\b/iu', $sec['teks'])) {
                $out[] = [8, "{$sec['id']} meminta pencatatan data rahasia/pribadi mentah ke log."];
            }
        }
        return $out;
    }

    private static function classified(array $k, string $table): bool
    {
        foreach ($k['klasifikasi_data'] ?? [] as $row) {
            if (self::mentions($row['lokasi'], $table)) {
                return true;
            }
        }
        return false;
    }

    private static function mentions(string $text, string $table): bool
    {
        return (bool) preg_match('/(^|[^a-z0-9_])' . preg_quote(mb_strtolower($table), '/') . '([^a-z0-9_]|$)/u', mb_strtolower($text));
    }
}
