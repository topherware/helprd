<?php
/**
 * Buka plan dari sidebar "Semua Plan": jadikan project aktif (cookie planku_token), lalu ke langkah terakhirnya.
 * Hanya project milik browser ini (owner_token sama) yang bisa dibuka. ?id=N&csrf=...
 */
require __DIR__ . '/../src/helpers.php';

$owner = owner_token();
$id = (int) ($_GET['id'] ?? 0);
if (!csrf_valid($_GET['csrf'] ?? null) || !$owner) {
    redirect('index.php');
}
$project = Database::run('SELECT * FROM projects WHERE id = ? AND owner_token = ?', [$id, $owner])->fetch();
if (!$project) {
    redirect('index.php');
}
set_project_cookie($project['session_token']);
redirect(step_url(max_step($project)));
