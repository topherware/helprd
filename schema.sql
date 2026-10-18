-- Skema database PlanKu (MySQL / MariaDB, utf8mb4)
-- Impor lewat phpMyAdmin atau: mysql -u root < schema.sql

CREATE DATABASE IF NOT EXISTS planku CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE planku;

CREATE TABLE IF NOT EXISTS projects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_token CHAR(64) NOT NULL,
  judul VARCHAR(150) NOT NULL,
  ide_awal TEXT NOT NULL,
  tech_mode ENUM('ai','manual') NULL,
  tech_stack_pilihan VARCHAR(255) NULL,
  rekomendasi_stack TEXT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'teknologi',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_projects_token (session_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS questions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  urutan TINYINT UNSIGNED NOT NULL,
  teks TEXT NOT NULL,
  tipe ENUM('teks_bebas','pilihan') NOT NULL,
  opsi JSON NULL,
  boleh_pilih_banyak TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_questions_project (project_id, urutan),
  CONSTRAINT fk_questions_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS answers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question_id INT UNSIGNED NOT NULL,
  jawaban TEXT NULL,
  dilewati TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_answers_question (question_id),
  CONSTRAINT fk_answers_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS features (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  nama VARCHAR(100) NOT NULL,
  fase TINYINT UNSIGNED NOT NULL CHECK (fase BETWEEN 1 AND 4),
  ikon VARCHAR(30) NOT NULL DEFAULT 'bintang',
  status ENUM('direncanakan','dikerjakan','selesai') NOT NULL DEFAULT 'direncanakan',
  urutan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_features_project (project_id, fase, urutan),
  CONSTRAINT fk_features_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sub_features (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  feature_id INT UNSIGNED NOT NULL,
  nama VARCHAR(100) NOT NULL,
  urutan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  KEY idx_sub_features_feature (feature_id, urutan),
  CONSTRAINT fk_sub_features_feature FOREIGN KEY (feature_id) REFERENCES features(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS api_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NULL,
  ip_address VARCHAR(45) NOT NULL,
  endpoint VARCHAR(100) NOT NULL,
  input_tokens INT UNSIGNED NOT NULL DEFAULT 0,
  output_tokens INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_api_logs_ip_time (ip_address, created_at),
  CONSTRAINT fk_api_logs_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dokumen hasil langkah 5. feature_id = 0 berarti PRD.
-- peta_hash menandai versi peta fitur saat dokumen dibuat; bila peta berubah, dokumen dibuat ulang.
CREATE TABLE IF NOT EXISTS documents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  feature_id INT UNSIGNED NOT NULL DEFAULT 0,
  peta_hash CHAR(40) NOT NULL,
  data JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_documents (project_id, feature_id),
  CONSTRAINT fk_documents_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Laporan Kualitas (Standar PlanKu) terakhir per project.
CREATE TABLE IF NOT EXISTS quality_reports (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT UNSIGNED NOT NULL,
  peta_hash CHAR(40) NOT NULL,
  standar_versi VARCHAR(10) NOT NULL,
  skor_keamanan TINYINT UNSIGNED NOT NULL,
  lulus TINYINT(1) NOT NULL DEFAULT 0,
  data JSON NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_quality_reports_project (project_id),
  CONSTRAINT fk_quality_reports_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
