-- Sidebar "Semua Plan" berfolder + lampiran referensi di kotak ide (aditif).
-- Folder milik browser (owner_token). Plan tanpa folder_id tampil di folder bawaan "Umum".
CREATE TABLE IF NOT EXISTS plan_folders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_token CHAR(64) NOT NULL,
  nama VARCHAR(60) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_folders_owner (owner_token, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Folder dihapus: plan di dalamnya kembali ke "Umum" (ON DELETE SET NULL), datanya tidak ikut terhapus.
-- referensi = ringkasan isi lampiran dari AI (dibuat sekali, dipakai di semua prompt).
ALTER TABLE projects
  ADD COLUMN folder_id INT UNSIGNED NULL AFTER owner_token,
  ADD COLUMN referensi MEDIUMTEXT NULL AFTER ide_awal,
  ADD CONSTRAINT fk_projects_folder FOREIGN KEY (folder_id) REFERENCES plan_folders (id) ON DELETE SET NULL;

-- Berkas referensi (gambar/PDF/teks) yang dilampirkan bersama ide. Berkas disimpan di storage/uploads (di luar public).
CREATE TABLE IF NOT EXISTS project_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  nama_asli VARCHAR(160) NOT NULL,
  mime VARCHAR(80) NOT NULL,
  ukuran INT UNSIGNED NOT NULL,
  path VARCHAR(200) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_files_project (project_id),
  CONSTRAINT fk_files_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
