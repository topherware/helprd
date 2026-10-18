# Progres Standar Kualitas v1.2.1 (Paritas)

Dikerjakan berurutan G1 → G6 tanpa jeda konfirmasi (permintaan pemilik). Tiap tahap: tes mode mock, commit, catatan di sini.

| Tahap | Status | Commit | Catatan |
|---|---|---|---|
| Cadangan | selesai | 23a3d72 | `git init` + commit snapshot v1.2 (tanpa `.env`, `logs/`). |
| G1 Audit | selesai | (lihat log) | `docs/rencana-v1.2.1.md`. Brief fixture dari project #34 (`tests/fixtures/paritas/salon.brief.json`). |
| G2 Pemicu, asal, invarian | selesai | (lihat log) | Migrasi 005 (`features.asal`, `asal_alasan`, `disertakan`, `tasks.koordinasi`); `Triggers` + 5 bendera (`hasTimeFields`, `hasScheduler`, `hasPublicPages`, `hasOfflineChannel`, `hasTimeRange`) + `Triggers::invariants()`; peta fitur memberi `asal` (maks. 3 usulan AI, nonaktif bawaan); lencana **Usulan** + saklar "Sertakan ke PRD" di langkah 4; Roadmap workspace tidak berubah (hanya fitur yang disertakan). Tes: `tests/paritas_test.php` (15 lulus) + uji browser mock. |
| G3 Prompt & mock | selesai | (lihat log) | Potongan PRD ke-9 `p8` (bab 13 rencana terkoordinasi). Kunci JSON baru: `requirements.usulan_tambahan`, `requirements.seo`, `database.invarian`/`migrasi_kustom`/`pengaturan`, `kedalaman`, `milestone.rencana`, microcopy `judul_utama`/`cta`/`penuh`, `tasks.koordinasi`. `stacks.json` v2: `kunci`, `kedalaman`, `struktur`, `resep_invarian` per DB. Mock: tabel pengaturan dari angka input, FR + rute tamu (kode rahasia), rute input manual offline, master prompt bergerbang, ≤ 8 task/fitur dengan "→ Task Mx.y". Uji e2e mock: ZIP berisi semua bagian baru, 56/56 task punya rujukan koordinasi, SQL exclusion/atomik ada. 4 berkas tes lulus. |
| G4 Validator | belum | | |
| G5 Pipeline & skor | belum | | |
| G6 Benchmark | belum | | |

## Belum selesai

_(diisi bila sebuah tes gagal tiga kali pada hal yang sama)_
