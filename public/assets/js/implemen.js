'use strict';

/**
 * Tombol Implemen: popup Download PRD / Download ZIP / Prompt AI agent, plus audit kualitas v1.2 opsional
 * Standar Kualitas v1.2 (validator, audit AI, perbaikan bab, QUALITY.md) lewat api/quality_audit.php.
 */
const Implemen = (() => {
  const button = document.getElementById('implemen');
  const modal = document.getElementById('implModal');
  const agentModal = document.getElementById('agentModal');
  const auditEl = document.getElementById('implAudit');
  const promptText = document.getElementById('promptText');
  const toastEl = document.getElementById('toast');
  let auditRunning = false;

  function toast(text) {
    toastEl.textContent = text;
    toastEl.hidden = false;
    clearTimeout(toast.timer);
    toast.timer = setTimeout(() => { toastEl.hidden = true; }, 2200);
  }

  /** Unduhan lewat POST + CSRF, lalu disimpan sebagai Blob (K8). */
  async function download(format, btn) {
    btn.disabled = true;
    try {
      const res = await fetch('../api/prd_export.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ format }),
      });
      if (!res.ok) {
        const d = await res.json().catch(() => null);
        throw new Error((d && d.error) || 'Gagal mengunduh.');
      }
      const name = (res.headers.get('Content-Disposition') || '').match(/filename="([^"]+)"/)?.[1] || `planku.${format}`;
      const url = URL.createObjectURL(await res.blob());
      const a = el('a', { href: url, download: name });
      document.body.append(a);
      a.click();
      a.remove();
      setTimeout(() => URL.revokeObjectURL(url), 1000);
      toast(`${name} diunduh`);
    } catch (err) {
      toast(err.message);
    } finally {
      btn.disabled = false;
    }
  }

  /* ---------- Audit Standar Kualitas v1.2 ---------- */

  async function refreshAudit() {
    if (auditRunning) return;
    auditEl.replaceChildren(el('span', { class: 'spinner spinner-sm' }));
    try {
      const s = await api('quality_status.php');
      if (s.laporan) {
        const gagal = s.laporan.gagal.length ? ` Belum terpenuhi: ${s.laporan.gagal.join('; ')}.` : '';
        auditEl.replaceChildren(
          el('span', { class: `impl-badge ${s.laporan.lulus ? 'is-pass' : 'is-fail'}` }, s.laporan.lulus ? 'Lulus' : 'Belum lulus'),
          el('span', {}, `Standar Kualitas v1.2 · skor ${s.laporan.skor}/100 · ${s.laporan.temuan} temuan. QUALITY.md ikut di ZIP.${gagal}`),
          el('button', { type: 'button', class: 'btn btn-ghost btn-sm', onclick: runAudit }, 'Audit ulang'));
      } else if (!s.prd_siap) {
        auditEl.replaceChildren(el('span', { class: 'muted' },
          'Audit v1.2 butuh PRD format v1.2 yang sesuai peta fitur dan pilihan Bagian B sekarang. Klik "Buat ulang PRD" di tampilan PRD dulu.'));
      } else if (!s.task_ada) {
        auditEl.replaceChildren(el('span', { class: 'muted' }, 'Audit juga memeriksa task bertanda milestone. Klik "Bikin Task" dulu.'));
      } else {
        auditEl.replaceChildren(
          el('span', { class: 'muted' }, 'Opsional: audit Standar Kualitas v1.2 menjalankan semua validator, audit AI singkat, lalu memperbaiki bab yang bermasalah (±1–7 panggilan AI).'),
          el('button', { type: 'button', class: 'btn btn-ghost btn-sm', onclick: runAudit }, 'Jalankan audit'));
      }
    } catch (err) {
      auditEl.replaceChildren(el('span', { class: 'form-error' }, err.message));
    }
  }

  /** Pass 3–5: nilai (validator + audit AI) → perbaiki potongan bertemuan → nilai lagi, maksimal 2 putaran perbaikan. */
  async function runAudit() {
    auditRunning = true;
    const status = el('span', {}, '');
    auditEl.replaceChildren(el('span', { class: 'spinner spinner-sm' }), status);
    try {
      status.textContent = 'Menjalankan validator dan audit AI…';
      let res = await api('quality_audit.php', { langkah: 'nilai', ai: true });
      for (let round = 1; round <= 2 && !res.laporan.lulus && res.perbaiki.length; round++) {
        for (let i = 0; i < res.perbaiki.length; i++) {
          status.textContent = `Putaran ${round}: memperbaiki bagian PRD (${i + 1}/${res.perbaiki.length})…`;
          await api('quality_audit.php', { langkah: 'perbaiki', bagian: res.perbaiki[i] });
        }
        status.textContent = `Putaran ${round}: menilai ulang…`;
        res = await api('quality_audit.php', { langkah: 'nilai' });
      }
      auditRunning = false;
      await refreshAudit();
      document.dispatchEvent(new CustomEvent('prd:updated')); // PRD bisa berubah karena perbaikan
    } catch (err) {
      auditRunning = false;
      auditEl.replaceChildren(el('span', { class: 'form-error' }, err.message),
        el('button', { type: 'button', class: 'btn btn-ghost btn-sm', onclick: refreshAudit }, 'Coba lagi'));
    }
  }

  /* ---------- Prompt AI agent ---------- */

  async function openAgent() {
    modal.close();
    agentModal.showModal();
    promptText.textContent = 'Memuat…';
    try {
      promptText.textContent = (await api('prompt_agent.php')).prompt;
    } catch (err) {
      promptText.textContent = err.message;
    }
  }

  document.getElementById('promptCopy').addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(promptText.textContent);
    } catch {
      const ta = el('textarea', { style: 'position:fixed;opacity:0' });
      ta.value = promptText.textContent;
      document.body.append(ta);
      ta.select();
      document.execCommand('copy');
      ta.remove();
    }
    toast('Prompt tersalin');
  });
  document.getElementById('promptExpand').addEventListener('click', () => {
    document.getElementById('promptBox').classList.toggle('is-expanded');
  });

  /* ---------- Popup ---------- */

  button.addEventListener('click', () => {
    modal.showModal();
    refreshAudit();
  });
  document.getElementById('implPrd').addEventListener('click', (e) => download('md', e.currentTarget));
  document.getElementById('implZip').addEventListener('click', (e) => download('zip', e.currentTarget));
  document.getElementById('implAgent').addEventListener('click', openAgent);
  document.querySelectorAll('[data-close-dialog]').forEach((b) => b.addEventListener('click', () => b.closest('dialog').close()));
  [modal, agentModal].forEach((d) => d.addEventListener('click', (e) => { if (e.target === d) d.close(); })); // klik latar menutup

  return {
    /** Aktifkan tombol Implemen setelah PRD selesai. */
    setReady(ready) { button.disabled = !ready; },
  };
})();
