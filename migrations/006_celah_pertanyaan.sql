-- v1.2.1: pertanyaan klarifikasi dinamis berbasis celah (aditif). Label celah dipakai untuk memetakan jawaban ke PRD.
ALTER TABLE questions ADD COLUMN celah VARCHAR(30) NULL AFTER urutan;
