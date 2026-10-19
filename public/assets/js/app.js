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

const SVG_NS = 'http://www.w3.org/2000/svg';
/** Ikon SVG garis (24×24) dari atribut d. */
function svgIcon(d, size = 16) {
  const svg = document.createElementNS(SVG_NS, 'svg');
  for (const [k, v] of Object.entries({ viewBox: '0 0 24 24', width: size, height: size, fill: 'none', stroke: 'currentColor',
    'stroke-width': 2, 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'aria-hidden': 'true' })) svg.setAttribute(k, v);
  const path = document.createElementNS(SVG_NS, 'path');
  path.setAttribute('d', d);
  svg.append(path);
  return svg;
}

/**
 * Komponen UI bersama: popup konfirmasi (pengganti alert/confirm bawaan browser) dan dropdown kustom
 * (pengganti tampilan <select> bawaan; <select> asli tetap ada sebagai sumber nilai, form, dan event change).
 */
const ui = (() => {
  const ICON = {
    info: 'M12 8h.01M11 12h1v5h1M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z',
    warn: 'M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z',
    trash: 'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
    check: 'm5 12 5 5 9-10',
    chevron: 'm6 9 6 6 6-6',
  };

  /** Popup modal; resolve true bila tombol utama dipilih. */
  function popup({ title, message = '', ok = 'Lanjutkan', cancel = 'Batal', tone = 'default', icon, single = false }) {
    return new Promise((resolve) => {
      const okBtn = el('button', { type: 'submit', value: 'ok', class: 'btn' + (tone === 'danger' ? ' btn-danger' : '') }, ok);
      const cancelBtn = single ? null : el('button', { type: 'submit', value: 'batal', class: 'btn btn-ghost' }, cancel);
      const form = el('form', { method: 'dialog' },
        el('div', { class: 'popup-icon' }, svgIcon(ICON[icon || (tone === 'danger' ? 'warn' : 'info')], 22)),
        el('h2', { id: 'popupTitle' }, title),
        message ? el('p', {}, message) : null,
        el('div', { class: 'popup-actions' }, cancelBtn, okBtn));
      const dialog = el('dialog', { class: 'modal popup' + (tone === 'danger' ? ' is-danger' : ''), 'aria-labelledby': 'popupTitle' }, form);
      const opener = document.activeElement;
      dialog.addEventListener('close', () => {
        resolve(dialog.returnValue === 'ok');
        dialog.remove();
        if (opener && opener.isConnected) opener.focus({ preventScroll: true });
      });
      dialog.addEventListener('click', (e) => { if (e.target === dialog && !single) dialog.close('batal'); });
      document.body.append(dialog);
      dialog.showModal();
      (tone === 'danger' && cancelBtn ? cancelBtn : okBtn).focus();
    });
  }

  /** Konfirmasi: pengganti window.confirm. */
  function confirm(title, message = '', opts = {}) {
    return popup({ title, message, ...opts });
  }

  /** Pemberitahuan satu tombol: pengganti window.alert. */
  function notify(title, message = '', opts = {}) {
    return popup({ title, message, ok: 'Mengerti', single: true, icon: 'warn', ...opts });
  }

  /* ---------- Dropdown kustom ---------- */
  let openDd = null;

  function enhanceSelect(sel) {
    if (sel.dataset.dd || sel.multiple || sel.size > 1) return;
    sel.dataset.dd = '1';
    const wrap = el('div', { class: 'dd' });
    sel.before(wrap);
    wrap.append(sel);
    sel.classList.add('dd-native');
    sel.tabIndex = -1;
    sel.setAttribute('aria-hidden', 'true');

    const value = el('span', { class: 'dd-value' });
    const chevron = svgIcon(ICON.chevron, 16);
    chevron.classList.add('dd-chevron');
    const btn = el('button', { type: 'button', class: 'dd-btn', 'aria-haspopup': 'listbox', 'aria-expanded': 'false' }, value, chevron);
    wrap.prepend(btn);
    const label = sel.id ? document.querySelector(`label[for="${CSS.escape(sel.id)}"]`) : null;
    if (label) {
      if (!label.id) label.id = sel.id + 'Label';
      btn.setAttribute('aria-labelledby', `${label.id} ${sel.id}Value`);
      value.id = sel.id + 'Value';
      label.addEventListener('click', (e) => { e.preventDefault(); btn.focus(); });
    } else if (sel.getAttribute('aria-label')) {
      btn.setAttribute('aria-label', sel.getAttribute('aria-label'));
    }

    const refresh = () => {
      const opt = sel.options[sel.selectedIndex];
      value.textContent = opt ? opt.textContent : '';
      btn.disabled = sel.disabled;
    };
    // Nilai yang diubah lewat kode (sel.value = …) tidak memicu event; label ikut diperbarui lewat properti instans.
    for (const prop of ['value', 'selectedIndex']) {
      const desc = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, prop);
      Object.defineProperty(sel, prop, { configurable: true, get() { return desc.get.call(this); }, set(v) { desc.set.call(this, v); refresh(); } });
    }
    sel.addEventListener('change', refresh);
    new MutationObserver(refresh).observe(sel, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'selected'] });
    refresh();

    let list = null;
    let active = -1;
    const items = () => [...list.querySelectorAll('.dd-opt')];
    const setActive = (i) => {
      const all = items();
      if (!all.length) return;
      active = Math.max(0, Math.min(all.length - 1, i));
      all.forEach((li, n) => li.classList.toggle('is-active', n === active));
      all[active].scrollIntoView({ block: 'nearest' });
      list.setAttribute('aria-activedescendant', all[active].id);
    };
    const choose = (i) => {
      const opt = sel.options[i];
      if (!opt || opt.disabled) return;
      if (sel.selectedIndex !== i) {
        sel.selectedIndex = i;
        sel.dispatchEvent(new Event('input', { bubbles: true }));
        sel.dispatchEvent(new Event('change', { bubbles: true }));
      }
      close(true);
    };
    const place = () => {
      const r = btn.getBoundingClientRect();
      const below = innerHeight - r.bottom - 12;
      const up = below < Math.min(240, list.scrollHeight) && r.top > below;
      list.classList.toggle('is-up', up);
      Object.assign(list.style, {
        left: `${r.left}px`, width: `${Math.max(r.width, 180)}px`,
        top: up ? 'auto' : `${r.bottom + 6}px`, bottom: up ? `${innerHeight - r.top + 6}px` : 'auto',
        maxHeight: `${Math.max(140, Math.min(280, (up ? r.top : below) - 12))}px`,
      });
    };
    function open() {
      if (openDd) openDd.close(false);
      list = el('ul', { class: 'dd-list', role: 'listbox', tabindex: '-1' });
      [...sel.options].forEach((opt, i) => {
        list.append(el('li', {
          id: `dd${Math.random().toString(36).slice(2, 8)}`, class: 'dd-opt', role: 'option',
          'aria-selected': String(i === sel.selectedIndex), 'aria-disabled': opt.disabled ? 'true' : null,
          onmousedown: (e) => e.preventDefault(), onclick: () => choose(i), onmousemove: () => setActive(i),
        }, el('span', { class: 'truncate' }, opt.textContent), svgIcon(ICON.check, 15)));
      });
      document.body.append(list);
      place();
      btn.setAttribute('aria-expanded', 'true');
      setActive(Math.max(0, sel.selectedIndex));
      openDd = { close, wrap, list };
    }
    function close(focus) {
      if (!list) return;
      list.remove();
      list = null;
      btn.setAttribute('aria-expanded', 'false');
      if (openDd && openDd.wrap === wrap) openDd = null;
      if (focus) btn.focus();
    }
    btn.addEventListener('click', () => (list ? close(true) : open()));
    btn.addEventListener('keydown', (e) => {
      const keys = { ArrowDown: 1, ArrowUp: -1 };
      if (!list && (e.key in keys || e.key === 'Enter' || e.key === ' ')) {
        e.preventDefault();
        return open();
      }
      if (!list) return;
      if (e.key in keys) { e.preventDefault(); setActive(active + keys[e.key]); }
      else if (e.key === 'Home') { e.preventDefault(); setActive(0); }
      else if (e.key === 'End') { e.preventDefault(); setActive(sel.options.length - 1); }
      else if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); choose(active); }
      else if (e.key === 'Escape' || e.key === 'Tab') { if (e.key === 'Escape') e.preventDefault(); close(e.key === 'Escape'); }
      else if (e.key.length === 1) {
        const i = [...sel.options].findIndex((o, n) => n > active && o.textContent.trim().toLowerCase().startsWith(e.key.toLowerCase()));
        const j = i >= 0 ? i : [...sel.options].findIndex((o) => o.textContent.trim().toLowerCase().startsWith(e.key.toLowerCase()));
        if (j >= 0) setActive(j);
      }
    });
    btn.addEventListener('blur', () => setTimeout(() => { if (list && !list.contains(document.activeElement)) close(false); }, 120));
  }

  document.addEventListener('mousedown', (e) => {
    if (openDd && !openDd.wrap.contains(e.target) && !openDd.list.contains(e.target)) openDd.close(false);
  });
  addEventListener('resize', () => openDd && openDd.close(false));
  document.addEventListener('scroll', (e) => { if (openDd && !openDd.list.contains(e.target)) openDd.close(false); }, true);

  const enhanceAll = (root) => root.querySelectorAll?.('select').forEach(enhanceSelect);
  enhanceAll(document);
  new MutationObserver((records) => records.forEach((r) => r.addedNodes.forEach((n) => {
    if (n.nodeType !== 1) return;
    if (n.tagName === 'SELECT') enhanceSelect(n);
    else enhanceAll(n);
  }))).observe(document.body, { childList: true, subtree: true });

  return { confirm, notify, enhanceSelect, svgIcon };
})();

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
