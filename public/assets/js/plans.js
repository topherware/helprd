'use strict';

/**
 * Sidebar "Semua Plan": cari, urutkan (terbaru → terlama → A–Z), buka/tutup folder, hapus plan,
 * dan buka/tutup sidebar (desktop: diingat di localStorage; layar kecil: drawer).
 */
(() => {
  const list = document.getElementById('planList');
  const search = document.getElementById('planSearch');
  const sortBtn = document.getElementById('planSort');
  const empty = document.getElementById('planEmpty');
  const sidebar = document.getElementById('plans');
  const scrim = document.getElementById('plansScrim');
  const mobile = window.matchMedia('(max-width: 900px)');
  let groups = [...list.children];
  const SORTS = {
    baru: ['terbaru', (a, b) => b.dataset.waktu - a.dataset.waktu],
    lama: ['terlama', (a, b) => a.dataset.waktu - b.dataset.waktu],
    nama: ['nama A–Z', (a, b) => a.dataset.judul.localeCompare(b.dataset.judul, 'id')],
  };

  function filter() {
    const q = search.value.trim().toLowerCase();
    let shown = 0;
    groups.forEach((g) => {
      g.hidden = q !== '' && !g.dataset.judul.includes(q);
      shown += g.hidden ? 0 : 1;
    });
    empty.textContent = groups.length ? 'Tidak ada plan yang cocok.' : 'Belum ada plan. Tulis ide pertamamu di sebelah kanan.';
    empty.hidden = shown > 0;
  }

  search.addEventListener('input', filter);
  sortBtn.addEventListener('click', () => {
    const keys = Object.keys(SORTS);
    const next = keys[(keys.indexOf(sortBtn.dataset.sort) + 1) % keys.length];
    const [label, compare] = SORTS[next];
    sortBtn.dataset.sort = next;
    sortBtn.title = `Urutkan: ${label}`;
    sortBtn.setAttribute('aria-label', sortBtn.title);
    list.append(...groups.sort(compare));
  });

  list.addEventListener('click', async (e) => {
    const folder = e.target.closest('.plan-folder');
    if (folder) {
      const open = folder.parentElement.classList.toggle('is-open');
      folder.setAttribute('aria-expanded', String(open));
      return;
    }
    const del = e.target.closest('.plan-delete');
    if (!del) return;
    const group = del.closest('.plan-group');
    const name = group.querySelector('.plan-folder .truncate').textContent;
    if (!(await ui.confirm(`Hapus plan "${name}"?`, 'PRD, peta fitur, task, dan chat agent ikut terhapus permanen dan tidak bisa dikembalikan.', { ok: 'Hapus plan', tone: 'danger', icon: 'trash' }))) return;
    del.disabled = true;
    try {
      const res = await api('plan_delete.php', { id: Number(group.dataset.id) });
      if (res.aktif) {
        location.reload(); // plan yang sedang dibuka terhapus: tampilan "lanjutkan rencana terakhir" ikut berubah
        return;
      }
      group.remove();
      groups = groups.filter((g) => g !== group);
      filter();
    } catch (err) {
      del.disabled = false;
      ui.notify('Plan gagal dihapus', err.message);
    }
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
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && mobile.matches) setOpen(false); });
  mobile.addEventListener('change', () => { sidebar.classList.remove('is-open'); scrim.hidden = true; });
})();
