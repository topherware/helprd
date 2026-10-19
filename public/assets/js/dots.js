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

  const REACH = [0.9, 1.46, 0.6, 1.3, 1.1, 1.52, 0.7, 1.24]; // panjang relatif 8 tonjolan (sisi, sudut, sisi, sudut, …), tak beraturan
  const ROWS = 13;         // jumlah baris titik melintang pita
  const STEP = 14;         // jarak antar titik sepanjang pita (px)
  const GAP = 16;          // jarak tepi dalam pita ke kotak judul + kotak ide (px), sama di semua sisi
  const THICK = 80;        // setengah tebal pita di sisi kiri-kanan (px): pita lebar, titik tersebar
  const THICK_Y = 50;      // setengah tebal pita di atas-bawah (px): lebih tipis karena ruang di bawah stepper terbatas
  const CURSOR_R = 120;    // radius pengaruh kursor
  const PUSH = 40;         // jarak sibak maksimum
  const PALE = [147, 197, 253];  // biru muda (#93C5FD, accent palet)
  const HOT = [37, 99, 235];      // biru pekat (#2563EB) untuk sorotan yang mengalir
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
  /** Setengah tebal pita menurut arah normal: penuh di samping, lebih tipis di atas-bawah. */
  const thick = (nx, ny) => Math.hypot(THICK * nx, THICK_Y * ny);

  /** Kotak yang dikelilingi pita: dari judul sampai kotak ide (+ tautan "lanjutkan" bila ada), relatif terhadap kanvas. */
  function target(rect) {
    const parts = [document.querySelector('.hero h1'), document.getElementById('ideForm'), document.querySelector('.hero > .link-muted')].filter(Boolean).map((n) => n.getBoundingClientRect());
    if (!parts.length) return { x: rect.width * 0.2, y: rect.height * 0.25, w: rect.width * 0.6, h: rect.height * 0.5 };
    const left = Math.min(...parts.map((r) => r.left));
    const right = Math.max(...parts.map((r) => r.right));
    const top = Math.min(...parts.map((r) => r.top));
    const bottom = Math.max(...parts.map((r) => r.bottom));
    return { x: left - rect.left, y: top - rect.top, w: right - left, h: bottom - top };
  }

  /** Haluskan sudut poligon tertutup (Chaikin satu putaran, rasio 0.22/0.78: sudut tegas, ujungnya tidak runcing). */
  function soften(pts, rounds) {
    let out = pts;
    for (let r = 0; r < rounds; r++) {
      const next = [];
      for (let i = 0; i < out.length; i++) {
        const [ax, ay] = out[i];
        const [bx, by] = out[(i + 1) % out.length];
        next.push([ax + (bx - ax) * 0.22, ay + (by - ay) * 0.22], [ax + (bx - ax) * 0.78, ay + (by - ay) * 0.78]);
      }
      out = next;
    }
    return out;
  }

  /**
   * Garis tengah pita: poligon 8 sudut tak beraturan di sekeliling kotak, diambil merata per panjang busur.
   * Jarak diukur dalam piksel dari tepi kotak (bukan skala elips), jadi celah kiri-kanan sama dengan atas-bawah
   * walau kotaknya lebar. Sudut kotak mendapat tonjolan panjang (ujung bintang), tengah sisi tonjolan pendek.
   */
  function outline(box) {
    const cx = box.x + box.w / 2;
    const cy = box.y + box.h / 2;
    // Garis tengah pita minimal GAP + setengah tebal dari kotak: pita tidak menyentuh konten.
    const hw = box.w / 2 + GAP + THICK;
    const hh = box.h / 2 + GAP + THICK_Y;
    // Titik pada kotak yang diperbesar, di arah sudut a dari pusat.
    const onBox = (a) => {
      const c = Math.cos(a);
      const s = Math.sin(a);
      const k = 1 / Math.max(Math.abs(c) / hw, Math.abs(s) / hh);
      return [cx + c * k, cy + s * k, c, s];
    };
    const diag = Math.atan2(hh, hw);
    // Urutan arah: kanan, kanan-bawah, bawah, kiri-bawah, kiri, kiri-atas, atas, kanan-atas (sedikit diacak).
    const dirs = [0, diag, Math.PI / 2, Math.PI - diag, Math.PI, Math.PI + diag, Math.PI * 1.5, Math.PI * 2 - diag];
    const shapeAt = (extra) => soften(dirs.map((a0, k) => {
      const a = a0 + (rand(k + 101) - 0.5) * (k % 2 ? 0.22 : 0.5);
      const [x, y, c, s] = onBox(a);
      // Panjang tonjolan (px): ujung di sudut kotak jauh, tengah sisi dekat; tak beraturan per sudut.
      const out = extra + REACH[k] * (k % 2 ? 95 : 20) + rand(k + 202) * 14;
      return [x + c * out, y + s * out];
    }), 1);
    // Kekurangan jarak titik p: jarak yang dibutuhkan (GAP + tebal pita ke arah p) dikurangi jarak sebenarnya ke kotak.
    const lackAt = ([x, y]) => {
      const ex = Math.max(box.x - x, 0, x - box.x - box.w);
      const ey = Math.max(box.y - y, 0, y - box.y - box.h);
      const d = Math.hypot(ex, ey) || 0.001;
      return GAP + thick(ex / d, ey / d) - d;
    };
    // Periksa juga titik di sepanjang sisi, bukan hanya sudut poligon.
    const dense = (pts) => pts.flatMap((p, i) => {
      const q = pts[(i + 1) % pts.length];
      return [0, 0.25, 0.5, 0.75].map((f) => [p[0] + (q[0] - p[0]) * f, p[1] + (q[1] - p[1]) * f]);
    });
    // Sisi poligon memotong sudut kotak: dorong seluruh tonjolan keluar sedikit demi sedikit sampai aman.
    let extra = 0;
    let poly = shapeAt(extra);
    for (let i = 0; i < 60; i++) {
      const lack = Math.max(...dense(poly).map(lackAt));
      if (lack <= 0) break;
      extra += Math.max(2, lack * 0.6);
      poly = shapeAt(extra);
    }
    // Batas lembut atas: ruang stepper (± 84px dari atas kanvas) tetap bersih dari pita.
    const top = 84 + THICK_Y;
    poly = poly.map(([x, y]) => [x, y < top ? top - (top - y) * 0.25 : y]);
    // Batas lembut kiri-kanan: sudut yang keluar kanvas (mis. sidebar terbuka) diredam ke dalam tepi.
    const left = THICK + 14;
    const right = w - THICK - 14;
    poly = poly.map(([x, y]) => [x < left ? left - (left - x) * 0.2 : x > right ? right + (x - right) * 0.2 : x, y]);
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
    const ts = new Float64Array(M);
    for (let m = 0; m < M; m++) ts[m] = thick(nxs[m], nys[m]);
    return { xs, ys, nxs, nys, ts, M, total };
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
        // Tersebar: baris pinggir makin jarang (sebagian titik dilewati), posisi digeser acak sepanjang & melintang pita.
        if (rand(id + 31) > 1 - 0.5 * Math.abs(v) ** 1.4) continue;
        const pos = (((i + (row % 2) * 0.5 + (rand(id + 41) - 0.5) * 0.8) / count) % 1 + 1) % 1;
        const jitter = (rand(id + 53) - 0.5) * 20;
        dots.push({
          idx: Math.floor(pos * path.M) % path.M, u: pos * Math.PI * 2, v, jitter,
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
    const { xs, ys, nxs, nys, ts } = path;
    for (const d of dots) {
      const u = d.u; // posisi sepanjang pita tetap: tidak memutar
      // Maju-mundur: titik bergeser keluar-masuk tegak lurus pita mengikuti gelombang yang merambat.
      const swing = Math.sin(time * 1.3 - u * 5 + d.v * 1.2) * 6 + Math.sin(time * 0.7 + d.phase) * 2;
      const off = d.v * ts[d.idx] + d.jitter + swing;
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
  [canvas, document.querySelector('.hero h1'), document.getElementById('ideForm'), document.getElementById('attachList')].filter(Boolean).forEach((n) => resized.observe(n));
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
