<?php
/**
 * Streaming PRD (Server-Sent Events lewat POST; dibaca dengan fetch + ReadableStream).
 * Body: {"ulang": true} untuk membuat ulang PRD (versi lama di prd_versions tetap disimpan).
 *
 * Event: status {teks}, snapshot {markdown}, done {markdown}, error {message, markdown}.
 * PRD v1.2 (bab 1–13) ditulis per potongan pendek (Prompts::PRD_PARTS): profil dulu (bendera, stack terkunci, peran),
 * lalu bab 1–13. JSON parsial diperbaiki dan dirender ke markdown di server, lalu dikirim sebagai snapshot berkala.
 */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

[$project, $body] = api_request();
$id = (int) $project['id'];
$features = project_features($id);
if (!$features) {
    throw new UserError('Buat peta fitur dulu.', 409);
}

// Mulai dari sini respons berupa stream: matikan semua buffer dan batas waktu.
set_time_limit(0);
ignore_user_abort(true); // pemutusan dideteksi sendiri supaya hasil parsial sempat disimpan
while (ob_get_level()) {
    ob_end_clean();
}
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-transform');
header('X-Accel-Buffering: no');

$lastPing = time();
$aborted = false;
$snapshot = ''; // markdown terakhir yang dikirim
$versionId = null;
$savedAt = 0.0;

// Versi PRD dibuat sejak snapshot pertama (selesai=0) dan diperbarui berkala, supaya refresh di tengah
// streaming selalu menemukan hasil sebagian. Saat selesai, baris yang sama ditandai selesai=1.
$persist = function (string $markdown, int $selesai) use (&$versionId, &$savedAt, $id) {
    if ($versionId === null) {
        Database::run("INSERT INTO prd_versions (project_id, content_md, source, selesai) VALUES (?, ?, 'ai', ?)", [$id, $markdown, $selesai]);
        $versionId = Database::lastId();
    } else {
        Database::run('UPDATE prd_versions SET content_md = ?, selesai = ? WHERE id = ?', [$markdown, $selesai, $versionId]);
    }
    $savedAt = microtime(true);
};

$send = function (string $event, array $data) use (&$lastPing) {
    echo "event: $event\ndata: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    flush();
    $lastPing = time();
};
// Dipanggil berkala selama AI bekerja: kirim ping supaya Apache/proxy tidak memutus koneksi, dan deteksi klien pergi.
$tick = function () use (&$lastPing, &$aborted) {
    if (time() - $lastPing >= 15) {
        echo ": ping\n\n";
        flush();
        $lastPing = time();
    }
    if (connection_aborted()) {
        $aborted = true;
    }
    return !$aborted;
};
/** Stream satu panggilan AI; bila koneksi putus, hanya panggilan ini yang diulang. */
$streamPart = function (string $endpoint, string $prompt, string $mock, callable $render) use ($id, $send, &$streamOnce, &$snapshot): array {
    if (Documents::callsLeft($id) <= 0) {
        throw new UserError('Batas panggilan AI untuk dokumen project ini tercapai (DOC_MAX_CALLS_PER_PROJECT).', 429);
    }
    for ($attempt = 0; ; $attempt++) {
        try {
            // Saat dicoba ulang, snapshot yang lebih pendek dari yang sudah tampil tidak dikirim.
            return $streamOnce($endpoint, $prompt, $mock, $render, $attempt ? strlen($snapshot) : 0);
        } catch (AiException $e) {
            // Koneksi ke AI putus di tengah jalan (sering terjadi lewat proxy): ulangi potongan ini dari awal.
            // JSON yang tetap rusak setelah diperbaiki otomatis juga diulang, tetapi hanya sekali.
            $badJson = $e->getCode() === AiClient::BAD_JSON && $attempt === 0;
            if (!$badJson && ($e->getCode() !== AiClient::NETWORK || $attempt >= AiClient::retries())) {
                throw $e;
            }
            if ($badJson) {
                log_error('JSON potongan PRD rusak, ditulis ulang sekali: ' . mb_substr($e->getMessage(), 0, 300));
                $send('status', ['teks' => 'Hasil AI tidak terbaca, menulis ulang bagian ini…']);
                continue;
            }
            log_error('Koneksi AI putus saat streaming, dicoba ulang: ' . $e->getMessage());
            $send('status', ['teks' => sprintf('Koneksi ke AI terputus, mencoba lagi (%d/%d)…', $attempt + 1, AiClient::retries())]);
        }
    }
};
$streamOnce = function (string $endpoint, string $prompt, string $mock, callable $render, int $floor) use ($project, $send, $tick, $persist, &$snapshot, &$savedAt): array {
    $buffer = '';
    $last = 0.0;
    $text = ai_stream($endpoint, $project, $prompt, $mock, Documents::PRD_MAX_TOKENS, function (string $chunk) use (&$buffer, &$last, $floor, $render, $send, $persist, &$snapshot, &$savedAt) {
        $buffer .= $chunk;
        // Snapshot berisi seluruh dokumen: makin besar dokumen, makin jarang dikirim supaya browser tidak tertinggal.
        if (microtime(true) - $last < 0.35 + strlen($snapshot) / 40000) {
            return;
        }
        $last = microtime(true);
        $partial = PartialJson::parse($buffer);
        $markdown = $partial ? $render($partial) : null;
        if ($markdown !== null && $floor > 0 && strlen($markdown) <= $floor) {
            // Masih mengejar teks yang sudah tampil sebelum koneksi putus: tunjukkan kemajuannya.
            $send('status', ['teks' => sprintf('Menyusun ulang setelah koneksi terputus… %d%%', min(99, (int) (100 * strlen($markdown) / $floor)))]);
            return;
        }
        if ($markdown !== null && $markdown !== $snapshot) {
            $snapshot = $markdown;
            $send('snapshot', ['markdown' => $markdown]);
            if (microtime(true) - $savedAt >= 2) {
                $persist($markdown, 0);
            }
        }
    }, $tick);
    return AiClient::decode($text);
};

$qa = project_qa($id);
$brief = Brief::fromProject($project, $features, $qa);
$hash = Documents::hash($features, $project);
$docs = Documents::current($id, $hash);
$prd = empty($body['ulang']) ? ($docs[0] ?? null) : null;
if ($prd && ($prd['versi'] ?? '') !== '1.2') {
    $prd = null; // PRD format lama yang belum selesai ditulis ulang dengan format v1.2
}
$count = count($features);
if (!empty($body['ulang'])) {
    // Buat ulang: buang PRD JSON lama supaya "Lanjutkan" sesudahnya tidak kembali ke versi lama.
    // Teks versi lama tetap tersimpan di riwayat prd_versions.
    Database::run('DELETE FROM prd_parts WHERE project_id = ?', [$id]);
    Database::run('DELETE FROM documents WHERE project_id = ? AND feature_id = 0', [$id]);
    Database::run('DELETE FROM quality_reports WHERE project_id = ?', [$id]);
}

/** Simpan satu potongan selesai di prd_parts supaya dilewati saat dilanjutkan. */
$savePart = function (string $name, array $piece) use ($id, $hash) {
    Database::run(
        'INSERT INTO prd_parts (project_id, bagian, peta_hash, data) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE peta_hash = VALUES(peta_hash), data = VALUES(data)',
        [$id, "v12:$name", $hash, json_encode($piece, JSON_UNESCAPED_UNICODE)]
    );
};

try {
    if (!$prd) {
        $done = [];
        foreach (Database::run("SELECT data FROM prd_parts WHERE project_id = ? AND peta_hash = ? AND bagian LIKE 'v12:%'", [$id, $hash])->fetchAll(PDO::FETCH_COLUMN) as $json) {
            $done += json_decode($json, true);
        }

        // Potongan "profil": bendera, stack terkunci, peran. Tidak dirender.
        if (!isset($done['profil'])) {
            $send('status', ['teks' => 'Menganalisis kebutuhan & mengunci tech stack…']);
            $raw = $streamPart('generate_prd', Prompts::profil($brief), json_encode(MockData::profil($brief), JSON_UNESCAPED_UNICODE), fn () => null);
            $done['profil'] = Pipeline::lockProfil($brief, Validator::aiProfil($raw));
            $savePart('profil', ['profil' => $done['profil']]);
        }
        $profil = $done['profil'];
        $parts = Prompts::parts($brief->uiOnly);
        unset($parts['profil']);
        $mock = MockData::prd($brief, Pipeline::context($brief, $profil, []));
        $n = 0;

        foreach ($parts as $name => $keys) {
            $n++;
            if (!array_diff($keys, array_keys($done))) {
                continue;
            }
            $babs = array_unique(array_map(fn ($k) => Prompts::BAB[$k], $keys));
            $send('status', ['teks' => sprintf('Menyusun bab %s (bagian %d/%d)…', min($babs) === max($babs) ? min($babs) : min($babs) . '–' . max($babs), $n, count($parts))]);
            $ctx = Pipeline::context($brief, $profil, Validator::aiPrd($done, $count, true));
            $raw = $streamPart('generate_prd', Prompts::prdPart($brief, $ctx, $keys, Prompts::dependencies($name, $done)),
                json_encode(array_intersect_key($mock, array_flip($keys)), JSON_UNESCAPED_UNICODE),
                fn ($partial) => Documents::renderPrd($features, ['profil' => $profil] + Validator::aiPrd($done + $partial, $count, true),
                    array_values(array_diff(array_keys($done + $partial), ['profil']))));
            $piece = array_intersect_key($raw, array_flip($keys));
            Validator::requireKeys(Validator::aiPrd($piece, $count, true), $keys, $count);
            $savePart($name, $piece);
            $done += $piece;
        }

        $prd = ['profil' => $profil] + Validator::aiPrd($done, $count);
        Documents::save($id, 0, $hash, $prd);
        Database::run("DELETE FROM prd_parts WHERE project_id = ? AND bagian LIKE 'v12:%'", [$id]);
    }

    $markdown = Documents::renderPrd($features, $prd);
    $persist($markdown, 1);
    $send('done', ['markdown' => $markdown]);
} catch (Throwable $e) {
    [, $message] = $aborted ? [0, 'Koneksi terputus.'] : public_error($e);
    if ($snapshot !== '') {
        try {
            $persist($snapshot, 0); // hasil sebagian tetap terlihat setelah refresh dan bisa dilanjutkan
        } catch (Throwable $saveError) {
            [, $message] = public_error($saveError); // mis. database mati: tetap kirim pesan yang jelas ke browser
        }
    }
    if (!$aborted) {
        $send('error', ['message' => $message, 'markdown' => $snapshot]);
    }
}
