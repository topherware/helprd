'use strict';

/**
 * Kotak ide di halaman awal:
 * - Lampiran referensi: pilih lewat tombol klip, seret berkas ke kotak, atau tempel gambar (Ctrl+V).
 *   Daftar berkas disinkronkan ke <input type="file"> lewat DataTransfer, jadi form tetap dikirim biasa (multipart).
 *   Batas jumlah/ukuran/jenis dicek di sini untuk umpan balik cepat; server tetap memeriksa ulang (Validator::lampiran).
 * - Mikrofon: dikte lewat Web Speech API (SpeechRecognition). Tombol disembunyikan bila browser tidak mendukung.
 */
(() => {
  const form = document.getElementById('ideForm');
  if (!form) return;
  const textarea = document.getElementById('ide');
  const input = document.getElementById('lampiran');
  const listEl = document.getElementById('attachList');
  const note = document.getElementById('composerNote');
  const MAX_FILES = Number(form.dataset.maxFiles) || 5;
  const MAX_BYTES = Number(form.dataset.maxBytes) || 5 * 1024 * 1024;
  const TYPES = ['image/png', 'image/jpeg', 'image/webp', 'image/gif', 'application/pdf', 'text/plain', 'text/markdown'];
  const t = (s) => I18N.tr(s);
  let files = [];

  const allowed = (f) => TYPES.includes(f.type) || /\.(md|markdown|txt)$/i.test(f.name);
  const size = (n) => (n < 1024 * 1024 ? `${Math.max(1, Math.round(n / 1024))} KB` : `${(n / 1048576).toFixed(1)} MB`);

  function sync() {
    const dt = new DataTransfer();
    files.forEach((f) => dt.items.add(f));
    input.files = dt.files;
    render();
  }

  function render() {
    listEl.querySelectorAll('img').forEach((img) => URL.revokeObjectURL(img.src));
    listEl.replaceChildren(...files.map((f, i) => {
      const isImg = f.type.startsWith('image/');
      const thumb = isImg
        ? el('img', { src: URL.createObjectURL(f), alt: '' })
        : svgIcon('M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8zM14 3v5h5M9 13h6M9 17h4', 18);
      return el('li', { class: 'attach' },
        el('span', { class: 'attach-thumb' + (isImg ? ' is-img' : '') }, thumb),
        el('span', { class: 'attach-meta' }, el('span', { class: 'attach-name truncate', title: f.name }, f.name), el('span', { class: 'attach-size' }, size(f.size))),
        el('button', { type: 'button', class: 'attach-remove', title: t('Hapus lampiran'), 'aria-label': `${t('Hapus lampiran')} ${f.name}`, onclick: () => { files.splice(i, 1); sync(); textarea.focus(); } },
          svgIcon('M6 6l12 12M18 6 6 18', 14)));
    }));
    listEl.hidden = files.length === 0;
  }

  /** Tambah berkas baru; tolak yang tidak sesuai dengan satu pemberitahuan. */
  function add(list) {
    const problems = [];
    for (const f of list) {
      if (!allowed(f)) problems.push(`${f.name}: ${t('jenis tidak didukung')}`);
      else if (f.size > MAX_BYTES) problems.push(`${f.name}: ${t('lebih dari')} ${size(MAX_BYTES)}`);
      else if (files.length >= MAX_FILES) { problems.push(t(`Maksimal ${MAX_FILES} lampiran.`)); break; } else if (!files.some((x) => x.name === f.name && x.size === f.size)) files.push(f);
    }
    sync();
    if (problems.length) ui.notify('Sebagian lampiran tidak ditambahkan', problems.join(' · ') + ' — ' + t('Gunakan gambar (PNG, JPG, WEBP, GIF), PDF, atau teks (TXT, MD).'));
  }

  input.addEventListener('change', () => {
    // input.files sekarang hanya berisi pilihan baru: gabungkan dengan daftar yang sudah ada.
    const picked = [...input.files];
    add(picked);
  });

  // Seret berkas ke kotak ide.
  let depth = 0;
  const hasFiles = (e) => [...(e.dataTransfer?.types || [])].includes('Files');
  form.addEventListener('dragenter', (e) => { if (!hasFiles(e)) return; e.preventDefault(); depth++; form.classList.add('is-dropping'); });
  form.addEventListener('dragover', (e) => { if (hasFiles(e)) e.preventDefault(); });
  form.addEventListener('dragleave', () => { depth = Math.max(0, depth - 1); if (!depth) form.classList.remove('is-dropping'); });
  form.addEventListener('drop', (e) => {
    if (!hasFiles(e)) return;
    e.preventDefault();
    depth = 0;
    form.classList.remove('is-dropping');
    add([...e.dataTransfer.files]);
  });
  // Tempel gambar (mis. tangkapan layar) langsung ke kotak ide.
  textarea.addEventListener('paste', (e) => {
    const pasted = [...(e.clipboardData?.files || [])];
    if (!pasted.length) return;
    e.preventDefault();
    add(pasted.map((f, i) => (f.name && f.name !== 'image.png' ? f : new File([f], `tempel-${Date.now()}-${i + 1}.${(f.type.split('/')[1] || 'png')}`, { type: f.type }))));
  });

  /* ---------- Mikrofon (dikte) ---------- */
  const Speech = window.SpeechRecognition || window.webkitSpeechRecognition;
  const mic = document.getElementById('micBtn');
  if (!Speech || !mic) return;
  mic.hidden = false;
  let rec = null;
  let base = '';

  // Bahasa dikte: Indonesia bila antarmuka atau browser berbahasa Indonesia, selain itu Inggris.
  const speechLang = () => (I18N.lang === 'id' || (navigator.languages || [navigator.language]).some((l) => /^id\b/i.test(l)) ? 'id-ID' : 'en-US');

  function setListening(on) {
    mic.classList.toggle('is-listening', on);
    mic.setAttribute('aria-pressed', String(on));
    mic.title = t(on ? 'Berhenti merekam' : 'Bicara untuk mengetik');
    mic.setAttribute('aria-label', mic.title);
    note.textContent = on ? t('Mendengarkan… klik mikrofon untuk berhenti') : '';
  }

  mic.addEventListener('click', () => {
    if (rec) { rec.stop(); return; }
    rec = new Speech();
    rec.lang = speechLang();
    rec.continuous = true;
    rec.interimResults = true;
    base = textarea.value.replace(/\s+$/, '');
    rec.onresult = (e) => {
      let finalText = '';
      let interim = '';
      for (let i = 0; i < e.results.length; i++) {
        const r = e.results[i];
        if (r.isFinal) finalText += r[0].transcript;
        else interim += r[0].transcript;
      }
      const spoken = (finalText + interim).trim();
      textarea.value = (base ? base + ' ' : '') + spoken;
      textarea.dispatchEvent(new Event('input', { bubbles: true }));
      textarea.scrollTop = textarea.scrollHeight;
    };
    rec.onerror = (e) => {
      if (e.error === 'not-allowed' || e.error === 'service-not-allowed') ui.notify('Mikrofon tidak bisa dipakai', 'Izinkan akses mikrofon untuk situs ini di pengaturan browser, lalu coba lagi.');
      else if (e.error === 'no-speech') note.textContent = t('Tidak ada suara yang terdengar.');
      else if (e.error !== 'aborted') ui.notify('Dikte terhenti', 'Pengenalan suara tidak tersedia saat ini. Coba lagi nanti.');
    };
    rec.onend = () => {
      rec = null;
      const msg = note.textContent;
      setListening(false);
      if (msg === t('Tidak ada suara yang terdengar.')) note.textContent = msg;
      textarea.focus();
    };
    try {
      rec.start();
      setListening(true);
    } catch {
      rec = null;
      setListening(false);
    }
  });
  // Kirim form saat dikte berjalan: hentikan dulu supaya teks terakhir tidak hilang.
  form.addEventListener('submit', () => rec && rec.stop());
})();
