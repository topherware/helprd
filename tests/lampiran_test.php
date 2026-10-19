<?php
/**
 * Uji lampiran referensi: blok konten untuk AI (gambar/PDF/teks), ringkasan masuk ke prompt pertanyaan, peta fitur, dan PRD,
 * serta ringkasan tiruan (mock). Tidak memanggil AI dan tidak menulis ke database.
 * Jalankan: C:\xampp\php\php.exe tests\lampiran_test.php
 */
require __DIR__ . '/_lib.php';

$dir = UPLOAD_DIR . '/_tes';
@mkdir($dir, 0775, true);
file_put_contents("$dir/a.png", base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
file_put_contents("$dir/b.txt", "Minimal pesan 20 box.\nDP 50%.");
file_put_contents("$dir/c.pdf", "%PDF-1.4\n%%EOF");
$files = [
    ['nama_asli' => 'sketsa.png', 'mime' => 'image/png', 'path' => '_tes/a.png'],
    ['nama_asli' => 'aturan.txt', 'mime' => 'text/plain', 'path' => '_tes/b.txt'],
    ['nama_asli' => 'brosur.pdf', 'mime' => 'application/pdf', 'path' => '_tes/c.pdf'],
];
$project = ['id' => 0, 'judul' => 'X', 'ide_awal' => 'Website pemesanan nasi box untuk kantor', 'referensi' => null];

$blocks = Prompts::referensi($project, $files);
check('satu blok per berkas + satu blok instruksi', count($blocks) === 4);
check('gambar dikirim sebagai blok image base64', $blocks[0]['type'] === 'image' && $blocks[0]['source']['media_type'] === 'image/png' && base64_decode($blocks[0]['source']['data']) === file_get_contents("$dir/a.png"));
check('teks dikirim sebagai document bersumber text', $blocks[1]['type'] === 'document' && $blocks[1]['source']['type'] === 'text' && str_contains($blocks[1]['source']['data'], 'Minimal pesan 20 box'));
check('PDF dikirim sebagai document base64', $blocks[2]['type'] === 'document' && $blocks[2]['source']['media_type'] === 'application/pdf');
check('instruksi menyebut semua nama berkas dan meminta JSON ringkasan', $blocks[3]['type'] === 'text'
    && str_contains($blocks[3]['text'], 'sketsa.png') && str_contains($blocks[3]['text'], 'brosur.pdf') && str_contains($blocks[3]['text'], '"ringkasan"'));
check('blok konten bisa di-encode ke JSON (body API)', json_encode(['messages' => [['role' => 'user', 'content' => $blocks]]]) !== false);

$mock = MockData::referensi($files);
check('ringkasan tiruan memuat isi berkas teks', str_contains($mock['ringkasan'], 'Minimal pesan 20 box') && str_contains($mock['ringkasan'], 'sketsa.png'));

// Tanpa lampiran: prompt tidak berubah (tidak ada blok <referensi>).
check('tanpa referensi: prompt pertanyaan tanpa <referensi>', !str_contains(Prompts::questions($project), '<referensi>'));
$project['referensi'] = "- aturan.txt: minimal pesan 20 box, DP 50%";
check('dengan referensi: prompt pertanyaan memuat <referensi>', str_contains(Prompts::questions($project), "<referensi>\n- aturan.txt: minimal pesan 20 box"));
$b = Brief::fromArray(['judul' => 'X', 'ide' => $project['ide_awal']]);
$b->referensi = $project['referensi'];
$ctx = (new ReflectionMethod(Prompts::class, 'baseContext'));
$ctx->setAccessible(true);
check('konteks PRD memuat <referensi>', str_contains($ctx->invoke(null, $b), '<referensi>'));
check('audit kualitas memuat <referensi>', str_contains(Prompts::auditKualitas($b, []), '<referensi>'));
check('prompt sistem menandai <referensi> sebagai data pengguna', str_contains(Prompts::SYSTEM, '<referensi>'));
check('celah dihitung dari ide + referensi', Prompts::ideWithReference($project) === $project['ide_awal'] . "\n" . $project['referensi']);

array_map('unlink', glob("$dir/*"));
@rmdir($dir);
finish();
