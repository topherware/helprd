'use strict';

/**
 * Latar halaman awal: pita titik bergelombang yang melingkari judul (bukan menutupi seluruh halaman).
 * Bentuk pita berubah pelan seperti ombak (jari-jari & ketebalan berdenyut), opasitas titik hilang-timbul,
 * dan titik menyibak menjauhi kursor. Warna: oranye dan oranye pucat. Dekoratif (aria-hidden, pointer-events: none).
 * prefers-reduced-motion: digambar sekali tanpa animasi.
 */
(() => {
  const canvas = document.getElementById('dots');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  const ROWS = 9;          // jumlah baris titik melintang pita
  const STEP = 17;         // jarak antar titik sepanjang pita (px)
  const CURSOR_R = 120;    // radius pengaruh kursor
  const PUSH = 40;         // jarak sibak maksimum
  const COLORS = ['234, 88, 12', '249, 115, 22', '253, 186, 140']; // oranye tua, oranye, oranye pucat
  let w = 0;
  let h = 0;
  let cx = 0;
  let cy = 0;
  let rx = 0;
  let ry = 0;
  let dots = [];
  let frame = 0;
  const mouse = { x: -9999, y: -9999, tx: -9999, ty: -9999 };

  /** Bilangan acak deterministik per titik supaya pola stabil saat ukuran layar berubah. */
  const rand = (n) => {
    const x = Math.sin(n * 12.9898) * 43758.5453;
    return x - Math.floor(x);
  };

  function build() {
    const rect = canvas.getBoundingClientRect();
    w = rect.width;
    h = rect.height;
    const dpr = Math.min(devicePixelRatio || 1, 2);
    canvas.width = Math.round(w * dpr);
    canvas.height = Math.round(h * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    cx = w / 2;
    cy = h * 0.5;
    rx = Math.max(320, w * 0.4);
    ry = Math.max(240, h * 0.38);
    const perimeter = Math.PI * (3 * (rx + ry) - Math.sqrt((3 * rx + ry) * (rx + 3 * ry)));
    const count = Math.round(perimeter / STEP);
    dots = [];
    let id = 0;
    for (let row = 0; row < ROWS; row++) {
      const v = (row / (ROWS - 1)) * 2 - 1; // -1 (dalam) … 1 (luar)
      for (let i = 0; i < count; i++) {
        id++;
        const tone = rand(id + 13);
        dots.push({
          u: ((i + (row % 2) * 0.5) / count) * Math.PI * 2, v,
          phase: rand(id) * Math.PI * 2, speed: 0.7 + rand(id + 7) * 1.1,
          color: tone < 0.22 ? 0 : tone < 0.55 ? 1 : 2, ox: 0, oy: 0,
        });
      }
    }
  }

  function draw(t) {
    ctx.clearRect(0, 0, w, h);
    mouse.x += (mouse.tx - mouse.x) * 0.18;
    mouse.y += (mouse.ty - mouse.y) * 0.18;
    const time = t / 1000;
    for (const d of dots) {
      // Bentuk pita: elips yang berombak (jari-jari dimodulasi beberapa gelombang yang bergeser pelan).
      const u = d.u + time * 0.025;
      const radial = 1 + 0.07 * Math.sin(2 * u + time * 0.35) + 0.05 * Math.sin(3 * u - time * 0.5) + 0.03 * Math.sin(5 * u + time * 0.8);
      const thick = 46 + 30 * Math.sin(u * 2 - time * 0.45) + 14 * Math.sin(u * 5 + time * 0.6); // pita menebal-menipis
      const ripple = Math.sin(u * 9 + d.v * 2.2 - time * 1.4) * 5;                             // riak kecil merambat
      const off = d.v * thick + ripple;
      const ex = Math.cos(u) * rx * radial;
      const ey = Math.sin(u) * ry * radial;
      let x = cx + ex + Math.cos(u) * off;
      let y = cy + ey + Math.sin(u) * off;
      if (x < -20 || x > w + 20 || y < -20 || y > h + 20) continue;

      // Sibak kursor: titik terdorong menjauh secara radial, kembali pelan (pegas).
      const dx = x - mouse.x;
      const dy = y - mouse.y;
      const dist = Math.hypot(dx, dy);
      let tx = 0;
      let ty = 0;
      if (dist < CURSOR_R && dist > 0.001) {
        const f = (1 - dist / CURSOR_R) ** 2 * PUSH;
        tx = (dx / dist) * f;
        ty = (dy / dist) * f;
      }
      d.ox += (tx - d.ox) * 0.14;
      d.oy += (ty - d.oy) * 0.14;
      x += d.ox;
      y += d.oy;

      // Hilang-timbul: kedip per titik × pita terang yang berjalan memutar × tepi pita lebih tipis.
      const twinkle = 0.5 + 0.5 * Math.sin(time * d.speed + d.phase);
      const sweep = 0.35 + 0.65 * (0.5 + 0.5 * Math.sin(u * 1.5 - time * 0.6));
      const edge = 1 - Math.abs(d.v) * 0.55;
      const alpha = (0.12 + 0.8 * twinkle) * sweep * edge;
      if (alpha < 0.03) continue;
      ctx.fillStyle = `rgba(${COLORS[d.color]}, ${alpha})`;
      ctx.beginPath();
      ctx.arc(x, y, d.color === 0 ? 1.9 : 1.6 + (1 - Math.abs(d.v)) * 0.6, 0, Math.PI * 2);
      ctx.fill();
    }
  }

  function loop(t) {
    draw(t);
    frame = requestAnimationFrame(loop);
  }

  function start() {
    cancelAnimationFrame(frame);
    if (reduce) {
      draw(0);
    } else {
      frame = requestAnimationFrame(loop);
    }
  }

  let resizeTimer = 0;
  new ResizeObserver(() => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => { build(); start(); }, 80);
  }).observe(canvas);
  addEventListener('pointermove', (e) => {
    const rect = canvas.getBoundingClientRect();
    mouse.tx = e.clientX - rect.left;
    mouse.ty = e.clientY - rect.top;
    if (mouse.x < -9000) {
      mouse.x = mouse.tx;
      mouse.y = mouse.ty;
    }
  }, { passive: true });
  document.addEventListener('pointerleave', () => { mouse.tx = -9999; mouse.ty = -9999; });
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) cancelAnimationFrame(frame);
    else start();
  });

  build();
  start();
})();
