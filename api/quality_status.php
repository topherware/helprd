<?php
/**
 * Status audit Standar Kualitas v1.2 untuk popup Implemen: apakah PRD v1.2 & task siap, dan ringkasan laporan bila sudah ada.
 * Auditnya sendiri lewat api/quality_audit.php.
 */
const IS_API = true;
require __DIR__ . '/../src/helpers.php';

[$project] = api_request();
$id = (int) $project['id'];
$hash = Documents::hash(project_features($id), $project);
$prd = Documents::current($id, $hash)[0] ?? null;
$report = QualityReport::load($id, $hash);

json_response([
    'ok' => true,
    // PRD JSON v1.2 harus sesuai peta fitur & isian Bagian B sekarang; bila sudah berubah, PRD perlu dibuat ulang dulu.
    'prd_siap' => ($prd['versi'] ?? '') === '1.2',
    'task_ada' => (bool) Database::run('SELECT 1 FROM tasks WHERE project_id = ? LIMIT 1', [$id])->fetchColumn(),
    'sisa_panggilan' => max(0, Documents::callsLeft($id)),
    'laporan' => $report ? [
        'lulus' => $report['lulus'], 'skor' => $report['skor'], 'temuan' => count($report['temuan']),
        'gagal' => array_values(array_map(fn ($k) => $k['judul'], array_filter($report['checklist'], fn ($k) => $k['status'] === 'gagal'))),
    ] : null,
]);
