'use strict';

/**
 * Latar halaman awal: pita titik yang rapi mengelilingi judul dan kotak ide (persegi membulat/superellipse
 * yang ukurannya mengikuti konten), dengan sudut yang bernapas halus seperti bintang abstrak.
 * - Bentuk berubah pelan: sudut-sudut menonjol bergantian, pita sedikit menebal-menipis, riak halus merambat.
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

  const ROWS = 7;          // jumlah baris titik melintang pita
  const STEP = 17;         // jarak antar titik sepanjang pita (px)
  const GAP = 44;          // jarak tepi dalam pita ke kotak judul + kotak ide (px)
  const POWER = 3.2;       // eksponen superellipse: 2 = elips, makin besar makin mengikuti bentuk kotak
  const CURSOR_R = 120;    // radius pengaruh kursor
  const PUSH = 40;         // jarak sibak maksimum
  const PALE = [251, 176, 128];  // oranye pucat
  const HOT = [234, 88, 12];     // oranye pekat (sorotan yang mengalir)
  let w = 0;
  let h = 0;
  let cx = 0;
  let cy = 0;
  let ax = 0;
  let ay = 0;
  let dots = [];
  let frame = 0;
  let table = null; // tabel panjang busur superellipse: u (sudut) → posisi 0..1 sepanjang keliling
  const mouse = { x: -9999, y: -9999, tx: -9999, ty: -9999 };

  /** Bilangan acak deterministik per titik supaya pola stabil saat ukuran layar berubah. */
  const rand = (n) => {
    const x = Math.sin(n * 12.9898) * 43758.5453;
    return x - Math.floor(x);
  };
  /** Puncak lembut di sekitar selisih sudut 0 (untuk sudut bintang dan sorotan warna). */
  const peak = (delta, sharp) => (0.5 + 0.5 * Math.cos(delta)) ** sharp;
  const sgnPow = (v, e) => Math.sign(v) * Math.abs(v) ** e;

  /** Kotak yang dikelilingi pita: dari judul sampai kotak ide, relatif terhadap kanvas. */
  function target(rect) {
    const parts = [document.querySelector('.hero h1'), document.getElementById('ideForm')].filter(Boolean).map((n) => n.getBoundingClientRect());
    if (!parts.length) return { x: rect.width * 0.2, y: rect.height * 0.25, w: rect.width * 0.6, h: rect.height * 0.5 };
    const left = Math.min(...parts.map((r) => r.left));
    const right = Math.max(...parts.map((r) => r.right));
    const top = Math.min(...parts.map((r) => r.top));
    const bottom = Math.max(...parts.map((r) => r.bottom));
    return { x: left - rect.left, y: top - rect.top, w: right - left, h: bottom - top };
  }

  function build() {
    const rect = canvas.getBoundingClientRect();
    w = rect.width;
    h = rect.height;
    const dpr = Math.min(devicePixelRatio || 1, 2);
    canvas.width = Math.round(w * dpr);
    canvas.height = Math.round(h * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    const box = target(rect);
    cx = box.x + box.w / 2;
    cy = box.y + box.h / 2;
    // Garis tengah pita: setengah kotak + jarak + setengah tebal pita, sehingga baris terdalam tidak menyentuh konten.
    ax = Math.min(box.w / 2 + GAP + 36, w / 2 - 12);
    ay = box.h / 2 + GAP + 36;
    // Sampling merata sepanjang busur: parameter sudut superellipse merapat di sudut dan merenggang di sumbu.
    const N = 2048;
    const us = new Float64Array(N + 1);
    const ss = new Float64Array(N + 1);
    let prev = null;
    for (let i = 0; i <= N; i++) {
      const u = (i / N) * Math.PI * 2;
      const pt = [ax * sgnPow(Math.cos(u), 2 / POWER), ay * sgnPow(Math.sin(u), 2 / POWER)];
      us[i] = u;
      ss[i] = i ? ss[i - 1] + Math.hypot(pt[0] - prev[0], pt[1] - prev[1]) : 0;
      prev = pt;
    }
    for (let i = 0; i <= N; i++) ss[i] /= ss[N];
    table = { us, ss, N };
    const count = Math.round((4 * (ax + ay) * 0.92) / STEP);
    dots = [];
    let id = 0;
    for (let row = 0; row < ROWS; row++) {
      const v = (row / (ROWS - 1)) * 2 - 1; // -1 (dalam) … 1 (luar)
      for (let i = 0; i < count; i++) {
        id++;
        dots.push({
          pos: (i + (row % 2) * 0.5) / count, v,
          phase: rand(id) * Math.PI * 2, speed: 0.6 + rand(id + 7) * 0.9, tint: rand(id + 13), ox: 0, oy: 0,
        });
      }
    }
  }

  /** Sudut superellipse untuk posisi 0..1 sepanjang keliling (pencarian biner pada tabel panjang busur). */
  function angleAt(pos) {
    const { us, ss, N } = table;
    let lo = 0;
    let hi = N;
    while (hi - lo > 1) {
      const mid = (lo + hi) >> 1;
      if (ss[mid] < pos) lo = mid; else hi = mid;
    }
    const f = (pos - ss[lo]) / ((ss[hi] - ss[lo]) || 1);
    return us[lo] + (us[hi] - us[lo]) * f;
  }

  /** Napas bentuk: sudut-sudut sedikit menonjol bergantian (bintang abstrak halus), berputar pelan. */
  function breathe(u, time) {
    return 1 + 0.035 * peak(4 * (u - Math.PI / 4 - time * 0.04), 6) + 0.02 * Math.sin(2 * u + time * 0.4);
  }

  function draw(t) {
    ctx.clearRect(0, 0, w, h);
    mouse.x += (mouse.tx - mouse.x) * 0.18;
    mouse.y += (mouse.ty - mouse.y) * 0.18;
    const time = t / 1000;
    const flow = time * 0.45; // posisi sorotan warna yang mengalir sepanjang pita
    const e = 2 / POWER;
    for (const d of dots) {
      const u = angleAt((d.pos + time * 0.004) % 1);
      const c = Math.cos(u);
      const s = Math.sin(u);
      const k = breathe(u, time);
      // Titik garis tengah pada superellipse (persegi membulat yang mengikuti kotak) dan normal keluarnya.
      const px = ax * k * sgnPow(c, e);
      const py = ay * k * sgnPow(s, e);
      let nx = sgnPow(px / ax, POWER - 1) / ax;
      let ny = sgnPow(py / ay, POWER - 1) / ay;
      const nl = Math.hypot(nx, ny) || 1;
      nx /= nl;
      ny /= nl;
      const thick = 30 + 5 * Math.sin(u * 2 - time * 0.5);                  // pita sedikit menebal-menipis
      const ripple = Math.sin(u * 10 + d.v * 1.6 - time * 1.4) * 2.2;       // riak halus merambat
      const off = d.v * thick + ripple;
      let x = cx + px + nx * off;
      let y = cy + py + ny * off;
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
  const resized = new ResizeObserver(() => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => { build(); start(); }, 80);
  });
  // Ikuti ukuran kanvas dan kotak yang dikelilingi (mis. pesan galat menambah tinggi kotak ide).
  [canvas, document.querySelector('.hero h1'), document.getElementById('ideForm')].filter(Boolean).forEach((n) => resized.observe(n));
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
