<?php
/**
 * Riwayat versi PRD. Body: {"action": "list"}, {"action": "restore", "id": N}, atau {"action": "latest"} (isi versi terbaru).
 * Versi parsial (streaming yang terputus) tidak ditampilkan di riwayat.
 * Memulihkan = menyalin versi lama menjadi versi terbaru; versi lain tidak diubah.
 */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

[$project, $body] = api_request();
$id = (int) $project['id'];

if (($body['action'] ?? 'list') === 'restore') {
    $row = Database::run(
        'SELECT content_md, source FROM prd_versions WHERE id = ? AND project_id = ? AND selesai = 1',
        [(int) ($body['id'] ?? 0), $id]
    )->fetch();
    if (!$row) {
        throw new UserError('Versi tidak ditemukan.', 404);
    }
    Database::run(
        'INSERT INTO prd_versions (project_id, content_md, source, selesai) VALUES (?, ?, ?, 1)',
        [$id, $row['content_md'], $row['source']]
    );
    json_response(['ok' => true, 'id' => Database::lastId(), 'content_md' => $row['content_md']]);
}

if (($body['action'] ?? 'list') === 'latest') {
    $md = Database::run('SELECT content_md FROM prd_versions WHERE project_id = ? AND selesai = 1 ORDER BY id DESC LIMIT 1', [$id])->fetchColumn();
    json_response(['ok' => true, 'content_md' => $md ?: null]);
}

$versions = Database::run(
    'SELECT id, source, CHAR_LENGTH(content_md) AS panjang, created_at FROM prd_versions
     WHERE project_id = ? AND selesai = 1 ORDER BY id DESC LIMIT 30',
    [$id]
)->fetchAll();
$latest = (int) Database::run('SELECT MAX(id) FROM prd_versions WHERE project_id = ?', [$id])->fetchColumn();

json_response(['ok' => true, 'versions' => $versions, 'terbaru' => $latest]);
