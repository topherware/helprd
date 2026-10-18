<?php
/**
 * Jalankan migrasi database yang belum dijalankan (migrations/NNN_*.sql), berurutan, masing-masing sekali.
 * Jalankan: C:\xampp\php\php.exe migrate.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Hanya bisa dijalankan dari command line.');
}
require __DIR__ . '/src/helpers.php';

$pdo = Database::pdo();
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
    nama VARCHAR(150) PRIMARY KEY,
    dijalankan_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

$done = $pdo->query('SELECT nama FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$files = glob(__DIR__ . '/migrations/*.sql');
sort($files);
$ran = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $done, true)) {
        continue;
    }
    echo "Menjalankan $name ... ";
    // Buang komentar "--", lalu pecah per pernyataan (file migrasi tidak memakai ";" di dalam string).
    $sql = preg_replace('/--.*$/m', '', file_get_contents($file));
    foreach (explode(';', $sql) as $statement) {
        if (trim($statement) !== '') {
            $pdo->exec($statement);
        }
    }
    Database::run('INSERT INTO schema_migrations (nama) VALUES (?)', [$name]);
    echo "selesai\n";
    $ran++;
}
echo $ran ? "$ran migrasi dijalankan.\n" : "Tidak ada migrasi baru.\n";
