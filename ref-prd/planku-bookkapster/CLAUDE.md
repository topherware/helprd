# CLAUDE.md — BookKapster

Panduan untuk AI coding assistant. Baca berkas ini sebelum mengerjakan apa pun.

## Stack

Standar PlanKu: Next.js + Auth.js + PostgreSQL (Neon) + Drizzle ORM + Tailwind CSS + shadcn/ui

## Struktur Folder

```text
bookkapster/
  app/
    (publik)/
      booking/
      layanan/
    (admin)/
      dashboard/
      layanan/
      kapster/
      booking/
    api/
      booking/
      layanan/
      kapster/
      auth/
      cron/
  components/
    ui/ (shadcn/ui)
    booking/
    admin/
  db/
    schema.ts
    migrations/
    seed.ts
  lib/
    auth.ts
    adapters/
      whatsapp.ts
      rate-limit.ts
    validasi/
  features/
    booking-tanpa-akun.md
    jadwal-anti-dobel.md
    kelola-layanan-harga.md
    kelola-status-booking.md
    jadwal-kapster.md
    pengingat-otomatis-wa.md
    jejak-perubahan.md
    dashboard-pemilik.md
    pencarian-jadwal.md
    riwayat-pelanggan.md
  tests/
    unit/
    e2e/
  CLAUDE.md
  PRD.md
  PROGRESS.md
  .env.example
```

## Konvensi

- Nama tabel, kolom, dan variabel database memakai bahasa Indonesia snake_case sesuai skema di PRD.md, tidak diterjemahkan ke bahasa Inggris
- Kolom uang selalu integer rupiah, tidak pernah float
- Setiap mutasi admin (harga, jadwal, status booking) wajib menulis ke log_audit dan/atau riwayat_status_booking dalam transaksi yang sama dengan mutasi utamanya
- Validasi input di server memakai skema validasi (misal Zod) sebelum query ke database, tidak mengandalkan validasi di frontend saja
- Semua query database lewat Drizzle ORM, tidak ada string SQL mentah
- Pemanggilan layanan pihak ketiga (WhatsApp, rate limit) selalu lewat interface adapter di lib/adapters/, bukan panggilan langsung ke SDK vendor
- Komponen UI memakai shadcn/ui dan Tailwind, konsisten dengan gaya Bersih & Modern aksen biru/indigo
- Setiap API route memeriksa sesi dan peran sebelum mutasi, mengikuti matriks hak akses di PRD.md bab keamanan

## Perintah

- Jalankan: `npm run dev`
- Uji: `npm run test && npm run test:e2e`

## Aturan Checkpoint

AI coding assistant bekerja Bertahap per Fase: pada setiap milestone (M0-M4), AI menyelesaikan seluruh cakupan milestone tersebut (UI, backend, dan integrasinya sesuai lingkup milestone), menjalankan pengujian yang relevan, memperbarui PROGRESS.md dengan status terbaru, lalu berhenti tepat di kalimat CHECKPOINT milestone itu untuk melapor ringkas dan menunggu persetujuan pengguna sebelum lanjut ke milestone berikutnya. AI tidak boleh melompat ke milestone selanjutnya tanpa persetujuan eksplisit, dan tidak boleh mengerjakan sebagian milestone lalu diam-diam melanjutkan ke milestone lain.

Di setiap CHECKPOINT: berhenti, perbarui PROGRESS.md, ringkas hasil, tunggu persetujuan, dan sarankan percakapan baru bila konteks sudah panjang.

## Rujukan di PRD.md

- Design system: bab 9
- Peta rute & layout: bab 10
- Data dummy & copywriting: bab 11 (jangan mengarang data di luar ini)
- Keamanan Wajib MVP: bab 8
- Layanan & variabel lingkungan: bab 12 dan `.env.example`
