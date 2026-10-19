'use strict';

/**
 * Latar halaman awal: titik-titik pada cincin konsentris yang bergerak seperti ombak (posisi berdenyut radial),
 * opasitasnya hilang-timbul, dan menyibak menjauhi kursor. Dekoratif saja (canvas aria-hidden, pointer-events: none).
 * prefers-reduced-motion: digambar sekali tanpa animasi.
 */
(() => {
  const canvas = document.getElementById('dots');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  const RING_GAP = 30;     // jarak antar cincin (px)
  const DOT_GAP = 15;      // jarak antar titik dalam satu cincin (px)
  const CURSOR_R = 130;    // radius pengaruh kursor
  const PUSH = 46;         // jarak sibak maksimum
  let w = 0;
  let h = 0;
  let dpr = 1;
  let cx = 0;
  let cy = 0;
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
    dpr = Math.min(devicePixelRatio || 1, 2);
    canvas.width = Math.round(w * dpr);
    canvas.height = Math.round(h * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    cx = w / 2;
    cy = h * 0.47;
    const inner = Math.min(w, h) * 0.36;
    const outer = Math.hypot(w, h) * 0.62;
    dots = [];
    let id = 0;
    for (let r = inner, ring = 0; r < outer; r += RING_GAP, ring++) {
      const n = Math.max(12, Math.round((Math.PI * 2 * r) / DOT_GAP));
      const offset = rand(ring + 1) * Math.PI * 2;
      for (let i = 0; i < n; i++) {
        id++;
        dots.push({
          r, a: offset + (i / n) * Math.PI * 2, ring,
          phase: rand(id) * Math.PI * 2, speed: 0.6 + rand(id + 7) * 0.9,
          accent: rand(id + 13) < 0.06, ox: 0, oy: 0,
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
      // Ombak: jari-jari berdenyut dengan gelombang yang merambat ke luar, cincin berputar sangat pelan.
      const wave = Math.sin(d.r * 0.016 - time * 1.1 + d.a * 2);
      const rr = d.r + wave * 7;
      const ang = d.a + time * 0.012 * (d.ring % 2 ? 1 : -1);
      let x = cx + Math.cos(ang) * rr * 1.18;
      let y = cy + Math.sin(ang) * rr;
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

      // Hilang-timbul: kedip per titik dikali pita terang yang ikut merambat bersama ombak.
      const twinkle = 0.5 + 0.5 * Math.sin(time * d.speed + d.phase);
      const band = 0.55 + 0.45 * Math.sin(d.r * 0.011 - time * 0.8);
      const fade = Math.min(1, (d.r - Math.min(w, h) * 0.36) / 140 + 0.2);
      const alpha = (0.16 + 0.72 * twinkle * band) * fade;
      ctx.fillStyle = d.accent ? `rgba(234, 88, 12, ${Math.min(0.95, alpha + 0.12)})` : `rgba(63, 63, 70, ${alpha})`;
      const s = d.accent ? 2.3 : 1.7;
      ctx.fillRect(x - s / 2, y - s / 2, s, s);
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
