-- Standar Kualitas v1.2: isian Bagian B (desain, teknologi, cara kerja AI, catatan) dan penanda milestone task.
-- Aditif: kolom lama tech_mode/tech_stack_pilihan tetap ada dan tetap diisi.

ALTER TABLE projects
  ADD COLUMN design_style  VARCHAR(20)   NOT NULL DEFAULT 'bebas' AFTER rekomendasi_stack,
  ADD COLUMN design_custom VARCHAR(1000) NULL AFTER design_style,
  ADD COLUMN stack_choice  VARCHAR(30)   NOT NULL DEFAULT 'bebas' AFTER design_custom,
  ADD COLUMN stack_custom  VARCHAR(500)  NULL AFTER stack_choice,
  ADD COLUMN ui_only       TINYINT(1)    NOT NULL DEFAULT 0 AFTER stack_custom,
  ADD COLUMN ai_workflow   VARCHAR(10)   NOT NULL DEFAULT 'fase' AFTER ui_only,
  ADD COLUMN extra_notes   TEXT NULL AFTER ai_workflow;

-- Project lama yang memilih teknologi sendiri menjadi "Tulis Sendiri" dengan teks yang sama.
UPDATE projects SET stack_choice = 'tulis_sendiri', stack_custom = tech_stack_pilihan
 WHERE tech_mode = 'manual' AND tech_stack_pilihan IS NOT NULL;

-- NULL = task lama (dibuat sebelum v1.2).
ALTER TABLE tasks
  ADD COLUMN milestone ENUM('M1','M2','M3') NULL AFTER phase;
