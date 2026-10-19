'use strict';

/**
 * Sidebar "Semua Plan":
 * - Folder: folder bawaan "Umum" (tidak bisa diubah/dihapus) + folder buatan sendiri (buat, ganti nama, hapus).
 *   Hapus folder tidak menghapus plan: plan di dalamnya kembali ke "Umum".
 * - Plan: seret ke folder lain (drag & drop), atau lewat menu ⋯ (Pindahkan ke…, Ganti nama, Hapus).
 * - Cari (folder yang berisi hasil ikut terbuka), urutkan (terbaru → terlama → A–Z) per folder,
 *   buka/tutup folder (diingat di localStorage), buka/tutup sidebar (desktop: diingat; layar kecil: drawer).
 */
(() => {
  const list = document.getElementById('planList');
  const search = document.getElementById('planSearch');
  const sortBtn = document.getElementById('planSort');
  const empty = document.getElementById('planEmpty');
  const sidebar = document.getElementById('plans');
  const scrim = document.getElementById('plansScrim');
  const mobile = window.matchMedia('(max-width: 900px)');
  const t = (s) => I18N.tr(s);
  const SORTS = {
    baru: ['terbaru', (a, b) => b.dataset.waktu - a.dataset.waktu],
    lama: ['terlama', (a, b) => a.dataset.waktu - b.dataset.waktu],
    nama: ['nama A–Z', (a, b) => a.dataset.judul.localeCompare(b.dataset.judul, 'id')],
  };
  const ICON = {
    dots: 'M5 12h.01M12 12h.01M19 12h.01',
    folder: 'M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z',
    pencil: 'M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4',
    trash: 'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
    check: 'm5 12 5 5 9-10',
  };

  const folders = () => [...list.querySelectorAll('.folder')];
  const plans = () => [...list.querySelectorAll('.plan')];
  const folderId = (f) => Number(f.dataset.folder) || null; // 0 = "Umum" (folder_id NULL)
  const folderName = (f) => (f.querySelector('.folder-name, .folder-name-default')?.textContent || '').trim();

  /* ---------- Buka/tutup folder (diingat) ---------- */
  const CLOSED_KEY = 'planku.folders.closed';
  let closed = [];
  try { closed = JSON.parse(localStorage.getItem(CLOSED_KEY) || '[]'); } catch { /* penyimpanan tidak tersedia */ }
  function setFolderOpen(f, open, remember = true) {
    f.classList.toggle('is-open', open);
    f.querySelector('.folder-toggle').setAttribute('aria-expanded', String(open));
    if (!remember) return;
    const id = f.dataset.folder;
    closed = closed.filter((c) => c !== id);
    if (!open) closed.push(id);
    try { localStorage.setItem(CLOSED_KEY, JSON.stringify(closed)); } catch { /* penyimpanan tidak tersedia */ }
  }
  folders().forEach((f) => { if (closed.includes(f.dataset.folder)) setFolderOpen(f, false, false); });

  /** Hitung ulang jumlah plan per folder, status kosong, dan pesan hasil pencarian. */
  function refresh() {
    const q = search.value.trim().toLowerCase();
    let shown = 0;
    folders().forEach((f) => {
      const items = [...f.querySelectorAll('.plan')];
      let match = 0;
      items.forEach((p) => {
        p.hidden = q !== '' && !p.dataset.judul.includes(q);
        match += p.hidden ? 0 : 1;
      });
      f.querySelector('.plan-count').textContent = items.length;
      f.classList.toggle('is-empty', items.length === 0);
      // Saat mencari: folder tanpa hasil disembunyikan, folder dengan hasil dibuka sementara (tidak diingat).
      f.hidden = q !== '' && match === 0;
      f.classList.toggle('is-searching', q !== '' && match > 0);
      shown += match;
    });
    const total = plans().length;
    empty.textContent = t(total ? 'Tidak ada plan yang cocok.' : 'Belum ada plan. Tulis ide pertamamu di sebelah kanan.');
    empty.hidden = q === '' ? total > 0 : shown > 0;
  }

  search.addEventListener('input', refresh);
  sortBtn.addEventListener('click', () => {
    const keys = Object.keys(SORTS);
    const next = keys[(keys.indexOf(sortBtn.dataset.sort) + 1) % keys.length];
    const [label] = SORTS[next];
    sortBtn.dataset.sort = next;
    sortBtn.title = t(`Urutkan: ${label}`);
    sortBtn.setAttribute('aria-label', sortBtn.title);
    folders().forEach(sortFolder);
  });
  function sortFolder(f) {
    const ul = f.querySelector('.folder-plans');
    ul.append(...[...ul.children].sort(SORTS[sortBtn.dataset.sort][1]));
  }

  /* ---------- Menu ⋯ ---------- */
  let menu = null;
  function closeMenu() {
    if (!menu) return;
    menu.opener.setAttribute('aria-expanded', 'false');
    menu.remove();
    menu = null;
  }
  /** items: {label, icon, danger, current, run} atau {heading}. */
  function openMenu(opener, items) {
    closeMenu();
    menu = el('div', { class: 'menu', role: 'menu' }, ...items.map((it) => (it.heading
      ? el('div', { class: 'menu-heading' }, t(it.heading))
      : el('button', { type: 'button', role: 'menuitem', class: 'menu-item' + (it.danger ? ' is-danger' : '') + (it.current ? ' is-current' : ''), disabled: it.current ? true : null },
        svgIcon(it.current ? ICON.check : ICON[it.icon], 16), el('span', { class: 'truncate' }, it.label)))));
    items.filter((it) => !it.heading).forEach((it, i) => {
      menu.querySelectorAll('.menu-item')[i].addEventListener('click', () => { closeMenu(); it.run(); });
    });
    menu.opener = opener;
    opener.setAttribute('aria-expanded', 'true');
    document.body.append(menu);
    const r = opener.getBoundingClientRect();
    const mw = menu.offsetWidth;
    const mh = menu.offsetHeight;
    menu.style.left = `${Math.max(8, Math.min(r.right - mw, innerWidth - mw - 8))}px`;
    menu.style.top = `${r.bottom + 6 + mh > innerHeight - 8 ? Math.max(8, r.top - mh - 6) : r.bottom + 6}px`;
    menu.querySelector('.menu-item:not(:disabled)')?.focus();
  }
  document.addEventListener('pointerdown', (e) => { if (menu && !menu.contains(e.target) && e.target !== menu.opener) closeMenu(); });
  document.addEventListener('keydown', (e) => {
    if (!menu) return;
    const items = [...menu.querySelectorAll('.menu-item:not(:disabled)')];
    const i = items.indexOf(document.activeElement);
    if (e.key === 'Escape') { const o = menu.opener; closeMenu(); o.focus(); } else if (e.key === 'ArrowDown') { e.preventDefault(); items[(i + 1) % items.length]?.focus(); } else if (e.key === 'ArrowUp') { e.preventDefault(); items[(i - 1 + items.length) % items.length]?.focus(); } else if (e.key === 'Tab') closeMenu();
  });
  list.addEventListener('scroll', closeMenu, { passive: true });
  addEventListener('resize', closeMenu);

  /* ---------- Aksi ---------- */
  async function movePlan(plan, folder) {
    const from = plan.closest('.folder');
    if (from === folder) return;
    const next = plan.nextElementSibling;
    folder.querySelector('.folder-plans').append(plan);
    sortFolder(folder);
    setFolderOpen(folder, true);
    refresh();
    try {
      await api('plan_organize.php', { aksi: 'plan_move', id: Number(plan.dataset.id), folder_id: folderId(folder) });
    } catch (err) {
      from.querySelector('.folder-plans').insertBefore(plan, next);
      refresh();
      ui.notify('Plan gagal dipindahkan', err.message);
    }
  }

  /** Ganti nama di tempat: teks diganti input; Enter/klik di luar = simpan, Esc = batal. */
  function rename(target, save) {
    const old = target.textContent.trim();
    const input = el('input', { type: 'text', class: 'rename-input', maxlength: target.classList.contains('plan-title') ? 80 : 60, 'aria-label': t('Nama baru') });
    input.value = old;
    const host = target.closest('.plan-item, .folder-toggle');
    host.classList.add('is-renaming');
    host.after(input);
    input.focus();
    input.select();
    let done = false;
    const finish = async (commit) => {
      if (done) return;
      done = true;
      const value = input.value.trim().replace(/\s+/g, ' ');
      input.remove();
      host.classList.remove('is-renaming');
      if (!commit || !value || value === old) return;
      target.textContent = value;
      try {
        await save(value);
      } catch (err) {
        target.textContent = old;
        ui.notify('Nama gagal disimpan', err.message);
      }
    };
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') { e.preventDefault(); finish(true); }
      if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); finish(false); host.focus?.(); }
    });
    input.addEventListener('blur', () => finish(true));
  }

  function renamePlan(plan) {
    const title = plan.querySelector('.plan-title');
    rename(title, async (value) => {
      await api('plan_organize.php', { aksi: 'plan_rename', id: Number(plan.dataset.id), judul: value });
      plan.dataset.judul = value.toLowerCase();
      plan.querySelector('.plan-item').title = value;
      sortFolder(plan.closest('.folder'));
    });
  }

  async function deletePlan(plan) {
    const name = plan.querySelector('.plan-title').textContent;
    if (!(await ui.confirm(`Hapus plan "${name}"?`, 'PRD, peta fitur, task, dan chat agent ikut terhapus permanen dan tidak bisa dikembalikan.', { ok: 'Hapus plan', tone: 'danger', icon: 'trash' }))) return;
    try {
      const res = await api('plan_delete.php', { id: Number(plan.dataset.id) });
      if (res.aktif) {
        location.reload(); // plan yang sedang dibuka terhapus: tampilan "lanjutkan rencana terakhir" ikut berubah
        return;
      }
      plan.remove();
      refresh();
    } catch (err) {
      ui.notify('Plan gagal dihapus', err.message);
    }
  }

  function renameFolder(folder) {
    rename(folder.querySelector('.folder-name'), (value) => api('plan_organize.php', { aksi: 'folder_rename', id: folderId(folder), nama: value }));
  }

  async function deleteFolder(folder) {
    const n = folder.querySelectorAll('.plan').length;
    const message = n ? `${n} plan di dalamnya dipindah ke folder Umum, tidak ikut terhapus.` : 'Folder ini kosong.';
    if (!(await ui.confirm(`Hapus folder "${folderName(folder)}"?`, message, { ok: 'Hapus folder', tone: 'danger', icon: 'trash' }))) return;
    try {
      await api('plan_organize.php', { aksi: 'folder_delete', id: folderId(folder) });
      const general = list.querySelector('.folder[data-folder="0"]');
      general.querySelector('.folder-plans').append(...folder.querySelectorAll('.plan'));
      sortFolder(general);
      folder.remove();
      refresh();
    } catch (err) {
      ui.notify('Folder gagal dihapus', err.message);
    }
  }

  document.getElementById('folderNew').addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    btn.disabled = true;
    try {
      const { folder: data } = await api('plan_organize.php', { aksi: 'folder_create', nama: t('Folder baru') });
      const tpl = list.querySelector('.folder[data-folder="0"]');
      const folder = el('li', { class: 'folder is-open is-empty', 'data-folder': String(data.id) },
        el('div', { class: 'folder-head' },
          el('button', { type: 'button', class: 'folder-toggle', 'aria-expanded': 'true' },
            ...[...tpl.querySelectorAll('.folder-toggle > svg')].map((s) => s.cloneNode(true)),
            el('span', { class: 'folder-name truncate' }, data.nama), el('span', { class: 'plan-count' }, '0')),
          el('button', { type: 'button', class: 'row-menu', 'data-menu': 'folder', title: t('Opsi folder'), 'aria-label': t('Opsi folder'), 'aria-haspopup': 'menu' }, svgIcon(ICON.dots, 16))),
        el('ul', { class: 'folder-plans' }),
        el('p', { class: 'folder-empty' }, t('Seret plan ke sini')));
      list.append(folder);
      search.value = '';
      refresh();
      folder.scrollIntoView({ block: 'nearest' });
      renameFolder(folder);
    } catch (err) {
      ui.notify('Folder gagal dibuat', err.message);
    } finally {
      btn.disabled = false;
    }
  });

  list.addEventListener('click', (e) => {
    const toggle = e.target.closest('.folder-toggle');
    if (toggle) {
      const f = toggle.closest('.folder');
      setFolderOpen(f, !f.classList.contains('is-open'));
      return;
    }
    const btn = e.target.closest('.row-menu');
    if (!btn) return;
    if (menu && menu.opener === btn) { closeMenu(); return; }
    if (btn.dataset.menu === 'folder') {
      const folder = btn.closest('.folder');
      openMenu(btn, [
        { label: t('Ganti nama'), icon: 'pencil', run: () => renameFolder(folder) },
        { label: t('Hapus folder'), icon: 'trash', danger: true, run: () => deleteFolder(folder) },
      ]);
      return;
    }
    const plan = btn.closest('.plan');
    const here = plan.closest('.folder');
    openMenu(btn, [
      { label: t('Ganti nama'), icon: 'pencil', run: () => renamePlan(plan) },
      { heading: 'Pindahkan ke' },
      ...folders().map((f) => ({ label: folderName(f), icon: 'folder', current: f === here, run: () => movePlan(plan, f) })),
      { label: t('Hapus plan'), icon: 'trash', danger: true, run: () => deletePlan(plan) },
    ]);
  });

  /* ---------- Drag & drop ---------- */
  let dragged = null;
  list.addEventListener('dragstart', (e) => {
    dragged = e.target.closest('.plan');
    if (!dragged) return;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', dragged.dataset.id);
    closeMenu();
    requestAnimationFrame(() => { dragged?.classList.add('is-dragging'); list.classList.add('is-dragging'); });
  });
  list.addEventListener('dragend', () => {
    dragged?.classList.remove('is-dragging');
    dragged = null;
    list.classList.remove('is-dragging');
    list.querySelectorAll('.is-drop').forEach((f) => f.classList.remove('is-drop'));
  });
  list.addEventListener('dragover', (e) => {
    const f = dragged && e.target.closest('.folder');
    if (!f) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    list.querySelectorAll('.is-drop').forEach((x) => x !== f && x.classList.remove('is-drop'));
    f.classList.toggle('is-drop', f !== dragged.closest('.folder'));
  });
  list.addEventListener('drop', (e) => {
    const f = dragged && e.target.closest('.folder');
    if (!f) return;
    e.preventDefault();
    const plan = dragged;
    f.classList.remove('is-drop');
    movePlan(plan, f);
  });

  // "Buat plan baru": di halaman ini cukup fokus ke kotak ide.
  document.getElementById('planNew').addEventListener('click', (e) => {
    const ide = document.getElementById('ide');
    if (!ide) return;
    e.preventDefault();
    if (mobile.matches) setOpen(false);
    ide.focus();
  });

  /** Desktop: sidebar tetap di kiri dan bisa ditutup (diingat). Layar kecil: drawer di atas konten. */
  function setOpen(open) {
    if (mobile.matches) {
      sidebar.classList.toggle('is-open', open);
      scrim.hidden = !open;
    } else {
      document.body.classList.toggle('plans-closed', !open);
      try { localStorage.setItem('planku.plans', open ? '1' : '0'); } catch { /* penyimpanan tidak tersedia */ }
    }
  }
  document.getElementById('plansOpen').addEventListener('click', () => setOpen(true));
  document.getElementById('plansShow').addEventListener('click', () => setOpen(true));
  document.getElementById('plansClose').addEventListener('click', () => setOpen(false));
  scrim.addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && mobile.matches && !menu) setOpen(false); });
  mobile.addEventListener('change', () => { sidebar.classList.remove('is-open'); scrim.hidden = true; });

  refresh();
})();
