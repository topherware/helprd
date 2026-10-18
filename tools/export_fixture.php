<?php
/**
 * Ekspor project dari database menjadi fixture benchmark: Brief, PRD JSON terakhir, dan task.
 *   php tools/export_fixture.php 9 benchmark/katering-lama.json
 * Data yang diekspor adalah rencana produk (ide, jawaban, PRD), bukan data pribadi pengguna.
 */
if (PHP_SAPI !== 'cli') {
    exit("Hanya untuk CLI.\n");
}
require __DIR__ . '/../src/helpers.php';

[$id, $out] = [(int) ($argv[1] ?? 0), $argv[2] ?? ''];
$project = Database::run('SELECT * FROM projects WHERE id = ?', [$id])->fetch();
$prd = $project ? Documents::latestPrd($id) : null;
if (!$project || !$prd || $out === '') {
    fwrite(STDERR, "Pemakaian: php tools/export_fixture.php <project_id> <keluaran.json> (project harus punya PRD)\n");
    exit(1);
}
$fixture = [
    'catatan' => "Diekspor dari project #$id pada " . date('Y-m-d H:i'),
    'brief' => Brief::fromProject($project)->toArray(),
    'prd' => $prd[0],
    'tasks' => Pipeline::projectTasks($id),
];
file_put_contents($out, json_encode($fixture, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");
echo "Tersimpan: $out (" . count($fixture['tasks']) . " task)\n";
