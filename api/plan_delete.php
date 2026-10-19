<?php
/**
 * Hapus satu plan dari sidebar "Semua Plan". Body: {"id": N}. POST + CSRF.
 * Hanya project milik browser ini (owner_token sama) yang bisa dihapus. Data turunannya (pertanyaan, fitur, PRD, task,
 * laporan kualitas, chat agent) ikut terhapus lewat ON DELETE CASCADE; api_logs tetap ada untuk batas laju.
 */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    throw new UserError('Metode tidak diizinkan.', 405);
}
if (!csrf_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    throw new UserError('Sesi kedaluwarsa. Muat ulang halaman.', 403);
}
session_write_close();
$body = json_decode(file_get_contents('php://input') ?: '[]', true);
$owner = owner_token();
$project = $owner ? Database::run('SELECT id, session_token FROM projects WHERE id = ? AND owner_token = ?', [(int) ($body['id'] ?? 0), $owner])->fetch() : null;
if (!$project) {
    throw new UserError('Plan tidak ditemukan.', 404);
}
Database::run('DELETE FROM projects WHERE id = ?', [$project['id']]);
// Berkas lampiran referensi ikut dihapus (baris project_files sudah terhapus lewat CASCADE).
$dir = UPLOAD_DIR . '/' . (int) $project['id'];
if (is_dir($dir)) {
    array_map('unlink', glob($dir . '/*') ?: []);
    @rmdir($dir);
}

// Plan yang sedang aktif dihapus: lepas cookie project supaya halaman lain tidak menunjuk project yang sudah tidak ada.
$active = hash_equals($project['session_token'], (string) ($_COOKIE[COOKIE_NAME] ?? ''));
if ($active) {
    setcookie(COOKIE_NAME, '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
}
json_response(['ok' => true, 'aktif' => $active]);
