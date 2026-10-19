'use strict';

(() => {
  const data = JSON.parse(document.getElementById('wsData').textContent);
  const main = document.getElementById('main');
  const prdEl = document.getElementById('prd');
  const tocBody = document.getElementById('tocBody');
  const toc = document.getElementById('toc');
  const agent = document.getElementById('agent');
  const scrim = document.getElementById('scrim');
  const mobile = window.matchMedia('(max-width: 900px)');

  const VIEW_NAMES = { prd: 'PRD', roadmap: 'Roadmap', kanban: 'Kanban', fitur: 'Fitur' };
  let currentRoute = { view: 'prd' }; // dikirim ke agent sebagai konteks tampilan
  const AGENT_CONTEXT = { prd: 'PRD · PRD aktif', roadmap: 'Roadmap · Fitur terpilih', fitur: 'Roadmap · Fitur terpilih', kanban: 'Task · Fitur terpilih' };

  /* ---------- Router hash: #/prd, #/roadmap, #/kanban?fitur=12, #/fitur/12, #/fitur/12/sub/40 ---------- */

  function parseRoute() {
    const [path, query = ''] = location.hash.replace(/^#/, '').split('?');
    const parts = path.split('/').filter(Boolean);
    const params = new URLSearchParams(query);
    if (parts[0] === 'fitur' && /^\d+$/.test(parts[1] || '')) {
      return { view: 'fitur', fitur: Number(parts[1]), sub: parts[2] === 'sub' && /^\d+$/.test(parts[3] || '') ? Number(parts[3]) : null };
    }
    if (parts[0] === 'prd') return { view: 'prd' };
    if (parts[0] === 'roadmap') return { view: 'roadmap', panel: params.get('panel'), fitur: Number(params.get('fitur')) || null };
    if (parts[0] === 'kanban') return { view: 'kanban', fitur: Number(params.get('fitur')) || null };
    return null;
  }

  function route() {
    const r = parseRoute();
    if (!r) {
      history.replaceState(null, '', '#/prd');
      return route();
    }
    main.querySelectorAll('[data-view]').forEach((el) => { el.hidden = el.dataset.view !== r.view; });
    document.querySelectorAll('[data-views]').forEach((el) => { el.hidden = !el.dataset.views.split(' ').includes(r.view); });
    document.body.dataset.route = r.view;
    currentRoute = r;
    document.getElementById('viewName').textContent = VIEW_NAMES[r.view];
    document.getElementById('agentContext').textContent = AGENT_CONTEXT[r.view];
    document.title = `${VIEW_NAMES[r.view]} · ${data.judul}`;

    if (r.view === 'roadmap') {
      Roadmap.show(r, { prd: data.prd, judul: data.judul });
    }
    if (r.view === 'kanban') {
      Kanban.show(r);
    }
    if (r.view === 'fitur') {
      FiturPage.show(r);
    }
    closeDrawers();
    main.scrollTop = 0;
  }

  /* ---------- PRD + daftar isi ---------- */

  function slugify(text, used) {
    let slug = text.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'bagian';
    while (used.has(slug)) slug += '-';
    used.add(slug);
    return slug;
  }

  function renderPrd(markdown, { streaming = false } = {}) {
    if (!markdown) {
      prdEl.replaceChildren(el('div', { class: 'ws-empty' },
        el('h2', {}, 'PRD belum dibuat'),
        el('p', { class: 'muted' }, 'Klik "Lanjutkan / coba lagi" untuk menyusun PRD.')));
      tocBody.replaceChildren();
      return;
    }
    prdEl.innerHTML = DOMPurify.sanitize(marked.parse(markdown));
    renderDiagrams();
    if (streaming) {
      scheduleToc();
    } else {
      buildToc();
    }
  }

  let tocTimer = null;
  function scheduleToc() {
    clearTimeout(tocTimer);
    tocTimer = setTimeout(buildToc, 300);
  }

  /* ---------- Diagram mermaid ---------- */

  const diagramCache = new Map(); // kode → {svg} | {error}
  let mermaidReady = null;

  function loadMermaid() {
    mermaidReady ??= new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = 'vendor/mermaid.min.js';
      s.onload = () => {
        mermaid.initialize({
          startOnLoad: false,
          securityLevel: 'strict',
          theme: 'base',
          // Warna diagram mengikuti mode saat diagram pertama dirender (terang atau gelap).
          themeVariables: document.documentElement.dataset.theme === 'dark' ? {
            background: '#141416', primaryColor: '#18181b', primaryTextColor: '#f4f4f5', primaryBorderColor: '#3b3b42',
            lineColor: '#71717a', secondaryColor: '#2a1a10', tertiaryColor: '#232327', fontFamily: 'Inter, system-ui, sans-serif',
            noteBkgColor: '#2a1a10', noteBorderColor: '#9a3412', actorBkg: '#18181b', actorBorder: '#3b3b42',
          } : {
            background: '#fafaf9', primaryColor: '#ffffff', primaryTextColor: '#18181b', primaryBorderColor: '#d6d6d3',
            lineColor: '#a1a1aa', secondaryColor: '#fff4ec', tertiaryColor: '#f5f5f4', fontFamily: 'Inter, system-ui, sans-serif',
            noteBkgColor: '#fff4ec', noteBorderColor: '#fdba8c', actorBkg: '#ffffff', actorBorder: '#d6d6d3',
          },
        });
        resolve();
      };
      s.onerror = () => reject(new Error('Gagal memuat mermaid.'));
      document.head.append(s);
    });
    return mermaidReady;
  }

  function renderDiagrams() {
    prdEl.querySelectorAll('pre > code.language-diagram-pending').forEach((code) => {
      code.parentElement.replaceWith(el('div', { class: 'diagram is-pending' }, el('span', { class: 'spinner' }), 'Menggambar diagram…'));
    });
    prdEl.querySelectorAll('pre > code.language-mermaid').forEach((code) => {
      const source = code.textContent;
      const card = el('div', { class: 'diagram' });
      code.parentElement.replaceWith(card);
      const cached = diagramCache.get(source);
      if (cached) return fillDiagram(card, source, cached);
      card.classList.add('is-pending');
      card.append(el('span', { class: 'spinner' }), 'Menggambar diagram…');
      drawDiagram(source).then((result) => {
        // Kartu bisa sudah diganti oleh render berikutnya; isi semua kartu dengan kode yang sama.
        prdEl.querySelectorAll('.diagram.is-pending[data-source]').forEach((c) => {
          if (c.dataset.source === source) fillDiagram(c, source, result);
        });
      });
      card.dataset.source = source;
    });
  }

  async function drawDiagram(source) {
    if (diagramCache.has(source)) return diagramCache.get(source);
    let result;
    try {
      await loadMermaid();
      await mermaid.parse(source);
      const { svg } = await mermaid.render('mmd' + Math.random().toString(36).slice(2), source);
      result = { svg };
    } catch (err) {
      result = { error: true };
    }
    diagramCache.set(source, result);
    return result;
  }

  function fillDiagram(card, source, result) {
    card.classList.remove('is-pending');
    if (result.svg) {
      card.innerHTML = DOMPurify.sanitize(result.svg, { USE_PROFILES: { svg: true, svgFilters: true }, ADD_TAGS: ['foreignObject'] });
    } else {
      // Sintaks diagram dari AI tidak valid: tampilkan kode mentah tanpa merusak halaman.
      card.replaceChildren(el('p', { class: 'muted small' }, 'Diagram tidak bisa digambar; ini kodenya:'), el('pre', {}, el('code', {}, source)));
    }
  }

  /* ---------- Streaming PRD ---------- */

  const statusEl = document.getElementById('prdStatus');
  const retryBtn = document.getElementById('prdRetry');
  const regenBtn = document.getElementById('prdRegen');
  let streaming = false;

  function setPrdState({ status = '', busy = false, retry = false }) {
    statusEl.replaceChildren(...(busy ? [el('span', { class: 'spinner spinner-sm' })] : []), status);
    statusEl.classList.toggle('is-error', !busy && retry);
    retryBtn.hidden = !retry;
    regenBtn.hidden = busy || retry || !data.prdSelesai;
    updateRail();
  }

  async function startStream(ulang) {
    if (streaming || editing) return;
    streaming = true;
    setPrdState({ status: 'Menyusun PRD…', busy: true });
    document.getElementById('staleBanner').hidden = true;
    let finished = false;
    try {
      const res = await fetch('../api/stream_prd.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ ulang }),
      });
      if (!res.ok || !(res.headers.get('content-type') || '').includes('text/event-stream')) {
        const d = await res.json().catch(() => null);
        throw new Error((d && d.error) || 'Gagal memulai penyusunan PRD.');
      }
      const reader = res.body.getReader();
      const decoder = new TextDecoder();
      let buffer = '';
      for (;;) {
        const { value, done } = await reader.read();
        if (done) break;
        buffer += decoder.decode(value, { stream: true });
        let cut;
        pendingSnapshot = null;
        while ((cut = buffer.indexOf('\n\n')) >= 0) {
          const block = buffer.slice(0, cut);
          buffer = buffer.slice(cut + 2);
          finished = handleEvent(block) || finished;
        }
        // Bila beberapa snapshot datang sekaligus, cukup render yang terakhir (merender ulang dokumen besar itu mahal).
        if (pendingSnapshot !== null) renderSnapshot(pendingSnapshot);
      }
      if (!finished) throw new Error('Koneksi terputus sebelum PRD selesai.');
    } catch (err) {
      setPrdState({ status: err.message, retry: true });
    } finally {
      streaming = false;
      updateRail();
    }
  }

  let phaseStatus = 'Menyusun PRD…';
  let pendingSnapshot = null;

  function renderSnapshot(markdown) {
    renderPrd(markdown, { streaming: true });
    if (statusEl.textContent !== phaseStatus) setPrdState({ status: phaseStatus, busy: true });
  }

  /** Proses satu blok SSE; mengembalikan true bila stream sudah berakhir (done/error). */
  function handleEvent(block) {
    let event = 'message';
    let payload = '';
    for (const line of block.split('\n')) {
      if (line.startsWith('event:')) event = line.slice(6).trim();
      else if (line.startsWith('data:')) payload += line.slice(5).trim();
    }
    if (!payload) return false; // ping
    const d = JSON.parse(payload);
    if (event === 'status') {
      // Status fase ("Menyusun PRD…") diingat; status sementara saat mencoba ulang tidak.
      if (!/terputus/i.test(d.teks)) phaseStatus = d.teks;
      setPrdState({ status: d.teks, busy: true });
    } else if (event === 'snapshot') {
      pendingSnapshot = d.markdown; // dirender setelah semua blok yang sudah diterima diproses
    } else if (event === 'done') {
      pendingSnapshot = null;
      data.prd = d.markdown;
      data.prdSelesai = true;
      renderPrd(d.markdown);
      setPrdState({ status: '' });
      return true;
    } else if (event === 'error') {
      pendingSnapshot = null;
      if (d.markdown) renderPrd(d.markdown);
      setPrdState({ status: d.message, retry: true });
      return true;
    }
    return false;
  }

  /* ---------- Toast ---------- */

  const toastEl = document.getElementById('toast');
  let toastTimer = null;
  function toast(text) {
    toastEl.textContent = text;
    toastEl.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toastEl.hidden = true; }, 2200);
  }

  /* ---------- Rail: export, edit, riwayat versi ---------- */

  const editBtn = document.getElementById('editBtn');
  const exportBtn = document.getElementById('exportBtn');
  const historyBtn = document.getElementById('historyBtn');
  const exportMenu = document.getElementById('exportMenu');
  const historyMenu = document.getElementById('historyMenu');
  const editor = document.getElementById('prdEditor');
  const editorText = document.getElementById('prdText');
  let editing = false;
  let dirty = false;

  function updateRail() {
    editBtn.disabled = streaming || !data.prd;
    exportBtn.disabled = streaming || !data.prd;
    historyBtn.disabled = streaming;
    bikinBtn.disabled = streaming || editing || makingTasks || !data.prdSelesai;
    Implemen.setReady(!streaming && data.prdSelesai);
  }

  function closeMenus() {
    [[exportMenu, exportBtn], [historyMenu, historyBtn]].forEach(([menu, btn]) => {
      menu.hidden = true;
      btn.setAttribute('aria-expanded', 'false');
    });
  }

  function toggleMenu(menu, btn) {
    const open = menu.hidden;
    closeMenus();
    menu.hidden = !open;
    btn.setAttribute('aria-expanded', String(open));
    return open;
  }

  exportBtn.addEventListener('click', () => toggleMenu(exportMenu, exportBtn));
  document.getElementById('downloadMd').addEventListener('click', () => {
    // Isi unduhan = PRD yang sedang tampil, jadi selalu sama dengan layar dan dengan "Salin teks".
    const name = (data.judul.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'project') + '-PRD.md';
    const url = URL.createObjectURL(new Blob([data.prd], { type: 'text/markdown;charset=utf-8' }));
    const a = el('a', { href: url, download: name });
    document.body.append(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
    closeMenus();
  });
  document.getElementById('copyMd').addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(data.prd);
    } catch {
      // Cadangan untuk browser yang menolak Clipboard API.
      const ta = el('textarea', { style: 'position:fixed;opacity:0' });
      ta.value = data.prd;
      document.body.append(ta);
      ta.select();
      document.execCommand('copy');
      ta.remove();
    }
    closeMenus();
    toast('Tersalin');
  });

  function setEditing(on) {
    editing = on;
    dirty = false;
    editor.hidden = !on;
    prdEl.hidden = on;
    document.querySelector('.prd-bar').hidden = on;
    toc.classList.toggle('is-editing', on);
    editBtn.classList.toggle('is-active', on);
    editBtn.setAttribute('aria-pressed', String(on));
    editBtn.title = on ? 'Selesai edit' : 'Edit PRD';
    updateRail();
    if (on) {
      editorText.value = data.prd;
      editorText.focus();
    } else {
      renderPrd(data.prd);
    }
  }

  editBtn.addEventListener('click', async () => {
    if (!editing) return setEditing(true);
    if (dirty && !(await ui.confirm('Tinggalkan mode edit?', 'Perubahan belum disimpan dan akan hilang.', { ok: 'Tinggalkan', tone: 'danger' }))) return;
    setEditing(false);
  });
  editorText.addEventListener('input', () => { dirty = editorText.value !== data.prd; });
  window.addEventListener('beforeunload', (e) => {
    if (dirty) e.preventDefault();
  });

  const saveBtn = document.getElementById('prdSave');
  saveBtn.addEventListener('click', async () => {
    saveBtn.disabled = true;
    try {
      const res = await api('prd_save.php', { content_md: editorText.value });
      data.prd = res.content_md;
      data.prdSelesai = true;
      dirty = false;
      toast('Versi tersimpan');
    } catch (err) {
      toast(err.message);
    } finally {
      saveBtn.disabled = false;
    }
  });
  document.addEventListener('keydown', (e) => {
    if (editing && e.key === 's' && (e.ctrlKey || e.metaKey)) {
      e.preventDefault();
      saveBtn.click();
    }
  });

  historyBtn.addEventListener('click', async () => {
    if (!toggleMenu(historyMenu, historyBtn)) return;
    const list = document.getElementById('historyList');
    list.replaceChildren(el('li', { class: 'muted' }, 'Memuat…'));
    try {
      const res = await api('prd_versions.php', { action: 'list' });
      if (!res.versions.length) return list.replaceChildren(el('li', { class: 'muted' }, 'Belum ada versi.'));
      list.replaceChildren(...res.versions.map((v) => el('li', {},
        el('div', {},
          el('span', {}, `${v.source === 'ai' ? 'AI' : 'Edit manual'} · ${v.created_at.slice(0, 16)}`),
          el('span', { class: 'muted small' }, `${Number(v.panjang).toLocaleString('id-ID')} karakter`)),
        v.id === res.terbaru
          ? el('span', { class: 'muted small' }, 'Aktif')
          : el('button', { type: 'button', class: 'btn btn-ghost btn-sm', onclick: () => restore(v.id) }, 'Pulihkan'))));
    } catch (err) {
      list.replaceChildren(el('li', { class: 'form-error' }, err.message));
    }
  });

  async function restore(id) {
    if (editing && dirty && !(await ui.confirm('Pulihkan versi ini?', 'Perubahan di editor belum disimpan dan akan hilang.', { ok: 'Pulihkan', tone: 'danger' }))) return;
    try {
      const res = await api('prd_versions.php', { action: 'restore', id });
      data.prd = res.content_md;
      data.prdSelesai = true;
      if (editing) setEditing(false);
      else renderPrd(data.prd);
      closeMenus();
      setPrdState({});
      toast('Versi dipulihkan');
    } catch (err) {
      toast(err.message);
    }
  }

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.ws-menu, #exportBtn, #historyBtn')) closeMenus();
  });

  // Audit v1.2 bisa menulis ulang bab PRD: ambil versi terbaru supaya tampilan PRD tidak basi.
  document.addEventListener('prd:updated', async () => {
    try {
      const res = await api('prd_versions.php', { action: 'latest' });
      if (res.content_md && res.content_md !== data.prd && !editing) {
        data.prd = res.content_md;
        renderPrd(data.prd);
      }
    } catch { /* tampilan lama tetap dipakai */ }
  });

  /* ---------- Pesan agent ---------- */

  const agentMessagesEl = document.getElementById('agentMessages');
  const agentBody = document.getElementById('agentBody');

  const agentForm = document.getElementById('agentForm');
  const agentInput = document.getElementById('agentInput');
  const agentSend = document.getElementById('agentSend');
  const agentMore = document.getElementById('agentMore');
  let oldestId = data.agentMessages[0]?.id || 0;
  let chatting = false;

  function messageEl(role, content) {
    const msg = el('div', { class: `msg msg-${role}` });
    // Pesan pengguna tampil apa adanya; jawaban agent dan pesan sistem berupa markdown.
    if (role === 'user') msg.textContent = content;
    else msg.innerHTML = DOMPurify.sanitize(marked.parse(content));
    return msg;
  }

  function addAgentMessage(role, content) {
    const msg = messageEl(role, content);
    agentMessagesEl.append(msg);
    document.getElementById('agentEmpty').hidden = true;
    agentBody.scrollTop = agentBody.scrollHeight;
    return msg;
  }

  data.agentMessages.forEach((m) => addAgentMessage(m.role, m.content));
  agentMore.hidden = !data.agentMore;

  agentMore.addEventListener('click', async () => {
    agentMore.disabled = true;
    try {
      const res = await api('agent_history.php', { sebelum: oldestId });
      const height = agentBody.scrollHeight;
      agentMessagesEl.prepend(...res.messages.map((m) => messageEl(m.role, m.content)));
      agentBody.scrollTop += agentBody.scrollHeight - height; // posisi baca tetap
      oldestId = res.messages[0]?.id || oldestId;
      agentMore.hidden = !res.ada_lagi;
    } catch (err) {
      toast(err.message);
    } finally {
      agentMore.disabled = false;
    }
  });

  /** Kirim pertanyaan ke agent dan tampilkan jawabannya sambil di-stream (SSE lewat POST). */
  async function askAgent(question) {
    if (chatting || !question.trim()) return;
    chatting = true;
    agentSend.disabled = true;
    agentInput.value = '';
    const userMsg = addAgentMessage('user', question);
    const reply = el('div', { class: 'msg msg-assistant msg-pending' }, el('span', { class: 'spinner spinner-sm' }), el('span', {}, 'Mengetik…'));
    agentMessagesEl.append(reply);
    agentBody.scrollTop = agentBody.scrollHeight;

    let text = '';
    let frame = 0;
    const render = () => {
      frame = 0;
      const stick = agentBody.scrollHeight - agentBody.scrollTop - agentBody.clientHeight < 60;
      reply.classList.remove('msg-pending');
      reply.innerHTML = DOMPurify.sanitize(marked.parse(text));
      if (stick) agentBody.scrollTop = agentBody.scrollHeight;
    };
    try {
      const res = await fetch('../api/agent.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content },
        body: JSON.stringify({ pesan: question, view: currentRoute.view, fitur: currentRoute.fitur || null }),
      });
      if (!res.ok || !(res.headers.get('content-type') || '').includes('text/event-stream')) {
        const d = await res.json().catch(() => null);
        throw new Error((d && d.error) || 'Agent tidak bisa dihubungi.');
      }
      const reader = res.body.getReader();
      const decoder = new TextDecoder();
      let buffer = '';
      let finished = false;
      for (;;) {
        const { value, done } = await reader.read();
        if (done) break;
        buffer += decoder.decode(value, { stream: true });
        let cut;
        while ((cut = buffer.indexOf('\n\n')) >= 0) {
          const block = buffer.slice(0, cut);
          buffer = buffer.slice(cut + 2);
          let event = 'message';
          let payload = '';
          for (const line of block.split('\n')) {
            if (line.startsWith('event:')) event = line.slice(6).trim();
            else if (line.startsWith('data:')) payload += line.slice(5).trim();
          }
          if (!payload) continue; // ping
          const d = JSON.parse(payload);
          if (event === 'delta') text += d.teks;
          else if (event === 'reset') text = ''; // koneksi AI putus, jawaban diulang dari awal
          else if (event === 'error') throw new Error(d.message);
          else if (event === 'done') finished = true;
        }
        if (!frame && text) frame = requestAnimationFrame(render);
      }
      if (!finished) throw new Error('Koneksi terputus sebelum jawaban selesai.');
      cancelAnimationFrame(frame);
      render();
    } catch (err) {
      // Pertanyaan belum tersimpan: tampilkan galat dan kembalikan teksnya supaya bisa dikirim ulang.
      cancelAnimationFrame(frame);
      reply.className = 'msg msg-error';
      reply.replaceChildren(el('span', {}, err.message), el('button', {
        type: 'button', class: 'btn btn-ghost btn-sm',
        onclick: () => { userMsg.remove(); reply.remove(); askAgent(question); },
      }, 'Coba lagi'));
      if (!agentInput.value) agentInput.value = question;
    } finally {
      chatting = false;
      agentSend.disabled = false;
    }
  }

  agentForm.addEventListener('submit', (e) => {
    e.preventDefault();
    askAgent(agentInput.value);
  });
  agentInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
      e.preventDefault();
      askAgent(agentInput.value);
    }
  });
  document.querySelectorAll('.agent-examples .chip').forEach((chip) => chip.addEventListener('click', () => askAgent(chip.textContent)));

  /* ---------- Bikin Task ---------- */

  const bikinBtn = document.getElementById('bikinTask');
  let makingTasks = false;

  async function bikinTask(ulang) {
    makingTasks = true;
    updateRail();
    setAgentOpen(true);
    const bubble = el('div', { class: 'msg msg-system msg-pending' }, el('span', { class: 'spinner spinner-sm' }), el('span', {}, 'Menyusun task…'));
    agentMessagesEl.append(bubble);
    document.getElementById('agentEmpty').hidden = true;
    try {
      for (let first = true; ; first = false) {
        const res = await api('tasks_generate.php', { ulang: first && ulang });
        if (res.done) {
          bubble.remove();
          addAgentMessage('system', res.pesan);
          data.taskCount = res.jumlah.task;
          notifyDataChanged();
          location.hash = '#/roadmap';
          break;
        }
        bubble.lastChild.textContent = `Menyusun task (${res.selesai}/${res.total})…`;
      }
    } catch (err) {
      // Kelompok yang sudah selesai tersimpan; "Coba lagi" melanjutkan sisanya.
      bubble.classList.replace('msg-pending', 'msg-error');
      bubble.replaceChildren(el('span', {}, err.message), el('button', {
        type: 'button', class: 'btn btn-ghost btn-sm', onclick: () => { bubble.remove(); bikinTask(false); },
      }, 'Coba lagi'));
    } finally {
      makingTasks = false;
      updateRail();
    }
  }

  bikinBtn.addEventListener('click', async () => {
    if (data.taskCount > 0 && !(await ui.confirm('Buat ulang task?', 'Status task yang lama akan hilang.', { ok: 'Buat ulang', tone: 'danger' }))) return;
    bikinTask(data.taskCount > 0);
  });

  retryBtn.addEventListener('click', () => startStream(false));
  regenBtn.addEventListener('click', async () => {
    if (await ui.confirm('Buat ulang PRD?', 'Versi sekarang tetap disimpan di riwayat versi.', { ok: 'Buat ulang' })) startStream(true);
  });

  function buildToc() {
    const used = new Set();
    const headings = [...prdEl.querySelectorAll('h1, h2, h3')];
    headings.forEach((h) => { h.id = slugify(h.textContent, used); });

    const list = el('ol', { class: 'toc-list' });
    let phases = null;
    const seenPhase = new Set();
    for (const h of headings) {
      if (h.tagName === 'H2') {
        const item = el('li', {}, tocLink(h, h.textContent));
        list.append(item);
        phases = /core features/i.test(h.textContent) ? el('ol', { class: 'toc-sub' }) : null;
        if (phases) item.append(phases);
      } else if (h.tagName === 'H3' && phases) {
        // Di Core Features, tiap fitur berjudul "Fase n — Nama"; daftar isi cukup menampilkan "Fase n" sekali.
        const m = h.textContent.match(/^Fase\s+(\d+)/i);
        if (m && !seenPhase.has(m[1])) {
          seenPhase.add(m[1]);
          phases.append(el('li', {}, tocLink(h, `Fase ${m[1]}`)));
        }
      }
    }
    const title = headings.find((h) => h.tagName === 'H1');
    tocBody.replaceChildren(...[title ? el('div', { class: 'toc-title' }, tocLink(title, title.textContent)) : null, list].filter(Boolean));
    observeHeadings(headings.filter((h) => tocBody.querySelector(`a[href="#${CSS.escape(h.id)}"]`)));
  }

  function tocLink(heading, text) {
    return el('a', {
      href: `#${heading.id}`,
      onclick: (e) => {
        e.preventDefault(); // jangan ubah hash router
        heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
        closeDrawers();
      },
    }, text);
  }

  let observer = null;
  function observeHeadings(headings) {
    observer?.disconnect();
    const visible = new Map();
    observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => visible.set(entry.target, entry.isIntersecting));
      // Sorot heading teratas yang terlihat; bila tak ada, heading terakhir di atas layar.
      const top = headings.find((h) => visible.get(h)) || [...headings].reverse().find((h) => h.getBoundingClientRect().top < 120);
      tocBody.querySelectorAll('a.is-active').forEach((a) => a.classList.remove('is-active'));
      if (top) tocBody.querySelector(`a[href="#${CSS.escape(top.id)}"]`)?.classList.add('is-active');
    }, { root: main, rootMargin: '0px 0px -65% 0px' });
    headings.forEach((h) => observer.observe(h));
  }

  /* ---------- Agent & drawer ---------- */

  function setAgentOpen(open) {
    document.body.classList.toggle('agent-closed', !open);
    document.getElementById('agentToggle').setAttribute('aria-expanded', String(open));
    if (mobile.matches) {
      agent.classList.toggle('is-open', open);
      scrim.hidden = !open;
    } else {
      try { localStorage.setItem('planku.agent', open ? '1' : '0'); } catch { /* penyimpanan tidak tersedia */ }
    }
  }

  function closeDrawers() {
    toc.classList.remove('is-open');
    if (mobile.matches) setAgentOpen(false);
    scrim.hidden = true;
  }

  document.getElementById('agentToggle').addEventListener('click', () => setAgentOpen(document.body.classList.contains('agent-closed')));
  document.getElementById('agentClose').addEventListener('click', () => setAgentOpen(false));
  document.getElementById('tocToggle').addEventListener('click', () => {
    toc.classList.add('is-open');
    scrim.hidden = false;
  });
  scrim.addEventListener('click', closeDrawers);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeDrawers();
      closeMenus();
    }
  });

  let saved = '1';
  try { saved = localStorage.getItem('planku.agent') ?? '1'; } catch { /* abaikan */ }
  setAgentOpen(!mobile.matches && saved !== '0');
  mobile.addEventListener('change', () => setAgentOpen(!mobile.matches));

  document.getElementById('staleBanner').hidden = !data.prdBasi;
  renderPrd(data.prd);
  if (!data.prd) {
    startStream(false); // dibuka dari "Lanjutkan" dan PRD belum ada: susun langsung
  } else if (!data.prdSelesai) {
    setPrdState({ status: 'PRD belum selesai disusun (proses sebelumnya terputus).', retry: true });
  } else {
    setPrdState({});
  }
  window.addEventListener('hashchange', route);
  route();
})();
