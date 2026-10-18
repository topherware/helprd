# Progres Standar Kualitas v1.2.1 (Paritas)

Dikerjakan berurutan G1 → G6 tanpa jeda konfirmasi (permintaan pemilik). Tiap tahap: tes mode mock, commit, catatan di sini.

| Tahap | Status | Commit | Catatan |
|---|---|---|---|
| Cadangan | selesai | 23a3d72 | `git init` + commit snapshot v1.2 (tanpa `.env`, `logs/`). |
| G1 Audit | selesai | (lihat log) | `docs/rencana-v1.2.1.md`. Brief fixture dari project #34 (`tests/fixtures/paritas/salon.brief.json`). |
| G2 Pemicu, asal, invarian | selesai | (lihat log) | Migrasi 005 (`features.asal`, `asal_alasan`, `disertakan`, `tasks.koordinasi`); `Triggers` + 5 bendera (`hasTimeFields`, `hasScheduler`, `hasPublicPages`, `hasOfflineChannel`, `hasTimeRange`) + `Triggers::invariants()`; peta fitur memberi `asal` (maks. 3 usulan AI, nonaktif bawaan); lencana **Usulan** + saklar "Sertakan ke PRD" di langkah 4; Roadmap workspace tidak berubah (hanya fitur yang disertakan). Tes: `tests/paritas_test.php` (15 lulus) + uji browser mock. |
| G3 Prompt & mock | belum | | |
| G4 Validator | belum | | |
| G5 Pipeline & skor | belum | | |
| G6 Benchmark | belum | | |

## Belum selesai

_(diisi bila sebuah tes gagal tiga kali pada hal yang sama)_
