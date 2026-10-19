'use strict';

/**
 * Latar halaman awal: pita titik yang rapi mengelilingi judul dan kotak ide, berbentuk poligon 8 sudut tak beraturan
 * (sudut sedikit dihaluskan) seperti bintang abstrak. Ukuran mengikuti konten; posisi sudut tetap (tidak memutar).
 * - Titik bergerak maju-mundur tegak lurus pita mengikuti gelombang yang merambat.
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

  const CORNERS = 8;       // jumlah sudut bentuk
  const ROWS = 7;          // jumlah baris titik melintang pita
  const STEP = 17;         // jarak antar titik sepanjang pita (px)
  const GAP = 40;          // jarak tepi dalam pita ke kotak judul + kotak ide (px)
  const THICK = 32;        // setengah tebal pita (px)
  const CURSOR_R = 120;    // radius pengaruh kursor
  const PUSH = 40;         // jarak sibak maksimum
  const PALE = [251, 176, 128];  // oranye pucat
  const HOT = [234, 88, 12];     // oranye pekat (sorotan yang mengalir)
  let w = 0;
  let h = 0;
  let dots = [];
  let frame = 0;
  let path = null; // garis tengah pita: titik & normal keluar, diambil merata sepanjang keliling
  const mouse = { x: -9999, y: -9999, tx: -9999, ty: -9999 };

  /** Bilangan acak deterministik supaya bentuk & pola stabil saat ukuran layar berubah. */
  const rand = (n) => {
    const x = Math.sin(n * 12.9898) * 43758.5453;
    return x - Math.floor(x);
  };
  /** Puncak lembut di sekitar selisih sudut 0 (untuk sorotan warna). */
  const peak = (delta, sharp) => (0.5 + 0.5 * Math.cos(delta)) ** sharp;

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

  /** Haluskan sudut poligon tertutup (Chaikin dengan rasio 0.18/0.82: sudut tetap terlihat, tidak runcing). */
  function soften(pts, rounds) {
    let out = pts;
    for (let r = 0; r < rounds; r++) {
      const next = [];
      for (let i = 0; i < out.length; i++) {
        const [ax, ay] = out[i];
        const [bx, by] = out[(i + 1) % out.length];
        next.push([ax + (bx - ax) * 0.18, ay + (by - ay) * 0.18], [ax + (bx - ax) * 0.82, ay + (by - ay) * 0.82]);
      }
      out = next;
    }
    return out;
  }

  /** Garis tengah pita: poligon 8 sudut tak beraturan di sekeliling kotak, diambil merata per panjang busur. */
  function outline(box) {
    const cx = box.x + box.w / 2;
    const cy = box.y + box.h / 2;
    // Sudut tak beraturan di sekitar elips seukuran kotak; panjang sudut selang-seling seperti bintang abstrak.
    const shapeAt = (scale) => {
      const verts = [];
      for (let k = 0; k < CORNERS; k++) {
        const a = (k / CORNERS) * Math.PI * 2 + Math.PI / CORNERS + (rand(k + 101) - 0.5) * 0.5;
        const r = 1 + (k % 2 ? 0.02 : 0.16) + rand(k + 202) * 0.08;
        // Tonjolan sudut lebih ke samping daripada ke atas/bawah, supaya pita tidak menabrak stepper.
        verts.push([cx + Math.cos(a) * (box.w / 2) * r * scale.x, cy + Math.sin(a) * (box.h / 2) * (1 + (r - 1) * 0.35) * scale.y]);
      }
      return soften(verts, 2);
    };
    // Perbesar bertahap sampai seluruh garis tengah pita berjarak ≥ GAP + THICK dari kotak (pita tidak menyentuh konten).
    const need = GAP + THICK;
    const distToBox = ([x, y]) => Math.hypot(Math.max(box.x - x, 0, x - box.x - box.w), Math.max(box.y - y, 0, y - box.y - box.h));
    // Periksa juga titik di sepanjang sisi, bukan hanya sudut poligon.
    const dense = (pts) => pts.flatMap((p, i) => {
      const q = pts[(i + 1) % pts.length];
      return [0, 0.25, 0.5, 0.75].map((f) => [p[0] + (q[0] - p[0]) * f, p[1] + (q[1] - p[1]) * f]);
    });
    const scale = { x: 1.05, y: 1.1 };
    let poly = shapeAt(scale);
    // Bentuk awal sudah keluar kanvas (layar sempit): kecilkan lebarnya dulu.
    while (scale.x > 0.6 && poly.some(([x]) => x < THICK + 12 || x > w - THICK - 12)) {
      scale.x *= 0.97;
      poly = shapeAt(scale);
    }
    for (let i = 0; i < 80 && Math.min(...dense(poly).map(distToBox)) < need; i++) {
      const worst = dense(poly).reduce((m, p) => (distToBox(p) < distToBox(m) ? p : m));
      // Tambah skala pada sumbu yang paling membantu titik terdekat (sisi atas/bawah → y, sisi kiri/kanan → x).
      const dx = Math.abs(worst[0] - cx) / (box.w / 2);
      const dy = Math.abs(worst[1] - cy) / (box.h / 2);
      // Lebar kanvas terbatas (mis. sidebar terbuka): bila melebar akan keluar kanvas, perbesar ke arah vertikal saja.
      const wide = shapeAt({ x: scale.x * 1.025, y: scale.y }).some(([x]) => x < THICK + 12 || x > w - THICK - 12);
      if (dx >= dy && !wide) scale.x *= 1.025; else scale.y *= 1.025;
      poly = shapeAt(scale);
    }
    // Batas lembut atas: ruang stepper (± 80px dari atas kanvas) tetap bersih dari pita.
    const top = 80 + THICK + 10;
    poly = poly.map(([x, y]) => [x, y < top ? top - (top - y) * 0.25 : y]);
    const lens = [0];
    for (let i = 1; i <= poly.length; i++) {
      const [ax, ay] = poly[i - 1];
      const [bx, by] = poly[i % poly.length];
      lens.push(lens[i - 1] + Math.hypot(bx - ax, by - ay));
    }
    const total = lens[poly.length];
    const M = 1600;
    const xs = new Float64Array(M);
    const ys = new Float64Array(M);
    let seg = 0;
    for (let m = 0; m < M; m++) {
      const target = (m / M) * total;
      while (lens[seg + 1] < target) seg++;
      const [ax, ay] = poly[seg];
      const [bx, by] = poly[(seg + 1) % poly.length];
      const f = (target - lens[seg]) / ((lens[seg + 1] - lens[seg]) || 1);
      xs[m] = ax + (bx - ax) * f;
      ys[m] = ay + (by - ay) * f;
    }
    const nxs = new Float64Array(M);
    const nys = new Float64Array(M);
    for (let m = 0; m < M; m++) {
      const tx = xs[(m + 3) % M] - xs[(m - 3 + M) % M];
      const ty = ys[(m + 3) % M] - ys[(m - 3 + M) % M];
      let nx = ty;
      let ny = -tx;
      if (nx * (xs[m] - cx) + ny * (ys[m] - cy) < 0) {
        nx = -nx;
        ny = -ny;
      }
      const l = Math.hypot(nx, ny) || 1;
      nxs[m] = nx / l;
      nys[m] = ny / l;
    }
    return { xs, ys, nxs, nys, M, total };
  }

  function build() {
    if (!canvas.offsetWidth) return;
    const rect = canvas.getBoundingClientRect();
    w = rect.width;
    h = rect.height;
    const dpr = Math.min(devicePixelRatio || 1, 2);
    canvas.width = Math.round(w * dpr);
    canvas.height = Math.round(h * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    path = outline(target(rect));
    const count = Math.round(path.total / STEP);
    dots = [];
    let id = 0;
    for (let row = 0; row < ROWS; row++) {
      const v = (row / (ROWS - 1)) * 2 - 1; // -1 (dalam) … 1 (luar)
      for (let i = 0; i < count; i++) {
        id++;
        const pos = (i + (row % 2) * 0.5) / count;
        dots.push({
          idx: Math.floor(pos * path.M) % path.M, u: pos * Math.PI * 2, v,
          phase: rand(id) * Math.PI * 2, speed: 0.6 + rand(id + 7) * 0.9, tint: rand(id + 13), ox: 0, oy: 0,
        });
      }
    }
  }

  function draw(t) {
    ctx.clearRect(0, 0, w, h);
    mouse.x += (mouse.tx - mouse.x) * 0.18;
    mouse.y += (mouse.ty - mouse.y) * 0.18;
    const time = t / 1000;
    const flow = time * 0.45; // posisi sorotan warna yang mengalir sepanjang pita
    const { xs, ys, nxs, nys } = path;
    for (const d of dots) {
      const u = d.u; // posisi sepanjang pita tetap: tidak memutar
      // Maju-mundur: titik bergeser keluar-masuk tegak lurus pita mengikuti gelombang yang merambat.
      const swing = Math.sin(time * 1.3 - u * 5 + d.v * 1.2) * 6 + Math.sin(time * 0.7 + d.phase) * 2;
      const off = d.v * THICK + swing;
      let x = xs[d.idx] + nxs[d.idx] * off;
      let y = ys[d.idx] + nys[d.idx] * off;
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
    if (!canvas.offsetWidth) return; // kanvas disembunyikan (layar sempit): tidak menggambar
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
