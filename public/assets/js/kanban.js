'use strict';

/**
 * Kanban task: 4 kolom status (atau tampilan Daftar), filter fitur, urutan dari server, ubah status satu/banyak task.
 * Rute: #/kanban dan #/kanban?fitur=ID. Dikendalikan router di workspace.js lewat Kanban.show().
 */
const Kanban = (() => {
  const section = document.querySelector('[data-view="kanban"]');
  const COLUMNS = [['belum_mulai', 'Belum mulai'], ['dikerjakan', 'Dikerjakan'], ['selesai', 'Selesai'], ['gagal', 'Gagal']];
  const STATUS = Object.fromEntries(COLUMNS);
  const PRIORITY = { utama: 'Utama', penting: 'Penting', opsional: 'Opsional' };
  const SORTS = [
    ['roadmap', 'Urutan roadmap', '<path d="M4 6h16M4 12h16M4 18h16"/>'],
    ['diperbarui', 'Terakhir diperbarui', '<path d="M20 12a8 8 0 1 1-2.3-5.7M20 4v4h-4"/>'],
    ['dibuat', 'Terbaru dibuat', '<circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/>'],
    ['judul', 'Judul A–Z', '<path d="M4 16 7 6l3 10M5 13h4M14 6h6l-6 10h6"/>'],
    ['prioritas', 'Prioritas tertinggi', '<path d="M5 20v-6M12 20V9M19 20V4"/>'],
  ];
  const ICON = {
    board: '<rect x="3" y="4" width="18" height="16" rx="3"/><path d="M9 4v16M15 4v16"/>',
    list: '<path d="M4 6h16M4 12h16M4 18h16"/>',
    chevron: '<path d="m6 9 6 6 6-6"/>', check: '<path d="m5 12 4 4 10-10"/>', search: '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.4-4.4"/>',
    prio: '<path d="M5 20v-4M12 20v-9M19 20V5"/>',
  };

  const pref = (key, fallback) => { try { return localStorage.getItem('planku.kanban.' + key) || fallback; } catch { return fallback; } };
  const savePref = (key, value) => { try { localStorage.setItem('planku.kanban.' + key, value); } catch { /* abaikan */ } };

  let tasks = [];
  let features = [];
  let loadedKey = null;
  let route = {};
  let sort = pref('sort', 'roadmap');
  let mode = pref('mode', 'kanban');
  let listSort = null; // { key, dir } untuk klik judul kolom di tampilan Daftar
  const selected = new Set();
  let popover = null;

  document.addEventListener('ws:data', () => { loadedKey = null; });

  const svgIcon = (path, size = 18) => {
    const span = el('span', { class: 'icon' });
    span.innerHTML = `<svg viewBox="0 0 24 24" width="${size}" height="${size}" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">${path}</svg>`;
    return span;
  };

  async function show(r) {
    route = r;
    const key = `${r.fitur || 0}|${sort}`;
    if (key !== loadedKey) {
      section.replaceChildren(el('div', { class: 'loading' }, el('span', { class: 'spinner' })));
      try {
        const res = await api('tasks_list.php', { fitur: r.fitur, sort });
        if (route !== r) return;
        tasks = res.tasks;
        features = res.features;
        loadedKey = key;
        selected.clear();
      } catch (err) {
        return section.replaceChildren(el('div', { class: 'loading' }, el('p', { class: 'form-error' }, err.message),
          el('button', { type: 'button', class: 'btn', onclick: () => show(r) }, 'Coba lagi')));
      }
    }
    render();
  }

  /* ---------- Render ---------- */

  function render() {
    closePopover();
    const done = tasks.filter((t) => t.status === 'selesai').length;
    section.replaceChildren(...[
      el('p', { class: 'kb-hint' }, 'Klik task untuk mengubah statusnya, atau pilih beberapa task untuk memperbaruinya sekaligus.'),
      el('div', { class: 'kb-progress' },
        el('div', { class: 'kb-bar' }, el('span', { style: `width:${tasks.length ? (100 * done) / tasks.length : 0}%` })),
        el('span', {}, `${done}/${tasks.length} selesai`)),
      toolbar(),
      selected.size ? bulkBar() : null,
      mode === 'kanban' ? board() : table(),
    ].filter(Boolean));
  }

  function toolbar() {
    const current = features.find((f) => f.id === route.fitur);
    const visibleIds = tasks.map((t) => t.id);
    const allSelected = visibleIds.length > 0 && visibleIds.every((id) => selected.has(id));
    const seg = (value, path, label) => el('button', {
      type: 'button', class: 'seg-btn', 'aria-pressed': String(mode === value), title: label, 'aria-label': label,
      onclick: () => { mode = value; savePref('mode', value); render(); },
    }, svgIcon(path, 16));
    return el('div', { class: 'kb-toolbar' },
      el('div', { class: 'seg' }, seg('kanban', ICON.board, 'Tampilan Kanban'), seg('daftar', ICON.list, 'Tampilan Daftar')),
      el('button', { type: 'button', class: 'kb-select', 'aria-haspopup': 'listbox', onclick: (e) => featureMenu(e.currentTarget) },
        el('span', { class: 'truncate' }, current ? current.nama : 'Semua fitur'), svgIcon(ICON.chevron, 16)),
      el('button', { type: 'button', class: 'kb-icon-btn', title: 'Urutkan', 'aria-label': 'Urutkan', 'aria-haspopup': 'menu', onclick: (e) => sortMenu(e.currentTarget) },
        svgIcon(ICON.list)),
      el('button', {
        type: 'button', class: 'kb-link', disabled: !visibleIds.length,
        onclick: () => {
          visibleIds.forEach((id) => (allSelected ? selected.delete(id) : selected.add(id)));
          render();
        },
      }, allSelected ? 'Batal pilih' : 'Pilih semua'));
  }

  function bulkBar() {
    return el('div', { class: 'kb-bulk' },
      el('strong', {}, `${selected.size} task dipilih`),
      el('span', { class: 'muted' }, 'Ubah status ke:'),
      ...COLUMNS.map(([value, label]) => el('button', { type: 'button', class: 'btn btn-ghost btn-sm', onclick: () => update([...selected], value) },
        el('i', { class: `kb-dot st-${value}` }), label)),
      el('button', { type: 'button', class: 'kb-link', onclick: () => { selected.clear(); render(); } }, 'Batal'));
  }

  function board() {
    return el('div', { class: 'kb-board' }, ...COLUMNS.map(([value, label]) => {
      const items = tasks.filter((t) => t.status === value);
      const allInColumn = items.length > 0 && items.every((t) => selected.has(t.id));
      return el('section', { class: 'kb-col', 'aria-label': label },
        el('header', { class: 'kb-col-head' },
          el('i', { class: `kb-dot st-${value}` }), el('strong', {}, label), el('span', { class: 'muted' }, String(items.length)),
          items.length ? checkbox(allInColumn, `Pilih semua task ${label}`, (on) => {
            items.forEach((t) => (on ? selected.add(t.id) : selected.delete(t.id)));
            render();
          }) : null),
        el('div', { class: 'kb-cards' }, ...(items.length ? items.map(card) : [el('p', { class: 'kb-empty' }, 'Kosong')])));
    }));
  }

  function card(t) {
    const node = el('div', {
      class: 'kb-card' + (selected.has(t.id) ? ' is-selected' : ''), role: 'button', tabindex: '0', title: 'Ubah status',
      onclick: () => statusMenu(node, t),
      onkeydown: (e) => { if (e.key === 'Enter') statusMenu(node, t); },
    },
      el('div', { class: 'kb-card-top' },
        el('i', { class: `kb-dot st-${t.status}` }),
        el('span', { class: 'kb-chip kb-chip-fitur', title: t.feature_nama }, t.feature_nama),
        el('span', { class: 'kb-chip' }, `Fase ${t.phase}`),
        el('span', { class: `kb-prio prio-${t.priority}` }, svgIcon(ICON.prio, 13), PRIORITY[t.priority]),
        checkbox(selected.has(t.id), `Pilih task ${t.title}`, (on) => { on ? selected.add(t.id) : selected.delete(t.id); render(); })),
      el('p', { class: 'kb-card-title' }, t.title),
      t.sub_nama ? el('p', { class: 'kb-card-sub muted' }, t.sub_nama) : null);
    return node;
  }

  function checkbox(checked, label, onChange) {
    return el('input', {
      type: 'checkbox', class: 'kb-check', 'aria-label': label, checked,
      onclick: (e) => e.stopPropagation(),
      onchange: (e) => onChange(e.target.checked),
    });
  }

  function table() {
    const cols = [['status', 'Status'], ['title', 'Judul'], ['feature_nama', 'Fitur'], ['phase', 'Fase'], ['priority', 'Prioritas'], ['updated_at', 'Diperbarui']];
    const rank = { status: (t) => COLUMNS.findIndex(([v]) => v === t.status), priority: (t) => ['utama', 'penting', 'opsional'].indexOf(t.priority) };
    let rows = tasks.slice();
    if (listSort) {
      const value = rank[listSort.key] || ((t) => t[listSort.key]);
      rows.sort((a, b) => (value(a) > value(b) ? 1 : value(a) < value(b) ? -1 : 0) * listSort.dir);
    }
    return el('div', { class: 'kb-table-wrap' }, el('table', { class: 'kb-table' },
      el('thead', {}, el('tr', {}, el('th', {}),
        ...cols.map(([key, label]) => el('th', {}, el('button', {
          type: 'button', class: 'kb-th',
          onclick: () => { listSort = { key, dir: listSort && listSort.key === key ? -listSort.dir : 1 }; render(); },
        }, label, listSort && listSort.key === key ? (listSort.dir > 0 ? ' ↑' : ' ↓') : ''))))),
      el('tbody', {}, ...(rows.length ? rows.map((t) => {
        const statusBtn = el('button', { type: 'button', class: 'kb-status-btn', onclick: () => statusMenu(statusBtn, t) },
          el('i', { class: `kb-dot st-${t.status}` }), STATUS[t.status]);
        return el('tr', { class: selected.has(t.id) ? 'is-selected' : '' },
          el('td', {}, checkbox(selected.has(t.id), `Pilih task ${t.title}`, (on) => { on ? selected.add(t.id) : selected.delete(t.id); render(); })),
          el('td', {}, statusBtn),
          el('td', {}, t.title),
          el('td', { class: 'muted' }, t.feature_nama),
          el('td', {}, `Fase ${t.phase}`),
          el('td', {}, el('span', { class: `kb-prio prio-${t.priority}` }, PRIORITY[t.priority])),
          el('td', { class: 'muted small' }, (t.updated_at || '').slice(0, 16)));
      }) : [el('tr', {}, el('td', { colspan: 7, class: 'kb-empty' }, 'Kosong'))]))));
  }

  /* ---------- Popover (filter fitur, urutan, status) ---------- */

  function openPopover(anchor, content) {
    closePopover();
    const r = anchor.getBoundingClientRect();
    popover = el('div', { class: 'ws-menu kb-pop', style: `position:fixed;top:${r.bottom + 6}px;left:${Math.min(r.left, innerWidth - 300)}px` }, ...content);
    document.body.append(popover);
    return popover;
  }

  function closePopover() {
    popover?.remove();
    popover = null;
  }

  document.addEventListener('click', (e) => {
    if (popover && !popover.contains(e.target) && !e.target.closest('.kb-select, .kb-icon-btn, .kb-card, .kb-status-btn')) closePopover();
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePopover(); });
  window.addEventListener('hashchange', closePopover);

  function featureMenu(anchor) {
    const option = (id, label) => el('button', {
      type: 'button', role: 'menuitem', class: (route.fitur || null) === id ? 'is-active' : '', 'data-label': label.toLowerCase(),
      onclick: () => { closePopover(); location.hash = id ? `#/kanban?fitur=${id}` : '#/kanban'; },
    }, (route.fitur || null) === id ? svgIcon(ICON.check, 16) : el('span', { class: 'kb-pop-gap' }), el('span', { class: 'truncate' }, label));
    const list = el('div', { class: 'kb-pop-list' }, option(null, 'Semua fitur'), ...features.map((f) => option(f.id, f.nama)));
    const search = el('input', {
      type: 'text', placeholder: 'Cari fitur…', 'aria-label': 'Cari fitur',
      oninput: () => list.querySelectorAll('button').forEach((b, i) => {
        b.hidden = i > 0 && !b.dataset.label.includes(search.value.trim().toLowerCase());
      }),
    });
    openPopover(anchor, [el('label', { class: 'kb-search' }, svgIcon(ICON.search, 16), search), list]);
    search.focus();
  }

  function sortMenu(anchor) {
    openPopover(anchor, [
      el('p', { class: 'kb-pop-title muted' }, 'Urutkan di server'),
      ...SORTS.map(([value, label, path]) => el('button', {
        type: 'button', role: 'menuitem',
        onclick: () => { closePopover(); sort = value; savePref('sort', value); show(route); },
      }, svgIcon(path, 16), el('span', {}, label), sort === value ? svgIcon(ICON.check, 16) : null)),
    ]);
  }

  function statusMenu(anchor, t) {
    openPopover(anchor, [
      el('p', { class: 'kb-pop-title muted' }, 'Ubah status'),
      ...COLUMNS.map(([value, label]) => el('button', { type: 'button', role: 'menuitem', onclick: () => { closePopover(); update([t.id], value); } },
        el('i', { class: `kb-dot st-${value}` }), el('span', {}, label), t.status === value ? svgIcon(ICON.check, 16) : null)),
    ]);
  }

  /* ---------- Simpan ---------- */

  async function update(ids, status) {
    try {
      await api('task_update.php', { ids, status });
    } catch (err) {
      ui.notify('Status task gagal disimpan', err.message);
      return;
    }
    const now = new Date().toISOString().slice(0, 19).replace('T', ' ');
    tasks.forEach((t) => {
      if (ids.includes(t.id)) {
        t.status = status;
        t.updated_at = now;
      }
    });
    selected.clear();
    const key = loadedKey;
    notifyDataChanged(); // Roadmap & Halaman Fitur memuat ulang progres saat dibuka
    loadedKey = key; // data Kanban sendiri sudah diperbarui di tempat
    render();
  }

  return { show };
})();
