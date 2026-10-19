'use strict';

/**
 * Tombol Implemen: popup Download PRD / Download ZIP / Prompt AI agent.
 * (Tombol audit Standar Kualitas dihapus dari popup atas permintaan pemilik; api/quality_audit.php tetap ada.)
 */
const Implemen = (() => {
  const button = document.getElementById('implemen');
  const modal = document.getElementById('implModal');
  const agentModal = document.getElementById('agentModal');
  const promptText = document.getElementById('promptText');
  const toastEl = document.getElementById('toast');

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
