<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/AiClient.php';
require_once __DIR__ . '/Prompts.php';
require_once __DIR__ . '/MockData.php';
require_once __DIR__ . '/Documents.php';
require_once __DIR__ . '/PartialJson.php';
require_once __DIR__ . '/Quality/Brief.php';
require_once __DIR__ . '/Quality/Triggers.php';
require_once __DIR__ . '/Quality/Report.php';
require_once __DIR__ . '/Quality/Pipeline.php';
require_once __DIR__ . '/Quality/Security/Triggers.php';
require_once __DIR__ . '/Quality/Security/Validators.php';
foreach (array_merge(glob(__DIR__ . '/Quality/Validators/*.php'), glob(__DIR__ . '/Quality/Paritas/*.php')) as $file) {
    require_once $file;
}

/** Error yang pesannya aman ditampilkan ke pengguna. Code = HTTP status. */
class UserError extends RuntimeException {}

/** Kegagalan memanggil / membaca hasil AI. Detail hanya masuk log. */
class AiException extends RuntimeException {}

date_default_timezone_set('Asia/Jakarta');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', LOG_DIR . '/php-error.log');

if (PHP_SAPI !== 'cli') {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/']);
    session_start();
}

/** [kode HTTP, pesan aman untuk pengguna] dari sebuah exception; selain UserError, detailnya dicatat di log. */
function public_error(Throwable $e): array
{
    if ($e instanceof UserError) {
        return [$e->getCode() ?: 400, $e->getMessage()];
    }
    log_error(get_class($e) . ': ' . $e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
    $quota = $e instanceof AiException && $e->getCode() === 429;
    return match (true) {
        $quota => [429, 'Kuota atau batas pemakaian layanan AI sudah habis. Coba lagi nanti, atau periksa saldo/kuota API key.'],
        $e instanceof AiException => [502, 'Layanan AI sedang bermasalah. Coba lagi sebentar lagi.'],
        $e instanceof PDOException => [500, 'Database tidak bisa diakses. Pastikan MySQL di XAMPP sudah jalan dan schema.sql sudah diimpor.'],
        default => [500, 'Terjadi kesalahan di server. Coba lagi nanti.'],
    };
}

set_exception_handler(function (Throwable $e) {
    [$code, $message] = public_error($e);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
    if (defined('IS_API')) {
        json_response(['ok' => false, 'error' => $message], $code);
    }
    http_response_code($code);
    echo '<!doctype html><meta charset="utf-8"><title>Error</title><link rel="stylesheet" href="assets/css/app.css">'
        . '<main class="page narrow"><h1>Ups, ada masalah</h1><p class="muted">' . e($message) . '</p>'
        . '<p><a class="btn" href="index.php">Kembali ke beranda</a></p></main>';
    exit;
});

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function log_error(string $message, array $context = []): void
{
    if (!is_dir(LOG_DIR)) {
        @mkdir(LOG_DIR, 0775, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . ($context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '');
    @file_put_contents(LOG_DIR . '/app.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_valid($token): bool
{
    return is_string($token) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/* ---------- Project milik pengguna (diidentifikasi lewat cookie session_token) ---------- */

function set_project_cookie(string $token): void
{
    setcookie(COOKIE_NAME, $token, [
        'expires' => time() + 60 * 60 * 24 * 30,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Token pemilik riwayat plan di browser ini (cookie planku_owner, 1 tahun). $create = true membuatnya bila belum ada.
 * Semua project yang dibuat dari browser yang sama tampil di sidebar "Semua Plan".
 */
function owner_token(bool $create = false): ?string
{
    $token = $_COOKIE[OWNER_COOKIE] ?? '';
    if (preg_match('/^[a-f0-9]{64}$/', $token)) {
        return $token;
    }
    if (!$create) {
        return null;
    }
    $token = bin2hex(random_bytes(32));
    setcookie(OWNER_COOKIE, $token, ['expires' => time() + 60 * 60 * 24 * 365, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    $_COOKIE[OWNER_COOKIE] = $token;
    return $token;
}

/**
 * Riwayat plan milik browser ini, terbaru dulu. Project aktif yang belum punya pemilik ikut diklaim.
 * Project lama (dibuat sebelum ada riwayat) hanya bisa diklaim browser lama itu sendiri: cookie project-nya menunjuk
 * project tanpa pemilik. Di XAMPP lokal (akses dari 127.0.0.1/::1) browser itu sekaligus mengklaim semua project lama
 * tanpa pemilik, supaya riwayat sebelum fitur ini tetap terlihat.
 */
function owner_projects(?array $current): array
{
    $owner = owner_token(true);
    if ($current && $current['owner_token'] === null) {
        Database::run('UPDATE projects SET owner_token = ? WHERE id = ? AND owner_token IS NULL', [$owner, $current['id']]);
        if (in_array(client_ip(), ['127.0.0.1', '::1'], true)) {
            Database::run('UPDATE projects SET owner_token = ? WHERE owner_token IS NULL', [$owner]);
        }
    }
    return Database::run(
        'SELECT p.id, p.judul, p.status, p.created_at, (SELECT COUNT(*) FROM features f WHERE f.project_id = p.id) AS fitur
         FROM projects p WHERE p.owner_token = ? ORDER BY p.created_at DESC, p.id DESC LIMIT 200',
        [$owner]
    )->fetchAll();
}

function current_project(): ?array
{
    $token = $_COOKIE[COOKIE_NAME] ?? '';
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    return Database::run('SELECT * FROM projects WHERE session_token = ?', [$token])->fetch() ?: null;
}

/** Langkah terjauh yang boleh dibuka berdasarkan status project. */
function max_step(?array $project): int
{
    return ['teknologi' => 2, 'pertanyaan' => 3, 'peta_fitur' => 5][$project['status'] ?? ''] ?? 1;
}

/** URL halaman untuk langkah $n (langkah 5 = workspace). */
function step_url(int $n): string
{
    return [1 => 'index.php', 5 => 'workspace.php'][$n] ?? "step$n.php";
}

/** Pastikan project ada dan sudah sampai langkah $step; kalau belum, arahkan ke langkah yang benar. */
function require_project(int $step): array
{
    $project = current_project();
    if (!$project) {
        redirect('index.php');
    }
    $max = max_step($project);
    if ($step > $max) {
        redirect(step_url($max));
    }
    return $project;
}

/** Pertanyaan project beserta jawabannya. dilewati = null berarti belum pernah dijawab. */
function project_questions(int $projectId): array
{
    $rows = Database::run(
        'SELECT q.id, q.teks, q.tipe, q.opsi, q.boleh_pilih_banyak, a.jawaban, a.dilewati
         FROM questions q LEFT JOIN answers a ON a.question_id = q.id
         WHERE q.project_id = ? ORDER BY q.urutan',
        [$projectId]
    )->fetchAll();
    foreach ($rows as &$row) {
        $row['opsi'] = json_decode($row['opsi'] ?? '[]', true) ?: [];
        $row['boleh_pilih_banyak'] = (bool) $row['boleh_pilih_banyak'];
        if ($row['tipe'] === 'pilihan' && $row['jawaban'] !== null) {
            $row['jawaban'] = json_decode($row['jawaban'], true) ?: [];
        }
        $row['dilewati'] = $row['dilewati'] === null ? null : (bool) $row['dilewati'];
    }
    return $rows;
}

/**
 * Status fitur: setelah fitur punya task, dihitung dari task (semua selesai = selesai, ada yang dimulai = dikerjakan,
 * belum ada yang dimulai = direncanakan). Sebelum ada task, status manual dari langkah 4 yang dipakai.
 */
function feature_status(array $stats, string $stored): string
{
    $total = (int) ($stats['total'] ?? 0);
    if ($total === 0) {
        return $stored;
    }
    if ((int) $stats['selesai'] === $total) {
        return 'selesai';
    }
    return ((int) $stats['selesai'] + (int) $stats['dikerjakan'] + (int) $stats['gagal']) > 0 ? 'dikerjakan' : 'direncanakan';
}

/** Pasangan pertanyaan-jawaban untuk prompt AI. */
function project_qa(int $projectId): array
{
    $qa = [];
    foreach (project_questions($projectId) as $q) {
        $answer = is_array($q['jawaban']) ? implode(', ', $q['jawaban']) : $q['jawaban'];
        $qa[] = ['teks' => $q['teks'], 'jawaban' => $q['dilewati'] === false ? $answer : '(dilewati)'];
    }
    return $qa;
}

/**
 * Fitur project (urut per fase) beserta daftar sub fitur. Bawaan: hanya fitur yang disertakan ke PRD
 * (fitur usulan AI yang belum dinyalakan pengguna tidak ikut). $all = true untuk peta fitur langkah 4.
 */
function project_features(int $projectId, bool $all = false): array
{
    $features = Database::run(
        'SELECT id, nama, fase, ikon, status, asal, asal_alasan, disertakan FROM features WHERE project_id = ?'
        . ($all ? '' : ' AND disertakan = 1') . ' ORDER BY fase, urutan, id',
        [$projectId]
    )->fetchAll();
    $subs = Database::run(
        'SELECT s.feature_id, s.nama FROM sub_features s JOIN features f ON f.id = s.feature_id
         WHERE f.project_id = ? ORDER BY s.urutan, s.id',
        [$projectId]
    )->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);
    foreach ($features as &$feature) {
        $feature['sub_fitur'] = $subs[$feature['id']] ?? [];
    }
    return $features;
}

/* ---------- API ---------- */

/** Validasi request API (POST + CSRF + project) lalu kembalikan [project, body JSON]. */
function api_request(): array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new UserError('Metode tidak diizinkan.', 405);
    }
    if (!csrf_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        throw new UserError('Sesi kedaluwarsa. Muat ulang halaman.', 403);
    }
    session_write_close(); // lepas lock sesi supaya panggilan AI yang lama tidak memblokir request lain
    $project = current_project();
    if (!$project) {
        throw new UserError('Project tidak ditemukan. Mulai dari halaman awal.', 404);
    }
    $body = json_decode(file_get_contents('php://input') ?: '[]', true);
    return [$project, is_array($body) ? $body : []];
}

/** Panggil AI (atau data contoh bila USE_MOCK_AI=true), dengan rate limit dan pencatatan token. */
function ai_generate(string $endpoint, array $project, string $prompt, array $mock, ?int $maxTokens = null): array
{
    $ip = client_ip();
    $useMock = (bool) env('USE_MOCK_AI', true);
    if (!$useMock) {
        RateLimiter::check($ip); // mode demo gratis, jadi tidak dibatasi
    }
    $in = $out = 0;
    try {
        if ($useMock) {
            usleep(700000); // beri kesan proses, supaya state loading ikut teruji
            return $mock;
        }
        [$data, $in, $out] = AiClient::json(Prompts::SYSTEM, $prompt, $maxTokens);
        return $data;
    } finally {
        if (!$useMock) {
            RateLimiter::log((int) $project['id'], $ip, $endpoint, $in, $out);
        }
    }
}

/**
 * Versi streaming dari ai_generate(): $onText menerima potongan teks, $onTick dipanggil berkala dan
 * membatalkan bila mengembalikan false. Mode demo memecah $mockText per ±40 karakter dengan jeda ±30 ms.
 * $system menggantikan prompt sistem bawaan (Prompts::SYSTEM), mis. untuk chat agent yang menjawab dalam markdown.
 * @return string teks lengkap
 */
function ai_stream(string $endpoint, array $project, string $prompt, string $mockText, ?int $maxTokens, callable $onText, callable $onTick, ?string $system = null): string
{
    $ip = client_ip();
    $useMock = (bool) env('USE_MOCK_AI', true);
    if (!$useMock) {
        RateLimiter::check($ip, $endpoint);
    }
    $in = $out = 0;
    try {
        if ($useMock) {
            foreach (mb_str_split($mockText, 40) as $chunk) {
                usleep(30000);
                $onText($chunk);
                if ($onTick() === false) {
                    throw new AiException('Dibatalkan: klien terputus.');
                }
            }
            return $mockText;
        }
        [$text, $in, $out] = AiClient::stream($system ?? Prompts::SYSTEM, $prompt, $maxTokens, $onText, $onTick);
        return $text;
    } finally {
        if (!$useMock) {
            RateLimiter::log((int) $project['id'], $ip, $endpoint, $in, $out);
        }
    }
}

/* ---------- Tampilan ---------- */

function page_header(string $title, int $step, ?array $project = null, string $bodyClass = ''): void
{
    $steps = [1 => 'Ide', 2 => 'Teknologi', 3 => 'Pertanyaan', 4 => 'Peta Fitur', 5 => 'Workspace'];
    $max = max_step($project);
    ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title) ?> · <?= e(APP_NAME) ?></title>
    <link rel="icon" href="data:image/svg+xml,<?= rawurlencode(logo_svg()) ?>">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="<?= e($bodyClass) ?>">
<?php if ($step < 4): ?>
<header class="topbar">
    <a class="brand" href="index.php"><?= logo_svg() ?><span><?= e(APP_NAME) ?></span></a>
    <nav class="stepper" aria-label="Langkah">
        <?php foreach ($steps as $n => $label):
            $cls = $n === $step ? 'is-active' : ($n < $step ? 'is-done' : '');
            $href = step_url($n); ?>
            <?php if ($n > 1): ?><span class="stepper-line"></span><?php endif; ?>
            <?php if ($n <= $max && $n !== $step): ?>
                <a class="step <?= $cls ?>" href="<?= $href ?>"><i></i><?= $label ?></a>
            <?php else: ?>
                <span class="step <?= $cls ?>" <?= $n === $step ? 'aria-current="step"' : '' ?>><i></i><?= $label ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</header>
<?php endif;
}

function page_footer(array $scripts = []): void
{
    echo '<script src="assets/js/app.js"></script>';
    foreach ($scripts as $src) {
        echo '<script src="assets/js/' . e($src) . '"></script>';
    }
    echo '</body></html>';
}

function logo_svg(): string
{
    return '<svg class="logo" viewBox="0 0 32 32" width="28" height="28" aria-hidden="true"><rect width="32" height="32" rx="9" fill="#ff5a1f"/>'
        . '<circle cx="10" cy="16" r="3" fill="#fff"/><circle cx="22" cy="9" r="3" fill="#fff"/><circle cx="22" cy="23" r="3" fill="#fff"/>'
        . '<path d="M12.5 15 19.5 10M12.5 17l7 5" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>';
}
