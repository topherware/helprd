-- Potongan PRD yang sudah selesai ditulis (PRD dibuat dalam beberapa panggilan pendek).
-- Dipakai untuk melanjutkan setelah koneksi ke AI putus; dihapus setelah PRD lengkap tersimpan di documents.
CREATE TABLE IF NOT EXISTS prd_parts (
  project_id INT UNSIGNED NOT NULL,
  bagian VARCHAR(20) NOT NULL, -- mis. "prd:0", "keamanan:2"
  peta_hash CHAR(40) NOT NULL,
  data JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (project_id, bagian),
  CONSTRAINT fk_prd_parts_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
