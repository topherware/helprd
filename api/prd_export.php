<?php
/**
 * Unduhan Implemen (POST + CSRF; browser menyimpan hasilnya sebagai Blob).
 * Body: {"format": "md"} → PRD.md versi terbaru, {"format": "zip"} → PRD.md + TASKS.md + features/*.md (+ QUALITY.md).
 */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

[$project, $body] = api_request();
$id = (int) $project['id'];

$prd = Database::run(
    'SELECT content_md, source FROM prd_versions WHERE project_id = ? AND selesai = 1 ORDER BY id DESC LIMIT 1',
    [$id]
)->fetch();
if (!$prd) {
    throw new UserError('PRD belum selesai disusun.', 409);
}
$name = Documents::slug($project['judul']) ?: 'project';

if (($body['format'] ?? '') === 'md') {
    header('Content-Type: text/markdown; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"$name-PRD.md\"");
    echo $prd['content_md'];
    exit;
}

$path = Documents::exportZip($project, $prd['content_md'], $prd['source'] === 'manual');
header('Content-Type: application/zip');
header("Content-Disposition: attachment; filename=\"$name.zip\"");
header('Content-Length: ' . filesize($path));
readfile($path);
unlink($path);
