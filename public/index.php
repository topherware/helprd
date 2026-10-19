<?php
require __DIR__ . '/../src/helpers.php';

$error = null;
$ide = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ide = (string) ($_POST['ide'] ?? '');
    try {
        // Kiriman melebihi post_max_size: PHP mengosongkan $_POST & $_FILES, jadi beri pesan ukuran, bukan "sesi kedaluwarsa".
        if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            throw new UserError('Lampiran terlalu besar. Maksimal ' . ATTACH_MAX_FILES . ' berkas, ' . (ATTACH_MAX_BYTES / 1048576) . ' MB per berkas.');
        }
        if (!csrf_valid($_POST['csrf'] ?? null)) {
            throw new UserError('Sesi kedaluwarsa. Coba kirim lagi.');
        }
        $ide = Validator::ide($ide);
        $files = Validator::lampiran($_FILES['lampiran'] ?? null);
        $token = bin2hex(random_bytes(32));
        Database::transaction(function () use ($token, $ide, $files) {
            Database::run(
                'INSERT INTO projects (session_token, owner_token, judul, ide_awal) VALUES (?, ?, ?, ?)',
                [$token, owner_token(true), auto_title($ide), $ide]
            );
            save_attachments(Database::lastId(), $files);
        });
        set_project_cookie($token);
        redirect('step3.php');
    } catch (UserError $e) {
        $error = $e->getMessage();
    }
}

$previous = current_project();
$plans = owner_projects($previous);
// Folder bawaan "Umum" (id 0 = folder_id NULL) selalu pertama, lalu folder buatan pengguna.
$folders = array_merge([['id' => 0, 'nama' => 'Umum']], owner_folders());

page_header('Mau bikin apa?', 1, $previous, 'has-plans');
$icon = fn (string $path, int $size = 18) => '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
$steps = ['teknologi' => 'Pertanyaan', 'pertanyaan' => 'Teknologi', 'peta_fitur' => 'Workspace'];
$dots = $icon('<circle cx="5" cy="12" r="1.2"/><circle cx="12" cy="12" r="1.2"/><circle cx="19" cy="12" r="1.2"/>', 16);
?>
<script>try { if (localStorage.getItem('planku.plans') === '0') document.body.classList.add('plans-closed'); } catch (e) { /* penyimpanan tidak tersedia */ }</script>
<aside class="plans" id="plans" aria-label="Semua Plan">
    <div class="plans-head">
        <strong>Semua Plan</strong>
        <span class="plans-head-actions">
            <button type="button" class="plans-new-folder" id="folderNew" title="Folder baru" aria-label="Folder baru"><?= $icon('<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2zM12 11v5M9.5 13.5h5"/>') ?></button>
            <a class="plans-new" href="index.php" id="planNew" title="Buat plan baru" aria-label="Buat plan baru"><?= $icon('<rect x="4" y="4" width="16" height="16" rx="4"/><path d="M12 8v8M8 12h8"/>') ?></a>
            <button type="button" class="plans-close" id="plansClose" title="Tutup sidebar" aria-label="Tutup sidebar" aria-controls="plans"><?= $icon('<rect x="3" y="4" width="18" height="16" rx="3"/><path d="M9 4v16M15 10l-2 2 2 2"/>') ?></button>
        </span>
    </div>
    <div class="plans-tools">
        <label class="plans-search"><?= $icon('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>', 16) ?>
            <input type="search" id="planSearch" placeholder="Cari plan…" aria-label="Cari plan"></label>
        <button type="button" class="plans-sort" id="planSort" data-sort="baru" title="Urutkan: terbaru" aria-label="Urutkan: terbaru"><?= $icon('<path d="M4 6h10M4 12h7M4 18h4M17 4v16M14 17l3 3 3-3"/>') ?></button>
    </div>
    <ul class="plans-list" id="planList">
        <?php foreach ($folders as $f):
            $items = array_values(array_filter($plans, fn ($p) => (int) $p['folder_id'] === (int) $f['id'])); ?>
            <li class="folder is-open" data-folder="<?= (int) $f['id'] ?>">
                <div class="folder-head">
                    <button type="button" class="folder-toggle" aria-expanded="true">
                        <?= $icon('<path d="m9 6 6 6-6 6"/>', 14) ?>
                        <?= $icon('<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>', 17) ?>
                        <?php if ($f['id']): ?><span class="folder-name truncate"><?= e($f['nama']) ?></span>
                        <?php else: ?><span class="folder-name-default truncate">Umum</span><?php endif; ?>
                        <span class="plan-count"><?= count($items) ?></span>
                    </button>
                    <?php if ($f['id']): ?><button type="button" class="row-menu" data-menu="folder" title="Opsi folder" aria-label="Opsi folder" aria-haspopup="menu"><?= $dots ?></button><?php endif; ?>
                </div>
                <ul class="folder-plans">
                    <?php foreach ($items as $p):
                        $active = $previous && (int) $previous['id'] === (int) $p['id']; ?>
                        <li class="plan" draggable="true" data-id="<?= (int) $p['id'] ?>" data-judul="<?= e(mb_strtolower($p['judul'])) ?>" data-waktu="<?= strtotime($p['created_at']) ?>">
                            <a class="plan-item<?= $active ? ' is-active' : '' ?>" draggable="false" href="open.php?id=<?= (int) $p['id'] ?>&amp;csrf=<?= e(csrf_token()) ?>"
                                title="<?= e($p['judul'] . ($p['fitur'] ? " · {$p['fitur']} fitur" : '')) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                                <span class="plan-title truncate"><?= e($p['judul']) ?></span><span class="plan-step"><?= $steps[$p['status']] ?? 'Ide' ?></span>
                            </a>
                            <button type="button" class="row-menu" data-menu="plan" title="Opsi plan" aria-label="Opsi plan" aria-haspopup="menu"><?= $dots ?></button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="folder-empty">Seret plan ke sini</p>
            </li>
        <?php endforeach; ?>
    </ul>
    <p class="plans-empty muted" id="planEmpty"<?= $plans ? ' hidden' : '' ?>><?= $plans ? 'Tidak ada plan yang cocok.' : 'Belum ada plan. Tulis ide pertamamu di sebelah kanan.' ?></p>
</aside>
<div class="plans-scrim" id="plansScrim" hidden></div>
<button type="button" class="btn btn-ghost btn-sm plans-show" id="plansShow" title="Buka sidebar" aria-controls="plans"><?= $icon('<rect x="3" y="4" width="18" height="16" rx="3"/><path d="M9 4v16M13 10l2 2-2 2"/>', 16) ?>Semua plan</button>
<canvas class="dots-bg" id="dots" aria-hidden="true"></canvas>
<main class="page hero">
    <button type="button" class="btn btn-ghost btn-sm plans-open" id="plansOpen" aria-controls="plans"><?= $icon('<path d="M4 6h16M4 12h16M4 18h16"/>', 16) ?>Semua plan</button>
    <h1>Mau bikin apa?</h1>
    <p class="lead">Ubah ide kamu jadi peta fitur yang jelas, tersusun per prioritas, dan siap dikerjakan.</p>

    <form method="post" class="composer" id="ideForm" enctype="multipart/form-data"
        data-max-files="<?= ATTACH_MAX_FILES ?>" data-max-bytes="<?= ATTACH_MAX_BYTES ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label for="ide" class="sr-only">Ide aplikasi atau website</label>
        <textarea id="ide" name="ide" rows="3" maxlength="3000" required minlength="10" autofocus
            placeholder="Ceritakan ide aplikasi atau website kamu… misalnya: website pemesanan catering untuk acara kantor"><?= e($ide) ?></textarea>
        <ul class="attach-list" id="attachList" hidden></ul>
        <?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <div class="composer-bar">
            <div class="composer-tools">
                <label class="tool-btn" title="Lampirkan gambar atau dokumen referensi">
                    <input type="file" id="lampiran" name="lampiran[]" multiple class="sr-only" aria-label="Lampirkan gambar atau dokumen referensi"
                        accept="image/png,image/jpeg,image/webp,image/gif,application/pdf,text/plain,text/markdown,.md,.txt">
                    <?= $icon('<path d="m21 11.5-8.6 8.6a5.5 5.5 0 0 1-7.8-7.8l8.6-8.6a3.7 3.7 0 0 1 5.2 5.2l-8.6 8.6a1.8 1.8 0 0 1-2.6-2.6l7.9-7.9"/>') ?>
                </label>
                <button type="button" class="tool-btn" id="micBtn" title="Bicara untuk mengetik" aria-label="Bicara untuk mengetik" aria-pressed="false" hidden>
                    <?= $icon('<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/>') ?>
                </button>
                <span class="composer-note" id="composerNote" aria-live="polite"></span>
            </div>
            <button type="submit" class="btn btn-icon" aria-label="Lanjut">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
    </form>

    <?php if ($previous): ?>
        <a class="link-muted" href="<?= step_url(max_step($previous)) ?>">
            <svg viewBox="0 0 24 24" width="16" height="16"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            Lanjutkan rencana terakhir: <strong><?= e($previous['judul']) ?></strong>
        </a>
    <?php endif; ?>
</main>
<?php page_footer(['dots.js', 'plans.js', 'composer.js']);
