<?php

/**
 * V16 LayananPihakKetiga (bab 12): tiap kebutuhan layanan punya vendor atau keputusan mock, vendor sesuai stacks.json,
 * rate-limit serverless tidak memakai memori proses, semua variabel ada di .env.example, tanpa rahasia asli.
 */
final class V16LayananPihakKetiga
{
    public const SECRET = [
        '/\bsk-(?:live|test|proj|ant)?[-_]?[A-Za-z0-9]{20,}/',
        '/\bAKIA[0-9A-Z]{16}\b/',
        '/\bAIza[0-9A-Za-z_\-]{35}\b/',
        '/\bghp_[A-Za-z0-9]{36}\b/',
        '/\bxox[abp]-[A-Za-z0-9-]{10,}/',
        '/\beyJ[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}/',
    ];
    private const NOT_ENV = ['CURRENT_TIMESTAMP', 'NOT_NULL', 'PRIMARY_KEY', 'ON_DELETE', 'AUTO_INCREMENT'];

    public static function check(array $c): array
    {
        $l = $c['prd']['layanan'] ?? [];
        $k = fn ($pesan, $saran = '') => QV::finding('V16', 'K', 12, $pesan, $saran);
        $m = fn ($pesan, $saran = '') => QV::finding('V16', 'M', 12, $pesan, $saran);
        if (empty($l['env_example']) && empty($l['layanan'])) {
            return [$k('Bab 12 Layanan Pihak Ketiga & Variabel Lingkungan belum ada.', 'Tulis tabel layanan (vendor atau mock) dan blok .env.example.')];
        }
        $out = [];
        $rows = [];
        foreach ($l['layanan'] ?? [] as $row) {
            $rows[QV::lower($row['kebutuhan'])] = $row;
        }
        foreach (Triggers::services($c['flags']) as $code) {
            $row = $rows[$code] ?? null;
            $default = $c['stack']['vendor'][$code]['nama'] ?? '';
            if (!$row || ($row['vendor'] === '' && !$row['mock'])) {
                $out[] = $k("Kebutuhan layanan \"$code\" belum punya vendor konkret atau keputusan adapter tiruan.", "Vendor default stack: $default.");
            } elseif (!$row['mock'] && $default !== '' && !str_contains(QV::lower($row['vendor']), QV::lower($default))) {
                $out[] = $m("Vendor $code \"{$row['vendor']}\" berbeda dengan default stack ($default).", 'Pakai vendor default dari stacks.json atau tandai sebagai adapter tiruan.');
            }
        }
        if ($c['flags']['serverless'] && $c['flags']['usesRateLimit']) {
            $text = $l['rate_limit'] . ' ' . (($rows['rate_limit'] ?? null) ? $rows['rate_limit']['vendor'] . ' ' . $rows['rate_limit']['mode_mvp'] : '');
            // Memori proses boleh disebut untuk adapter tiruan MVP selama penyimpanan terpusat untuk produksi juga ditulis.
            $central = preg_match('/terpusat|redis|upstash|\bkv\b|durable object|basis data|database|postgres/iu', $text);
            $memory = array_filter(QV::sentences($text), fn ($s) => preg_match('/memori|memory|in-?memory/iu', $s) && !QV::negated($s));
            if (($memory && !$central) || trim($text) === '') {
                $out[] = $m('Pembatasan laju di lingkungan serverless belum memakai penyimpanan terpusat (memori proses tidak bertahan antar-instans).');
            }
        }

        $defined = [];
        foreach ($l['env_example'] ?? [] as $g) {
            foreach ($g['variabel'] as $v) {
                $defined[$v['nama']] = $v['nilai'];
            }
        }
        $mentioned = array_merge(...array_map(fn ($r) => $r['env'], $l['layanan'] ?? []), ...[[]]);
        $scan = json_encode([$c['prd']['tech_stack'] ?? [], $c['prd']['keputusan_teknologi'] ?? [], $l['layanan'] ?? [], $l['rate_limit'] ?? '', $c['prd']['milestone'] ?? []], JSON_UNESCAPED_UNICODE);
        // Bukan ID dataset (DUMMY-PROFIL_TOKO) atau bagian nama lain bertanda hubung.
        preg_match_all('/(?<![A-Za-z0-9_-])[A-Z][A-Z0-9]*_[A-Z0-9_]+\b/', $scan, $tok);
        $mentioned = array_diff(array_unique(array_merge($mentioned, $tok[0])), self::NOT_ENV);
        $missing = array_diff($mentioned, array_keys($defined));
        if ($missing) {
            $out[] = $k('Variabel lingkungan disebut di PRD tetapi tidak ada di .env.example: ' . implode(', ', $missing) . '.');
        }

        $secrets = [];
        foreach (self::SECRET as $re) {
            if (preg_match($re, $c['markdown'] . "\n" . implode("\n", $defined), $hit)) {
                $secrets[] = mb_substr($hit[0], 0, 8) . '…';
            }
        }
        foreach ($defined as $name => $value) {
            if (!str_starts_with($value, 'isi_dengan_') && strlen($value) >= 24 && preg_match('/^[A-Za-z0-9+\/=_\-]+$/', $value) && preg_match('/\d/', $value) && preg_match('/[A-Za-z]/', $value)) {
                $secrets[] = $name;
            }
        }
        if ($secrets) {
            $out[] = $k('Ada nilai yang tampak seperti rahasia asli: ' . implode(', ', array_unique($secrets)) . '.', 'Ganti dengan penampung "isi_dengan_...".');
        }
        return $out;
    }
}
