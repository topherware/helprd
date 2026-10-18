<?php
/**
 * Chat Workspace Agent (Server-Sent Events lewat POST; dibaca dengan fetch + ReadableStream).
 * Body: {"pesan": "...", "view": "prd|roadmap|kanban|fitur", "fitur": ID (opsional, fitur yang sedang dibuka)}.
 *
 * Event: delta {teks}, reset {} (koneksi AI putus dan jawaban diulang dari awal), done {}, error {message}.
 * Konteks: PRD aktif, fitur + sub-fitur + progres task, fitur terpilih, dan 10 pesan terakhir.
 * v1 hanya tanya-jawab: agent tidak bisa mengubah data. Pertanyaan dan jawaban disimpan di agent_messages setelah selesai.
 */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

[$project, $body] = api_request();
$id = (int) $project['id'];
$question = Validator::text($body['pesan'] ?? null, 'Pesan', 1, 2000);
$view = in_array($body['view'] ?? '', ['prd', 'roadmap', 'kanban', 'fitur'], true) ? $body['view'] : 'prd';

// Konteks project
$prd = (string) Database::run('SELECT content_md FROM prd_versions WHERE project_id = ? ORDER BY id DESC LIMIT 1', [$id])->fetchColumn();
$maxPrd = (int) env('AGENT_PRD_CHARS', 60000);
if (mb_strlen($prd) > $maxPrd) {
    $prd = mb_substr($prd, 0, $maxPrd) . "\n\n(PRD dipotong karena terlalu panjang.)";
}
$counts = Database::run('SELECT feature_id, status, COUNT(*) FROM tasks WHERE project_id = ? GROUP BY feature_id, status', [$id])->fetchAll(PDO::FETCH_NUM);
$features = project_features($id);
$selected = null;
foreach ($features as &$f) {
    $f['task'] = ['total' => 0, 'belum_mulai' => 0, 'dikerjakan' => 0, 'selesai' => 0, 'gagal' => 0];
    foreach ($counts as [$fid, $status, $n]) {
        if ((int) $fid === (int) $f['id']) {
            $f['task'][$status] = (int) $n;
            $f['task']['total'] += (int) $n;
        }
    }
    if ((int) $f['id'] === (int) ($body['fitur'] ?? 0)) {
        $selected = $f;
    }
}
unset($f);
if ($selected) {
    $selected['tasks'] = Database::run('SELECT title, status FROM tasks WHERE feature_id = ? ORDER BY sort_order, id', [$selected['id']])->fetchAll();
}
$history = array_reverse(Database::run(
    'SELECT role, content FROM agent_messages WHERE project_id = ? ORDER BY id DESC LIMIT 10',
    [$id]
)->fetchAll());

$prompt = Prompts::agent($project, $prd, $features, $view, $selected, $history, $question);
$mock = MockData::agentReply($question, $features, $selected);

// Mulai dari sini respons berupa stream: matikan semua buffer dan batas waktu.
set_time_limit(0);
ignore_user_abort(true);
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
$send = function (string $event, array $data) use (&$lastPing) {
    echo "event: $event\ndata: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    flush();
    $lastPing = time();
};
$tick = function () use (&$lastPing, &$aborted) {
    if (time() - $lastPing >= 15) {
        echo ": ping\n\n";
        flush();
        $lastPing = time();
    }
    $aborted = $aborted || connection_aborted();
    return !$aborted;
};

try {
    for ($attempt = 0; ; $attempt++) {
        try {
            $answer = ai_stream(RateLimiter::AGENT, $project, $prompt, $mock, (int) env('AGENT_MAX_TOKENS', 2000),
                fn (string $chunk) => $send('delta', ['teks' => $chunk]), $tick, Prompts::AGENT_SYSTEM);
            break;
        } catch (AiException $e) {
            // Koneksi ke AI putus (sering lewat proxy): ulangi jawaban dari awal.
            if ($e->getCode() !== AiClient::NETWORK || $attempt >= AiClient::retries()) {
                throw $e;
            }
            log_error('Koneksi AI putus saat chat agent, dicoba ulang: ' . $e->getMessage());
            $send('reset', []);
        }
    }
    $answer = trim($answer);
    Database::run(
        "INSERT INTO agent_messages (project_id, role, content) VALUES (?, 'user', ?), (?, 'assistant', ?)",
        [$id, $question, $id, $answer]
    );
    $send('done', []);
} catch (Throwable $e) {
    if (!$aborted) {
        [, $message] = public_error($e);
        $send('error', ['message' => $message]);
    }
}
