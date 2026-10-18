# Rencana v1.2.1: Paritas dengan Eduwebmu (hasil G1)

Audit `standar-kualitas-v1.2.1-paritas.md` terhadap kode PlanKu v1.2 dan tiga PRD acuan di `ref-prd/`.
Pemilik meminta G1–G6 dikerjakan berurutan tanpa jeda konfirmasi; keputusan yang biasanya ditanyakan diambil sesuai rekomendasi spec atau opsi paling aman, dan dicatat di `docs/keputusan.md`.

## A. Temuan dari PRD acuan

| Aspek | Eduwebmu (tolok ukur) | PlanKu BookKapster (v1.2) | ngodingpakeai |
|---|---|---|---|
| Bentrok jadwal | `EXCLUDE USING gist (barber_id WITH =, tstzrange(start_at,end_at) WITH &&)` + `SELECT … FOR UPDATE` + uji "dua pelanggan memilih slot yang sama bersamaan" | hanya `unique(kapster_id, mulai_pada)` | "batasan unik kapster + tanggal + jam" di SQLite, tanpa transaksi |
| Fitur tanpa asal | halaman Tentang/Kontak/testimoni (pelengkap) | Dashboard Pemilik, Pencarian Jadwal, Riwayat Pelanggan, peran kapster (login) | – |
| Data milik tamu | `/cek-booking` butuh kode + nomor WA, rate limit, nomor disamarkan | `/riwayat` cukup nomor WA (FR-27) | kode booking saja |
| Tugas | 26 tugas gemuk `Task X.Y` berisi berkas & perilaku (tanpa "selesai bila" per tugas) | 134 butir judul pendek (batas v1.2: 120) | daftar butir per fitur |
| Kedalaman stack | Next.js 15, Tailwind v4, Auth.js v5, Zod, `date-fns-tz`, `vercel.json` cron tiap 10 menit | tanpa versi, "Vitest/Jest", "(mis. Vercel)" | "(mis. Fonnte, Wablas)", "(mis. Vercel Cron / node-cron)" |
| SEO | title/meta, OG, JSON-LD, sitemap, robots, noindex, gambar, revalidate | tidak ada | tidak ada |
| Angka jadi pengaturan | `salon_settings.reminder_hours_before default 3`, template pesan di tabel | 3 jam hanya di teks | – |
| Batas | – | hardening 11 (batas 8), tugas 134 | – |
| Master Prompt | ringkas ≤ 10 poin, rencana file fase 1, tanya kesiapan, fase berikut "hanya setelah izin" | checkpoint ada, tanpa ringkasan 10 poin & tanya kesiapan | – |

Input asli BookKapster masih ada di database (project 34: ide, 5 jawaban, catatan, stack "Bebas", cara kerja per Fase). Input itu dipakai sebagai Brief fixture `tests/fixtures/paritas/salon.brief.json` untuk ketiga PRD acuan (kata salon/kapster hanya ada di fixture tes).

## B. Arsitektur: validator paritas membaca "paket dokumen"

PRD acuan berupa markdown, sedangkan PlanKu menyimpan JSON. Supaya **satu** implementasi P1–P10 dipakai untuk keduanya:
- `src/Quality/Paritas/Paket.php`: kumpulan berkas (`PRD.md`, `features/*.md`, `TASKS.md`, `CLAUDE.md`, `.env.example`, `db/schema.*`) dengan pembaca bab, blok kode, tabel, daftar tugas, dan rute.
  - `Paket::fromFolder()` / `fromFile()` untuk PRD acuan (satu dokumen = format Eduwebmu: aturan khusus file per fitur ditandai *tidak berlaku*).
  - `Paket::fromPrd()` merakit berkas yang sama dari JSON PlanKu (renderer yang sama dengan ZIP), ditambah data terstruktur (`asal`, `invarian`, rencana tugas) yang dipakai lebih dulu bila ada.
- `src/Quality/Paritas/P01Invarian.php` … `P10MasterPrompt.php` + `PBatas.php` (P8). Masukan: `Brief`, bendera, profil stack, `Paket`. Keluaran temuan v1.2 (`id`, `sev`, `bab`, `pesan`, `saran`) + status per aturan: **lulus / sebagian (hanya minor) / gagal / tidak berlaku**.
- `QualityReport::evaluate()` menjalankan P1–P10 pada `Paket::fromPrd()`; `tools/parity.php <folder|file> [--brief …]` memakai fungsi evaluasi paritas yang sama.

## C. Perubahan per aturan

| Aturan | Pembangkit (prompt + mock) | Validator (sev) | Deteksi pada markdown acuan |
|---|---|---|---|
| **P1 Invarian** | Profil mendeklarasikan `invarian[]` (jenis: `rentang`, `kuota`, `stok`, `unik`, `durasi`); bab 6 punya `invarian[]` (aturan, mekanisme DB, konkurensi, uji) + `migrasi_kustom` (SQL). Resep per jenis DB di `stacks.json`. Tugas M3 "uji dua permintaan bersamaan". | **Invarian (K)**: tiap invarian punya mekanisme sesuai resep stack; rentang di PostgreSQL wajib exclusion/`tstzrange`/`btree_gist`, tidak boleh hanya `unique(sumber_daya, mulai)`; ada uji konkurensi. | Invarian dari Brief lewat kata pemicu `Triggers::INVARIAN`; mekanisme dicari di kode skema/blok SQL; uji dicari di tugas. |
| **P2 Lingkup** | Peta fitur: field `asal` (`ide`/`jawaban`/`catatan`/`keputusan`/`usulan_ai`) + `disertakan`. `usulan_ai` nonaktif secara default, lencana **Usulan** di peta fitur, tombol nyalakan. PRD bab 2.6 **Usulan Tambahan (belum dijadwalkan)** maks. 3 butir + alasan; tidak masuk milestone. Peran di profil punya `asal`. | **Lingkup (M; K untuk peran tanpa asal)**: fitur terlacak; tidak ada `usulan_ai` di milestone/rute/tugas; peran baru tanpa asal dilarang. | Heuristik kata (stem sederhana + sinonim umum) nama fitur terhadap Brief; peran login harus disebut sebagai pelaku di Brief atau berjenis pengelola; halaman pelengkap tanpa asal = minor ("tercatat sebagai usulan"). |
| **P3 KepemilikanData** | Bila `hasGuestFlow`: alur "cek/lihat data saya" memakai pengenal + kode rahasia yang diterbitkan saat transaksi; kontak disamarkan selain di panel admin; rate limit pencarian. | **KepemilikanData (K)**: tidak ada rute/fitur pencarian data milik pengguna dengan satu pengenal publik. (M): kontak tersamar tidak disebut. | Kalimat/rute "riwayat/histori/cek/status … berdasarkan/dengan nomor/email/nama" tanpa kode/token/PIN/OTP. |
| **P4 KekhususanTugas** | Bab 13 **Rencana Pengerjaan Terkoordinasi**: `Task M<n>.<k>` (≤ 10/milestone, ≤ 30 total) berisi berkas, perilaku, dataset (M1), SEC/skenario uji (M3), **Selesai bila**. Tugas M2 pertama membuat `db/seed.ts` dari bab 11. Butir di `features/*.md` (Bikin Task) merujuk `Task M<n>.<k>` (kolom baru `tasks.koordinasi`). | **KekhususanTugas (M)**: tiap tugas punya jalur berkas/modul + selesai bila (+ dataset di M1); butir fitur terpetakan ke tugas terkoordinasi. | Baris `Task X.Y` / `Task Mx.y`; jalur berkas lewat pola `dir/berkas.ext`, "Selesai bila". |
| **P5 KedalamanStack** | `stacks.json`: versi mayor paket, pustaka validasi, zona waktu, resep cron (berkas, interval, rahasia, idempotensi). Bab 7 memuat versi, auth (sesi + hashing), ORM & migrasi, validasi, kebijakan zona waktu (bila `hasTimeFields`), penjadwal (bila `hasScheduler`), alat uji, deployment. | **KedalamanStack (M)**: versi ada; zona waktu bila `hasTimeFields`; konfigurasi cron bila `hasScheduler`; pustaka validasi ada; dilarang "mis. A / B" atau "A/B" sebagai keputusan. | Pola versi (`Next.js 15`, `v5`), `UTC` + zona + pustaka, `vercel.json`/`wrangler`/`*/10`, Zod/Valibot/… |
| **P6 PublikSEO** | Bila `hasPublicPages`: NFR SEO/performa ≤ 8 butir (title/meta, OG, sitemap/robots, noindex privat, gambar, cache/revalidasi). | **PublikSEO (m)**. | Kata kunci yang sama di seluruh PRD. |
| **P7 Konfigurasi** | Angka/nilai dari Brief (mis. "3 jam", jam operasional, kontak, template pesan) → tabel pengaturan satu baris di bab 6 (kolom + default) dan/atau variabel `.env` + siapa yang boleh mengubah. | **Konfigurasi (M)**: angka dari Brief muncul sebagai pengaturan dengan nilai bawaan; template pesan disimpan sebagai pengaturan. | Angka Brief dicari sebagai `default(N)`/`DEFAULT N`/`VAR=N`; template di kolom `tpl_*`/`template*`. |
| **P8 Batas** | Prompt dan Pass 5 memangkas/menggabung. Bikin Task ≤ 8 per fitur (sebelumnya 14). | **Batas (K)**: hardening ≤ 8 tanpa duplikat MVP; tugas ≤ 8 per fitur dan ≤ 8 × fitur; tugas terkoordinasi ≤ 30 (dan ≤ 10 per milestone). Cek batas dipindah dari V17/V19 ke sini supaya tidak dihitung ganda. | Butir di bab hardening; centang per berkas fitur / TASKS.md. |
| **P9 KunciPilihan** | `stacks.json` diberi `kunci` per stack (framework, ORM, auth, DB). Prompt: dilarang menyebut alternatif. "Bebas" dikunci di profil. | **KunciPilihan (K)**: paket/ORM/auth/penyedia sesuai `kunci`; satu ORM dan satu auth di seluruh dokumen. | Kelompok ORM/auth/DB dikenali dari teks bab tech stack, CLAUDE.md, `.env.example`. |
| **P10 MasterPrompt** | Master Prompt bab 13: baca berkas, ringkasan ≤ 10 poin, rencana berkas milestone pertama, tanya kesiapan, "milestone berikutnya hanya setelah izin" (disesuaikan per Task/Fase/Sekaligus), larangan mengarang data di luar bab 11, patuh skema/rute/hak akses/aturan bisnis/design system. | **MasterPrompt (M)**. | Blok kode setelah heading "Master … Prompt". |

Kelengkapan lain (bagian 3 spec) dicek ringan sebagai **Kelengkapan (m)**: entitas dari alur publik punya rute kelola admin; jalur pencatatan manual bila `hasOfflineChannel`; microcopy (judul, CTA, sukses, gagal/penuh, kosong); benih data `db/seed.ts`; tabel keterlacakan FR → rute, tabel, tugas.

## D. Perubahan skema JSON & database

- **Peta fitur** (`Prompts::features`, `Validator::aiFeatures`): `asal` per fitur (default `ide` untuk data lama). Migrasi **005** (aditif): `features.asal VARCHAR(12) NOT NULL DEFAULT 'ide'`, `features.disertakan TINYINT(1) NOT NULL DEFAULT 1`, `tasks.koordinasi VARCHAR(10) NULL`.
- `project_features()` hanya mengembalikan fitur `disertakan = 1` (PRD, task, sidik); peta fitur/roadmap memakai daftar lengkap. Brief membawa `asal` dan daftar usulan.
- **Profil**: `invarian[]` `{jenis, aturan, sumber_daya, asal}`, `peran[]` boleh berupa `{nama, asal}` (format lama string tetap diterima).
- **Bab 2**: `requirements.usulan_tambahan[]` `{fitur, alasan}` (≤ 3).
- **Bab 6**: `database.invarian[]` `{id, jenis, aturan, mekanisme, konkurensi, uji}`, `database.migrasi_kustom` (SQL), `database.pengaturan[]` `{kunci, nilai_bawaan, lokasi, pengubah}`.
- **Bab 7**: `kedalaman` `{versi[], auth, orm_migrasi, validasi, zona_waktu, penjadwal{berkas, endpoint, interval, rahasia, idempotensi}, uji, deployment}`.
- **NFR SEO**: `requirements.seo[]` (≤ 8).
- **Bab 13**: `milestone.rencana[]` `{id, judul, berkas[], perilaku, dataset[], sec[], selesai_bila}`; Master Prompt dengan gerbang izin.
- **Data lama tetap sah**: semua kunci baru opsional di normalizer; PRD v1.2 lama dinilai dan gagal di aturan baru (diharapkan).

## E. `stacks.json` tambahan

Per stack: `kunci` (framework/ORM/auth/DB), `versi` (paket inti + versi mayor), `validasi` (Zod), `zona_waktu` (`date-fns-tz` / `Intl`), `cron` (berkas, sintaks interval, rahasia), `orm_migrasi`, `auth_detail`.
Per jenis DB (`resep_invarian`): `postgresql` (exclusion `btree_gist` + `tstzrange` + transaksi + `FOR UPDATE`), `mysql` (transaksi + `SELECT … FOR UPDATE` + kueri tumpang tindih), `sqlite` (`BEGIN IMMEDIATE` + kueri tumpang tindih), `d1` (pernyataan `INSERT … SELECT … WHERE NOT EXISTS` atomik dalam satu batch), `mongodb` (dokumen slot unik / update atomik bersyarat); juga resep `stok` (`CHECK (stok >= 0)` + `UPDATE … WHERE stok >= n`), `kuota` (transaksi + hitung + kunci), `unik` (unique index).

## F. Rencana panggilan AI

Pass 1 menjadi 9 potongan: profil (+invarian, asal peran) → bab 1–2 (+usulan, SEO) → 3–5 → 6–7 (+invarian, migrasi kustom, pengaturan, kedalaman stack) → 8 → 9–10 → 11 → 12 → 13 (rencana terkoordinasi + Master Prompt). Bab 13 dipisah dari bab 12 karena rencana tugas panjang. Bikin Task: ≤ 8 task per fitur, tiap task merujuk `Task M<n>.<k>`. Pass 5: temuan P8 diperbaiki dengan memangkas (untuk task: tulis ulang task fitur yang melanggar bila belum ada yang dikerjakan). Perkiraan ±26 panggilan per project 10 fitur (batas 36 tetap).

## G. Skor

Kesiapan Bangun (25) dibagi: **V12–V19 = 15**, **Paritas P1–P10 = 10** (dimensi baru `paritas`). Syarat lulus tambahan: semua validator kritis lulus (sudah berlaku). QUALITY.md menampilkan tabel P1–P10.

## H. Tes (G6)

- Fixture acuan (`ref-prd/` + `tests/fixtures/paritas/salon.brief.json`): Eduwebmu lulus P1, P3, P5, P7, P10, P2 sebagian; BookKapster gagal P1, P2, P3, P8; ngodingpakeai gagal sebagian besar P4–P7, P10.
- Uji mutasi (10 kasus spec §5.4), uji bendera (§5.5), uji generalisasi mock (booking / manajemen tugas tim / toko online kecil di 3 stack).
- AI sungguhan hanya di G6, satu project, dengan `AI_MIN_INTERVAL_SECONDS`.

## I. Risiko

- Heuristik markdown (P2 terutama) tidak sempurna: asal fitur pada PRD tanpa field `asal` hanya ditebak dari kata. Jalur utama PlanKu memakai `asal` terstruktur.
- Prompt makin panjang; bab 13 dipisah agar tetap pendek per panggilan.
