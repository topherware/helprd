<?php
/**
 * Atur sidebar "Semua Plan". POST + CSRF, body JSON {"aksi": ..., ...}:
 *   folder_create {nama}            → {folder: {id, nama}}
 *   folder_rename {id, nama}
 *   folder_delete {id}              → plan di dalamnya kembali ke folder bawaan "Umum" (folder_id NULL), tidak ikut terhapus
 *   plan_move     {id, folder_id}   → folder_id null = "Umum"
 *   plan_rename   {id, judul}
 * Hanya folder & plan milik browser ini (owner_token sama) yang bisa diubah.
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
$body = is_array($body) ? $body : [];
$owner = owner_token();
if (!$owner) {
    throw new UserError('Plan tidak ditemukan.', 404);
}

$name = function (string $field, int $max) use ($body): string {
    $v = trim(preg_replace('/\s+/u', ' ', (string) ($body[$field] ?? '')));
    if ($v === '') {
        throw new UserError('Nama tidak boleh kosong.');
    }
    return mb_substr($v, 0, $max);
};
$folder = function ($id) use ($owner): array {
    $f = Database::run('SELECT id, nama FROM plan_folders WHERE id = ? AND owner_token = ?', [(int) $id, $owner])->fetch();
    if (!$f) {
        throw new UserError('Folder tidak ditemukan.', 404);
    }
    return $f;
};
$plan = function ($id) use ($owner): array {
    $p = Database::run('SELECT id FROM projects WHERE id = ? AND owner_token = ?', [(int) $id, $owner])->fetch();
    if (!$p) {
        throw new UserError('Plan tidak ditemukan.', 404);
    }
    return $p;
};

switch ($body['aksi'] ?? '') {
    case 'folder_create':
        $nama = $name('nama', 60);
        if (Database::run('SELECT COUNT(*) FROM plan_folders WHERE owner_token = ?', [$owner])->fetchColumn() >= 50) {
            throw new UserError('Maksimal 50 folder.');
        }
        Database::run('INSERT INTO plan_folders (owner_token, nama) VALUES (?, ?)', [$owner, $nama]);
        json_response(['ok' => true, 'folder' => ['id' => Database::lastId(), 'nama' => $nama]]);
    case 'folder_rename':
        $f = $folder($body['id'] ?? 0);
        Database::run('UPDATE plan_folders SET nama = ? WHERE id = ?', [$name('nama', 60), $f['id']]);
        json_response(['ok' => true]);
    case 'folder_delete':
        $f = $folder($body['id'] ?? 0);
        Database::run('DELETE FROM plan_folders WHERE id = ?', [$f['id']]); // plan → folder_id NULL lewat FK
        json_response(['ok' => true]);
    case 'plan_move':
        $p = $plan($body['id'] ?? 0);
        $target = ($body['folder_id'] ?? null) === null ? null : $folder($body['folder_id'])['id'];
        Database::run('UPDATE projects SET folder_id = ? WHERE id = ?', [$target, $p['id']]);
        json_response(['ok' => true]);
    case 'plan_rename':
        $p = $plan($body['id'] ?? 0);
        Database::run('UPDATE projects SET judul = ? WHERE id = ?', [$name('judul', 80), $p['id']]);
        json_response(['ok' => true]);
}
throw new UserError('Aksi tidak dikenal.');
