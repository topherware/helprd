'use strict';

(() => {
  const viewport = document.getElementById('viewport');
  const world = document.getElementById('world');
  const edges = document.getElementById('edges');
  const loading = document.getElementById('mapLoading');
  const editor = document.getElementById('editor');
  const stackPanel = document.getElementById('stackPanel');

  const PHASES = { 1: 'Inti', 2: 'Penting', 3: 'Pengembangan', 4: 'Tambahan' };
  const prdGo = document.getElementById('prdGo');
  // Jarak layout (px, koordinat dunia)
  const X_FEATURE = 320;
  const X_SUB = 600;
  const ROW_GAP = 18;
  const GROUP_HEAD = 40;
  const GROUP_GAP = 40;
  const GROUP_PAD = 16;
  const READABLE_ZOOM = 0.7;

  let project = {};
  let features = [];
  let editingId = null;
  const icon = canvasIcon;
  const { place, anchor, curve, fit, clear, setBounds } = createCanvas(viewport, world, edges);

  async function load() {
    loading.replaceChildren(el('span', { class: 'spinner' }), el('p', {}, 'AI sedang menyusun peta fitur…'));
    loading.hidden = false;
    try {
      const data = await api('generate_features.php');
      project = data.project;
      features = data.features;
      document.getElementById('projectTitle').textContent = project.judul;
      document.title = project.judul + ' · Peta fitur';
      setupStack();
      loading.hidden = true;
      render();
      fit(READABLE_ZOOM);
    } catch (err) {
      loading.replaceChildren(
        el('p', { class: 'form-error' }, err.message),
        el('button', { type: 'button', class: 'btn', onclick: load }, 'Coba lagi'));
    }
  }

  function setupStack() {
    const btn = document.getElementById('stackToggle');
    if (!project.tech_stack) return;
    btn.hidden = false;
    document.getElementById('stackTitle').textContent = project.tech_mode === 'ai' ? 'Rekomendasi tech stack' : 'Tech stack pilihan kamu';
    document.getElementById('stackBody').textContent = project.tech_stack;
    btn.onclick = () => { stackPanel.hidden = !stackPanel.hidden; };
  }

  /* ---------- Render & layout ---------- */

  function render() {
    clear();

    const root = el('div', { class: 'node node-root' },
      el('div', { class: 'node-title' }, icon('dokumen'), el('span', { class: 'truncate' }, project.judul)),
      el('div', { class: 'node-meta' }, 'Perencanaan'));
    world.append(root);

    const rows = features.map((f) => {
      // Fitur usulan AI (tidak diminta pengguna) berlencana "Usulan" dan tidak masuk PRD sampai disertakan.
      const usulan = f.asal === 'usulan_ai';
      const node = el('button', {
        type: 'button', class: 'node node-feature' + (f.id === editingId ? ' is-selected' : '') + (usulan && !Number(f.disertakan) ? ' is-usulan-off' : ''),
        title: usulan ? `${f.nama} — usulan AI${Number(f.disertakan) ? ' (disertakan)' : ' (belum disertakan)'}` : f.nama, 'data-id': f.id,
        onclick: () => openEditor(f),
      },
        el('span', { class: `badge badge-${f.fase}` }, `FASE ${f.fase}`),
        usulan ? el('span', { class: 'badge badge-usulan' }, 'Usulan') : null,
        el('div', { class: 'node-title' }, icon(f.ikon), el('span', { class: 'truncate' }, f.nama)),
        el('div', { class: 'node-meta' }, `${f.sub_fitur.length} sub fitur`));
      const sub = el('div', { class: 'node node-sub' },
        el('div', { class: 'sub-head' }, icon('daftar', 12), 'SUB FITUR'),
        ...(f.sub_fitur.length ? f.sub_fitur : ['Belum ada sub fitur']).map((s) => el('div', { class: 'sub-item' }, s)));
      world.append(node, sub);
      return { f, node, sub };
    });

    // Bingkai grup per fase
    const groups = [1, 2, 3, 4]
      .map((fase) => ({ fase, rows: rows.filter((r) => r.f.fase === fase) }))
      .filter((g) => g.rows.length);
    groups.forEach((g) => {
      g.frame = el('div', { class: `group group-${g.fase}` },
        el('span', { class: 'group-label' }, `Fase ${g.fase} · ${PHASES[g.fase]}`, el('small', {}, `${g.rows.length} fitur`)));
      world.prepend(g.frame);
    });

    // Posisi vertikal: tiap baris setinggi node tertinggi (fitur atau kartu sub fitur).
    let y = 0;
    let maxRight = 0;
    groups.forEach((g) => {
      const top = y;
      y += GROUP_HEAD;
      g.rows.forEach((r) => {
        const fh = r.node.offsetHeight;
        const sh = r.sub.offsetHeight;
        const h = Math.max(fh, sh);
        place(r.node, X_FEATURE, y + (h - fh) / 2);
        place(r.sub, X_SUB, y + (h - sh) / 2);
        maxRight = Math.max(maxRight, X_SUB + r.sub.offsetWidth);
        y += h + ROW_GAP;
      });
      y += GROUP_PAD - ROW_GAP;
      g.top = top;
      g.bottom = y;
      y += GROUP_GAP;
    });
    groups.forEach((g) => {
      place(g.frame, X_FEATURE - GROUP_PAD, g.top);
      g.frame.style.width = maxRight + GROUP_PAD - (X_FEATURE - GROUP_PAD) + 'px';
      g.frame.style.height = g.bottom - g.top + 'px';
    });

    const total = Math.max(y - GROUP_GAP, root.offsetHeight);
    place(root, 0, (total - root.offsetHeight) / 2);
    setBounds({ x: -40, y: -40, w: maxRight + GROUP_PAD + 80, h: total + 80 });

    // Garis penghubung
    const rootOut = anchor(root, 'right');
    rows.forEach((r) => {
      curve(rootOut, anchor(r.node, 'left'), `edge-${r.f.status}`);
      curve(anchor(r.node, 'right'), anchor(r.sub, 'left'), `edge-${r.f.status}`);
    });
  }

  window.addEventListener('resize', () => features.length && fit(READABLE_ZOOM));

  /* ---------- Editor fitur (peta fitur = tempat mengedit; Roadmap di workspace hanya tampilan final) ---------- */

  const $ = (id) => document.getElementById(id);

  /** Peta berubah setelah PRD dibuat: PRD harus dibuat ulang. */
  function markStale() {
    if (prdGo.dataset.state === 'fresh') {
      prdGo.dataset.state = 'stale';
      $('prdGoLabel').textContent = I18N.tr('Buat ulang PRD');
    }
  }

  function openEditor(f) {
    editingId = f ? f.id : 'baru';
    $('edTitle').textContent = I18N.tr(f ? 'Ubah fitur' : 'Tambah fitur');
    $('edNama').value = f ? f.nama : '';
    $('edFase').value = String(f ? f.fase : 1);
    $('edSubs').value = f ? f.sub_fitur.join('\n') : '';
    $('edUsulan').hidden = !f || f.asal !== 'usulan_ai';
    if (f) {
      $('edAlasan').textContent = f.asal_alasan ? `Usulan AI: ${f.asal_alasan}` : 'Usulan AI: fitur ini tidak diminta di ide, jawaban, atau catatanmu.';
      $('edDisertakan').checked = Boolean(Number(f.disertakan));
    }
    $('edDelete').hidden = !f;
    $('edError').hidden = true;
    editor.hidden = false;
    world.querySelectorAll('.node-feature').forEach((n) => n.classList.toggle('is-selected', f && Number(n.dataset.id) === f.id));
    $('edNama').focus();
  }

  function closeEditor() {
    editor.hidden = true;
    editingId = null;
    world.querySelectorAll('.is-selected').forEach((n) => n.classList.remove('is-selected'));
  }

  editor.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = editor.querySelector('[type="submit"]');
    const errBox = $('edError');
    btn.disabled = true;
    const body = { nama: $('edNama').value, fase: Number($('edFase').value), sub_fitur: $('edSubs').value };
    try {
      const data = editingId === 'baru'
        ? await api('feature_create.php', body)
        : await api('feature_update.php', {
          id: editingId, ...body,
          ...(features.find((x) => x.id === editingId)?.asal === 'usulan_ai' ? { disertakan: $('edDisertakan').checked } : {}),
        });
      features = data.features;
      closeEditor();
      render();
      markStale();
    } catch (err) {
      errBox.textContent = err.message;
      errBox.hidden = false;
    } finally {
      btn.disabled = false;
    }
  });

  $('edDelete').addEventListener('click', async () => {
    const f = features.find((x) => x.id === editingId);
    if (!f || !(await ui.confirm(`Hapus fitur "${f.nama}"?`, 'Fitur beserta sub fitur dan task-nya ikut terhapus.', { ok: 'Hapus fitur', tone: 'danger', icon: 'trash' }))) return;
    try {
      await api('feature_delete.php', { id: f.id });
      features = features.filter((x) => x.id !== f.id);
      closeEditor();
      render();
      markStale();
    } catch (err) {
      ui.notify('Fitur gagal dihapus', err.message);
    }
  });

  $('addFeature').addEventListener('click', () => openEditor(null));

  // Tombol utama: PRD dibuat dari peta ini. Bila peta berubah setelah PRD ada, PRD (dan roadmap) dibuat ulang.
  prdGo.addEventListener('click', async (e) => {
    if (prdGo.dataset.state !== 'stale') return;
    e.preventDefault();
    if (await ui.confirm('Buat ulang PRD?', 'Peta fitur berubah sejak PRD dibuat. PRD dan roadmap dibuat ulang dari peta ini; versi lama tetap ada di riwayat versi.', { ok: 'Buat ulang PRD' })) {
      location.href = 'workspace.php?regen=1#/prd';
    }
  });

  document.querySelectorAll('[data-close]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const panel = btn.closest('.panel');
      if (panel === editor) closeEditor();
      else panel.hidden = true;
    });
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeEditor();
      stackPanel.hidden = true;
    }
  });

  load();
})();
