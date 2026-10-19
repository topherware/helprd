'use strict';

/**
 * Halaman Fitur penuh: sidebar daftar fitur (accordion sub-fitur) + detail fitur atau sub-fitur.
 * Rute: #/fitur/ID dan #/fitur/ID/sub/SUBID. Dikendalikan router di workspace.js lewat FiturPage.show().
 */
const FiturPage = (() => {
  const section = document.querySelector('[data-view="fitur"]');
  const STATUS = { direncanakan: 'Direncanakan', dikerjakan: 'Dikerjakan', selesai: 'Selesai' };
  const TASK_STATUS = { belum_mulai: 'Belum', dikerjakan: 'Dikerjakan', selesai: 'Selesai', gagal: 'Gagal' };
  const ICON = {
    list: '<path d="M4 6h16M4 12h16M4 18h16"/>', close: '<path d="M6 6l12 12M18 6 6 18"/>',
    prev: '<path d="m15 6-6 6 6 6"/>', next: '<path d="m9 6 6 6-6 6"/>',
    edit: '<path d="M4 20h4L19 9l-4-4L4 16zM14 6l4 4"/>', trash: '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/>',
    collapse: '<path d="M4 14h6v6M20 10h-6V4M14 10l7-7M3 21l7-7"/>', chevron: '<path d="m6 9 6 6 6-6"/>', check: '<path d="m5 12 4 4 10-10"/>',
  };

  let features = null;
  const tasksByFeature = new Map();
  let route = {};
  let sideOpen = true;

  document.addEventListener('ws:data', () => {
    features = null;
    tasksByFeature.clear();
  });

  const go = (hash) => { location.hash = hash; };
  const svgIcon = (path, size = 18) => {
    const span = el('span', { class: 'icon' });
    span.innerHTML = `<svg viewBox="0 0 24 24" width="${size}" height="${size}" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">${path}</svg>`;
    return span;
  };
  const iconBtn = (path, label, onclick, extra = {}) => el('button', { type: 'button', class: 'icon-btn', 'aria-label': label, title: label, onclick, ...extra }, svgIcon(path));
  const message = (...children) => section.replaceChildren(el('div', { class: 'ws-placeholder' }, ...children));

  async function show(r) {
    route = r;
    try {
      if (!features) {
        message(el('span', { class: 'spinner' }));
        features = (await api('feature_get.php')).features;
      }
      const f = features.find((x) => x.id === r.fitur);
      if (f && !tasksByFeature.has(f.id)) tasksByFeature.set(f.id, (await api('tasks_list.php', { fitur: f.id })).tasks);
    } catch (err) {
      return message(el('p', { class: 'form-error' }, err.message), el('button', { type: 'button', class: 'btn', onclick: () => show(r) }, 'Coba lagi'));
    }
    if (route !== r) return; // pengguna sudah pindah halaman saat data dimuat
    render();
  }

  function render() {
    const index = features.findIndex((x) => x.id === route.fitur);
    if (index < 0) {
      return message(el('h2', {}, 'Fitur tidak ditemukan'), el('a', { class: 'btn btn-ghost', href: '#/roadmap' }, 'Kembali ke Roadmap'));
    }
    const f = features[index];
    const sub = route.sub ? f.sub_fitur.find((s) => s.id === route.sub) : null;
    const tasks = tasksByFeature.get(f.id) || [];
    const body = sub ? subDetail(f, sub, tasks.filter((t) => t.sub_feature_id === sub.id)) : featureDetail(f, tasks);
    section.replaceChildren(...[
      sideOpen ? sidebar(f) : null,
      el('div', { class: 'ft-main' }, header(f, index), body),
    ].filter(Boolean));
  }

  function sidebar(f) {
    return el('aside', { class: 'ft-side' },
      el('div', { class: 'ft-side-head' },
        svgIcon(ICON.list), el('strong', {}, 'Fitur'), el('span', { class: 'muted' }, String(features.length)),
        iconBtn(ICON.close, 'Sembunyikan daftar fitur', () => { sideOpen = false; render(); })),
      el('ol', { class: 'ft-list' }, ...features.map((x, i) => {
        const active = x.id === f.id;
        return el('li', { class: active ? 'is-active' : '' },
          el('a', { class: 'ft-item' + (active && !route.sub ? ' is-current' : ''), href: `#/fitur/${x.id}` },
            el('span', { class: 'ft-icon' }, canvasIcon(x.ikon, 15)),
            el('span', { class: 'truncate' }, `${i + 1}. ${x.nama}`),
            svgIcon(ICON.chevron, 16)),
          active && x.sub_fitur.length ? el('ul', { class: 'ft-subs' }, ...x.sub_fitur.map((s) => {
            const done = s.task_total > 0 && s.task_selesai === s.task_total;
            return el('li', {}, el('a', { class: s.id === route.sub ? 'is-current' : '', href: `#/fitur/${x.id}/sub/${s.id}` },
              canvasIcon('kotak', 14), el('span', { class: 'truncate' }, s.nama),
              el('span', { class: 'ft-check' + (done ? ' is-done' : ''), title: done ? 'Semua task selesai' : `${s.task_selesai}/${s.task_total} task selesai` }, svgIcon(ICON.check, 14))));
          })) : null);
      })));
  }

  function header(f, index) {
    const nav = (i) => go(`#/fitur/${features[i].id}`);
    return el('div', { class: 'ft-head' },
      sideOpen ? null : iconBtn(ICON.list, 'Tampilkan daftar fitur', () => { sideOpen = true; render(); }),
      el('div', { class: 'rm-counter' },
        iconBtn(ICON.prev, 'Fitur sebelumnya', () => nav(index - 1), { disabled: index === 0 }),
        el('span', {}, `${index + 1} / ${features.length}`),
        iconBtn(ICON.next, 'Fitur berikutnya', () => nav(index + 1), { disabled: index === features.length - 1 })),
      el('div', { class: 'rm-panel-tools ft-tools' },
        iconBtn(ICON.edit, 'Ubah di Peta Fitur', () => { location.href = 'step4.php'; }),
        el('span', { class: 'rm-tools-sep' }),
        iconBtn(ICON.collapse, 'Kembali ke Roadmap', () => go(`#/roadmap?fitur=${f.id}`))));
  }

  function criteria(items) {
    return items.length
      ? el('ul', {}, ...items.map((c) => el('li', {}, c)))
      : el('p', { class: 'muted' }, 'Belum ada. Klik "Bikin Task" di tampilan PRD untuk menyusunnya.');
  }

  function featureDetail(f, tasks) {
    return el('div', { class: 'ft-content rm-detail' },
      el('h2', { class: 'ft-title' }, el('span', { class: 'rm-detail-icon' }, canvasIcon(f.ikon, 18)), f.nama),
      f.deskripsi ? el('p', { class: 'rm-lead' }, f.deskripsi) : null,
      el('p', { class: 'rm-status' }, el('span', { class: `chip-status chip-${f.status}` }, STATUS[f.status]), `${f.task_selesai}/${f.task_total} tugas selesai`),
      el('h3', {}, 'Tujuan'),
      f.tujuan ? el('p', {}, f.tujuan) : criteria([]),
      el('h3', {}, 'Selesai bila'),
      criteria(f.selesai_bila),
      taskCard(tasks, f.id));
  }

  function subDetail(f, s, tasks) {
    return el('div', { class: 'ft-content rm-detail' },
      el('a', { class: 'ft-crumb', href: `#/fitur/${f.id}` }, `‹ ${f.nama}`),
      el('h2', { class: 'ft-title' }, el('span', { class: 'rm-detail-icon' }, canvasIcon('kotak', 18)), s.nama),
      el('p', { class: 'rm-status' }, `${s.task_selesai}/${s.task_total} tugas selesai`),
      el('h3', {}, 'Tujuan'),
      s.tujuan ? el('p', {}, s.tujuan) : criteria([]),
      el('h3', {}, 'Selesai bila'),
      criteria(s.selesai_bila),
      taskCard(tasks, f.id));
  }

  function taskCard(tasks, featureId) {
    return el('div', { class: 'ft-tasks' },
      el('div', { class: 'ft-tasks-head' },
        el('strong', {}, `Task (${tasks.length})`),
        el('a', { href: `#/kanban?fitur=${featureId}` }, 'Buka di Kanban →')),
      ...(tasks.length
        ? tasks.slice(0, 2).map((t) => el('div', { class: 'ft-task' }, el('span', { class: 'truncate' }, t.title),
          el('span', { class: `ft-task-status st-${t.status}` }, TASK_STATUS[t.status])))
        : [el('p', { class: 'muted' }, 'Belum ada task. Klik "Bikin Task" di tampilan PRD.')]),
      tasks.length > 2 ? el('p', { class: 'muted small' }, `+${tasks.length - 2} task lainnya`) : null);
  }

  return { show };
})();
