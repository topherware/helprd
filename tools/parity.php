<?php
/**
 * Jalankan aturan paritas v1.2.1 (P1–P10) pada folder atau berkas PRD (tanpa AI, tanpa database).
 *   php tools/parity.php ref-prd/eduwebmu-salonbook.md --brief tests/fixtures/paritas/salon.brief.json
 *   php tools/parity.php ref-prd/planku-bookkapster --brief tests/fixtures/paritas/salon.brief.json --json
 *   php tools/parity.php benchmark/mock-katering.json        → fixture PlanKu {brief, prd, tasks}
 * --brief: berkas JSON berisi {"brief": {...Brief::toArray}} atau Brief langsung. Tanpa --brief, isi bab 1 PRD dipakai sebagai ide.
 */
if (PHP_SAPI !== 'cli') {
    exit("Hanya untuk CLI.\n");
}
require __DIR__ . '/../src/helpers.php';

$target = $argv[1] ?? '';
$opt = fn (string $name) => ($i = array_search($name, $argv, true)) !== false ? ($argv[$i + 1] ?? '') : null;
if ($target === '' || !file_exists($target)) {
    fwrite(STDERR, "Pemakaian: php tools/parity.php <folder|berkas.md|fixture.json> [--brief brief.json] [--json]\n");
    exit(1);
}

$fixture = str_ends_with($target, '.json') ? json_decode(file_get_contents($target), true) : null;
if ($fixture && isset($fixture['prd'])) {
    $brief = Brief::fromArray($fixture['brief']);
    $paket = Paket::fromPrd($brief, QualityReport::complete($fixture['prd'], count($brief->features)), $fixture['tasks'] ?? []);
} else {
    $paket = Paket::fromPath($target);
    if (($file = $opt('--brief')) !== null) {
        $data = json_decode((string) @file_get_contents($file), true);
        if (!$data) {
            fwrite(STDERR, "Brief tidak terbaca: $file\n");
            exit(1);
        }
        $brief = Brief::fromArray($data['brief'] ?? $data);
    } else {
        $overview = $paket->section('/overview|ringkasan|tujuan aplikasi/i');
        $brief = Brief::fromArray(['judul' => basename($target), 'ide' => $overview !== '' ? $overview : mb_substr($paket->prd(), 0, 4000)]);
        fwrite(STDERR, "Catatan: tanpa --brief, asal fitur (P2) dan angka (P7) dinilai dari bab 1 PRD itu sendiri.\n");
    }
}

$result = Paritas::evaluate($brief, $paket);
if (in_array('--json', $argv, true)) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";
    exit(0);
}
$icon = ['lulus' => 'LULUS   ', 'sebagian' => 'SEBAGIAN', 'gagal' => 'GAGAL   ', 'tidak berlaku' => 'N/A     '];
printf("%s — format %s\n", $target, $paket->single ? 'satu dokumen (aturan berkas per fitur tidak berlaku)' : 'paket berkas');
foreach ($result['aturan'] as $rule) {
    printf("  %-4s %s %s\n", $rule['id'], $icon[$rule['status']], $rule['judul']);
    foreach ($rule['temuan'] as $f) {
        printf("         %s bab %-3s %s\n", $f['sev'], $f['bab'] ?? '-', $f['pesan']);
    }
}
foreach ($result['kelengkapan'] as $f) {
    printf("  PK   %s %s\n", $f['sev'], $f['pesan']);
}
$count = array_count_values(array_column($result['aturan'], 'status'));
printf("Ringkas: %d lulus, %d sebagian, %d gagal, %d tidak berlaku.\n", $count['lulus'] ?? 0, $count['sebagian'] ?? 0, $count['gagal'] ?? 0, $count['tidak berlaku'] ?? 0);
