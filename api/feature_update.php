<?php
/**
 * Ubah fitur: nama, fase, deskripsi, tujuan, selesai_bila, dan status (status hanya bila fitur belum punya task;
 * setelah ada task, status dihitung dari task). Dipakai peta fitur langkah 4 dan Roadmap workspace.
 */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

[$project, $body] = api_request();
$fields = Validator::featureUpdate($body);

$featureId = (int) ($body['id'] ?? 0);
$exists = Database::run('SELECT id FROM features WHERE id = ? AND project_id = ?', [$featureId, $project['id']])->fetch();
if (!$exists) {
    throw new UserError('Fitur tidak ditemukan.', 404);
}
if (isset($fields['status']) && Database::run('SELECT 1 FROM tasks WHERE feature_id = ? LIMIT 1', [$featureId])->fetchColumn()) {
    unset($fields['status']); // status dihitung dari task
}

if ($fields) {
    // Nama kolom aman: hanya kunci yang lolos Validator::featureUpdate.
    $set = implode(', ', array_map(fn ($column) => "$column = ?", array_keys($fields)));
    Database::run("UPDATE features SET $set WHERE id = ?", [...array_values($fields), $featureId]);
}
if (isset($fields['fase'])) {
    Database::run('UPDATE tasks SET phase = ? WHERE feature_id = ?', [$fields['fase'], $featureId]);
}

json_response(['ok' => true, 'features' => project_features((int) $project['id'], true)]);
