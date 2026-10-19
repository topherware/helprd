<?php

/**
 * Kelengkapan (minor, spec v1.2.1 bagian 3): jalur kelola admin untuk entitas dari alur publik, pencatatan manual kanal luring,
 * contoh microcopy, benih data, dan tabel keterlacakan FR → rute, tabel, tugas. Bukan bagian status P1–P10.
 */
final class PKelengkapan
{
    public static function check(array $c): array
    {
        $p = $c['paket'];
        $prd = $p->prd();
        $flags = $c['flags'];
        $missing = [];
        if (!$flags['uiOnly'] && $flags['hasGuestFlow'] && !preg_match('#/admin/[\w-]+/\[#u', $prd)) {
            $missing[] = 'jalur kelola admin (daftar + detail) untuk data dari alur publik';
        }
        if (!$flags['uiOnly'] && $flags['hasOfflineChannel'] && !preg_match('/manual|walk-in|luring|offline|pencatatan|datang langsung/iu', $p->section('/rute|halaman|pages|routing/i'))) {
            $missing[] = 'pencatatan manual oleh pengelola untuk kanal luring';
        }
        if (!preg_match('/\bcta\b|tombol|judul utama|hero/iu', $p->section('/copywriting|microcopy|bahasa|data dummy/i'))) {
            $missing[] = 'contoh microcopy (judul utama, CTA, sukses, gagal/penuh, kosong)';
        }
        if (!$flags['uiOnly'] && !preg_match('/\bseed/iu', $prd)) {
            $missing[] = 'benih data (db/seed) dari dataset dummy';
        }
        if (!$p->single && !preg_match('/keterlacakan|traceab/iu', $prd)) {
            $missing[] = 'tabel keterlacakan FR → rute, tabel, tugas';
        }
        return $missing ? [QV::finding('PK', 'm', 13, 'Kelengkapan belum ada: ' . implode('; ', $missing) . '.', 'Lengkapi sesuai bagian 3 Standar v1.2.1.')] : [];
    }
}
