<?php
/**
 * Teks prompt untuk AI agent (Claude Code, Cursor, dll.). Prompt berdiri sendiri: tidak ada CLI, token,
 * atau kunci apa pun di dalamnya. PRD v1.2 memakai Master Prompt bab 13; PRD lama memakai prompt rakitan di bawah.
 */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

[$project] = api_request();
$id = (int) $project['id'];
$features = Documents::exportData($id);
$prdJson = Documents::latestPrd($id)[0] ?? [];

// PRD v1.2: Master Prompt bab 13 dipakai apa adanya (sudah memuat aturan checkpoint sesuai Cara Kerja AI).
if (($prdJson['milestone']['master_prompt'] ?? '') !== '') {
    json_response(['ok' => true, 'prompt' => $prdJson['milestone']['master_prompt']]);
}

$summary = trim($prdJson['overview']['deskripsi'] ?? '') ?: $project['ide_awal'];
$stack = array_map(fn ($r) => "- {$r['lapisan']}: {$r['pilihan']}", $prdJson['tech_stack'] ?? []);
if (!$stack) {
    $stack = ['- ' . ($project['tech_mode'] === 'manual' ? $project['tech_stack_pilihan'] : ($project['rekomendasi_stack'] ?: 'lihat PRD.md bagian 7'))];
}
$order = array_map(fn ($i, $f) => ($i + 1) . ". [Fase {$f['fase']}] {$f['nama']} — " . Documents::featureFilename($i, $f), array_keys($features), $features);
$taskCount = array_sum(array_map(fn ($f) => count($f['tasks']), $features));

$prompt = implode("\n", [
    "Kamu akan membangun \"{$project['judul']}\" berdasarkan dokumen perencanaan dari PlanKu.",
    '',
    'Ringkasan produk:',
    preg_replace('/\*\*/', '', $summary),
    '',
    'Tech stack:',
    ...$stack,
    '',
    'Sebelum mulai:',
    '1. Baca PRD.md dan semua file di folder features/ (dari ZIP PlanKu yang sudah diletakkan di root project ini).',
    '2. Baca TASKS.md: daftar ' . ($taskCount ?: 'semua') . ' task bercentang, dikelompokkan per fase dan per fitur.',
    '3. Bila file-file itu belum ada di project, berhenti dan minta saya mengunduh ZIP dari PlanKu dulu.',
    '',
    'Urutan fitur:',
    ...$order,
    '',
    'Aturan kerja:',
    '- Kerjakan per fase: selesaikan Fase 1 dulu, lalu Fase 2, dan seterusnya.',
    '- Kerjakan SATU task per giliran, mulai dari task pertama yang belum dicentang di TASKS.md.',
    '- Setelah sebuah task selesai, jalankan uji/build yang relevan. Bila lolos, centang task itu di TASKS.md ([ ] menjadi [x]); bila gagal, perbaiki dulu sebelum lanjut.',
    '- Ikuti "Selesai bila" dan persyaratan keamanan (SEC-xx di PRD bagian 8) untuk fitur yang sedang dikerjakan.',
    '- Simpan kunci API dan rahasia hanya di variabel lingkungan; jangan pernah menaruhnya di kode atau repositori.',
    '- Bila PRD ambigu, bertentangan, atau butuh keputusan bisnis, berhenti dan tanyakan kepada saya, jangan menebak.',
    '- Setelah tiap task, laporkan singkat: apa yang dibuat, file yang berubah, dan cara mengujinya.',
    '',
    'Mulai sekarang dengan membaca dokumen di atas, lalu kerjakan task pertama yang belum dicentang.',
]);

json_response(['ok' => true, 'prompt' => $prompt]);
