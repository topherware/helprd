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

// --- G4: validator P1–P10 pada tiga PRD acuan (spec v1.2.1 bagian 5.1–5.3) ---
$ref = __DIR__ . '/../ref-prd/';
$status = fn (Paket $p, ?Brief $b = null) => array_column(Paritas::evaluate($b ?? $salon, $p)['aturan'], 'status', 'id');
$edu = $status(Paket::fromPath($ref . 'eduwebmu-salonbook.md'));
foreach (['P1', 'P3', 'P5', 'P7', 'P10'] as $id) {
    check("Eduwebmu: $id lulus", $edu[$id] === 'lulus', $edu[$id]);
}
check('Eduwebmu: P2 lulus sebagian (halaman pelengkap tercatat sebagai usulan)', $edu['P2'] === 'sebagian', $edu['P2']);
$bk = $status(Paket::fromPath($ref . 'planku-bookkapster'));
foreach (['P1', 'P2', 'P3', 'P8'] as $id) {
    check("BookKapster: $id gagal", $bk[$id] === 'gagal', $bk[$id]);
}
$bkP2 = Paritas::evaluate($salon, Paket::fromPath($ref . 'planku-bookkapster'))['aturan']['P2']['temuan'];
check('BookKapster P2 menyebut Dashboard Pemilik, Pencarian Jadwal, Riwayat Pelanggan, dan peran kapster (kritis)',
    str_contains($bkP2[0]['pesan'], 'Dashboard Pemilik') && str_contains($bkP2[0]['pesan'], 'Pencarian Jadwal') && str_contains($bkP2[0]['pesan'], 'Riwayat Pelanggan')
    && (bool) array_filter($bkP2, fn ($f) => $f['sev'] === 'K' && str_contains($f['pesan'], 'kapster')), json_encode($bkP2, JSON_UNESCAPED_UNICODE));
$ng = $status(Paket::fromPath($ref . 'ngodingpakeai'));
$ngFail = array_filter(['P4', 'P5', 'P6', 'P7', 'P10'], fn ($id) => $ng[$id] !== 'lulus');
check('ngodingpakeai: gagal di sebagian besar P4–P7, P10', count($ngFail) >= 4, json_encode($ng));

// --- G4: PRD mock dengan peta fitur v1.2.1 (fitur tanpa asal menjadi usulan nonaktif) lulus semua aturan ---
/** Brief salon seperti hasil peta fitur v1.2.1: fitur terlacak ke masukan = "ide", sisanya usulan AI yang belum disertakan. */
function salon121(Brief $b): Brief
{
    $a = $b->toArray();
    $input = $b->inputText(false);
    $a['features'] = [];
    $a['usulan'] = [];
    foreach ($b->features as $f) {
        if (Telusur::traced($f['nama'], $input)) {
            $a['features'][] = $f + ['asal' => 'ide'];
        } else {
            $a['usulan'][] = ['nama' => $f['nama'], 'alasan' => 'Tidak disebut pengguna; diusulkan sebagai pelengkap.'];
        }
    }
    return Brief::fromArray($a);
}
$s121 = salon121($salon);
check('simulasi peta fitur v1.2.1: 3 fitur salon tanpa asal menjadi usulan', array_column($s121->usulan, 'nama') === ['Dashboard Pemilik', 'Pencarian Jadwal', 'Riwayat Pelanggan'],
    json_encode(array_column($s121->usulan, 'nama'), JSON_UNESCAPED_UNICODE));
/** Status P1–P10 dan id temuan setelah PRD/task mock diubah oleh $mutate(&$prd, &$tasks). */
function parity(Brief $b, ?callable $mutate = null): array
{
    $prd = Pipeline::mockPrd($b);
    $tasks = Pipeline::mockTasks($b, $prd);
    if ($mutate) {
        $mutate($prd, $tasks);
    }
    return Paritas::evaluate($b, Paket::fromPrd($b, $prd, $tasks));
}
$base = parity($s121);
$notOk = array_filter($base['aturan'], fn ($a) => !in_array($a['status'], ['lulus', 'tidak berlaku'], true));
check('mock salon v1.2.1: semua P1–P10 lulus', !$notOk, json_encode(array_map(fn ($a) => $a['temuan'], $notOk), JSON_UNESCAPED_UNICODE));
check('mock salon v1.2.1: tanpa temuan Kelengkapan', !$base['kelengkapan'], json_encode($base['kelengkapan'], JSON_UNESCAPED_UNICODE));
foreach (array_keys(cases()) as $case) {
    foreach (['bebas', 'full_supabase', 'react_node', 'react_cloudflare'] as $st) {
        $r = parity(brief($case, ['stack_choice' => $st]));
        check("mock $case / $st: tanpa temuan paritas", !$r['temuan'], json_encode($r['temuan'], JSON_UNESCAPED_UNICODE));
    }
}

// --- G4: uji mutasi (spec 5.4) ---
$mutations = [
    'constraint rentang menjadi unique(sumber_daya, mulai) → P1' => ['P1', function (&$p) {
        $p['database']['migrasi_kustom'] = 'CREATE UNIQUE INDEX transaksi_slot_unik ON transaksi (item_id, mulai_pada);';
        foreach ($p['database']['invarian'] as &$inv) {
            $inv['mekanisme'] = 'unique(item_id, mulai_pada)';
        }
    }],
    'hapus uji konkurensi → P1' => ['P1', function (&$p, &$t) {
        $strip = fn ($s) => preg_replace(P01Invarian::KONKUREN, 'berurutan', $s);
        $p['milestone'] = json_decode($strip(json_encode($p['milestone'], JSON_UNESCAPED_UNICODE)), true);
        $t = json_decode($strip(json_encode($t, JSON_UNESCAPED_UNICODE)), true);
    }],
    'hapus berkas dari tugas → P4' => ['P4', fn (&$p) => $p['milestone']['rencana'][1]['berkas'] = []],
    'hapus kebijakan zona waktu → P5' => ['P5', fn (&$p) => $p['kedalaman']['zona_waktu'] = ''],
    'hapus nilai bawaan pengaturan → P7' => ['P7', function (&$p) {
        $p['database']['pengaturan'] = array_values(array_filter($p['database']['pengaturan'], fn ($r) => $r['kunci'] !== 'pengingat_jam'));
        $p['database']['kode_skema'] = preg_replace('/\.default\(3\)/', '', $p['database']['kode_skema']);
    }],
    'tambah butir hardening ke-9 → P8' => ['P8', function (&$p) {
        while (count($p['keamanan']['hardening']) < 9) {
            $p['keamanan']['hardening'][] = 'Butir hardening tambahan nomor ' . count($p['keamanan']['hardening']) . '.';
        }
    }],
    '9 butir tugas pada satu fitur → P8' => ['P8', function (&$p, &$t) {
        for ($i = 1; $i <= 9; $i++) {
            $t[] = ['fitur' => $t[0]['fitur'], 'title' => "Rapikan detail tampilan ke-$i", 'milestone' => 'M3', 'koordinasi' => 'M3.1', 'status' => 'belum_mulai'];
        }
    }],
    'ORM berbeda dari pilihan → P9' => ['P9', function (&$p) {
        foreach ($p['tech_stack'] as &$row) {
            if ($row['lapisan'] === 'Database') {
                $row['pilihan'] = 'PostgreSQL (Neon) + Prisma';
            }
        }
    }],
    'hapus gerbang izin pada Master Prompt → P10' => ['P10', fn (&$p) => $p['milestone']['master_prompt'] = preg_replace(P10MasterPrompt::GERBANG, '', $p['milestone']['master_prompt'])],
];
foreach ($mutations as $name => [$id, $mutate]) {
    $r = parity($s121, $mutate);
    check("mutasi: $name", $r['aturan'][$id]['status'] === 'gagal', json_encode($r['aturan'][$id], JSON_UNESCAPED_UNICODE));
}
$extra = Brief::fromArray(array_merge($s121->toArray(), ['features' => array_merge($s121->features, [['nama' => 'Program Loyalitas Poin', 'fase' => 3, 'sub_fitur' => ['Kumpulkan Poin']]])]));
$r = parity($extra);
check('mutasi: tambah fitur tanpa asal → P2', $r['aturan']['P2']['status'] === 'gagal' && str_contains($r['aturan']['P2']['temuan'][0]['pesan'], 'Program Loyalitas Poin'), json_encode($r['aturan']['P2'], JSON_UNESCAPED_UNICODE));
$r = parity($s121, function (&$p) {
    $p['requirements']['fungsional'][] = ['id' => 'FR-99', 'teks' => 'Pelanggan dapat melihat riwayat transaksi miliknya berdasarkan nomor HP.', 'fitur' => []];
});
check('mutasi: tambah riwayat hanya dengan nomor → P3', $r['aturan']['P3']['status'] === 'gagal', json_encode($r['aturan']['P3'], JSON_UNESCAPED_UNICODE));
$r = parity($s121, function (&$p) {
    $p['profil']['peran'][] = 'mitra';
});
check('mutasi: peran baru tanpa asal → P2 kritis', (bool) array_filter($r['aturan']['P2']['temuan'], fn ($f) => $f['sev'] === 'K'), json_encode($r['aturan']['P2'], JSON_UNESCAPED_UNICODE));

// --- G4: uji bendera (spec 5.5) ---
$office = Brief::fromArray(['judul' => 'Inventaris', 'ide' => 'Aplikasi internal pencatatan inventaris kantor. Semua staf wajib login; admin mengelola daftar barang dan kategori.',
    'features' => [['nama' => 'Daftar Barang', 'fase' => 1, 'sub_fitur' => ['Tambah Barang'], 'asal' => 'ide'], ['nama' => 'Kategori Barang', 'fase' => 1, 'sub_fitur' => ['Atur Kategori'], 'asal' => 'ide']]]);
$r = parity($office, fn (&$p) => $p['kedalaman']['zona_waktu'] = '');
check('tanpa data waktu: zona waktu tidak dituntut (P5)', $r['aturan']['P5']['status'] === 'lulus', json_encode($r['aturan']['P5'], JSON_UNESCAPED_UNICODE));
check('tanpa invarian rentang: P1 tidak berlaku', $r['aturan']['P1']['status'] === 'tidak berlaku', $r['aturan']['P1']['status']);
check('tanpa alur tamu: P3 tidak berlaku', $r['aturan']['P3']['status'] === 'tidak berlaku', $r['aturan']['P3']['status']);
check('tanpa halaman publik: P6 tidak berlaku', $r['aturan']['P6']['status'] === 'tidak berlaku', $r['aturan']['P6']['status']);
$r = parity(Brief::fromArray(array_merge($s121->toArray(), ['ui_only' => true])));
foreach (['P1', 'P3', 'P7', 'P9'] as $id) {
    check("mode UI-only: $id tidak berlaku", $r['aturan'][$id]['status'] === 'tidak berlaku', $r['aturan'][$id]['status']);
}
check('mode UI-only: P5 hanya versi (lulus)', $r['aturan']['P5']['status'] === 'lulus', json_encode($r['aturan']['P5'], JSON_UNESCAPED_UNICODE));

// --- G5: skor, laporan, dan Pass 5 ---
$std = QualityReport::standard();
check('bobot: Kesiapan Bangun 15 + Paritas 10', $std['bobot']['kesiapan_bangun'] === 15 && $std['bobot']['paritas'] === 10 && $std['versi'] === '1.2.1');
[$kb, $kp, $kt] = fixture('katering');
$report = QualityReport::evaluate($kb, $kp, $kt);
check('laporan mock katering lulus dengan dimensi paritas 100', $report['lulus'] && $report['skor_dimensi']['paritas'] === 100, json_encode($report['skor_dimensi']));
check('laporan memuat status P1–P10', count($report['paritas']) === 10 && $report['paritas'][0]['id'] === 'P1');
$md = QualityReport::render($report);
check('QUALITY.md memuat tabel Paritas P1–P10', str_contains($md, '## Paritas v1.2.1 (P1–P10)') && str_contains($md, '| P10 |'));
$over = $kt;
for ($i = 1; $i <= 9; $i++) {
    $over[] = ['fitur' => $kt[0]['fitur'], 'title' => "Rapikan detail tampilan ke-$i", 'milestone' => 'M3', 'koordinasi' => 'M3.1', 'status' => 'belum_mulai'];
}
$bad = QualityReport::evaluate($kb, $kp, $over);
check('9+ task pada satu fitur: laporan tidak lulus (P8 kritis)', !$bad['lulus'] && in_array('P8', array_column($bad['temuan'], 'id'), true));
check('Pass 5: temuan batas task diarahkan ke bagian "task"', isset(QualityReport::partsToRepair($bad)['task']), json_encode(array_keys(QualityReport::partsToRepair($bad))));
$rows = array_map(fn ($i) => ['id' => $i, 'title' => "Kerjakan bagian $i", 'milestone' => $i <= 6 ? 'M1' : 'M2', 'koordinasi' => $i <= 3 ? 'M1.1' : ($i <= 6 ? 'M1.2' : 'M2.1')], range(1, 12));
$plan = Pipeline::mergeTasks($rows);
$left = 12 - count($plan['hapus']);
check('Pass 5 gabung task: 12 → ≤ 8, judul ≤ 200, tidak lintas milestone', $left <= 8 && !array_filter($plan['judul'], fn ($t) => mb_strlen($t) > 200)
    && !array_filter($plan['judul'], fn ($t) => str_contains($t, 'bagian 6; kerjakan bagian 7')), json_encode($plan, JSON_UNESCAPED_UNICODE));

finish();
