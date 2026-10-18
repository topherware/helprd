-- U1: tabel & kolom untuk workspace (PRD versi, task, agent). Aditif: tidak menghapus apa pun.

CREATE TABLE IF NOT EXISTS prd_versions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  content_md LONGTEXT NOT NULL,
  source ENUM('ai','manual') NOT NULL,
  selesai TINYINT(1) NOT NULL DEFAULT 1, -- 0 = hasil streaming parsial (terputus)
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_prd_versions_project (project_id, id),
  CONSTRAINT fk_prd_versions_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- fase, ikon, status, urutan sudah ada (setara phase, icon, status, sort_order di spec).
ALTER TABLE features
  ADD COLUMN IF NOT EXISTS deskripsi TEXT NULL,
  ADD COLUMN IF NOT EXISTS tujuan TEXT NULL,
  ADD COLUMN IF NOT EXISTS selesai_bila JSON NULL;

ALTER TABLE sub_features
  ADD COLUMN IF NOT EXISTS deskripsi TEXT NULL,
  ADD COLUMN IF NOT EXISTS tujuan TEXT NULL,
  ADD COLUMN IF NOT EXISTS selesai_bila JSON NULL;

CREATE TABLE IF NOT EXISTS tasks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  feature_id INT UNSIGNED NOT NULL,
  sub_feature_id INT UNSIGNED NULL,
  title VARCHAR(200) NOT NULL,
  phase TINYINT UNSIGNED NOT NULL DEFAULT 1,
  priority ENUM('utama','penting','opsional') NOT NULL DEFAULT 'penting',
  status ENUM('belum_mulai','dikerjakan','selesai','gagal') NOT NULL DEFAULT 'belum_mulai',
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_tasks_project_feature_status (project_id, feature_id, status),
  CONSTRAINT fk_tasks_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
  CONSTRAINT fk_tasks_feature FOREIGN KEY (feature_id) REFERENCES features(id) ON DELETE CASCADE,
  CONSTRAINT fk_tasks_sub_feature FOREIGN KEY (sub_feature_id) REFERENCES sub_features(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agent_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  role ENUM('user','assistant','system') NOT NULL,
  content TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_agent_messages_project (project_id, id),
  CONSTRAINT fk_agent_messages_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
