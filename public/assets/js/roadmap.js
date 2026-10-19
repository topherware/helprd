'use strict';

/**
 * Tampilan Roadmap di workspace: pohon 4 kolom (akar → fitur → sub fitur → task) di atas kanvas bersama,
 * plus panel kiri (Perencanaan / detail fitur). Dikendalikan router di workspace.js lewat Roadmap.show().
 * Rute: #/roadmap, #/roadmap?panel=rencana, #/roadmap?fitur=ID
 */
/** Umumkan bahwa data fitur/task berubah; tiap tampilan (Roadmap, Fitur, Kanban) memuat ulang saat dibuka. */
function notifyDataChanged() {
  document.dispatchEvent(new Event('ws:data'));
}

const Roadmap = (() => {
  const section = document.querySelector('[data-view="roadmap"]');
  const viewport = document.getElementById('rmViewport');
  const world = document.getElementById('rmWorld');
  const loading = document.getElementById('rmLoading');
  const panel = document.getElementById('rmPanel');
  const canvas = createCanvas(viewport, world, document.getElementById('rmEdges'), { zoomRoot: section });

  const STATUS = { direncanakan: 'Direncanakan', dikerjakan: 'Dikerjakan', selesai: 'Selesai' };
  const PHASES = { 1: 'Fase 1 · Inti', 2: 'Fase 2 · Penting', 3: 'Fase 3 · Pengembangan', 4: 'Fase 4 · Tambahan' };
  const X_FEATURE = 300;
  const X_SUB = 580;
  const X_TASK = 860;
  const ROW_GAP = 26;

  let features = null;
  let ctx = { prd: '', judul: '' };
  let route = {};
  let fitted = false;
  let panelMode = 'preview'; // Perencanaan: 'preview' atau 'code'

  const go = (hash) => { location.hash = hash; };
  const icon = canvasIcon;
  const svgIcon = (path, size = 18) => {
    const span = el('span', { class: 'icon' });
    span.innerHTML = `<svg viewBox="0 0 24 24" width="${size}" height="${size}" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">${path}</svg>`;
    return span;
  };
  const ICON = {
    prev: '<path d="m15 6-6 6 6 6"/>', next: '<path d="m9 6 6 6-6 6"/>', close: '<path d="M6 6l12 12M18 6 6 18"/>',
    edit: '<path d="M4 20h4L19 9l-4-4L4 16zM14 6l4 4"/>', trash: '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/>',
    expand: '<path d="M14 4h6v6M10 20H4v-6M20 4l-7 7M4 20l7-7"/>', eye: '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    code: '<path d="m8 8-4 4 4 4M16 8l4 4-4 4M14 5l-4 14"/>', check: '<path d="m5 12 4 4 10-10"/>',
  };
  const iconBtn = (path, label, onclick, extra = {}) => el('button', { type: 'button', class: 'icon-btn', 'aria-label': label, title: label, onclick, ...extra }, svgIcon(path));

  /* ---------- Data ---------- */

  async function load() {
    loading.replaceChildren(el('span', { class: 'spinner' }), el('p', {}, 'Memuat roadmap…'));
    loading.hidden = false;
    try {
      features = (await api('feature_get.php')).features;
      loading.hidden = true;
      return true;
    } catch (err) {
      loading.replaceChildren(el('p', { class: 'form-error' }, err.message),
        el('button', { type: 'button', class: 'btn', onclick: () => show(route, ctx) }, 'Coba lagi'));
      return false;
    }
  }

  async function reload() {
    features = null;
    if (await load()) render();
  }

  /* ---------- Pohon ---------- */

  function render() {
    canvas.clear();
    const hasTasks = features.some((f) => f.task_total > 0);

    const root = el('button', { type: 'button', class: 'node node-root', onclick: () => go('#/roadmap?panel=rencana') },
      el('div', { class: 'node-title' }, icon('dokumen'), el('span', { class: 'truncate' }, ctx.judul)),
      el('div', { class: 'node-meta' }, 'Perencanaan'));
    world.append(root);

    const rows = features.map((f) => {
      const node = el('a', { class: 'node node-feature', href: `#/roadmap?fitur=${f.id}`, title: f.nama, 'data-id': f.id },
        el('span', { class: `badge badge-${f.fase}` }, `FASE ${f.fase}`),
        el('div', { class: 'node-title' }, icon(f.ikon), el('span', { class: 'truncate' }, f.nama)),
        el('div', { class: 'node-meta' },
          el('i', { class: `dot dot-${f.status}` }), STATUS[f.status],
          hasTasks ? el('span', { class: 'node-progress' }, icon('progres', 11), `${f.task_selesai}/${f.task_total}`) : null));

      const subs = f.sub_fitur;
      const sub = el('div', { class: 'node node-sub' },
        el('div', { class: 'sub-head' }, icon('daftar', 12), 'SUB FITUR'),
        ...(subs.length
          ? subs.slice(0, 3).map((s) => el('a', { class: 'sub-item', href: `#/fitur/${f.id}/sub/${s.id}`, title: s.nama }, s.nama))
          : [el('div', { class: 'sub-item' }, 'Belum ada sub fitur')]),
        subs.length ? el('a', { class: 'card-more', href: `#/fitur/${f.id}` }, `Lihat semua (${subs.length}) ›`) : null);
      world.append(node, sub);

      let tasks = null;
      if (hasTasks) {
        const open = () => go(`#/kanban?fitur=${f.id}`);
        tasks = el('div', {
          class: 'node node-tasks', role: 'link', tabindex: '0', title: `Buka task ${f.nama} di Kanban`,
          onclick: open, onkeydown: (e) => { if (e.key === 'Enter') open(); },
        },
          el('div', { class: 'sub-head' }, icon('daftar', 12), 'TASKS', el('span', { class: 'card-count' }, `${f.task_selesai}/${f.task_total}`)),
          ...f.tasks_awal.map((t) => el('div', { class: 'task-item' },
            icon(t.status === 'selesai' ? 'kotakCentang' : 'kotak', 12), el('span', { class: 'truncate' }, t.title))),
          el('span', { class: 'card-more' }, `Lihat semua (${f.task_total}) ›`));
        world.append(tasks);
      }
      return { f, node, sub, tasks };
    });

    // Tiap baris setinggi kartu tertingginya; akar di tengah.
    let y = 0;
    let right = X_SUB + 220;
    rows.forEach((r) => {
      const hs = [r.node.offsetHeight, r.sub.offsetHeight, r.tasks ? r.tasks.offsetHeight : 0];
      const h = Math.max(...hs);
      canvas.place(r.node, X_FEATURE, y + (h - hs[0]) / 2);
      canvas.place(r.sub, X_SUB, y + (h - hs[1]) / 2);
      if (r.tasks) {
        canvas.place(r.tasks, X_TASK, y + (h - hs[2]) / 2);
        right = X_TASK + r.tasks.offsetWidth;
      }
      y += h + ROW_GAP;
    });
    const total = Math.max(y - ROW_GAP, root.offsetHeight);
    canvas.place(root, 0, (total - root.offsetHeight) / 2);
    canvas.setBounds({ x: -40, y: -40, w: right + 80, h: total + 80 });

    const rootOut = canvas.anchor(root, 'right');
    rows.forEach((r) => {
      canvas.curve(rootOut, canvas.anchor(r.node, 'left'), `edge-${r.f.status}`);
      canvas.curve(canvas.anchor(r.node, 'right'), canvas.anchor(r.sub, 'left'), 'edge-dashed');
      if (r.tasks) canvas.curve(canvas.anchor(r.sub, 'right'), canvas.anchor(r.tasks, 'left'), 'edge-dashed');
    });
    markSelected();
  }

  function markSelected() {
    world.querySelectorAll('.is-selected').forEach((n) => n.classList.remove('is-selected'));
    if (route.fitur) world.querySelector(`.node-feature[data-id="${route.fitur}"]`)?.classList.add('is-selected');
    if (route.panel === 'rencana') world.querySelector('.node-root')?.classList.add('is-selected');
  }

  /* ---------- Panel kiri ---------- */

  function renderPanel() {
    if (route.panel === 'rencana') return panelRencana();
    const index = features.findIndex((f) => f.id === route.fitur);
    if (index < 0) {
      panel.hidden = true;
      return;
    }
    panelFitur(features[index], index);
  }

  function panelRencana() {
    const body = el('div', { class: 'rm-panel-body' });
    if (panelMode === 'code') {
      body.append(el('pre', { class: 'rm-raw' }, ctx.prd || ''));
    } else {
      const prose = el('div', { class: 'prose prose-sm' });
      prose.innerHTML = DOMPurify.sanitize(marked.parse(ctx.prd || '_PRD belum dibuat._'));
      body.append(prose);
    }
    const toggle = (mode, path, label) => el('button', {
      type: 'button', class: 'seg-btn', 'aria-pressed': String(panelMode === mode), title: label, 'aria-label': label,
      onclick: () => { panelMode = mode; panelRencana(); },
    }, svgIcon(path, 16));
    panel.replaceChildren(
      el('div', { class: 'rm-panel-head' },
        el('strong', {}, 'Perencanaan'),
        el('div', { class: 'rm-panel-tools' },
          el('div', { class: 'seg' }, toggle('preview', ICON.eye, 'Pratinjau'), toggle('code', ICON.code, 'Kode')),
          iconBtn(ICON.expand, 'Buka layar penuh', () => go('#/prd')),
          iconBtn(ICON.close, 'Tutup', () => go('#/roadmap')))),
      el('p', { class: 'rm-panel-note' }, svgIcon(ICON.check, 14), `${features.length} fitur dari rencana ini.`),
      body);
    panel.hidden = false;
  }

  function panelFitur(f, index, editing = false) {
    const nav = (i) => go(`#/roadmap?fitur=${features[i].id}`);
    const head = el('div', { class: 'rm-panel-head' },
      el('div', { class: 'rm-counter' },
        iconBtn(ICON.prev, 'Fitur sebelumnya', () => nav(index - 1), { disabled: index === 0 }),
        el('span', {}, `${index + 1} / ${features.length}`),
        iconBtn(ICON.next, 'Fitur berikutnya', () => nav(index + 1), { disabled: index === features.length - 1 })),
      el('div', { class: 'rm-panel-tools' },
        iconBtn(ICON.edit, 'Edit fitur', () => panelFitur(f, index, true)),
        iconBtn(ICON.trash, 'Hapus fitur', async () => {
          if (await removeFeature(f)) {
            await reload();
            go('#/roadmap');
          }
        }),
        el('span', { class: 'rm-tools-sep' }),
        iconBtn(ICON.expand, 'Buka halaman fitur', () => go(`#/fitur/${f.id}`)),
        iconBtn(ICON.close, 'Tutup', () => go('#/roadmap'))));
    const form = () => editForm(f, {
      onSaved: async () => { await reload(); renderPanel(); },
      onCancel: () => panelFitur(f, index),
    });
    panel.replaceChildren(head, editing ? form() : featureDetail(f));
    panel.hidden = false;
    panel.scrollTop = 0;
  }

  function featureDetail(f) {
    const criteria = (items) => items.length
      ? el('ul', {}, ...items.map((c) => el('li', {}, c)))
      : el('p', { class: 'muted' }, 'Belum ada. Klik "Bikin Task" di tampilan PRD untuk menyusunnya.');
    return el('div', { class: 'rm-panel-body rm-detail' },
      el('h2', {}, el('span', { class: 'rm-detail-icon' }, icon(f.ikon, 18)), f.nama),
      f.deskripsi ? el('p', { class: 'rm-lead' }, f.deskripsi) : null,
      el('p', { class: 'rm-status' }, el('span', { class: `chip-status chip-${f.status}` }, STATUS[f.status]),
        `${f.task_selesai}/${f.task_total} tugas selesai`, el('span', { class: 'muted' }, ` · ${PHASES[f.fase]}`)),
      el('h3', {}, 'Tujuan'),
      f.tujuan ? el('p', {}, f.tujuan) : criteria([]),
      el('h3', {}, 'Selesai bila'),
      criteria(f.selesai_bila),
      el('h3', {}, `Sub-fitur (${f.sub_fitur.length})`),
      f.sub_fitur.length
        ? el('ul', { class: 'rm-subs' }, ...f.sub_fitur.map((s) => el('li', {},
          el('a', { href: `#/fitur/${f.id}/sub/${s.id}` }, s.nama),
          s.task_total && s.task_selesai === s.task_total ? svgIcon(ICON.check, 14) : null)))
        : el('p', { class: 'muted' }, 'Belum ada sub fitur.'),
      f.task_total ? el('a', { class: 'rm-kanban-link', href: `#/kanban?fitur=${f.id}` }, `Buka ${f.task_total} task di Kanban →`) : null);
  }

  /** Form edit fitur (dipakai Roadmap dan Halaman Fitur). */
  function editForm(f, { onSaved, onCancel }) {
    const field = (label, input) => el('label', { class: 'rm-field' }, el('span', {}, label), input);
    const nama = el('input', { type: 'text', maxlength: 100, required: true, value: f.nama });
    const deskripsi = el('textarea', { rows: 2, maxlength: 500 }, f.deskripsi);
    const fase = el('select', {}, ...[1, 2, 3, 4].map((n) => el('option', { value: n, selected: n === f.fase }, PHASES[n])));
    const tujuan = el('textarea', { rows: 3, maxlength: 800 }, f.tujuan);
    const selesai = el('textarea', { rows: 5 }, f.selesai_bila.join('\n'));
    const error = el('p', { class: 'form-error', hidden: true });
    const save = el('button', { type: 'submit', class: 'btn btn-sm' }, 'Simpan');
    return el('form', {
      class: 'rm-panel-body rm-form',
      onsubmit: async (e) => {
        e.preventDefault();
        save.disabled = true;
        try {
          await api('feature_update.php', {
            id: f.id, nama: nama.value, deskripsi: deskripsi.value, fase: Number(fase.value), tujuan: tujuan.value, selesai_bila: selesai.value,
          });
          notifyDataChanged();
          await onSaved();
        } catch (err) {
          error.textContent = err.message;
          error.hidden = false;
          save.disabled = false;
        }
      },
    },
      el('h2', {}, 'Edit fitur'),
      field('Nama', nama), field('Deskripsi', deskripsi), field('Fase', fase), field('Tujuan', tujuan),
      field('Selesai bila (satu kriteria per baris)', selesai),
      error,
      el('div', { class: 'rm-form-actions' },
        el('button', { type: 'button', class: 'btn btn-ghost btn-sm', onclick: onCancel }, 'Batal'), save));
  }

  /** Hapus fitur setelah konfirmasi; true bila terhapus. */
  async function removeFeature(f) {
    const detail = `${f.sub_fitur.length} sub-fitur dan ${f.task_total} task`;
    if (!(await ui.confirm(`Hapus fitur "${f.nama}"?`, `Fitur beserta ${detail} ikut terhapus. Teks PRD tidak berubah.`, { ok: 'Hapus fitur', tone: 'danger', icon: 'trash' }))) return false;
    try {
      await api('feature_delete.php', { id: f.id });
      notifyDataChanged();
      return true;
    } catch (err) {
      ui.notify('Fitur gagal dihapus', err.message);
      return false;
    }
  }

  /* ---------- API untuk router ---------- */

  async function show(r, context) {
    route = r;
    ctx = context;
    if (!features) {
      if (!(await load())) return;
      render();
      fitted = false;
    }
    if (!fitted) {
      canvas.fit(0.55);
      fitted = true;
    }
    markSelected();
    if (route.panel || route.fitur) renderPanel();
    else panel.hidden = true;
  }

  window.addEventListener('resize', () => {
    if (!section.hidden && features) canvas.fit(0.55);
  });

  // Data berubah di tampilan lain (mis. status task di Kanban): muat ulang saat Roadmap dibuka berikutnya.
  document.addEventListener('ws:data', () => { features = null; });

  return { show, editForm, removeFeature };
})();
