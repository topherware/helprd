<?php

/**
 * P7 Konfigurasi (mayor): angka yang disebut pengguna (Triggers::angka) menjadi pengaturan dengan nilai bawaan
 * (kolom default, baris tabel pengaturan, atau variabel .env); template pesan disimpan sebagai pengaturan, bukan hanya di kode.
 */
final class P07Konfigurasi
{
    public static function berlaku(array $c): bool
    {
        return !$c['flags']['uiOnly'] && (Triggers::angka($c['brief']) || self::needsTemplate($c['flags']));
    }

    private static function needsTemplate(array $flags): bool
    {
        return $flags['hasScheduler'] || $flags['usesWhatsappApi'] || $flags['usesEmail'];
    }

    public static function check(array $c): array
    {
        $p = $c['paket'];
        // Bagian pengaturan/konfigurasi, tanpa dataset dummy (contoh isi bukan nilai bawaan).
        $settings = $p->section('/^(?!.*dummy).*(pengaturan|konfigurasi|setting)/i');
        $scope = $p->schemaCode() . "\n" . $p->env() . "\n" . $settings;
        $rows = Paket::tableRows($settings);
        $out = [];
        $missing = [];
        foreach (Triggers::angka($c['brief']) as $a) {
            $n = preg_quote($a['nilai'], '/');
            $inRow = (bool) array_filter($rows, fn ($r) => count(array_filter($r, fn ($cell) => preg_match("/^`?$n`?(\s*\p{L}+)?$/u", $cell))) > 0);
            if (!$inRow && !preg_match("/default\s*\(\s*['\"]?$n(?:\.0+)?['\"]?\s*\)|\bdefault\s+'?$n'?\b|^\s*[A-Z][A-Z0-9_]*\s*=\s*$n\s*$/imu", $scope)) {
                $missing[] = "{$a['nilai']} {$a['satuan']}";
            }
        }
        if ($missing) {
            $out[] = QV::finding('P7', 'M', 6, 'Angka dari masukan belum menjadi pengaturan dengan nilai bawaan: ' . implode(', ', $missing) . '.',
                'Buat tabel/baris pengaturan (kolom + nilai bawaan + siapa yang boleh mengubah) atau variabel lingkungan.');
        }
        if (self::needsTemplate($c['flags']) && !preg_match('/template|tpl_/iu', $p->schemaCode() . "\n" . $settings)) {
            $out[] = QV::finding('P7', 'M', 6, 'Template pesan belum disimpan sebagai pengaturan (hanya di kode/teks).', 'Simpan template pesan di tabel pengaturan agar bisa diubah pengelola.');
        }
        return $out;
    }
}
