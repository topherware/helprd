'use strict';

(() => {
  const box = document.getElementById('questions');
  const counter = document.getElementById('counter');
  const actions = document.getElementById('actions');
  const submitBtn = document.getElementById('submitAnswers');
  const formError = document.getElementById('formError');
  let questions = [];
  // state per pertanyaan: { teks, pilihan: Set, lainnya, dilewati }
  const state = new Map();

  async function load() {
    box.setAttribute('aria-busy', 'true');
    try {
      const data = await api('generate_questions.php');
      questions = data.questions;
      questions.forEach(initState);
      render();
      actions.hidden = false;
    } catch (err) {
      box.replaceChildren(el('div', { class: 'loading' },
        el('p', { class: 'form-error' }, err.message),
        el('button', { type: 'button', class: 'btn', onclick: () => { box.replaceChildren(loadingNode()); load(); } }, 'Coba lagi')));
    } finally {
      box.removeAttribute('aria-busy');
    }
  }

  function loadingNode() {
    return el('div', { class: 'loading' }, el('span', { class: 'spinner' }), el('p', {}, 'AI sedang menyusun pertanyaan untuk ide kamu…'));
  }

  function initState(q) {
    const s = { teks: '', pilihan: new Set(), lainnya: '', dilewati: q.dilewati === true };
    if (q.tipe === 'pilihan' && Array.isArray(q.jawaban)) {
      const custom = [];
      q.jawaban.forEach((j) => (q.opsi.includes(j) ? s.pilihan.add(j) : custom.push(j)));
      s.lainnya = custom.join(', ');
    } else if (typeof q.jawaban === 'string') {
      s.teks = q.jawaban;
    }
    state.set(q.id, s);
  }

  function isAnswered(q) {
    const s = state.get(q.id);
    return q.tipe === 'pilihan' ? s.pilihan.size > 0 || s.lainnya.trim() !== '' : s.teks.trim() !== '';
  }

  function updateCounter() {
    const done = questions.filter((q) => isAnswered(q) || state.get(q.id).dilewati).length;
    counter.textContent = `${done}/${questions.length}`;
  }

  function render() {
    box.replaceChildren(...questions.map(renderQuestion));
    updateCounter();
  }

  function renderQuestion(q, index) {
    const s = state.get(q.id);
    const skipBtn = el('button', {
      type: 'button', class: 'skip', 'aria-pressed': String(s.dilewati),
      onclick: () => { s.dilewati = !s.dilewati; refresh(); },
    }, s.dilewati ? 'Dilewati' : 'Lewati');

    const head = el('div', { class: 'q-head' },
      el('p', { class: 'q-text', id: `q${q.id}` },
        el('span', { class: 'q-num' }, `${index + 1}.`), ' ', q.teks,
        q.boleh_pilih_banyak ? el('span', { class: 'muted' }, ' (boleh pilih beberapa)') : null),
      skipBtn);

    const body = q.tipe === 'pilihan' ? renderChoices(q, s) : renderText(q, s);
    const card = el('section', { class: 'question' + (s.dilewati ? ' is-skipped' : ''), 'aria-labelledby': `q${q.id}` }, head, body);

    function refresh() {
      card.replaceWith(renderQuestion(q, index));
      updateCounter();
    }
    return card;
  }

  function renderText(q, s) {
    return el('textarea', {
      rows: 3, maxlength: 1500, placeholder: 'Ketik jawaban…', 'aria-labelledby': `q${q.id}`,
      disabled: s.dilewati,
      oninput: (e) => { s.teks = e.target.value; updateCounter(); },
    }, s.teks);
  }

  function renderChoices(q, s) {
    const wrap = el('div', { class: 'chips', role: 'group', 'aria-labelledby': `q${q.id}` });
    const other = el('input', {
      type: 'text', class: 'other-input', maxlength: 200, placeholder: 'Tulis jawaban lain…',
      hidden: s.lainnya === '', disabled: s.dilewati, value: s.lainnya,
      oninput: (e) => { s.lainnya = e.target.value; updateCounter(); },
    });

    q.opsi.forEach((opt) => {
      const chip = el('button', {
        type: 'button', class: 'chip', 'aria-pressed': String(s.pilihan.has(opt)), disabled: s.dilewati,
        onclick: () => {
          if (s.pilihan.has(opt)) {
            s.pilihan.delete(opt);
          } else {
            if (!q.boleh_pilih_banyak) {
              s.pilihan.clear();
              s.lainnya = '';
              other.value = '';
              other.hidden = true;
              wrap.querySelectorAll('.chip[aria-pressed]').forEach((c) => c.setAttribute('aria-pressed', 'false'));
            }
            s.pilihan.add(opt);
          }
          chip.setAttribute('aria-pressed', String(s.pilihan.has(opt)));
          updateCounter();
        },
      }, opt);
      wrap.append(chip);
    });

    wrap.append(el('button', {
      type: 'button', class: 'chip chip-dashed', disabled: s.dilewati,
      onclick: () => {
        if (!q.boleh_pilih_banyak) {
          s.pilihan.clear();
          wrap.querySelectorAll('.chip[aria-pressed]').forEach((c) => c.setAttribute('aria-pressed', 'false'));
        }
        other.hidden = false;
        other.focus();
        updateCounter();
      },
    }, '+ Lainnya'));

    return el('div', {}, wrap, other);
  }

  submitBtn.addEventListener('click', async () => {
    formError.hidden = true;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Menyimpan…';
    const jawaban = questions.map((q) => {
      const s = state.get(q.id);
      const value = q.tipe === 'pilihan'
        ? [...s.pilihan, ...s.lainnya.split(',').map((x) => x.trim()).filter(Boolean)]
        : s.teks;
      return { question_id: q.id, jawaban: value, dilewati: s.dilewati || !isAnswered(q) };
    });
    try {
      await api('save_answers.php', { jawaban });
      location.href = 'step4.php';
    } catch (err) {
      formError.textContent = err.message;
      formError.hidden = false;
      submitBtn.disabled = false;
      submitBtn.textContent = 'Buat Peta Fitur';
    }
  });

  load();
})();
