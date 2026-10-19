<?php
require __DIR__ . '/../src/helpers.php';

$project = require_project(5);
$id = (int) $project['id'];
$features = project_features($id);
if (!$features) {
    redirect('step4.php');
}

// PRD yang ditampilkan: versi terbaru di prd_versions. Project lama yang PRD-nya dibuat lewat pipeline
// dokumen (sebelum workspace) ditampilkan dari tabel documents; bila sudah lengkap, dicatat sebagai versi pertama.
$prd = Database::run('SELECT content_md, source, selesai FROM prd_versions WHERE project_id = ? ORDER BY id DESC LIMIT 1', [$id])->fetch() ?: null;
$stale = false;
if (!$prd && ($latest = Documents::latestPrd($id))) {
    [$data, $hash] = $latest;
    $stale = $hash !== Documents::hash($features, $project);
    $prd = ['content_md' => Documents::renderPrd($features, $data), 'source' => 'ai', 'selesai' => empty($data['keamanan']) ? 0 : 1];
    if ($prd['selesai'] && !$stale) {
        Database::run("INSERT INTO prd_versions (project_id, content_md, source, selesai) VALUES (?, ?, 'ai', 1)", [$id, $prd['content_md']]);
    }
}

// Riwayat agent: 30 pesan terakhir; pesan lebih lama dimuat lewat api/agent_history.php.
$agentRows = Database::run('SELECT id, role, content FROM agent_messages WHERE project_id = ? ORDER BY id DESC LIMIT 31', [$id])->fetchAll();

// PRD basi bila peta fitur atau isian Bagian B berubah sejak PRD JSON terakhir dibuat.
if ($prd && !$stale && ($latest = Documents::latestPrd($id))) {
    $stale = $latest[1] !== Documents::hash($features, $project);
}

$wsData = [
    'judul' => $project['judul'],
    'prd' => $prd ? $prd['content_md'] : null,
    'prdSelesai' => $prd ? (bool) $prd['selesai'] : false,
    'prdBasi' => $stale,
    // Dari tombol "Buat ulang PRD" di peta fitur: PRD dibuat ulang otomatis bila memang basi.
    'regen' => !empty($_GET['regen']) && $stale,
    'taskCount' => (int) Database::run('SELECT COUNT(*) FROM tasks WHERE project_id = ?', [$id])->fetchColumn(),
    'agentMessages' => array_reverse(array_slice($agentRows, 0, 30)),
    'agentMore' => count($agentRows) > 30,
];

page_header('Workspace', 5, $project, 'ws');

$icon = fn (string $path, int $size = 18) => '<svg viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
$i = [
    'folder' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
    'share' => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
    'grid' => '<circle cx="7" cy="7" r="3"/><circle cx="17" cy="7" r="3"/><circle cx="7" cy="17" r="3"/><circle cx="17" cy="17" r="3"/>',
    'agent' => '<rect x="3" y="4" width="18" height="14" rx="3"/><path d="M8 21h8M12 8v2M10 12h4"/>',
    'map' => '<path d="m3 6 6-2 6 2 6-2v14l-6 2-6-2-6 2zM9 4v14M15 6v14"/>',
    'list' => '<path d="M10 6h10M10 12h10M10 18h10M4 6l1.5 1.5L8 5M4 12l1.5 1.5L8 11M4 18l1.5 1.5L8 17"/>',
    'doc' => '<path d="M6 3h9l4 4v14H6zM14 3v5h5M9 13h7M9 17h5"/>',
    'edit' => '<path d="M4 20h4L19 9l-4-4L4 16zM14 6l4 4"/>',
    'export' => '<path d="M6 3h9l4 4v14H6zM14 3v5h5M12 11v6M9.5 14.5 12 17l2.5-2.5"/>',
    'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    'close' => '<path d="M6 6l12 12M18 6 6 18"/>',
    'clip' => '<path d="m21 11-8.5 8.5a5 5 0 0 1-7-7L14 4a3.5 3.5 0 0 1 5 5l-8.5 8.5a2 2 0 0 1-3-3L15 7"/>',
    'mic' => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/>',
    'send' => '<path d="M12 19V5M5 12l7-7 7 7"/>',
    'history' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5M12 7v5l3 2"/>',
    'download' => '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
    'copy' => '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/>',
    'save' => '<path d="M5 3h11l3 3v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2zM8 3v5h7V3M8 21v-7h8v7"/>',
];
?>
<header class="ws-top">
    <button type="button" class="icon-btn ws-only-mobile" id="tocToggle" data-views="prd" aria-label="Daftar isi"><?= $icon($i['menu']) ?></button>
    <a class="brand" href="index.php"><?= brand_logo() ?><span><?= e(APP_NAME) ?></span></a>
    <span class="ws-dot" aria-hidden="true">·</span>
    <nav class="ws-crumbs" aria-label="Lokasi">
        <span class="ws-crumb"><?= $icon($i['folder'], 17) ?><span class="truncate" id="projectName"><?= e($project['judul']) ?></span></span>
        <span class="crumb-sep">/</span>
        <span class="ws-crumb truncate" id="viewName">PRD</span>
        <button type="button" class="icon-btn" disabled title="Berbagi belum tersedia"><?= $icon($i['share'], 17) ?></button>
    </nav>

    <div class="ws-actions">
        <a class="btn btn-ghost btn-sm" href="#/prd" data-views="roadmap kanban fitur" title="Lihat PRD"><?= $icon($i['doc'], 16) ?><span class="ws-hide-sm">PRD</span></a>
        <a class="btn btn-ghost btn-sm" href="#/roadmap" data-views="prd kanban fitur"><?= $icon($i['map'], 16) ?><span class="ws-hide-sm">Roadmap</span></a>
        <a class="icon-btn ws-icon-link" href="#/kanban" data-views="roadmap fitur" title="Daftar task (Kanban)"><?= $icon($i['list']) ?></a>
        <button type="button" class="btn btn-sm" id="bikinTask" data-views="prd" disabled title="Susun fitur, sub-fitur, dan task dari PRD"><?= $icon($i['grid'], 16) ?>Bikin Task</button>
        <button type="button" class="btn btn-sm" id="implemen" data-views="roadmap kanban fitur" disabled title="Download PRD/ZIP atau prompt AI agent">Implemen</button>
        <button type="button" class="ws-agent-btn" id="agentToggle" aria-controls="agent" aria-expanded="true" title="Workspace Agent"><?= $icon($i['agent']) ?></button>
        <?= header_controls() ?>
    </div>
</header>

<div class="ws-body">
    <nav class="ws-rail" data-views="prd" aria-label="Alat PRD">
        <button type="button" class="ws-rail-btn" id="editBtn" title="Edit PRD" aria-pressed="false"><?= $icon($i['edit']) ?></button>
        <span class="ws-rail-sep" aria-hidden="true"></span>
        <button type="button" class="ws-rail-btn" id="exportBtn" title="Export PRD" aria-haspopup="menu" aria-expanded="false"><?= $icon($i['export']) ?></button>
        <button type="button" class="ws-rail-btn" id="historyBtn" title="Riwayat versi" aria-haspopup="dialog" aria-expanded="false"><?= $icon($i['history']) ?></button>

        <div class="ws-menu" id="exportMenu" role="menu" hidden>
            <button type="button" role="menuitem" id="downloadMd"><?= $icon($i['download'], 16) ?>Download .md</button>
            <button type="button" role="menuitem" id="copyMd"><?= $icon($i['copy'], 16) ?>Salin teks</button>
        </div>
        <div class="ws-menu ws-history" id="historyMenu" role="dialog" aria-label="Riwayat versi PRD" hidden>
            <strong>Riwayat versi</strong>
            <ol id="historyList"></ol>
        </div>
    </nav>

    <aside class="ws-toc" id="toc" data-views="prd" aria-label="Daftar isi">
        <div id="tocBody"></div>
    </aside>

    <main class="ws-main" id="main">
        <section data-view="prd">
            <div class="prd-bar">
                <span class="prd-status" id="prdStatus" aria-live="polite"></span>
                <button type="button" class="btn btn-sm" id="prdRetry" hidden>Lanjutkan / coba lagi</button>
                <button type="button" class="btn btn-ghost btn-sm" id="prdRegen" hidden>Buat ulang PRD</button>
            </div>
            <p class="ws-banner" id="staleBanner" hidden>Peta fitur atau pilihan Teknologi sudah berubah sejak PRD ini dibuat. Klik "Buat ulang PRD" supaya PRD dan roadmap sesuai dengan peta fitur terbaru.</p>
            <article class="prose" id="prd"></article>
            <div class="prd-editor" id="prdEditor" hidden>
                <button type="button" class="btn btn-ghost btn-sm prd-save" id="prdSave"><?= $icon($i['save'], 16) ?>Simpan versi</button>
                <textarea id="prdText" spellcheck="false" aria-label="Markdown PRD"></textarea>
            </div>
        </section>
        <section data-view="roadmap" class="ws-roadmap" hidden>
            <div class="viewport rm-viewport" id="rmViewport">
                <div class="world" id="rmWorld">
                    <svg class="edges" id="rmEdges" width="1" height="1" aria-hidden="true"></svg>
                </div>
                <div class="loading loading-overlay" id="rmLoading" hidden></div>
                <div class="zoom-controls">
                    <button type="button" data-zoom="in" aria-label="Perbesar"><?= $icon('<path d="M12 5v14M5 12h14"/>', 16) ?></button>
                    <button type="button" data-zoom="out" aria-label="Perkecil"><?= $icon('<path d="M5 12h14"/>', 16) ?></button>
                    <button type="button" data-zoom="fit" aria-label="Pas di layar"><?= $icon('<path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>', 15) ?></button>
                </div>
            </div>
            <aside class="rm-panel" id="rmPanel" hidden></aside>
        </section>
        <section data-view="kanban" class="ws-kanban" hidden></section>
        <section data-view="fitur" class="ws-fitur" hidden></section>
    </main>

    <aside class="ws-agent" id="agent" aria-label="Workspace Agent">
        <div class="ws-agent-head">
            <div>
                <strong>Workspace Agent</strong>
                <span class="muted" id="agentContext">PRD · PRD aktif</span>
            </div>
            <button type="button" class="icon-btn" id="agentClose" aria-label="Tutup agent"><?= $icon($i['close']) ?></button>
        </div>
        <div class="ws-agent-body" id="agentBody">
            <button type="button" class="btn btn-ghost btn-sm agent-more" id="agentMore" hidden>Muat pesan sebelumnya</button>
            <div class="ws-agent-messages" id="agentMessages" aria-live="polite"></div>
            <div class="ws-agent-empty" id="agentEmpty">
                <strong>Tanya apa saja soal project ini.</strong>
                <p class="muted">Agent membaca PRD, peta fitur, dan progres task kamu. Coba salah satu:</p>
                <div class="agent-examples">
                    <button type="button" class="chip">Fitur apa yang harus dibangun lebih dulu?</button>
                    <button type="button" class="chip">Fitur mana yang paling berisiko?</button>
                    <button type="button" class="chip">Data pribadi apa saja yang disimpan aplikasi ini?</button>
                    <button type="button" class="chip">Ringkas progres task sekarang.</button>
                </div>
            </div>
        </div>
        <form class="ws-agent-input" id="agentForm">
            <textarea rows="3" id="agentInput" placeholder="Tanya soal project ini…" maxlength="2000" aria-label="Pesan untuk agent"></textarea>
            <div class="ws-agent-tools">
                <button type="button" class="icon-btn" disabled title="Lampiran belum tersedia"><?= $icon($i['clip'], 17) ?></button>
                <button type="button" class="icon-btn" disabled title="Mikrofon belum tersedia"><?= $icon($i['mic'], 17) ?></button>
                <span class="muted small">Enter kirim · Shift+Enter baris baru</span>
                <button type="submit" class="ws-send" id="agentSend" aria-label="Kirim"><?= $icon($i['send'], 16) ?></button>
            </div>
        </form>
    </aside>
    <div class="ws-scrim" id="scrim" hidden></div>
</div>
<div class="toast" id="toast" role="status" aria-live="polite" hidden></div>

<dialog class="modal impl-modal" id="implModal" aria-labelledby="implTitle">
    <button type="button" class="icon-btn modal-close" data-close-dialog aria-label="Tutup"><?= $icon($i['close']) ?></button>
    <h2 id="implTitle">Mulai implementasi</h2>
    <p class="muted">Pilih cara membawa rencana ini ke proses development.</p>
    <div class="impl-options">
        <button type="button" class="impl-option" id="implPrd">
            <span class="impl-icon"><?= $icon($i['download'], 20) ?></span>
            <span><strong>Download PRD</strong><span class="muted">Cuma dokumen PRD (.md).</span></span>
        </button>
        <button type="button" class="impl-option" id="implZip">
            <span class="impl-icon"><?= $icon('<path d="M4 7a2 2 0 0 1 2-2h4l2 2h6a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zM12 10v6M9.5 13.5 12 16l2.5-2.5"/>', 20) ?></span>
            <span><strong>Download ZIP</strong><span class="muted">PRD + spesifikasi fitur & task (.zip).</span></span>
        </button>
        <button type="button" class="impl-option" id="implAgent">
            <span class="impl-icon impl-icon-accent"><?= $icon('<rect x="5" y="8" width="14" height="11" rx="3"/><path d="M12 4v4M9 13h.01M15 13h.01M9.5 16h5M3 12v3M21 12v3"/>', 20) ?></span>
            <span><strong>Prompt AI agent</strong><span class="muted">Salin prompt untuk AI agent (Claude Code, Cursor, dll.) yang mengerjakan task satu per satu.</span></span>
        </button>
    </div>
</dialog>

<dialog class="modal impl-modal agent-modal" id="agentModal" aria-labelledby="agentModalTitle">
    <button type="button" class="icon-btn modal-close" data-close-dialog aria-label="Tutup"><?= $icon($i['close']) ?></button>
    <h2 id="agentModalTitle">Mulai implementasi dengan AI agent</h2>
    <p class="muted">Tempel prompt ini ke AI agent kamu. Agent akan membaca PRD, spesifikasi fitur, dan TASKS.md, lalu mengerjakan task satu per satu.</p>
    <ol class="agent-steps">
        <li>
            <span>Salin prompt lewat tombol di bawah.</span>
            <div class="code-box" id="promptBox">
                <div class="code-tools">
                    <button type="button" class="icon-btn" id="promptExpand" aria-label="Perbesar" title="Perbesar"><?= $icon('<path d="M14 4h6v6M10 20H4v-6M20 4l-7 7M4 20l7-7"/>', 16) ?></button>
                    <button type="button" class="btn btn-ghost btn-sm" id="promptCopy"><?= $icon($i['copy'], 15) ?>Copy</button>
                </div>
                <pre id="promptText">Memuat…</pre>
            </div>
        </li>
        <li><span>Buka AI agent kamu (Claude Code, Cursor, Codex, dll.) di folder project.</span></li>
        <li><span>Tempel prompt-nya lalu kirim. Agent mengerjakan task per fase dan mencentangnya di TASKS.md.</span></li>
    </ol>
    <p class="agent-note"><?= $icon('<path d="M12 3 2 20h20zM12 10v4M12 17h.01"/>', 16) ?>Unduh ZIP dulu dan letakkan isinya di folder project sebelum menempel prompt. Prompt ini tidak memuat kunci atau token apa pun.</p>
</dialog>

<script type="application/json" id="wsData"><?= json_encode($wsData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
<script src="vendor/marked.min.js"></script>
<script src="vendor/purify.min.js"></script>
<?php page_footer(['canvas.js', 'roadmap.js', 'fitur.js', 'kanban.js', 'implemen.js', 'workspace.js']);
