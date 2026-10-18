<?php
/**
 * Daftar task untuk Kanban & Halaman Fitur. Body: {"fitur": ID (opsional), "sort": roadmap|diperbarui|dibuat|judul|prioritas}.
 * Urutan dikerjakan di server.
 */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

[$project, $body] = api_request();
$id = (int) $project['id'];

$orders = [
    'roadmap' => 'f.fase, f.urutan, f.id, t.sort_order, t.id',
    'diperbarui' => 't.updated_at DESC, t.id DESC',
    'dibuat' => 't.created_at DESC, t.id DESC',
    'judul' => 't.title ASC, t.id',
    'prioritas' => "FIELD(t.priority, 'utama', 'penting', 'opsional'), f.fase, f.urutan, t.sort_order",
];
$order = $orders[$body['sort'] ?? 'roadmap'] ?? $orders['roadmap']; // hanya nilai dari daftar di atas yang masuk SQL

$params = [$id];
$where = 't.project_id = ?';
if (!empty($body['fitur'])) {
    $where .= ' AND t.feature_id = ?';
    $params[] = (int) $body['fitur'];
}

$tasks = Database::run(
    "SELECT t.id, t.title, t.status, t.priority, t.phase, t.feature_id, f.nama AS feature_nama,
            t.sub_feature_id, s.nama AS sub_nama, t.created_at, t.updated_at
     FROM tasks t JOIN features f ON f.id = t.feature_id LEFT JOIN sub_features s ON s.id = t.sub_feature_id
     WHERE $where ORDER BY $order",
    $params
)->fetchAll();
$features = Database::run('SELECT id, nama, fase FROM features WHERE project_id = ? ORDER BY fase, urutan, id', [$id])->fetchAll();

json_response(['ok' => true, 'tasks' => $tasks, 'features' => $features]);
