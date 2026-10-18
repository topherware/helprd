<?php

/** V17 BebanKeamanan (bab 8): MVP ≤ 12 aturan, Hardening ≤ 8, tanpa ambang mikro/ritual berkala/sertifikasi di MVP, per fitur ≤ 3 rujukan. */
final class V17BebanKeamanan
{
    public const TERLARANG = '/\b\d+\s*(ms|milidetik)\b|<\s*\d+\s*ms|kuartal|triwulan|bulanan|setiap bulan|per bulan|tahunan|setiap tahun|disaster recovery|pemulihan bencana|(?-i:\bDR\b)|sertifikasi|ISO\s*27001|SOC\s*2|rotasi kunci (terjadwal|berkala)|uji penetrasi berkala/iu';

    public static function check(array $c): array
    {
        $k = $c['prd']['keamanan'] ?? [];
        $f = fn ($pesan, $saran = '') => QV::finding('V17', 'M', 8, $pesan, $saran);
        $sec = $k['persyaratan'] ?? [];
        $out = [];
        if (count($sec) > 12) {
            $out[] = $f('Aturan keamanan Wajib MVP ada ' . count($sec) . ' (maksimal 12).', 'Gabungkan aturan sejenis; pindahkan sisanya ke Hardening setelah MVP.');
        }
        if (count($k['hardening'] ?? []) > 8) {
            $out[] = $f('Butir Hardening ada ' . count($k['hardening']) . ' (maksimal 8).');
        }
        $bad = array_column(array_filter($sec, fn ($s) => preg_match(self::TERLARANG, $s['teks'])), 'id');
        if ($bad) {
            $out[] = $f('Aturan MVP memuat ambang mikro, ritual berkala, atau sertifikasi: ' . implode(', ', $bad) . '.', 'Pindahkan ke Hardening setelah MVP.');
        }
        $ids = array_column($sec, 'id');
        foreach ($c['features'] as $feature) {
            $refs = Documents::featureSecurity($feature, $c['prd']);
            if (count($refs) > 3 || array_diff(array_column($refs, 'id'), $ids)) {
                $out[] = $f("Bagian Keamanan fitur \"{$feature['nama']}\" harus ≤ 3 butir dan hanya merujuk SEC yang ada.");
            }
        }
        return $out;
    }
}
