# Hasil Paritas v1.2.1 (G6)

Penilaian P1–P10 (`standar-kualitas-v1.2.1-paritas.md`) terhadap PRD acuan dan PRD PlanKu, sebelum dan sesudah v1.2.1.
Semua PRD salon dinilai dengan Brief yang sama, yaitu input asli project #34 (`tests/fixtures/paritas/salon.brief.json`).

Keterangan: ✅ lulus · 🟡 sebagian (hanya temuan minor) · ❌ gagal · ➖ tidak berlaku.

## Ringkasan sebelum → sesudah

| PRD | P1 | P2 | P3 | P4 | P5 | P6 | P7 | P8 | P9 | P10 | Lulus |
|---|---|---|---|---|---|---|---|---|---|---|---|
| Eduwebmu (tolok ukur) | ✅ | 🟡 | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | 8/10 |
| ngodingpakeai | ❌ | ✅ | ❌ | ❌ | ❌ | 🟡 | ❌ | ✅ | ✅ | ❌ | 3/10 |
| **Sebelum:** PlanKu v1.2 (BookKapster) | ❌ | ❌ | ❌ | ❌ | ❌ | 🟡 | ❌ | ❌ | ✅ | ❌ | 1/10 |
| Sesudah: mock v1.2.1, peta fitur lama (tanpa `asal`) | ✅ | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | 8/10 |
| Sesudah: mock v1.2.1, peta fitur v1.2.1 | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | 10/10 |
| **Sesudah: AI sungguhan v1.2.1**, keluaran mentah (sebelum `finalize`) | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ | 7/10 |
| **Sesudah: AI sungguhan v1.2.1** + `Pipeline::finalize` | ✅ | ✅ | ✅ | ❌* | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | 9/10 |

Kolom P5 baris "AI mentah" sudah memakai validator yang diperbaiki di G6. Sebelum diperbaiki, validator salah menganggap "(mis. untuk foto kapster)" sebagai alternatif teknis, sehingga P5 dan P9 tercatat gagal.

\* P4 gagal hanya karena task project uji dibuat sebelum `finalize` dipasang. Akibatnya 15 dari 51 butir task masih merujuk nomor rencana lama. Pada alur normal, "Bikin Task" berjalan sesudah PRD selesai dan sudah melewati `finalize`, jadi rujukannya cocok. Rencana terkoordinasinya sendiri lulus semua: berkas, "Selesai bila", dataset M1, dan SEC/uji M3.

Skor total Standar Kualitas (10 dimensi) untuk PRD AI sungguhan:
- keluaran mentah: **75/100**;
- sesudah `finalize`: **79/100**;
- keduanya belum lulus.

Sisa temuannya berasal dari validator v1.2, bukan paritas, dan memang tugas Pass 5:
- 3 SEC belum ada: unggah berkas (2) dan opt-in notifikasi;
- V15 transaksi tamu;
- V16 dua variabel WhatsApp belum masuk `.env.example`;
- V18 aturan checkpoint;
- V14 dua task tanpa ID dataset.

Pass 5 dengan AI sungguhan sudah dicoba, tetapi gagal karena layanan AI mengembalikan JSON rusak/galat. Lihat bagian "Belum selesai".

## Uji AI sungguhan (G6)

- **Input**: input salon asli lewat UI (ide, 5 jawaban, catatan, Bebas, Bersih & Modern, per Fase). Server lokal memakai AI sungguhan dan `AI_MIN_INTERVAL_SECONDS` dari `.env`.
- **Biaya**: 22 panggilan AI untuk satu project (#37), di bawah batas `DOC_MAX_CALLS_PER_PROJECT=36`.
- **Peta fitur**: AI memberi lencana **Usulan** pada 3 fitur di luar masukan (Riwayat Booking, Profil Salon, Statistik Sederhana). Ini perilaku P2 yang diharapkan.

Yang sudah benar langsung dari AI (dulu gagal di v1.2):
- **P1**: exclusion constraint `btree_gist` + `tstzrange` + `SELECT … FOR UPDATE`, ditambah uji dua permintaan bersamaan.
- **P3**: cek booking pakai kode + nomor WA, dan nomor ditampilkan tersamar.
- **P5**: versi paket (Next.js 15, Tailwind v4, Auth.js v5, Zod 3), zona waktu UTC → Asia/Jakarta dengan `date-fns-tz`, serta `vercel.json` `*/10 * * * *` dengan CRON_SECRET dan idempotensi.
- **P6**: SEO.
- **P7**: pengaturan "3 jam" punya nilai bawaan, dan template pesan ada di tabel.
- **P9**: satu stack.

Yang dilanggar AI walau prompt sudah melarang:
- usulan masuk rute dan milestone;
- 38 tugas terkoordinasi (batas 30);
- Master Prompt tanpa pertanyaan kesiapan.

Ketiganya kini ditegakkan secara deterministik oleh `Pipeline::finalize` setelah PRD dirakit dan setelah setiap perbaikan Pass 5.

## Uji generalisasi (mock, spec 5.6)

Ketiganya skor 100, lulus, tanpa temuan kritis, dan P1–P10 lulus atau tidak berlaku:

| Jenis app | Stack |
|---|---|
| Reservasi ruang | React + Node.js (PostgreSQL) |
| Manajemen tugas tim | Full Supabase |
| Toko online kecil | React + Cloudflare D1 |

Tiga kasus benchmark lama juga lulus tanpa temuan paritas di 4 stack (katering, kebersihan, lab).

## Bandingkan dengan Eduwebmu (jujur)

**Mock v1.2.1.** Pada aturan yang bisa dinilai mesin, mock v1.2.1 dengan peta fitur v1.2.1 lulus 10/10, sedangkan Eduwebmu 8/10. Eduwebmu gagal P4 karena tugasnya tanpa "Selesai bila".

**AI sungguhan.** Hasil AI sungguhan belum setara penuh dengan Eduwebmu. Sesudah `finalize`, paritasnya 9/10, tetapi Standar Kualitas totalnya masih 79/100 karena keamanan v1.2 belum lengkap. Kesetaraan baru bisa diklaim setelah Pass 5 berhasil memperbaiki bab 8, 6, 12, dan 13 pada run AI sungguhan; itu belum terbukti karena layanan AI galat saat audit.

**Batas heuristik.** Validator memeriksa ada tidaknya unsur, bukan mutu tulisan. Mutu narasi Eduwebmu (alur kerja per fitur yang rinci, aturan sistem bernomor) belum dinilai otomatis (dimensi Keterbacaan). P2 pada PRD tanpa field `asal` memakai heuristik kata. Hasilnya akurat untuk ketiga acuan, tetapi bisa meleset pada nama fitur yang sangat berbeda dari kata pengguna.

## Belum selesai

- **Pass 5 dengan AI sungguhan.** Audit project #37 berhenti di langkah perbaikan karena AI mengembalikan JSON rusak dan layanan bermasalah ("Layanan AI sedang bermasalah"). Ini tidak diulang tiga kali untuk menghemat kuota; dicatat juga di `docs/progress-paritas.md`. Untuk mengulang:
  1. Buka project BookKapster terbaru (#37).
  2. Klik "Buat ulang PRD", lalu "Bikin Task".
  3. Jalankan audit di langkah Implemen.

## Cara menjalankan ulang

```bash
# semua tes (mode mock, tanpa AI)
C:\xampp\php\php.exe tests\paritas_test.php
C:\xampp\php\php.exe tests\quality_v12_test.php
C:\xampp\php\php.exe tests\security_test.php
C:\xampp\php\php.exe tests\json_repair_test.php

# P1–P10 untuk PRD acuan atau folder ZIP
C:\xampp\php\php.exe tools\parity.php ref-prd\eduwebmu-salonbook.md --brief tests\fixtures\paritas\salon.brief.json
C:\xampp\php\php.exe tools\parity.php ref-prd\planku-bookkapster --brief tests\fixtures\paritas\salon.brief.json

# fixture PlanKu (JSON brief+prd+tasks) dan benchmark lengkap
C:\xampp\php\php.exe tools\export_fixture.php 37 benchmark\ai-salon.json
C:\xampp\php\php.exe tools\parity.php benchmark\ai-salon.json
C:\xampp\php\php.exe tools\benchmark.php
```
