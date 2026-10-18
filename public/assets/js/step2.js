'use strict';

// Bagian B: penghitung karakter Catatan Tambahan. Kolom "Tulis Sendiri" dibuka lewat CSS (:has).
(() => {
  const notes = document.getElementById('extra_notes');
  const count = document.getElementById('notesCount');
  const max = notes.maxLength;
  const update = () => { count.textContent = `${notes.value.length}/${max}`; };
  notes.addEventListener('input', update);
  update();
})();
