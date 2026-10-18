'use strict';

/** POST JSON ke endpoint di folder api/. Melempar Error berisi pesan dari server bila gagal. */
async function api(endpoint, body = {}) {
  let res;
  try {
    res = await fetch('../api/' + endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify(body),
    });
  } catch {
    throw new Error('Tidak bisa terhubung ke server. Periksa koneksi kamu.');
  }
  const data = await res.json().catch(() => null);
  if (!res.ok || !data || !data.ok) {
    throw new Error((data && data.error) || 'Terjadi kesalahan. Coba lagi.');
  }
  return data;
}

function el(tag, attrs = {}, ...children) {
  const node = document.createElement(tag);
  for (const [key, value] of Object.entries(attrs)) {
    if (key === 'class') node.className = value;
    else if (key.startsWith('on')) node.addEventListener(key.slice(2), value);
    else if (value !== false && value != null) node.setAttribute(key, value === true ? '' : value);
  }
  node.append(...children.filter((c) => c != null));
  return node;
}

// Langkah 1: chip contoh ide + Ctrl/Cmd+Enter untuk kirim.
document.querySelectorAll('[data-example]').forEach((chip) => {
  chip.addEventListener('click', () => {
    const ta = document.getElementById('ide');
    ta.value = chip.dataset.example;
    ta.focus();
  });
});
document.getElementById('ide')?.addEventListener('keydown', (e) => {
  if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) e.target.form.requestSubmit();
});

// Langkah 2: chip saran teknologi menambah teks ke input.
document.querySelectorAll('[data-append]').forEach((chip) => {
  chip.addEventListener('click', () => {
    const input = document.getElementById('tech_stack');
    input.value = input.value.trim() ? input.value.trim() + ', ' + chip.dataset.append : chip.dataset.append;
    input.focus();
  });
});
