-- Riwayat plan per browser: project dikelompokkan lewat token pemilik (cookie planku_owner), tanpa login.
ALTER TABLE projects
  ADD COLUMN owner_token CHAR(64) NULL AFTER session_token,
  ADD KEY idx_projects_owner (owner_token, created_at);
