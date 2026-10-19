<?php
/**
 * Ubah fitur: nama, fase, sub_fitur, disertakan, deskripsi, tujuan, selesai_bila, dan status (status hanya bila fitur belum punya task;
 * setelah ada task, status dihitung dari task). Dipakai peta fitur langkah 4 dan Roadmap workspace.
 */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

[$project, $body] = api_request();
$fields = Validator::featureUpdate($body);

$featureId = (int) ($body['id'] ?? 0);
$exists = Database::run('SELECT id FROM features WHERE id = ? AND project_id = ?', [$featureId, $project['id']])->fetch();
if (!$exists) {
    throw new UserError('Fitur tidak ditemukan.', 404);
}
if (isset($fields['status']) && Database::run('SELECT 1 FROM tasks WHERE feature_id = ? LIMIT 1', [$featureId])->fetchColumn()) {
    unset($fields['status']); // status dihitung dari task
}

if (array_key_exists('sub_fitur', $body)) {
    // Sinkronkan sub fitur menurut nama: yang tetap dipertahankan (beserta task-nya), yang baru ditambah, yang hilang dihapus.
    $names = Validator::subFeatures($body['sub_fitur']);
    Database::transaction(function () use ($featureId, $names) {
        $current = Database::run('SELECT id, nama FROM sub_features WHERE feature_id = ?', [$featureId])->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($current as $subId => $nama) {
            if (!in_array($nama, $names, true)) {
                Database::run('DELETE FROM sub_features WHERE id = ?', [$subId]);
            }
        }
        foreach ($names as $i => $nama) {
            $subId = array_search($nama, $current, true);
            if ($subId === false) {
                Database::run('INSERT INTO sub_features (feature_id, nama, urutan) VALUES (?, ?, ?)', [$featureId, $nama, $i]);
            } else {
                Database::run('UPDATE sub_features SET urutan = ? WHERE id = ?', [$i, $subId]);
            }
        }
    });
}

if ($fields) {
    // Nama kolom aman: hanya kunci yang lolos Validator::featureUpdate.
    $set = implode(', ', array_map(fn ($column) => "$column = ?", array_keys($fields)));
    Database::run("UPDATE features SET $set WHERE id = ?", [...array_values($fields), $featureId]);
}
if (isset($fields['fase'])) {
    Database::run('UPDATE tasks SET phase = ? WHERE feature_id = ?', [$fields['fase'], $featureId]);
}

json_response(['ok' => true, 'features' => project_features((int) $project['id'], true)]);
