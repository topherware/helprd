<?php
/**
 * Audit Standar Kualitas v1.2 (tombol "Jalankan audit" di Implemen). Browser memanggil per langkah:
 *   {"langkah": "nilai", "ai": true}      → Pass 3 semua validator + Pass 4 audit AI singkat (bila "ai"), simpan laporan.
 *                                           Mengembalikan potongan PRD yang perlu diperbaiki.
 *   {"langkah": "perbaiki", "bagian": "p5"} → Pass 5: tulis ulang satu potongan dengan temuannya, simpan versi PRD baru.
 *   {"langkah": "perbaiki", "bagian": "task"} → Pass 5 batas task (P8, v1.2.1): gabungkan task fitur yang melebihi batas (tanpa AI).
 * Browser mengulang nilai → perbaiki maksimal 2 putaran.
 */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';
set_time_limit(600);

[$project, $body] = api_request();
$id = (int) $project['id'];
$features = project_features($id);
$hash = Documents::hash($features, $project);
$prd = Documents::current($id, $hash)[0] ?? null;
if (!$prd || ($prd['versi'] ?? '') !== '1.2') {
    throw new UserError('Audit v1.2 butuh PRD format v1.2 yang sesuai peta fitur dan isian Bagian B sekarang. Klik "Buat ulang PRD" dulu.', 409);
}
$tasks = Pipeline::projectTasks($id);
if (!$tasks) {
    throw new UserError('Klik "Bikin Task" dulu; audit juga memeriksa task bertanda milestone.', 409);
}
$brief = Brief::fromProject($project, $features);

/** Panggilan AI dokumen: hormati batas per project dan jeda minimum antar panggilan. */
$call = function (string $endpoint, string $prompt, array $mock, int $maxTokens) use ($project, $id) {
    if (Documents::callsLeft($id) <= 0) {
        throw new UserError('Batas panggilan AI untuk dokumen project ini tercapai (DOC_MAX_CALLS_PER_PROJECT).', 429);
    }
    $last = Database::run('SELECT UNIX_TIMESTAMP(MAX(created_at)) FROM api_logs WHERE project_id = ?', [$id])->fetchColumn();
    $wait = (int) env('AI_MIN_INTERVAL_SECONDS', 2) - (time() - (int) $last);
    if ($last && $wait > 0) {
        sleep(min($wait, 30));
    }
    return ai_generate($endpoint, $project, $prompt, $mock, $maxTokens);
};

// Pass 5 untuk batas task (P8): gabungkan task fitur yang melebihi batas, hanya bila belum ada task fitur itu yang dikerjakan.
if (($body['langkah'] ?? '') === 'perbaiki' && ($body['bagian'] ?? '') === 'task') {
    $merged = 0;
    foreach ($features as $f) {
        $rows = Database::run('SELECT id, title, milestone, koordinasi, status FROM tasks WHERE project_id = ? AND feature_id = ? ORDER BY sort_order, id', [$id, $f['id']])->fetchAll();
        if (count($rows) <= P08Batas::PER_FITUR || array_filter($rows, fn ($r) => $r['status'] !== 'belum_mulai')) {
            continue;
        }
        $plan = Pipeline::mergeTasks(array_map(fn ($r) => ['id' => (int) $r['id']] + $r, $rows));
        Database::transaction(function () use ($plan, $id) {
            foreach ($plan['judul'] as $taskId => $title) {
                Database::run('UPDATE tasks SET title = ? WHERE id = ? AND project_id = ?', [$title, $taskId, $id]);
            }
            foreach ($plan['hapus'] as $taskId) {
                Database::run("DELETE FROM tasks WHERE id = ? AND project_id = ? AND status = 'belum_mulai'", [$taskId, $id]);
            }
        });
        $merged += count($plan['hapus']);
    }
    json_response(['ok' => true, 'diperbaiki' => $merged > 0]);
}

if (($body['langkah'] ?? '') === 'perbaiki') {
    $part = (string) ($body['bagian'] ?? '');
    $parts = Prompts::parts($brief->uiOnly);
    $report = QualityReport::load($id, $hash);
    $findings = $report ? (QualityReport::partsToRepair($report)[$part] ?? []) : [];
    if (!isset($parts[$part]) || $part === 'profil') {
        throw new UserError('Bagian PRD tidak dikenal.', 422);
    }
    if ($findings) {
        $keys = $parts[$part];
        $fixes = implode("\n", array_map(fn ($f) => "- [{$f['id']}, bab {$f['bab']}] {$f['pesan']}" . ($f['saran'] !== '' ? " Saran: {$f['saran']}" : ''), $findings));
        $ctx = Pipeline::context($brief, $prd['profil'], $prd) + ['current' => $prd]; // versi sekarang ikut dikirim supaya ID dipertahankan
        $mock = array_intersect_key(MockData::prd($brief, $ctx), array_flip($keys));
        $raw = $call('perbaikan_prd', Prompts::prdPart($brief, $ctx, $keys, Prompts::dependencies($part, $prd), $fixes), $mock, Documents::PRD_MAX_TOKENS);
        $piece = array_intersect_key($raw, array_flip($keys));
        Validator::requireKeys(Validator::aiPrd($piece, count($features), true), $keys, count($features));
        $prd = ['profil' => $prd['profil']] + Validator::aiPrd(array_replace($prd, $piece), count($features));
        Documents::save($id, 0, $hash, $prd);
        Database::run("INSERT INTO prd_versions (project_id, content_md, source, selesai) VALUES (?, ?, 'ai', 1)", [$id, Documents::renderPrd($features, $prd)]);
    }
    json_response(['ok' => true, 'diperbaiki' => (bool) $findings]);
}

// Pass 3 (validator) + Pass 4 (audit AI, opsional, hanya laporan).
$previous = QualityReport::load($id, $hash);
$audit = $previous['audit_ai'] ?? [];
if (!empty($body['ai'])) {
    $audit = Validator::aiAudit($call('audit_kualitas', Prompts::auditKualitas($brief, $prd), ['temuan' => []], 8000));
}
$report = QualityReport::evaluate($brief, $prd, $tasks, $audit);
QualityReport::save($id, $hash, $report);

json_response([
    'ok' => true,
    'laporan' => ['lulus' => $report['lulus'], 'skor' => $report['skor'], 'temuan' => count($report['temuan'])],
    'perbaiki' => array_keys(QualityReport::partsToRepair($report)),
]);
