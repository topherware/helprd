<?php
/** Simpan hasil edit PRD sebagai versi baru (source "manual"). Body: {"content_md": "..."} */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

[$project, $body] = api_request();
$markdown = Validator::text($body['content_md'] ?? null, 'PRD', 20, 500000);

Database::run(
    "INSERT INTO prd_versions (project_id, content_md, source, selesai) VALUES (?, ?, 'manual', 1)",
    [(int) $project['id'], $markdown]
);

json_response(['ok' => true, 'id' => Database::lastId(), 'content_md' => $markdown]);
