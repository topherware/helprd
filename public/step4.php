<?php
require __DIR__ . '/../src/helpers.php';

$project = require_project(4);

// Status PRD untuk tombol utama: belum ada → "Buat PRD"; sesuai peta fitur sekarang → "Buka PRD"; peta/Bagian B berubah → "Buat ulang PRD".
$latest = Documents::latestPrd((int) $project['id']);
$prdState = !$latest ? 'none' : ($latest[1] === Documents::hash(project_features((int) $project['id']), $project) ? 'fresh' : 'stale');

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
        <button type="button" class="btn btn-ghost btn-sm" id="addFeature">
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>Tambah fitur
        </button>
        <a class="btn btn-sm" id="prdGo" data-state="<?= $prdState ?>" href="workspace.php#/prd"><span id="prdGoLabel"><?= ['none' => 'Buat PRD', 'fresh' => 'Buka PRD', 'stale' => 'Buat ulang PRD'][$prdState] ?></span>
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="m9 6 6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <?= header_controls() ?>
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
        <span class="legend-hint">Klik fitur untuk mengubah. Peta ini dipakai untuk membuat PRD.</span>
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
            <strong id="edTitle">Ubah fitur</strong>
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
        <label class="field-label" for="edSubs">Sub fitur (satu per baris)</label>
        <textarea id="edSubs" rows="5" maxlength="900" required></textarea>
        <div class="usulan-field" id="edUsulan" hidden>
            <p class="muted small" id="edAlasan"></p>
            <label class="check-row"><input type="checkbox" id="edDisertakan"> Sertakan ke PRD</label>
        </div>
        <p class="form-error" id="edError" hidden></p>
        <div class="editor-actions">
            <button type="button" class="btn btn-ghost btn-sm btn-danger-ghost" id="edDelete">Hapus fitur</button>
            <button type="submit" class="btn btn-sm">Simpan</button>
        </div>
    </form>
</div>

<?php page_footer(['canvas.js', 'step4.js']);
