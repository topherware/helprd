'use strict';

/* Kanvas node bersama (peta fitur langkah 4 dan Roadmap di workspace): pan, zoom, garis bezier, ikon. */

const CANVAS_ICONS = {
  bintang: '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>',
  rumah: '<path d="M3 11 12 4l9 7M5 10v10h14V10"/>',
  keranjang: '<path d="M3 4h2l2.4 11h11L21 7H6"/><circle cx="9" cy="19.5" r="1.3"/><circle cx="17" cy="19.5" r="1.3"/>',
  pengguna: '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>',
  dokumen: '<path d="M6 3h9l4 4v14H6zM14 3v5h5M9 13h7M9 17h5"/>',
  kalender: '<rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
  chat: '<path d="M4 5h16v11H9l-5 4z"/>',
  grafik: '<path d="M5 20V11M11 20V5M17 20v-7M3 20h18"/>',
  kunci: '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
  lokasi: '<path d="M12 21s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/>',
  uang: '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.8"/>',
  gambar: '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.8"/><path d="m21 16-5-5-9 9"/>',
  pengaturan: '<circle cx="12" cy="12" r="3"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M5.3 18.7l2.1-2.1M16.6 7.4l2.1-2.1"/>',
  notifikasi: '<path d="M6 16v-5a6 6 0 0 1 12 0v5l2 2H4zM10 21h4"/>',
  pencarian: '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.4-4.4"/>',
  daftar: '<path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01"/>',
  centang: '<path d="m5 12 4 4 10-10"/>',
  kotak: '<rect x="4" y="4" width="16" height="16" rx="3"/>',
  kotakCentang: '<rect x="4" y="4" width="16" height="16" rx="3"/><path d="m8 12 3 3 5-6"/>',
  progres: '<circle cx="12" cy="12" r="8"/>',
};

function canvasIcon(name, size = 14) {
  const span = el('span', { class: 'icon' });
  span.innerHTML = `<svg viewBox="0 0 24 24" width="${size}" height="${size}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${CANVAS_ICONS[name] || CANVAS_ICONS.bintang}</svg>`;
  return span;
}

/**
 * Pasang perilaku kanvas pada viewport. Tombol [data-zoom="in|out|fit"] dicari di dalam zoomRoot.
 * Pan dimulai dari area kosong; elemen interaktif (tombol, tautan, input, panel) tidak memulai pan.
 */
function createCanvas(viewport, world, edges, { zoomRoot = document } = {}) {
  const SVG_NS = 'http://www.w3.org/2000/svg';
  const view = { x: 0, y: 0, k: 1 };
  let bounds = { x: 0, y: 0, w: 1, h: 1 };

  function apply() {
    world.style.transform = `translate(${view.x}px, ${view.y}px) scale(${view.k})`;
    // Grid latar ikut digeser & diperbesar bersama kanvas (seperti editor diagram).
    viewport.style.setProperty('--grid', `${20 * view.k}px`);
    viewport.style.backgroundPosition = `${view.x}px ${view.y}px`;
  }

  function zoomAt(px, py, factor) {
    const k = Math.min(2.5, Math.max(0.25, view.k * factor));
    view.x = px - (px - view.x) * (k / view.k);
    view.y = py - (py - view.y) * (k / view.k);
    view.k = k;
    apply();
  }

  // minK > 0: jangan perkecil di bawah minK supaya teks tetap terbaca; peta besar lalu dirata kiri-atas.
  function fit(minK = 0) {
    const vw = viewport.clientWidth;
    const vh = viewport.clientHeight;
    view.k = Math.max(minK, Math.min(1.1, vw / bounds.w, vh / bounds.h));
    const w = bounds.w * view.k;
    const h = bounds.h * view.k;
    view.x = (w <= vw ? (vw - w) / 2 : 0) - bounds.x * view.k;
    view.y = (h <= vh ? (vh - h) / 2 : 0) - bounds.y * view.k;
    apply();
  }

  function place(node, x, y) {
    node.style.left = x + 'px';
    node.style.top = y + 'px';
  }

  function anchor(node, side) {
    const x = node.offsetLeft + (side === 'right' ? node.offsetWidth : 0);
    return { x, y: node.offsetTop + node.offsetHeight / 2 };
  }

  function curve(a, b, className) {
    const dx = Math.max(40, (b.x - a.x) / 2);
    const path = document.createElementNS(SVG_NS, 'path');
    path.setAttribute('d', `M${a.x},${a.y} C${a.x + dx},${a.y} ${b.x - dx},${b.y} ${b.x},${b.y}`);
    path.setAttribute('class', `edge ${className}`);
    edges.append(path);
    [a, b].forEach((p) => {
      const c = document.createElementNS(SVG_NS, 'circle');
      c.setAttribute('cx', p.x);
      c.setAttribute('cy', p.y);
      c.setAttribute('r', 3);
      c.setAttribute('class', 'edge-dot');
      edges.append(c);
    });
  }

  function clear() {
    world.querySelectorAll('.node, .group').forEach((n) => n.remove());
    edges.replaceChildren();
  }

  // Pan baru dimulai setelah pointer bergeser > 4px, supaya klik biasa pada kartu tetap menjadi klik.
  let drag = null;
  viewport.addEventListener('pointerdown', (e) => {
    if (e.button !== 0 || e.target.closest('button, a, input, select, textarea, .panel, .legend, .zoom-controls')) return;
    drag = { id: e.pointerId, x: e.clientX - view.x, y: e.clientY - view.y, sx: e.clientX, sy: e.clientY, moving: false };
  });
  viewport.addEventListener('pointermove', (e) => {
    if (!drag || drag.id !== e.pointerId) return;
    if (!drag.moving) {
      if (Math.hypot(e.clientX - drag.sx, e.clientY - drag.sy) < 4) return;
      drag.moving = true;
      viewport.setPointerCapture(e.pointerId);
      viewport.classList.add('is-panning');
    }
    view.x = e.clientX - drag.x;
    view.y = e.clientY - drag.y;
    apply();
  });
  const endDrag = () => { drag = null; viewport.classList.remove('is-panning'); };
  viewport.addEventListener('pointerup', endDrag);
  viewport.addEventListener('pointercancel', endDrag);

  viewport.addEventListener('wheel', (e) => {
    if (e.target.closest('.panel')) return;
    e.preventDefault();
    const rect = viewport.getBoundingClientRect();
    zoomAt(e.clientX - rect.left, e.clientY - rect.top, Math.exp(-e.deltaY * 0.0015));
  }, { passive: false });

  zoomRoot.querySelectorAll('[data-zoom]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const action = btn.dataset.zoom;
      if (action === 'fit') return fit();
      zoomAt(viewport.clientWidth / 2, viewport.clientHeight / 2, action === 'in' ? 1.2 : 1 / 1.2);
    });
  });

  return { view, fit, zoomAt, place, anchor, curve, clear, setBounds: (b) => { bounds = b; } };
}
