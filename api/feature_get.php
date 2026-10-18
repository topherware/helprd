<?php
/**
 * Data Roadmap dan Halaman Fitur: semua fitur project beserta sub fitur, Tujuan, Selesai bila,
 * status (dihitung dari task), progres task, dan 3 task pertama per fitur.
 */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

[$project] = api_request();
$id = (int) $project['id'];

$features = Database::run(
    'SELECT id, nama, fase, ikon, status, deskripsi, tujuan, selesai_bila FROM features WHERE project_id = ? AND disertakan = 1 ORDER BY fase, urutan, id',
    [$id]
)->fetchAll();
$subs = Database::run(
    'SELECT s.feature_id, s.id, s.nama, s.tujuan, s.selesai_bila FROM sub_features s JOIN features f ON f.id = s.feature_id
     WHERE f.project_id = ? ORDER BY s.urutan, s.id',
    [$id]
)->fetchAll(PDO::FETCH_GROUP);
$stats = Database::run(
    "SELECT feature_id, COUNT(*) AS total, SUM(status = 'selesai') AS selesai, SUM(status = 'dikerjakan') AS dikerjakan,
            SUM(status = 'gagal') AS gagal
     FROM tasks WHERE project_id = ? GROUP BY feature_id",
    [$id]
)->fetchAll(PDO::FETCH_UNIQUE);
$subStats = Database::run(
    "SELECT sub_feature_id, COUNT(*) AS total, SUM(status = 'selesai') AS selesai
     FROM tasks WHERE project_id = ? AND sub_feature_id IS NOT NULL GROUP BY sub_feature_id",
    [$id]
)->fetchAll(PDO::FETCH_UNIQUE);
$preview = Database::run(
    'SELECT feature_id, id, title, status FROM tasks WHERE project_id = ? ORDER BY sort_order, id',
    [$id]
)->fetchAll(PDO::FETCH_GROUP);

// Sebelum Bikin Task, deskripsi fitur diambil dari PRD (urutannya sama dengan peta fitur).
$prdJson = Documents::latestPrd($id)[0] ?? null;

$list = function (?string $json): array {
    return $json ? (json_decode($json, true) ?: []) : [];
};
$out = [];
foreach ($features as $i => $f) {
    $s = $stats[$f['id']] ?? ['total' => 0, 'selesai' => 0, 'dikerjakan' => 0, 'gagal' => 0];
    $out[] = [
        'id' => (int) $f['id'],
        'nama' => $f['nama'],
        'fase' => (int) $f['fase'],
        'ikon' => $f['ikon'],
        'deskripsi' => $f['deskripsi'] ?: ($prdJson['fitur'][$i]['deskripsi'] ?? ''),
        'tujuan' => $f['tujuan'] ?? '',
        'selesai_bila' => $list($f['selesai_bila']),
        'status' => feature_status($s, $f['status']),
        'task_total' => (int) $s['total'],
        'task_selesai' => (int) $s['selesai'],
        'tasks_awal' => array_map(fn ($t) => ['id' => (int) $t['id'], 'title' => $t['title'], 'status' => $t['status']],
            array_slice($preview[$f['id']] ?? [], 0, 3)),
        'sub_fitur' => array_map(fn ($sub) => [
            'id' => (int) $sub['id'],
            'nama' => $sub['nama'],
            'tujuan' => $sub['tujuan'] ?? '',
            'selesai_bila' => $list($sub['selesai_bila']),
            'task_total' => (int) ($subStats[$sub['id']]['total'] ?? 0),
            'task_selesai' => (int) ($subStats[$sub['id']]['selesai'] ?? 0),
        ], $subs[$f['id']] ?? []),
    ];
}

json_response(['ok' => true, 'features' => $out]);
