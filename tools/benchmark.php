<?php
/**
 * Benchmark Standar Kualitas v1.2.
 *   php tools/benchmark.php           → buat ulang fixture mode tiruan dan cetak tabel hasil
 *   php tools/benchmark.php --tulis   → juga tulis docs/hasil-benchmark-v1.2.md
 * Fixture yang dinilai: benchmark/katering-lama.json (PRD v1.1, fixture negatif), benchmark/mock-*.json (dibuat ulang dari
 * benchmark/kasus.json), dan benchmark/ai-*.json bila ada (PRD hasil AI sungguhan, diekspor dengan tools/export_fixture.php).
 */
if (PHP_SAPI !== 'cli') {
    exit("Hanya untuk CLI.\n");
}
require __DIR__ . '/../src/helpers.php';

$dir = __DIR__ . '/../benchmark';
$labels = ['katering' => 'Full Supabase', 'kebersihan' => 'React Vite + Node.js', 'lab' => 'React + Cloudflare, mode UI-only'];
foreach (json_decode(file_get_contents("$dir/kasus.json"), true) as $name => $case) {
    $b = Brief::fromArray($case);
    $prd = Pipeline::mockPrd($b);
    $fixture = ['catatan' => "Mode tiruan: {$labels[$name]}", 'brief' => $b->toArray(), 'prd' => $prd, 'tasks' => Pipeline::mockTasks($b, $prd)];
    file_put_contents("$dir/mock-$name.json", json_encode($fixture, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");
}

$files = array_merge(["$dir/katering-lama.json"], glob("$dir/mock-*.json"), glob("$dir/ai-*.json"));
$rows = [];
foreach ($files as $file) {
    if (!is_file($file)) {
        continue;
    }
    $f = json_decode(file_get_contents($file), true);
    $r = QualityReport::evaluate(Brief::fromArray($f['brief']), $f['prd'], $f['tasks'] ?? []);
    $sev = array_count_values(array_column($r['temuan'], 'sev'));
    $byValidator = array_count_values(array_column($r['temuan'], 'id'));
    $rows[] = [basename($file), $f['catatan'] ?? '', $r, $sev, $byValidator];
}

$dims = array_keys(QualityReport::standard()['bobot']);
$short = ['konsistensi' => 'Kons', 'keterukuran' => 'Ukur', 'kejujuran' => 'Jujur', 'keamanan' => 'Aman', 'kecocokan_stack' => 'Stack',
    'keterlacakan' => 'Lacak', 'struktur' => 'Strk', 'keterbacaan' => 'Baca', 'kesiapan_bangun' => 'Siap'];
$md = ["| Fixture | Skor | Status | K/M/m | " . implode(' | ', array_map(fn ($d) => $short[$d], $dims)) . ' | Kekurangan 1–7 |',
    '|---|---|---|---|' . str_repeat('---|', count($dims)) . '---|'];
foreach ($rows as [$name, , $r, $sev]) {
    $check = implode('', array_map(fn ($k) => ['lulus' => '✅', 'gagal' => '❌', 'tidak berlaku' => '➖'][$k['status']], $r['checklist']));
    $md[] = "| $name | {$r['skor']} | " . ($r['lulus'] ? 'Lulus' : 'Belum lulus') . ' | ' . ($sev['K'] ?? 0) . '/' . ($sev['M'] ?? 0) . '/' . ($sev['m'] ?? 0) . ' | '
        . implode(' | ', array_map(fn ($d) => $r['skor_dimensi'][$d] ?? '–', $dims)) . " | $check |";
}
$table = implode("\n", $md);
echo $table, "\n";

if (in_array('--tulis', $argv, true)) {
    $detail = [];
    foreach ($rows as [$name, $note, $r, , $byValidator]) {
        ksort($byValidator);
        $detail[] = "### $name\n\n$note\n\n" . ($byValidator ? 'Temuan per validator: ' . implode(', ', array_map(fn ($k, $v) => "$k ($v)", array_keys($byValidator), $byValidator)) . '.' : 'Tidak ada temuan.');
    }
    $doc = "# Hasil Benchmark Standar Kualitas v1.2\n\nDibuat oleh `php tools/benchmark.php --tulis` pada " . date('Y-m-d H:i') . ".\n\n"
        . "Kolom dimensi: Kons = Konsistensi, Ukur = Keterukuran, Jujur = Kejujuran, Aman = Keamanan (Wajib MVP), Stack = Kecocokan Stack, Lacak = Keterlacakan, "
        . "Strk = Struktur, Baca = Keterbacaan (belum dinilai otomatis), Siap = Kesiapan Bangun (V12–V19). \"–\" = tidak berlaku atau belum dinilai.\n\n"
        . "Kekurangan 1–7: design system, peta rute, data dummy, skema kode, layanan & .env, beban keamanan, milestone & checkpoint.\n\n"
        . "$table\n\n## Rincian\n\n" . implode("\n\n", $detail) . "\n";
    file_put_contents(__DIR__ . '/../docs/hasil-benchmark-v1.2.md', $doc);
    echo "\nTersimpan: docs/hasil-benchmark-v1.2.md\n";
}
