<?php
require __DIR__ . '/../src/helpers.php';

$project = require_project(3);
$error = null;
$v = [
    'design_style' => $project['design_style'] ?? 'bebas',
    'design_custom' => $project['design_custom'] ?? '',
    'stack_choice' => $project['stack_choice'] ?? 'bebas',
    'stack_custom' => $project['stack_custom'] ?? '',
    'ui_only' => (int) ($project['ui_only'] ?? 0),
    'ai_workflow' => $project['ai_workflow'] ?? 'fase',
    'extra_notes' => $project['extra_notes'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $v = array_merge($v, array_map(fn ($x) => is_string($x) ? $x : '', array_intersect_key($_POST, $v)));
    $v['ui_only'] = empty($_POST['ui_only']) ? 0 : 1;
    try {
        if (!csrf_valid($_POST['csrf'] ?? null)) {
            throw new UserError('Sesi kedaluwarsa. Coba kirim lagi.');
        }
        $fields = Validator::brief($_POST);

        // Sejak v1.2.1 teknologi dipilih SETELAH pertanyaan: jawaban tetap, hanya peta fitur yang dibuat ulang bila stack berubah.
        $answered = (bool) Database::run('SELECT 1 FROM answers a JOIN questions q ON q.id = a.question_id WHERE q.project_id = ? LIMIT 1', [$project['id']])->fetchColumn();
        Database::transaction(function () use ($project, $fields, $answered) {
            // Stack atau mode hanya-UI berubah: peta fitur dibuat ulang. Isian lain cukup menandai PRD basi.
            $changed = $project['tech_mode'] !== null
                && ($project['stack_choice'] !== $fields['stack_choice'] || (string) $project['stack_custom'] !== (string) $fields['stack_custom']
                    || (int) $project['ui_only'] !== $fields['ui_only']);
            if ($changed) {
                Database::run('DELETE FROM features WHERE project_id = ?', [$project['id']]);
            }
            $status = $answered ? 'peta_fitur' : $project['status'];
            $sets = implode(', ', array_map(fn ($k) => "$k = ?", array_keys($fields)));
            Database::run("UPDATE projects SET $sets, status = ? WHERE id = ?", [...array_values($fields), $status, $project['id']]);
        });
        redirect($answered ? 'step4.php' : 'step3.php');
    } catch (UserError $e) {
        $error = $e->getMessage();
    }
}

$icon = fn (string $path) => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
$check = $icon('<path d="m5 12 5 5 9-10"/>');

/** Satu kartu radio: label, keterangan, lencana, centang saat aktif. */
$option = function (string $name, string $value, string $label, string $desc, string $badge, string $current) use ($check): string {
    return '<label class="opt"><input type="radio" name="' . $name . '" value="' . e($value) . '"' . ($current === $value ? ' checked' : '') . '>'
        . '<span class="opt-check">' . $check . '</span><strong>' . e($label) . '</strong><span class="opt-desc">' . e($desc) . '</span>'
        . ($badge !== '' ? '<span class="opt-badge">' . e($badge) . '</span>' : '') . '</label>';
};

page_header('Desain, Teknologi, & Cara Kerja AI', 3, $project, '',
    '<a class="btn btn-ghost btn-sm" href="step3.php">Kembali</a><button type="submit" class="btn btn-sm" form="briefForm">Buat Peta Fitur</button>');
?>
<main class="page page-wide">
    <p class="part-chip">Bagian B</p>
    <h1>Desain, Teknologi, &amp; Cara Kerja AI</h1>
    <p class="lead">Pilihan ini dipakai sebagai acuan PRD: design system, tech stack, skema database, dan aturan kerja AI coding assistant.</p>

    <form method="post" id="briefForm" novalidate>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <section class="b-card" aria-labelledby="b1">
            <h2 id="b1"><span class="b-num">1</span>Gaya Desain UI/UX</h2>
            <p class="muted">Menentukan arahan visual yang ditulis di bab Design System PRD.</p>
            <div class="opt-grid">
                <?php foreach (Brief::DESIGN as $id => [$label, $desc, $badge]) {
                    echo $option('design_style', $id, $label, $desc, $badge, $v['design_style']);
                } ?>
            </div>
            <div class="custom-field" data-for="design_style">
                <label class="field-label" for="design_custom">Jelaskan gaya desain idamanmu</label>
                <textarea id="design_custom" name="design_custom" rows="3" maxlength="1000"
                    placeholder="Contoh: nuansa hijau daun dan krem, font membulat, terinspirasi situs kafe lokal"><?= e($v['design_custom']) ?></textarea>
            </div>
        </section>

        <section class="b-card" aria-labelledby="b2">
            <h2 id="b2"><span class="b-num">2</span>Tech Stack &amp; Arsitektur</h2>
            <p class="muted">Fondasi teknologi untuk skema database, API, dan struktur folder.</p>
            <label class="switch-row">
                <span><strong>Hanya UI / frontend (tanpa backend &amp; database)</strong>
                    <span class="muted">Nyalakan bila kamu hanya butuh desain atau prototipe tampilan.</span></span>
                <input type="checkbox" name="ui_only" value="1" role="switch" <?= $v['ui_only'] ? 'checked' : '' ?>>
                <span class="switch" aria-hidden="true"></span>
            </label>
            <div class="opt-grid">
                <?= $option('stack_choice', 'bebas', 'Bebas (AI Auto-Pilot)', 'AI memilih framework dan database paling cocok, lalu menguncinya di PRD.', 'Default', $v['stack_choice']) ?>
                <?php foreach (Brief::stackConfig()['stacks'] as $s) {
                    echo $option('stack_choice', $s['id'], $s['nama'], $s['ringkas'] . '.', '', $v['stack_choice']);
                } ?>
                <?= $option('stack_choice', 'tulis_sendiri', 'Tulis Sendiri', 'Kombinasi framework, database, atau aplikasi mobile pilihan kamu.', '', $v['stack_choice']) ?>
            </div>
            <div class="custom-field" data-for="stack_choice">
                <label class="field-label" for="stack_custom">Tech stack pilihanmu</label>
                <input type="text" id="stack_custom" name="stack_custom" maxlength="500" value="<?= e($v['stack_custom']) ?>"
                    placeholder="Contoh: Laravel + MySQL, frontend Blade + Tailwind">
            </div>
        </section>

        <section class="b-card" aria-labelledby="b3">
            <h2 id="b3"><span class="b-num">3</span>Cara Kerja AI Coding Assistant</h2>
            <p class="muted">Menentukan aturan checkpoint di bab Milestone dan Master Prompt.</p>
            <div class="opt-grid">
                <?php foreach (Brief::WORKFLOW as $id => [$label, $desc, $badge]) {
                    echo $option('ai_workflow', $id, $label, $desc, $badge, $v['ai_workflow']);
                } ?>
            </div>
        </section>

        <section class="b-card" aria-labelledby="b4">
            <div class="b-head-row">
                <h2 id="b4"><span class="b-num">4</span>Catatan Tambahan</h2>
                <span class="muted small" id="notesCount"><?= mb_strlen($v['extra_notes']) ?>/<?= Brief::NOTES_MAX ?></span>
            </div>
            <p class="muted">Fitur tambahan, aturan bisnis unik, batasan, atau detail yang belum disebut.</p>
            <textarea id="extra_notes" name="extra_notes" rows="4" maxlength="<?= Brief::NOTES_MAX ?>" aria-describedby="notesCount"
                placeholder="Contoh: kupon diskon persentase, booking maksimal 3 jam per sesi, audit log untuk setiap perubahan status pesanan"><?= e($v['extra_notes']) ?></textarea>
        </section>

        <?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>

    </form>
</main>
<?php page_footer(['step2.js']);
