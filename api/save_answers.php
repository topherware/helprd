<?php
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

[$project, $body] = api_request();
$id = (int) $project['id'];

$questions = array_column(project_questions($id), null, 'id');
if (!$questions) {
    throw new UserError('Pertanyaan belum dibuat.', 409);
}
$answers = Validator::answers($body['jawaban'] ?? null, $questions);

Database::transaction(function () use ($id, $answers) {
    $changed = false;
    foreach ($answers as $questionId => $a) {
        // rowCount: 1 = baris baru, 2 = berubah, 0 = sama seperti sebelumnya
        $changed = Database::run(
            'INSERT INTO answers (question_id, jawaban, dilewati) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE jawaban = VALUES(jawaban), dilewati = VALUES(dilewati)',
            [$questionId, $a['jawaban'], $a['dilewati']]
        )->rowCount() > 0 || $changed;
    }
    if ($changed) {
        // Jawaban berubah: peta fitur lama tidak relevan lagi, akan dibuat ulang di langkah 4.
        Database::run('DELETE FROM features WHERE project_id = ?', [$id]);
    }
    // Urutan v1.2.1: setelah pertanyaan → Teknologi. Status tidak mundur bila peta fitur sudah pernah dibuka.
    Database::run("UPDATE projects SET status = IF(status = 'peta_fitur', status, 'pertanyaan') WHERE id = ?", [$id]);
});

json_response(['ok' => true]);
