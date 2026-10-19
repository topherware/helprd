<?php
require __DIR__ . '/../src/helpers.php';

$project = require_project(4);

page_header('Peta fitur', 4, $project, 'canvas-page');
?>
<header class="topbar topbar-full">
    <div class="crumbs">
        <a class="brand" href="index.php"><?= logo_svg() ?><span><?= e(APP_NAME) ?></span></a>
        <span class="crumb-sep">/</span>
        <span class="crumb" id="projectTitle"><?= e($project['judul']) ?></span>
    </div>
    <div class="topbar-actions">
        <a class="btn btn-ghost btn-sm" href="step3.php">Ubah jawaban</a>
        <button type="button" class="btn btn-ghost btn-sm" id="stackToggle" hidden>Tech stack</button>
        <a class="btn btn-sm" href="workspace.php#/prd">Lanjutkan
            <svg viewBox="0 0 24 24" width="16" height="16"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <?= theme_toggle() ?>
    </div>
</header>

<div class="viewport" id="viewport">
    <div class="world" id="world">
        <svg class="edges" id="edges" width="1" height="1" aria-hidden="true"></svg>
    </div>

    <div class="loading loading-overlay" id="mapLoading">
        <span class="spinner"></span>
        <p>AI sedang menyusun peta fitur…</p>
    </div>

    <div class="zoom-controls">
        <button type="button" data-zoom="in" aria-label="Perbesar"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
        <button type="button" data-zoom="out" aria-label="Perkecil"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M5 12h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
        <button type="button" data-zoom="fit" aria-label="Pas di layar">
            <svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
    </div>

    <div class="legend">
        <span><i class="dot dot-direncanakan"></i>Direncanakan</span>
        <span><i class="dot dot-dikerjakan"></i>Dikerjakan</span>
        <span><i class="dot dot-selesai"></i>Selesai</span>
        <span class="legend-hint">Klik fitur untuk mengubah</span>
    </div>

    <aside class="panel stack-panel" id="stackPanel" hidden>
        <div class="panel-head">
            <strong id="stackTitle">Tech stack</strong>
            <button type="button" class="icon-btn" data-close aria-label="Tutup"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
        </div>
        <p id="stackBody"></p>
    </aside>

    <form class="panel editor" id="editor" hidden>
        <div class="panel-head">
            <strong>Ubah fitur</strong>
            <button type="button" class="icon-btn" data-close aria-label="Tutup"><svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></button>
        </div>
        <label class="field-label" for="edNama">Nama</label>
        <input type="text" id="edNama" maxlength="100" required>
        <label class="field-label" for="edFase">Fase prioritas</label>
        <select id="edFase">
            <option value="1">Fase 1 · Inti</option>
            <option value="2">Fase 2 · Penting</option>
            <option value="3">Fase 3 · Pengembangan</option>
            <option value="4">Fase 4 · Tambahan</option>
        </select>
        <label class="field-label" for="edStatus">Status</label>
        <select id="edStatus">
            <option value="direncanakan">Direncanakan</option>
            <option value="dikerjakan">Dikerjakan</option>
            <option value="selesai">Selesai</option>
        </select>
        <div class="usulan-field" id="edUsulan" hidden>
            <p class="muted small" id="edAlasan"></p>
            <label class="check-row"><input type="checkbox" id="edDisertakan"> Sertakan ke PRD</label>
        </div>
        <p class="form-error" id="edError" hidden></p>
        <button type="submit" class="btn btn-sm">Simpan</button>
    </form>
</div>

<?php page_footer(['canvas.js', 'step4.js']);
