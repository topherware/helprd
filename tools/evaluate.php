<?php
/**
 * Nilai satu fixture PRD dengan Standar Kualitas v1.2 (tanpa AI).
 *   php tools/evaluate.php benchmark/katering-lama.json            → ringkasan + temuan
 *   php tools/evaluate.php benchmark/katering-lama.json --quality  → QUALITY.md lengkap
 *   php tools/evaluate.php benchmark/katering-lama.json --json     → laporan JSON
 * Fixture: {"brief": {...Brief::toArray}, "prd": {...}, "tasks": [{fitur, title, milestone, status}]}
 */
if (PHP_SAPI !== 'cli') {
    exit("Hanya untuk CLI.\n");
}
require __DIR__ . '/../src/helpers.php';

$file = $argv[1] ?? '';
$fixture = is_file($file) ? json_decode(file_get_contents($file), true) : null;
if (!$fixture) {
    fwrite(STDERR, "Pemakaian: php tools/evaluate.php <fixture.json> [--quality|--json]\n");
    exit(1);
}
$report = QualityReport::evaluate(Brief::fromArray($fixture['brief']), $fixture['prd'], $fixture['tasks'] ?? []);

if (in_array('--json', $argv, true)) {
    echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), "\n";
} elseif (in_array('--quality', $argv, true)) {
    echo QualityReport::render($report);
} else {
    printf("%s — skor %d/100, %s\n", basename($file), $report['skor'], $report['lulus'] ? 'LULUS' : 'BELUM LULUS');
    foreach ($report['skor_dimensi'] as $dim => $score) {
        printf("  %-16s %s\n", $dim, $score === null ? '-' : $score);
    }
    foreach ($report['checklist'] as $k) {
        printf("  [%s] %d. %s (%s)\n", ['lulus' => 'x', 'gagal' => ' ', 'tidak berlaku' => '-'][$k['status']], $k['no'], $k['judul'], $k['validator']);
    }
    foreach ($report['temuan'] as $t) {
        printf("  %s %-26s bab %-3s %s\n", $t['sev'], $t['id'], $t['bab'] ?? '-', $t['pesan']);
    }
}
