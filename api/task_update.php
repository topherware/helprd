<?php
/**
 * Ubah status satu atau banyak task. Body: {"ids": [1, 2, ...], "status": "belum_mulai|dikerjakan|selesai|gagal"}.
 * Status fitur yang terdampak ikut disinkronkan (features.status = cache dari status task).
 */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

[$project, $body] = api_request();
$id = (int) $project['id'];

$status = $body['status'] ?? '';
if (!in_array($status, ['belum_mulai', 'dikerjakan', 'selesai', 'gagal'], true)) {
    throw new UserError('Status task tidak valid.', 422);
}
$ids = array_values(array_unique(array_filter(array_map('intval', is_array($body['ids'] ?? null) ? $body['ids'] : []))));
if (!$ids || count($ids) > 500) {
    throw new UserError('Pilih 1 sampai 500 task.', 422);
}

$in = implode(',', array_fill(0, count($ids), '?'));
$updated = Database::transaction(function () use ($id, $ids, $in, $status) {
    // project_id di WHERE memastikan hanya task milik project ini yang bisa diubah.
    $updated = Database::run("UPDATE tasks SET status = ? WHERE project_id = ? AND id IN ($in)", [$status, $id, ...$ids])->rowCount();
    $features = Database::run("SELECT DISTINCT feature_id FROM tasks WHERE project_id = ? AND id IN ($in)", [$id, ...$ids])->fetchAll(PDO::FETCH_COLUMN);
    foreach ($features as $featureId) {
        $stats = Database::run(
            "SELECT COUNT(*) AS total, SUM(status = 'selesai') AS selesai, SUM(status = 'dikerjakan') AS dikerjakan, SUM(status = 'gagal') AS gagal
             FROM tasks WHERE feature_id = ?",
            [$featureId]
        )->fetch();
        Database::run('UPDATE features SET status = ? WHERE id = ?', [feature_status($stats, 'direncanakan'), $featureId]);
    }
    return $updated;
});

json_response(['ok' => true, 'updated' => $updated]);
