<?php
require __DIR__ . '/../src/helpers.php';

$error = null;
$ide = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ide = (string) ($_POST['ide'] ?? '');
    try {
        if (!csrf_valid($_POST['csrf'] ?? null)) {
            throw new UserError('Sesi kedaluwarsa. Coba kirim lagi.');
        }
        $ide = Validator::ide($ide);
        $token = bin2hex(random_bytes(32));
        $judul = mb_strimwidth(preg_replace('/\s+/u', ' ', $ide), 0, 60, '…');
        Database::run(
            'INSERT INTO projects (session_token, owner_token, judul, ide_awal) VALUES (?, ?, ?, ?)',
            [$token, owner_token(true), $judul, $ide]
        );
        set_project_cookie($token);
        redirect('step2.php');
    } catch (UserError $e) {
        $error = $e->getMessage();
    }
}

$previous = current_project();
$plans = owner_projects($previous);
$examples = [
    'Website pemesanan catering untuk acara kantor',
    'Aplikasi absensi siswa untuk sekolah kecil',
    'Toko online baju muslim dengan pre-order',
];
// Ikon contoh ide (garis 24×24): kalender acara, daftar centang, tas belanja.
$exampleIcons = [
    '<rect x="3.5" y="5" width="17" height="15" rx="3"/><path d="M8 3v4M16 3v4M3.5 10h17"/>',
    '<path d="M9 6h11M9 12h11M9 18h11"/><path d="m3.5 6 1.5 1.5L7.5 5M3.5 12l1.5 1.5L7.5 11M3.5 18l1.5 1.5L7.5 17"/>',
    '<path d="M5 8h14l-1.2 11.1a2 2 0 0 1-2 1.9H8.2a2 2 0 0 1-2-1.9z"/><path d="M9 8V6.5a3 3 0 0 1 6 0V8"/>',
];

page_header('Mau bikin apa?', 1, $previous, 'has-plans');
$icon = fn (string $path, int $size = 18) => '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
$steps = ['teknologi' => 'Teknologi', 'pertanyaan' => 'Pertanyaan', 'peta_fitur' => 'Workspace'];
?>
<script>try { if (localStorage.getItem('planku.plans') === '0') document.body.classList.add('plans-closed'); } catch (e) { /* penyimpanan tidak tersedia */ }</script>
<aside class="plans" id="plans" aria-label="Semua Plan">
    <div class="plans-head">
        <strong>Semua Plan</strong>
        <span class="plans-head-actions">
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
        <?php foreach ($plans as $i => $p):
            $open = $i < 4 || ($previous && (int) $previous['id'] === (int) $p['id']);
            $active = $previous && (int) $previous['id'] === (int) $p['id']; ?>
            <li class="plan-group<?= $open ? ' is-open' : '' ?>" data-id="<?= (int) $p['id'] ?>" data-judul="<?= e(mb_strtolower($p['judul'])) ?>" data-waktu="<?= strtotime($p['created_at']) ?>">
                <button type="button" class="plan-folder" aria-expanded="<?= $open ? 'true' : 'false' ?>">
                    <?= $icon('<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>') ?>
                    <span class="truncate"><?= e($p['judul']) ?></span>
                    <span class="plan-count">1</span>
                    <?= $icon('<path d="m9 6 6 6-6 6"/>', 16) ?>
                </button>
                <button type="button" class="plan-delete" title="Hapus plan" aria-label="Hapus plan <?= e($p['judul']) ?>"><?= $icon('<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>', 16) ?></button>
                <a class="plan-item<?= $active ? ' is-active' : '' ?>" href="open.php?id=<?= (int) $p['id'] ?>&amp;csrf=<?= e(csrf_token()) ?>"
                    title="<?= e($p['judul'] . ' · ' . ($steps[$p['status']] ?? 'Ide') . ($p['fitur'] ? " · {$p['fitur']} fitur" : '')) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                    <span class="muted">Plan 1</span><span class="truncate"><?= e($p['judul']) ?></span>
                </a>
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

    <form method="post" class="composer" id="ideForm">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label for="ide" class="sr-only">Ide aplikasi atau website</label>
        <textarea id="ide" name="ide" rows="5" maxlength="3000" required minlength="10" autofocus
            placeholder="Ceritakan ide aplikasi atau website kamu… misalnya: website pemesanan catering untuk acara kantor"><?= e($ide) ?></textarea>
        <?php if ($error): ?><p class="form-error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <div class="composer-bar">
            <span class="composer-hint"><kbd>Ctrl</kbd><kbd>Enter</kbd> untuk lanjut</span>
            <button type="submit" class="btn btn-icon" aria-label="Lanjut">
                <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
        </div>
    </form>

    <div class="examples" aria-label="Contoh ide">
        <?php foreach ($examples as $n => $ex): ?>
            <button type="button" class="chip" data-example="<?= e($ex) ?>"><?= $icon($exampleIcons[$n], 17) ?><?= e($ex) ?></button>
        <?php endforeach; ?>
    </div>

    <?php if ($previous): ?>
        <a class="link-muted" href="<?= step_url(max_step($previous)) ?>">
            <svg viewBox="0 0 24 24" width="16" height="16"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 7v5l3 2" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            Lanjutkan rencana terakhir: <strong><?= e($previous['judul']) ?></strong>
        </a>
    <?php endif; ?>
</main>
<?php page_footer(['dots.js', 'plans.js']);
