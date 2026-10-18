<?php
const IS_API = true;
require __DIR__ . '/../src/helpers.php';
set_time_limit(660);

[$project] = api_request();
if (max_step($project) < 4) {
    throw new UserError('Jawab pertanyaan klarifikasi dulu.', 409);
}

$id = (int) $project['id'];
$features = project_features($id);

// Peta fitur hanya dibuat sekali; dibuat ulang hanya bila jawaban berubah (lihat save_answers.php).
if (!$features) {
    $raw = ai_generate('generate_features', $project, Prompts::features($project, project_qa($id)), MockData::features($project));
    $data = Validator::aiFeatures($raw);

    Database::transaction(function () use ($id, $project, $data) {
        Database::run('SELECT id FROM projects WHERE id = ? FOR UPDATE', [$id]);
        if (Database::run('SELECT COUNT(*) FROM features WHERE project_id = ?', [$id])->fetchColumn()) {
            return; // sudah dibuat oleh request lain
        }
        foreach ($data['fitur'] as $i => $f) {
            Database::run(
                'INSERT INTO features (project_id, nama, fase, ikon, urutan) VALUES (?, ?, ?, ?, ?)',
                [$id, $f['nama'], $f['fase'], $f['ikon'], $i]
            );
            $featureId = Database::lastId();
            foreach ($f['sub_fitur'] as $j => $sub) {
                Database::run('INSERT INTO sub_features (feature_id, nama, urutan) VALUES (?, ?, ?)', [$featureId, $sub, $j]);
            }
        }
        Database::run(
            'UPDATE projects SET judul = ?, rekomendasi_stack = ? WHERE id = ?',
            [
                $data['judul'] !== '' ? $data['judul'] : $project['judul'],
                $project['tech_mode'] === 'ai' ? $data['rekomendasi_stack'] : null,
                $id,
            ]
        );
    });
    $features = project_features($id);
    $project = current_project();
}

json_response([
    'ok' => true,
    'project' => [
        'judul' => $project['judul'],
        'tech_mode' => $project['tech_mode'],
        'tech_stack' => $project['tech_mode'] === 'manual' ? $project['tech_stack_pilihan'] : $project['rekomendasi_stack'],
    ],
    'features' => $features,
]);
