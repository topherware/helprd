<?php
/**
 * Uji Standar Kualitas v1.2.1 (paritas P1–P10). Mode mock, tanpa database dan API.
 * Jalankan: C:\xampp\php\php.exe tests\paritas_test.php
 * Kata khusus domain (salon, kapster, booking) hanya ada di fixture tes ini, tidak di kode validator/prompt.
 */
require __DIR__ . '/_lib.php';

$salon = Brief::fromArray(json_decode(file_get_contents(__DIR__ . '/fixtures/paritas/salon.brief.json'), true)['brief']);
$stack = Brief::stack('standar_planku');

// --- G2: pemicu dan invarian ---
$inv = Triggers::invariants($salon);
check('invarian rentang terdeteksi dari catatan salon', in_array('rentang', array_column($inv, 'jenis'), true), json_encode($inv, JSON_UNESCAPED_UNICODE));
$flags = Triggers::flags($salon, [], $stack, ['pengunjung anonim', 'pemilik']);
foreach (['hasTimeRange', 'hasTimeFields', 'hasScheduler', 'hasPublicPages', 'hasOfflineChannel', 'hasGuestFlow'] as $flag) {
    check("bendera $flag aktif untuk kasus salon", $flags[$flag]);
}
$plain = Brief::fromArray(['judul' => 'Profil Sekolah', 'ide' => 'Website profil sekolah berisi visi misi dan berita kegiatan untuk orang tua.', 'features' => [
    ['nama' => 'Berita', 'fase' => 1, 'sub_fitur' => ['Daftar Berita']]]]);
$plainFlags = Triggers::flags($plain, [], $stack, ['pengunjung anonim']);
check('tanpa data waktu: hasTimeFields & hasTimeRange tidak aktif', !$plainFlags['hasTimeFields'] && !$plainFlags['hasTimeRange'], json_encode($plainFlags));
check('tanpa alur tamu: hasGuestFlow tidak aktif', !$plainFlags['hasGuestFlow']);
$shop = Brief::fromArray(['judul' => 'Toko', 'ide' => 'Toko online kecil: stok barang tidak boleh minus saat dua pembeli checkout bersamaan.', 'features' => []]);
check('invarian stok terdeteksi', in_array('stok', array_column(Triggers::invariants($shop), 'jenis'), true));
$ui = Brief::fromArray(array_merge($salon->toArray(), ['ui_only' => true]));
$uiFlags = Triggers::flags($ui, [], $stack, ['pengunjung anonim']);
check('mode UI-only mematikan hasTimeRange/hasScheduler', !$uiFlags['hasTimeRange'] && !$uiFlags['hasScheduler']);

// --- G2: asal fitur pada peta fitur ---
$features = Validator::aiFeatures(['judul' => 'X', 'fitur' => [
    ['nama' => 'A', 'fase' => 1, 'asal' => 'ide', 'sub_fitur' => ['a']],
    ['nama' => 'B', 'fase' => 1, 'asal' => 'usulan_ai', 'alasan' => 'b', 'sub_fitur' => ['b']],
    ['nama' => 'C', 'fase' => 2, 'asal' => 'usulan_ai', 'sub_fitur' => ['c']],
    ['nama' => 'D', 'fase' => 2, 'asal' => 'usulan_ai', 'sub_fitur' => ['d']],
    ['nama' => 'E', 'fase' => 3, 'asal' => 'usulan_ai', 'sub_fitur' => ['e']],
    ['nama' => 'F', 'fase' => 3, 'asal' => 'aneh', 'sub_fitur' => ['f']],
]])['fitur'];
check('usulan_ai dibatasi maksimal 3', count(array_filter($features, fn ($f) => $f['asal'] === 'usulan_ai')) === 3);
check('asal tidak sah menjadi "ide"', end($features)['asal'] === 'ide');
$profil = Validator::aiProfil(['profil' => ['peran' => [['nama' => 'Admin', 'asal' => 'ide'], 'staf'], 'invarian' => [['jenis' => 'rentang', 'aturan' => 'x tidak boleh bentrok']]]]);
check('profil: peran tetap string + asal peran terpisah', $profil['peran'] === ['pengunjung anonim', 'admin', 'staf'] && $profil['peran_asal']['admin'] === 'ide');
check('profil: invarian dideklarasikan', $profil['invarian'][0]['jenis'] === 'rentang');

finish();
