<?php
/**
 * Tambah fitur dari editor peta fitur. Body: {"nama": "...", "fase": 1-4, "sub_fitur": ["..."]}.
 * Asal "keputusan" (ditambahkan pengguna sendiri) dan langsung disertakan ke PRD.
 */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

[$project, $body] = api_request();
$id = (int) $project['id'];
$fields = Validator::featureUpdate(['nama' => $body['nama'] ?? '', 'fase' => $body['fase'] ?? 1]);
$subs = Validator::subFeatures($body['sub_fitur'] ?? []);

Database::transaction(function () use ($id, $fields, $subs) {
    $urutan = (int) Database::run('SELECT COALESCE(MAX(urutan), -1) + 1 FROM features WHERE project_id = ?', [$id])->fetchColumn();
    Database::run(
        "INSERT INTO features (project_id, nama, fase, ikon, asal, disertakan, urutan) VALUES (?, ?, ?, 'bintang', 'keputusan', 1, ?)",
        [$id, $fields['nama'], $fields['fase'], $urutan]
    );
    $featureId = Database::lastId();
    foreach ($subs as $i => $nama) {
        Database::run('INSERT INTO sub_features (feature_id, nama, urutan) VALUES (?, ?, ?)', [$featureId, $nama, $i]);
    }
});

json_response(['ok' => true, 'features' => project_features($id, true)]);
