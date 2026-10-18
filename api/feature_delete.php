<?php
/** Hapus fitur beserta sub fitur dan task-nya (di database; teks PRD tidak diubah). Body: {"id": N} */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

[$project, $body] = api_request();
$featureId = (int) ($body['id'] ?? 0);
$exists = Database::run('SELECT id FROM features WHERE id = ? AND project_id = ?', [$featureId, $project['id']])->fetch();
if (!$exists) {
    throw new UserError('Fitur tidak ditemukan.', 404);
}

Database::transaction(function () use ($project, $featureId) {
    Database::run('DELETE FROM documents WHERE project_id = ? AND feature_id = ?', [$project['id'], $featureId]);
    Database::run('DELETE FROM features WHERE id = ?', [$featureId]); // sub fitur & task ikut terhapus (ON DELETE CASCADE)
});

json_response(['ok' => true]);
