<?php
/**
 * Uji validator keamanan (v1.1 yang disederhanakan di v1.2: hanya tingkat Wajib MVP).
 * Jalankan: C:\xampp\php\php.exe tests\security_test.php
 * Tidak butuh database atau API: fixture dibangun dari mode tiruan.
 */
require __DIR__ . '/_lib.php';

[$b, $prd, $tasks] = fixture();
$c = QualityReport::context($b, $prd, $tasks);
$triggers = array_keys($c['security']);
foreach (['akun', 'admin', 'unggah', 'anak', 'kesehatan', 'dasar'] as $t) {
    check("pemicu '$t' terdeteksi", in_array($t, $triggers, true), 'terdeteksi: ' . implode(', ', $triggers));
}
$sec = $prd['keamanan']['persyaratan'];
check('Wajib MVP maksimal 12 aturan', count($sec) <= 12, (string) count($sec));
check('Hardening maksimal 8 butir', count($prd['keamanan']['hardening']) <= 8);
check('semua kontrol MVP tercakup SEC', !array_diff(array_keys($c['controls']['mvp']), array_merge(...array_column($sec, 'kontrol'))));
check('PRD memuat bab 8 a-f (MVP + Hardening)', (bool) preg_match('/## 8\. Keamanan & Privasi.*8a\..*8b\..*8c\. Persyaratan Keamanan — Wajib MVP.*8d\. Hardening.*8e\..*8f\./s', $c['markdown']));
$perFeature = array_map(fn ($f) => count(Documents::featureSecurity($f, $prd)), $b->features);
check('Keamanan per fitur maksimal 3 butir', max($perFeature) <= 3 && min($perFeature) >= 1, json_encode($perFeature));

$add = fn (string $group, string $text) => function (&$p) use ($group, $text) {
    $p['keamanan']['persyaratan'][] = ['id' => 'SEC-99', 'kelompok' => $group, 'teks' => $text, 'kontrol' => [], 'fitur' => ['Menu Minggu Ini']];
};
$cases = [
    'SecBagianLengkap' => fn (&$p) => $p['keamanan']['risiko_tersisa'] = [],
    'SecPemicuTerpenuhi' => fn (&$p) => $p['keamanan']['persyaratan'] = array_values(array_filter($p['keamanan']['persyaratan'], fn ($s) => !preg_grep('/^anak\./', $s['kontrol']))),
    'SecMatriksAkses' => fn (&$p) => $p['keamanan']['hak_akses'][] = ['peran' => 'kurir', 'sumber_daya' => 'transaksi', 'aksi' => ['baca']],
    'SecKeterlacakan' => fn (&$p) => $p['keamanan']['persyaratan'][0]['fitur'] = ['Fitur Fiktif'],
    'SecRahasia' => $add('Rahasia & Konfigurasi', 'Simpan API key di frontend supaya cepat.'),
    'SecDataSensitifTerlindungi' => function (&$p) {
        foreach ($p['keamanan']['klasifikasi_data'] as &$row) {
            if ($row['data'] === 'Data kesehatan/alergi') {
                $row['boleh_masuk_log'] = true;
            }
        }
    },
    'SecOwasp' => fn (&$p) => array_pop($p['keamanan']['owasp']['kategori']),
    'SecLogBersih' => $add('Logging & Audit', 'Catat kata sandi pengguna saat login gagal untuk analisis.'),
    'Kejujuran' => fn (&$p) => $p['overview']['sukses'] = 'Sistem ini 100% aman dan anti-hack.',
    'KecocokanStack' => $add('Otorisasi', 'Aktifkan Row Level Security untuk tabel transaksi.'),
];
foreach ($cases as $rule => $mutate) {
    $override = $rule === 'KecocokanStack' ? ['stack_choice' => 'react_node'] : [];
    $ids = failing($mutate, 'katering', $override);
    check("fixture gagal terdeteksi oleh $rule", in_array($rule, $ids, true), 'gagal: ' . implode(', ', $ids));
}

$ids = failing(fn (&$p) => $p['keamanan']['risiko_tersisa'][] = 'Dokumen ini tidak mengklaim sistem aman 100%.');
check('penyangkalan "tidak mengklaim aman 100%" tidak dianggap klaim', !in_array('Kejujuran', $ids, true), implode(', ', $ids));
$ids = failing(fn (&$p) => $p['dummy']['copywriting']['nada'] = "Hangat dan ringkas, tidak memakai klaim berlebihan seperti 'aman 100%'.");
check('panduan nada "tidak memakai klaim berlebihan seperti aman 100%" tidak dianggap klaim', !in_array('Kejujuran', $ids, true), implode(', ', $ids));
$ids = failing($add('Otorisasi', 'Akses per baris memakai Row Level Security.'));
check('klaim RLS pada stack Supabase → lolos KecocokanStack', !in_array('KecocokanStack', $ids, true), implode(', ', $ids));
$ids = failing(fn (&$p) => $p['tech_stack'] = array_values(array_filter($p['tech_stack'], fn ($r) => $r['lapisan'] !== 'Rahasia & Konfigurasi')));
check('stack tanpa tempat penyimpanan rahasia → gagal SecRahasia', in_array('SecRahasia', $ids, true), implode(', ', $ids));
$ids = failing($add('Rahasia & Konfigurasi', 'Hanya kunci publik (anon key Supabase) yang boleh dipakai di frontend.'));
check('kunci publik/anon di frontend → lolos SecRahasia', !in_array('SecRahasia', $ids, true), implode(', ', $ids));
$ids = failing($add('Backup & Pemulihan', 'Uji pemulihan bencana per kuartal.'));
check('ritual berkala di MVP → gagal V17', in_array('V17', $ids, true), implode(', ', $ids));

finish();
