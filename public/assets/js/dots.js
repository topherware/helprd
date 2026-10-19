'use strict';

/**
 * Latar halaman awal: pita titik berbentuk bintang abstrak (beberapa sudut membulat) yang melingkari judul.
 * - Bentuk berubah pelan: sudut-sudut berputar dan menguat/melemah, pita menebal-menipis, riak kecil merambat.
 * - Ukuran titik membesar-mengecil mengikuti gelombang yang berjalan.
 * - Warna mengalir: sorotan oranye pekat berjalan sepanjang pita, sisanya oranye pucat.
 * - Titik menyibak menjauhi kursor.
 * Dekoratif (aria-hidden, pointer-events: none). prefers-reduced-motion: digambar sekali tanpa animasi.
 */
(() => {
  const canvas = document.getElementById('dots');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  const ROWS = 8;          // jumlah baris titik melintang pita
  const STEP = 19;         // jarak antar titik sepanjang pita (px)
  const CURSOR_R = 120;    // radius pengaruh kursor
  const PUSH = 40;         // jarak sibak maksimum
  const PALE = [251, 176, 128];  // oranye pucat
  const HOT = [234, 88, 12];     // oranye pekat (sorotan yang mengalir)
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
  /** Puncak lembut di sekitar selisih sudut 0 (untuk sudut bintang dan sorotan warna). */
  const peak = (delta, sharp) => (0.5 + 0.5 * Math.cos(delta)) ** sharp;

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
    rx = Math.max(300, w * 0.37);
    ry = Math.max(230, h * 0.36);
    const perimeter = Math.PI * (3 * (rx + ry) - Math.sqrt((3 * rx + ry) * (rx + 3 * ry))) * 1.1;
    const count = Math.round(perimeter / STEP);
    dots = [];
    let id = 0;
    for (let row = 0; row < ROWS; row++) {
      const v = (row / (ROWS - 1)) * 2 - 1; // -1 (dalam) … 1 (luar)
      for (let i = 0; i < count; i++) {
        id++;
        dots.push({
          u: ((i + (row % 2) * 0.5) / count) * Math.PI * 2, v,
          phase: rand(id) * Math.PI * 2, speed: 0.6 + rand(id + 7) * 0.9, tint: rand(id + 13), ox: 0, oy: 0,
        });
      }
    }
  }

  /** Jari-jari relatif bentuk bintang abstrak: 4 sudut utama + 3 sudut sekunder, berputar pelan & bernapas. */
  function shape(u, time) {
    const corners = 0.2 + 0.06 * Math.sin(time * 0.4);
    return 0.86
      + corners * peak(4 * (u - 0.35 - time * 0.03), 7)
      + 0.1 * peak(3 * (u + 0.9 + time * 0.02), 6)
      + 0.025 * Math.sin(2 * u + time * 0.35);
  }

  function draw(t) {
    ctx.clearRect(0, 0, w, h);
    mouse.x += (mouse.tx - mouse.x) * 0.18;
    mouse.y += (mouse.ty - mouse.y) * 0.18;
    const time = t / 1000;
    const flow = time * 0.45; // posisi sorotan warna yang mengalir sepanjang pita
    for (const d of dots) {
      const u = d.u + time * 0.02;
      const radial = shape(u, time);
      const thick = 58 + 20 * Math.sin(u * 2 - time * 0.45) + 10 * Math.sin(u * 5 + time * 0.6); // pita menebal-menipis (min ±28px)
      const ripple = Math.sin(u * 9 + d.v * 2.2 - time * 1.4) * 5;                             // riak kecil merambat
      const off = d.v * thick + ripple;
      let x = cx + Math.cos(u) * (rx * radial + off);
      let y = cy + Math.sin(u) * (ry * radial + off);
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

      // Warna mengalir: dua sorotan oranye pekat berjalan memutar pita (sedikit bergeser per baris supaya terlihat mengalir).
      const lag = d.v * 0.25;
      const hot = Math.max(peak(u - flow - lag, 14), 0.8 * peak(u - flow - Math.PI - lag, 18));
      const mix = Math.min(1, hot * (0.75 + 0.5 * d.tint));
      const r = Math.round(PALE[0] + (HOT[0] - PALE[0]) * mix);
      const g = Math.round(PALE[1] + (HOT[1] - PALE[1]) * mix);
      const b = Math.round(PALE[2] + (HOT[2] - PALE[2]) * mix);

      // Ukuran membesar-mengecil: gelombang berjalan + denyut per titik; titik di sorotan sedikit lebih besar.
      const pulse = 0.5 + 0.5 * Math.sin(u * 7 + d.v * 1.5 - time * 1.8);
      const beat = 0.5 + 0.5 * Math.sin(time * d.speed + d.phase);
      const size = (0.9 + 1.6 * (0.6 * pulse + 0.4 * beat)) * (1 - Math.abs(d.v) * 0.2) + mix * 0.8;

      // Hilang-timbul: opasitas ikut denyut; tepi pita lebih tipis.
      const alpha = (0.45 + 0.55 * beat) * (1 - Math.abs(d.v) * 0.35) * (0.7 + 0.3 * mix + 0.2 * pulse);
      if (alpha < 0.04 || size < 0.3) continue;
      ctx.fillStyle = `rgba(${r}, ${g}, ${b}, ${Math.min(1, alpha)})`;
      ctx.beginPath();
      ctx.arc(x, y, size, 0, Math.PI * 2);
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
