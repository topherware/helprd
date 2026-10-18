<?php
/**
 * Uji koneksi ke API AI (selalu memanggil API sungguhan, mengabaikan USE_MOCK_AI).
 * Jalankan: php test_api.php   atau buka http://localhost/plankan/test_api.php dari komputer ini.
 */
require __DIR__ . '/src/helpers.php';

if (PHP_SAPI !== 'cli') {
    if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
        http_response_code(403);
        exit('Hanya bisa diakses dari localhost.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

echo 'Model    : ' . env('API_MODEL', 'claude-opus-5') . "\nMock AI  : " . (env('USE_MOCK_AI', true) ? 'true (aplikasi memakai data contoh)' : 'false') . "\n\n";

$start = microtime(true);
try {
    [$data, $in, $out] = AiClient::json(
        'Balas hanya dengan JSON valid.',
        'Balas dengan JSON {"status":"ok","pesan":"<sapaan singkat dalam Bahasa Indonesia>"}'
    );
    printf("BERHASIL (%.1f detik)\nToken    : input %d, output %d\nRespons  : %s\n", microtime(true) - $start, $in, $out, json_encode($data, JSON_UNESCAPED_UNICODE));
} catch (Throwable $e) {
    printf("GAGAL (%.1f detik)\n%s\n", microtime(true) - $start, $e->getMessage());
    exit(1);
}
