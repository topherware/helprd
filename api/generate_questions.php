<?php
const IS_API = true;
require __DIR__ . '/../src/helpers.php';
set_time_limit(660);

[$project] = api_request();

$id = (int) $project['id'];
$questions = project_questions($id);

// Pertanyaan hanya dibuat sekali per project; panggilan berikutnya mengembalikan yang sudah ada.
if (!$questions) {
    $raw = ai_generate('generate_questions', $project, Prompts::questions($project), MockData::questions($project));
    $data = Validator::aiQuestions($raw);

    Database::transaction(function () use ($id, $data) {
        Database::run('SELECT id FROM projects WHERE id = ? FOR UPDATE', [$id]);
        if (Database::run('SELECT COUNT(*) FROM questions WHERE project_id = ?', [$id])->fetchColumn()) {
            return; // sudah dibuat oleh request lain
        }
        foreach ($data as $i => $q) {
            Database::run(
                'INSERT INTO questions (project_id, urutan, celah, teks, tipe, opsi, boleh_pilih_banyak) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$id, $i + 1, $q['celah'], $q['teks'], $q['tipe'], json_encode($q['opsi'], JSON_UNESCAPED_UNICODE), $q['boleh_pilih_banyak']]
            );
        }
    });
    $questions = project_questions($id);
}

json_response(['ok' => true, 'questions' => $questions]);
