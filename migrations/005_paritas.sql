-- Standar Kualitas v1.2.1 (paritas): asal fitur, fitur usulan yang belum disertakan, dan rujukan task ke tugas terkoordinasi.
-- Aditif: data lama dianggap berasal dari ide dan tetap disertakan.
ALTER TABLE features
  ADD COLUMN asal VARCHAR(12) NOT NULL DEFAULT 'ide' AFTER ikon,
  ADD COLUMN asal_alasan VARCHAR(300) NULL AFTER asal,
  ADD COLUMN disertakan TINYINT(1) NOT NULL DEFAULT 1 AFTER asal_alasan;

ALTER TABLE tasks
  ADD COLUMN koordinasi VARCHAR(10) NULL AFTER milestone;
