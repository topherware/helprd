<?php
const IS_API = true;
require __DIR__ . '/../src/helpers.php';
set_time_limit(660);

[$project] = api_request();
if (max_step($project) < 4) {
    throw new UserError('Jawab pertanyaan klarifikasi dulu.', 409);
}

$id = (int) $project['id'];
$features = project_features($id, true);

// Peta fitur hanya dibuat sekali; dibuat ulang hanya bila jawaban berubah (lihat save_answers.php).
if (!$features) {
    ensure_reference($project); // bila ringkasan lampiran gagal saat pertanyaan dibuat, dicoba lagi di sini
    $raw = ai_generate('generate_features', $project, Prompts::features($project, project_qa($id)), MockData::features($project));
    $data = Validator::aiFeatures($raw);

    Database::transaction(function () use ($id, $project, $data) {
        Database::run('SELECT id FROM projects WHERE id = ? FOR UPDATE', [$id]);
        if (Database::run('SELECT COUNT(*) FROM features WHERE project_id = ?', [$id])->fetchColumn()) {
            return; // sudah dibuat oleh request lain
        }
        foreach ($data['fitur'] as $i => $f) {
            // Fitur usulan AI (tanpa asal dari input pengguna) tidak disertakan sampai pengguna menyalakannya.
            Database::run(
                'INSERT INTO features (project_id, nama, fase, ikon, asal, asal_alasan, disertakan, urutan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$id, $f['nama'], $f['fase'], $f['ikon'], $f['asal'], $f['asal_alasan'] ?: null, $f['asal'] === 'usulan_ai' ? 0 : 1, $i]
            );
            $featureId = Database::lastId();
            foreach ($f['sub_fitur'] as $j => $sub) {
                Database::run('INSERT INTO sub_features (feature_id, nama, urutan) VALUES (?, ?, ?)', [$featureId, $sub, $j]);
            }
        }
        Database::run(
            'UPDATE projects SET judul = ?, rekomendasi_stack = ? WHERE id = ?',
            [
                // Judul dari AI hanya menggantikan judul otomatis dari ide; judul yang sudah diganti pengguna di sidebar tetap.
                $data['judul'] !== '' && $project['judul'] === auto_title($project['ide_awal']) ? $data['judul'] : $project['judul'],
                $project['tech_mode'] === 'ai' ? $data['rekomendasi_stack'] : null,
                $id,
            ]
        );
    });
    $features = project_features($id, true);
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
