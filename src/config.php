<?php
// Ganti nama aplikasi cukup di sini.
const APP_NAME = 'HelPRD';

const COOKIE_NAME = 'planku_token';
/** Penanda browser pemilik riwayat plan (tanpa login). */
const OWNER_COOKIE = 'planku_owner';
const ROOT_DIR = __DIR__ . '/..';
const LOG_DIR = ROOT_DIR . '/logs';
/** Lampiran referensi dari kotak ide (di luar public; diblokir .htaccess). */
const UPLOAD_DIR = ROOT_DIR . '/storage/uploads';
/** Batas lampiran: jumlah berkas dan ukuran per berkas (byte). */
const ATTACH_MAX_FILES = 5;
const ATTACH_MAX_BYTES = 5 * 1024 * 1024;
/** Jenis berkas yang diterima (MIME hasil deteksi isi => jenis). */
const ATTACH_TYPES = [
    'image/png' => 'gambar', 'image/jpeg' => 'gambar', 'image/webp' => 'gambar', 'image/gif' => 'gambar',
    'application/pdf' => 'pdf', 'text/plain' => 'teks', 'text/markdown' => 'teks', 'text/x-markdown' => 'teks',
];

/** Parser .env sederhana: KEY=VALUE per baris, # untuk komentar. */
function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (getenv($key) !== false) {
            $_ENV[$key] = getenv($key); // variabel lingkungan proses menang atas .env (mis. USE_MOCK_AI=true untuk uji)
            continue;
        }
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && $value[-1] === $value[0]) {
            $value = substr($value, 1, -1);
        }
        $_ENV[$key] = $value;
    }
}

/** Ambil nilai .env; "true"/"false" dikonversi ke boolean, string kosong dianggap tidak ada. */
function env(string $key, $default = null)
{
    $value = $_ENV[$key] ?? null;
    if ($value === null || $value === '') {
        return $default;
    }
    $lower = strtolower($value);
    if ($lower === 'true' || $lower === 'false') {
        return $lower === 'true';
    }
    return $value;
}

load_env(ROOT_DIR . '/.env');
