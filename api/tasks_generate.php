<?php
/**
 * Bikin Task. Satu request = satu kelompok fitur (per fase, maksimal TASK_FEATURES_PER_CALL fitur), satu panggilan AI.
 * Browser memanggil berulang sampai {"done": true}. Kelompok yang sudah punya task dilewati, jadi bisa dilanjutkan.
 * Body: {"ulang": true} untuk menghapus semua task lama dulu (status task ikut hilang).
 */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';
set_time_limit(600);

[$project, $body] = api_request();
$id = (int) $project['id'];

$prd = Database::run('SELECT content_md FROM prd_versions WHERE project_id = ? AND selesai = 1 ORDER BY id DESC LIMIT 1', [$id])->fetchColumn();
if (!$prd) {
    throw new UserError('Selesaikan PRD dulu sebelum menyusun task.', 409);
}

if (!empty($body['ulang'])) {
    Database::transaction(function () use ($id) {
        Database::run('DELETE FROM tasks WHERE project_id = ?', [$id]);
        Database::run('UPDATE features SET deskripsi = NULL, tujuan = NULL, selesai_bila = NULL, status = ? WHERE project_id = ?', ['direncanakan', $id]);
        Database::run('UPDATE sub_features s JOIN features f ON f.id = s.feature_id SET s.tujuan = NULL, s.selesai_bila = NULL WHERE f.project_id = ?', [$id]);
    });
}

// Fitur (urut fase) beserta id sub fitur; dikelompokkan per fase, lalu dipecah bila terlalu banyak.
$features = Database::run('SELECT id, nama, fase FROM features WHERE project_id = ? ORDER BY fase, urutan, id', [$id])->fetchAll();
$subs = Database::run(
    'SELECT s.feature_id, s.id, s.nama FROM sub_features s JOIN features f ON f.id = s.feature_id WHERE f.project_id = ? ORDER BY s.urutan, s.id',
    [$id]
)->fetchAll(PDO::FETCH_GROUP);
$perCall = max(1, (int) env('TASK_FEATURES_PER_CALL', 4));
$chunks = [];
foreach ($features as $f) {
    $f['subs'] = array_map(fn ($s) => ['id' => (int) $s['id'], 'nama' => $s['nama']], $subs[$f['id']] ?? []);
    $last = count($chunks) - 1;
    if ($last >= 0 && $chunks[$last][0]['fase'] === $f['fase'] && count($chunks[$last]) < $perCall) {
        $chunks[$last][] = $f;
    } else {
        $chunks[] = [$f];
    }
}

$withTasks = array_map('intval', Database::run('SELECT DISTINCT feature_id FROM tasks WHERE project_id = ?', [$id])->fetchAll(PDO::FETCH_COLUMN));
$pending = array_values(array_filter(array_keys($chunks), fn ($i) => array_diff(array_column($chunks[$i], 'id'), $withTasks)));

if ($pending) {
    $chunk = array_values(array_filter($chunks[$pending[0]], fn ($f) => !in_array($f['id'], $withTasks, true)));

    // Jeda minimum antar panggilan Bikin Task supaya tidak membanjiri layanan AI.
    $lastCall = Database::run("SELECT UNIX_TIMESTAMP(MAX(created_at)) FROM api_logs WHERE project_id = ? AND endpoint = 'buat_task'", [$id])->fetchColumn();
    $wait = (int) env('AI_MIN_INTERVAL_SECONDS', 2) - (time() - (int) $lastCall);
    if ($lastCall && $wait > 0) {
        sleep(min($wait, 30));
    }

    // Rujukan v1.2 dari PRD JSON: ID dataset (bab 11), SEC Wajib MVP (bab 8), mode hanya-UI.
    $refs = Pipeline::taskRefs(Documents::latestPrd($id)[0] ?? []);
    $prompt = Prompts::buatTask($project, $prd, $chunk, $refs);
    for ($attempt = 1; ; $attempt++) {
        try {
            $result = Validator::aiTasks(ai_generate('buat_task', $project, $prompt, MockData::tasks($chunk, $refs), (int) env('MAX_TOKENS', 16000)), $chunk, $refs['ui_only']);
            break;
        } catch (AiException $e) {
            if ($attempt >= 2 || $e->getCode() === 429) {
                throw $e;
            }
            log_error('Bikin Task diulang sekali: ' . $e->getMessage());
        }
    }

    $order = (int) Database::run('SELECT COALESCE(MAX(sort_order), 0) FROM tasks WHERE project_id = ?', [$id])->fetchColumn();

    Database::transaction(function () use ($id, $chunk, $result, &$order) {
        foreach ($chunk as $f) {
            if (Database::run('SELECT 1 FROM tasks WHERE feature_id = ? LIMIT 1', [$f['id']])->fetchColumn()) {
                continue; // sudah dibuat oleh request lain
            }
            $r = $result[$f['id']];
            Database::run(
                'UPDATE features SET deskripsi = ?, tujuan = ?, selesai_bila = ? WHERE id = ?',
                [$r['deskripsi'], $r['tujuan'], json_encode($r['selesai_bila'], JSON_UNESCAPED_UNICODE), $f['id']]
            );
            foreach ($f['subs'] as $s) {
                $sub = $r['subs'][$s['id']];
                Database::run('UPDATE sub_features SET tujuan = ?, selesai_bila = ? WHERE id = ?',
                    [$sub['tujuan'], json_encode($sub['selesai_bila'], JSON_UNESCAPED_UNICODE), $s['id']]);
            }
            foreach ($r['tasks'] as $t) {
                Database::run(
                    'INSERT INTO tasks (project_id, feature_id, sub_feature_id, title, phase, milestone, priority, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [$id, $f['id'], $t['sub_feature_id'], $t['title'], $f['fase'], $t['milestone'], $t['priority'], ++$order]
                );
            }
        }
    });

    if (count($pending) > 1) {
        json_response(['ok' => true, 'done' => false, 'selesai' => count($chunks) - count($pending) + 1, 'total' => count($chunks)]);
    }
    $finishedNow = true;
}

$counts = Database::run(
    'SELECT (SELECT COUNT(*) FROM features WHERE project_id = ?) AS fitur,
            (SELECT COUNT(*) FROM sub_features s JOIN features f ON f.id = s.feature_id WHERE f.project_id = ?) AS sub_fitur,
            (SELECT COUNT(*) FROM tasks WHERE project_id = ?) AS task',
    [$id, $id, $id]
)->fetch();
$message = "Selesai! Aku sudah menyusun **{$counts['fitur']} fitur, {$counts['sub_fitur']} sub-fitur, dan {$counts['task']} task** dari PRD kamu.\n"
    . 'Klik fitur mana pun di peta untuk lihat detail atau langsung mulai dikerjakan.';
if (!empty($finishedNow)) {
    Database::run("INSERT INTO agent_messages (project_id, role, content) VALUES (?, 'system', ?)", [$id, $message]);
}

json_response(['ok' => true, 'done' => true, 'jumlah' => array_map('intval', $counts), 'pesan' => $message]);
