<?php
/**
 * Riwayat chat Workspace Agent, terbaru lebih dulu dibatasi 30 pesan, dikembalikan urut lama ke baru.
 * Body: {"sebelum": ID (opsional)} untuk memuat pesan yang lebih lama dari ID itu.
 */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

const PER_PAGE = 30;

[$project, $body] = api_request();
$before = (int) ($body['sebelum'] ?? 0);

$rows = Database::run(
    'SELECT id, role, content FROM agent_messages WHERE project_id = ? AND (? = 0 OR id < ?) ORDER BY id DESC LIMIT ' . (PER_PAGE + 1),
    [(int) $project['id'], $before, $before]
)->fetchAll();

json_response([
    'ok' => true,
    'messages' => array_reverse(array_slice($rows, 0, PER_PAGE)),
    'ada_lagi' => count($rows) > PER_PAGE,
]);
