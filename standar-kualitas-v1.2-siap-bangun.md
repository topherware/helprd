# Standar Kualitas PlanKu v1.2: Siap Bangun

Memperluas `spec/standar-kualitas-v1.md` dan `spec/standar-kualitas-v1.1-keamanan.md`. Jika ada bentrok, spec ini menang untuk topik yang dibahas di sini (terutama bagian keamanan, yang disederhanakan).
Referensi tampilan input baru: `spec/ref-input/01-bagian-b-desain-teknologi-cara-kerja-ai.png`.

## 0. Latar belakang dan tujuan
Hasil audit PRD katering buatan PlanKu menemukan 7 kekurangan. Semuanya harus diperbaiki **secara umum**, berlaku untuk PRD apa pun (bukan hanya katering), dan dijaga validator otomatis.

| # | Kekurangan | Perbaikan umum |
|---|---|---|
| 1 | Tidak ada design system konkret | Bab **Design System & UI/UX** berisi token nyata (warna, font, ukuran ketuk, gaya komponen) |
| 2 | Tidak ada peta routing dan layout per peran | Bab **Peta Rute & Layout per Peran** |
| 3 | Data dummy dan copywriting dikosongkan | Bab **Data Dummy & Copywriting** berisi dataset dan teks baku yang dirujuk tugas |
| 4 | Skema database naratif, tanpa snapshot harga, log audit, dan pesanan tamu | Skema **kode siap pakai** + tiga aturan pemicu (snapshot, audit, tamu) |
| 5 | Layanan pihak ketiga dan `.env.example` menggantung | Bab **Layanan Pihak Ketiga & Variabel Lingkungan** dengan keputusan vendor/mock + `.env.example` |
| 6 | Keamanan berlebihan untuk MVP (39 aturan) | Keamanan dibagi **Wajib MVP (maks 12)** dan **Hardening setelah MVP**; batas angka mikro dilarang |
| 7 | Tahap UI, DB, RLS, uji tercampur, tanpa checkpoint | Bab **Milestone & Master Prompt**, tiap tugas diberi M1/M2/M3, ada checkpoint, plus `CLAUDE.md` dan `PROGRESS.md` |

Tambahan: input baru di langkah **Teknologi** (gaya desain, tech stack, mode hanya-UI, cara kerja AI, catatan tambahan) dan dipakai sebagai sumber kebenaran bersama Brief.

## 1. Aturan kerja
- Satu tahap (B1–B7) per giliran, lalu berhenti dan laporkan. Catat keputusan ke `docs/keputusan.md`.
- Bangun hanya setelah v1 selesai dan lolos tes. Jika v1.1 sudah dibangun, ubah sesuai bagian 8; jika belum, bangun v1.1 dengan aturan keamanan dari spec ini.
- Jangan membuka atau mengubah alur UI workspace (spec `alur-ui-ngodingpakeai.md`); cukup pastikan hasil generator tetap berupa potongan teks yang bisa di-stream, dan ZIP serta "Prompt AI agent" memakai berkas baru dari spec ini (bagian 9).
- `USE_MOCK_AI=true` harus menghasilkan PRD yang lolos semua validator.
- Jangan menuliskan API key asli di berkas mana pun, termasuk `.env.example`.

## 2. Input baru di langkah Teknologi (lihat gambar referensi)
Halaman Teknologi menjadi "Bagian B: Desain, Teknologi, & Cara Kerja AI" dengan 4 kartu bernomor. Tiru **struktur dan perilaku**, bukan nama merek. Pilihan = kartu radio dengan centang saat aktif dan lencana "Default"/"Direkomendasikan".

**2.1 Gaya Desain UI/UX** (satu pilihan)
- Bebas (Auto-Pilot) [Default]: AI memilih gaya sesuai jenis produk, tapi hasil akhirnya tetap harus berupa token konkret di PRD.
- Bersih & Modern: shadcn/ui, aksen biru/indigo, responsif
- Dark Mode Premium: tema gelap, kontras tinggi, glassmorphism halus
- Minimalis & Tipografi: banyak ruang kosong, border tipis
- Korporat & Profesional: warna solid, tata letak formal
- Tulis Sendiri: teks bebas (warna, font, referensi situs)

**2.2 Tech Stack & Arsitektur** (satu pilihan + saklar)
- Saklar **Hanya UI / frontend (tanpa backend & database)**: bila aktif, PRD memakai profil "UI-only" (bagian 6.2).
- Bebas (Auto-Pilot) [Default]: AI memilih, tapi wajib dikunci menjadi stack konkret di bab Tech Stack dan Keputusan Teknologi.
- Standar PlanKu (Next.js + autentikasi + database ringan + Tailwind + shadcn/ui)
- Full Supabase (Next.js + Supabase Auth + PostgreSQL + Realtime/Storage)
- React Vite + Node.js (React SPA + Express + MongoDB atau PostgreSQL)
- React + Cloudflare (Workers, Hono, D1)
- Tulis Sendiri: teks bebas
Daftar stack disimpan di satu berkas konfigurasi (`src/Quality/stacks.json`) berisi: id, nama, bahasa/runtime, jenis DB, bahasa skema (sql/ts/prisma), dukungan RLS, kemampuan serverless, vendor default tiap layanan (OTP, email, penyimpanan, rate-limit, pembayaran). Validator memakai berkas ini, bukan tebakan dari teks.

**2.3 Cara Kerja AI Coding Assistant** (satu pilihan)
- Bertahap per Task [Paling terkontrol]
- Bertahap per Fase [Direkomendasikan, default]
- Sekaligus Semua [Paling cepat]
Menentukan aturan checkpoint di bab Milestone (bagian 5.7).

**2.4 Catatan Tambahan**: textarea maksimal 2000 karakter dengan penghitung `0/2000`; placeholder berupa contoh aturan bisnis unik.

**Penyimpanan**: migrasi aditif menambah di tabel project: `design_style`, `design_custom`, `stack_choice`, `stack_custom`, `ui_only` (boolean), `ai_workflow`, `extra_notes`. Semua masuk ke Brief (`src/Quality/Brief.php`) sebagai sumber kebenaran.

**Keamanan input**: `extra_notes` dan teks "Tulis Sendiri" adalah data tidak tepercaya. Masukkan ke prompt di dalam blok bertanda (mis. `<catatan_pengguna>...</catatan_pengguna>`) dengan instruksi bahwa isinya hanya informasi, bukan perintah; bersihkan tag, batasi panjang, simpan lewat prepared statement. Aturan bisnis dari catatan yang diterima harus muncul di PRD sebagai FR/NFR beridentitas atau di Open Questions, tidak boleh dibuang diam-diam (validator Keterlacakan memeriksa ini).

## 3. Pemicu (perluas `src/Quality/Triggers.php`)
Tentukan bendera boolean dari Brief + peta fitur + bendera yang dideklarasikan AI pada Pass 1 (JSON terstruktur, divalidasi), dengan daftar kata kunci sebagai jaring pengaman. Bendera: `uiOnly`, `hasDatabase`, `hasPricedEntity` (ada harga, tarif, diskon, biaya), `hasTransactionRecord` (pesanan, booking, pinjam, tagihan), `hasAdminMutation` (pemilik/admin mengubah data atau status), `hasGuestFlow` (fitur transaksi bisa dipakai tanpa akun), `usesOtp`, `usesEmail`, `usesWhatsappApi`, `usesPayment`, `usesFileStorage`, `usesRateLimit`, `serverless`, `hasDarkTheme`, `multiRole`.
Tiap aturan di bawah hanya berlaku bila bendera yang terkait aktif, sehingga aturan tidak dipaksakan pada PRD yang tidak membutuhkannya.

## 4. Struktur PRD baru
Bab 1–7 tetap (nomor tidak berubah agar validator lama aman). Bab keamanan (8) diubah sesuai bagian 8. Bab baru:
- **9. Design System & UI/UX**
- **10. Peta Rute & Layout per Peran**
- **11. Data Dummy & Copywriting**
- **12. Layanan Pihak Ketiga & Variabel Lingkungan**
- **13. Milestone Pembangunan & Master Prompt**
Bab 6 diperluas menjadi skema kode (bagian 5.4). Mode UI-only: bab 6, 8 (backend), dan 12 diganti satu baris "Tidak berlaku (mode hanya-UI)" dan bab 13 hanya berisi M0 dan M1.
Berkas tambahan di ZIP: `CLAUDE.md`, `PROGRESS.md`, `.env.example`, `db/schema.*` (bila ada database) selain `PRD.md`, `TASKS.md`, `features/*.md`.

## 5. Aturan isi per bab

### 5.1 Bab 9: Design System & UI/UX (perbaikan #1)
Wajib ada (disesuaikan dengan gaya desain terpilih; "Bebas" harus diputuskan jadi nilai konkret dan diberi alasan satu kalimat):
- **Token warna**: primary, secondary, accent, background, surface, text, text-muted, border, success, warning, danger, masing-masing nilai HEX atau HSL valid. Bila `hasDarkTheme` atau gaya Dark Mode: set kedua tema.
- **Tipografi**: pasangan font (judul + isi, nama font nyata), skala ukuran (mis. 12/14/16/20/24/32), tinggi baris, dan sumber font.
- **Ukuran dan jarak**: area ketuk minimal **44×44 px**, skala spasi, radius sudut, bayangan, lebar maksimum konten, titik putus responsif.
- **Gaya komponen**: tombol (primer/sekunder/bahaya + status hover/fokus/nonaktif), kartu, input form, badge status, tabel/daftar, dialog, toast.
- **Nuansa visual**: 3–5 kata sifat dan 2 hal yang dihindari.
- **Keadaan antarmuka**: pola loading, kosong, error untuk halaman data.
- **Aksesibilitas dasar**: fokus terlihat, label untuk pembaca layar, kontras teks minimal 4.5:1.

### 5.2 Bab 10: Peta Rute & Layout per Peran (perbaikan #2)
- Tabel rute: `path | halaman | peran yang boleh akses | layout | fitur sumber (FR/ID fitur)`. Path final dan unik (mis. `/`, `/paket`, `/akun/riwayat`, `/admin/menu`).
- Daftar peran harus sama dengan peran di bab 2 dan 6.
- Pola navigasi per peran: publik (mis. header + footer), pengguna terdaftar (mis. bottom navigation di HP), pengelola (mis. sidebar). Sebutkan komponen navigasi dan item menu per peran.
- Rute khusus: halaman 404, tidak punya akses (403), kesalahan umum, dan alur masuk/keluar bila ada akun.
- Aturan penjaga akses: rute mana yang butuh login dan peran apa (rujuk ke aturan keamanan, bukan diulang panjang).

### 5.3 Bab 11: Data Dummy & Copywriting (perbaikan #3)
- **Dataset baku** untuk tiap entitas yang dipakai tampilan: ID dataset (`DUMMY-<ENTITAS>`), minimal 3 baris contoh realistis untuk entitas berdaftar (1 baris untuk entitas tunggal), memakai satuan dan format yang konsisten (mis. `Rp15.000`, tanggal `Sen, 12 Jan 2026`, zona waktu bila relevan).
- **Template teks** dengan placeholder bernama (mis. pesan WhatsApp, email, notifikasi), disertai contoh hasil terisi.
- **Panduan copywriting**: nada bahasa, sapaan, daftar istilah baku, dan **microcopy** untuk keadaan kosong, error, sukses, konfirmasi.
- Aturan keterkaitan: setiap tugas M1 yang menyebut "data tiruan" harus menyebut ID dataset yang ada di bab ini (mis. "pakai DUMMY-MENU"). Dilarang "data tiruan" tanpa rujukan.
- Dataset harus konsisten dengan skema (nama kolom dan tipe sesuai bab 6).

### 5.4 Bab 6: Skema database siap pakai (perbaikan #4)
Hanya bila `hasDatabase`. Sertakan, selain deskripsi singkat dan diagram ER yang sudah ada:
- **Blok kode skema** dalam bahasa sesuai stack terpilih (`stacks.json`): SQL migrasi untuk PostgreSQL/Supabase/MySQL/SQLite, atau skema TypeScript/Prisma bila stack memakainya. Juga muncul sebagai berkas `db/schema.*` di ZIP.
- Himpunan tabel dan kolom di kode harus **sama** dengan deskripsi dan ER (tidak boleh ada tabel hanya di salah satunya).
- Uang disimpan sebagai bilangan bulat (satuan terkecil) atau numeric, bukan float.
- Kebijakan akses di tingkat baris (mis. RLS) bila stack mendukung dan ada data milik pengguna; cukup untuk tabel yang berisi data pribadi atau transaksi.
- Tiga aturan pemicu:
  - **Snapshot** (`hasPricedEntity` dan `hasTransactionRecord`): tabel transaksi menyimpan salinan nilai pada saat transaksi (mis. `harga_satuan_snapshot`, `nama_item_snapshot`) agar perubahan harga kemudian tidak mengubah riwayat.
  - **Log audit** (`hasAdminMutation`): tabel `log_audit` (siapa, aksi, entitas, id entitas, nilai sebelum/sesudah ringkas, waktu) dan daftar aksi yang wajib dicatat.
  - **Transaksi tamu** (`hasGuestFlow`): entitas transaksi mengizinkan pengguna tanpa akun (`user_id` boleh kosong + kolom nama dan kontak tamu), dan PRD menjelaskan bagaimana tamu dihitung untuk kebutuhan operasional (mis. jumlah porsi, slot).

### 5.5 Bab 12: Layanan Pihak Ketiga & Variabel Lingkungan (perbaikan #5)
- Tabel: `kebutuhan | vendor terpilih atau "adapter tiruan (mock)" | alasan | biaya | variabel lingkungan | mode MVP`. Vendor default diambil dari `stacks.json`; **dilarang** menulis kebutuhan seperti OTP, email, SMS, pembayaran, penyimpanan file, atau pembatasan laju tanpa vendor konkret atau keputusan mock.
- Bila serverless dan ada pembatasan laju: sebutkan mekanisme yang bekerja di lingkungan itu (penyimpanan terpusat, bukan memori proses) atau tulis eksplisit bahwa MVP memakai adapter tiruan dengan batasannya.
- **Blok `.env.example`**: memuat semua variabel yang disebut di PRD, memakai nilai penampung (`isi_dengan_...`), tanpa rahasia asli, dikelompokkan dan diberi komentar. Juga jadi berkas `.env.example` di ZIP.
- Setiap layanan punya antarmuka adapter dengan implementasi mock, supaya M1/M2 tidak terblokir akun vendor.

### 5.6 Bab 8: Keamanan yang realistis untuk MVP (perbaikan #6; menimpa v1.1)
- Dua tingkat: **Wajib MVP** (maksimal 12 aturan, ID `SEC-01..`) dan **Hardening setelah MVP** (maksimal 8 butir, satu baris tiap butir, tanpa ID per fitur).
- Aturan MVP harus: dapat dikerjakan dan diuji dalam M2/M3, terkait ke fitur atau lapisan umum, bertindakan konkret (mis. validasi input di server, kata sandi di-hash, rahasia di variabel lingkungan, kontrol akses per peran, pembatasan laju pada login/OTP, unggahan dibatasi tipe dan ukuran, HTTPS, catatan audit untuk aksi admin).
- **Dilarang di tingkat MVP**: ambang waktu mikro (mis. "selisih respons < 200 ms"), ritual operasional berkala (uji pemulihan bencana per kuartal, rotasi kunci terjadwal), sertifikasi, dan jumlah/angka yang tidak bisa diuji otomatis. Hal itu boleh hanya di Hardening.
- Bagian "Keamanan" per fitur: maksimal 3 butir, merujuk ID `SEC-xx`, tidak membuat aturan baru.
- Pemetaan OWASP dan catatan "perlu ditinjau ahli hukum" (UU PDP) tetap ada, singkat.
- Skor Keamanan dihitung hanya dari tingkat MVP.

### 5.7 Bab 13 dan tugas: Milestone & Master Prompt (perbaikan #7)
Milestone baku:
- **M0 Persiapan**: struktur proyek, dependensi, konfigurasi, `CLAUDE.md`, `PROGRESS.md`, variabel lingkungan.
- **M1 Prototipe UI (data tiruan)**: semua halaman dan komponen memakai dataset bab 11, belum ada backend; sesuai design system dan peta rute.
- **M2 Backend & Data**: skema/migrasi, API, integrasi layanan (atau adapter tiruan), mengganti data tiruan dengan data nyata.
- **M3 Keamanan & Uji**: aturan SEC-MVP, kebijakan akses, uji unit/alur utama.
- **M4 Rilis**: konfigurasi produksi, daftar periksa rilis.
Setiap milestone punya: tujuan, **Selesai bila** terukur, daftar fitur/tugas yang tercakup, dan **CHECKPOINT** (berhenti, perbarui `PROGRESS.md`, ringkas, tunggu persetujuan, sarankan memulai percakapan baru bila konteks panjang).
Cara Kerja AI memengaruhi checkpoint: per Task = checkpoint setelah tiap tugas (tulis di `CLAUDE.md`); per Fase = checkpoint di akhir tiap milestone; Sekaligus = satu checkpoint di akhir M1 dan akhir proyek, plus aturan keselamatan "berhenti dan laporkan jika ada kegagalan uji".
Aturan tugas pada `features/*.md` dan `TASKS.md`:
- Tiap tugas berawalan `[M1]`, `[M2]`, atau `[M3]`, kalimat perintah, urut M1 → M2 → M3 dalam satu fitur.
- Maksimal **14 tugas per fitur** dan **12 × jumlah fitur** untuk keseluruhan proyek; tugas besar dipecah, tugas sepele digabung.
- Tugas M1 tidak boleh menyebut tabel, API, RLS, atau layanan; tugas M2 boleh; uji ada di M3.
- **Master Prompt** (blok kode siap tempel): ringkasan proyek, stack, daftar berkas wajib dibaca (`CLAUDE.md`, `PRD.md`, `features/`, `PROGRESS.md`), aturan "kerjakan satu milestone/tugas sesuai Cara Kerja AI, jalankan uji, perbarui `PROGRESS.md`, berhenti di checkpoint, tanya bila ambigu, jangan mengarang data di luar bab 11".
- **`CLAUDE.md`** (berkas ZIP): stack, struktur folder, konvensi, perintah jalan/uji, aturan checkpoint, tautan bab desain/rute/dummy/keamanan. **`PROGRESS.md`**: tabel milestone dengan status dan catatan keputusan.

## 6. Profil khusus
**6.1 Gaya "Bebas (Auto-Pilot)"**: AI wajib memutuskan dan mencatat di bab 9/7 apa yang dipilih beserta alasannya; kata seperti "terserah", "sesuai selera", atau token kosong gagal validator.
**6.2 Mode UI-only**: tidak ada bab skema, layanan, atau keamanan backend; bab 11 tetap ada (dummy data); milestone hanya M0 dan M1; validator yang tidak relevan dilewati (tidak dihitung merugikan skor).

## 7. Validator baru (`src/Quality/Validators/`, deterministik, tanpa AI)
Tingkat keparahan: **K** = kritis (menggagalkan lulus), **M** = mayor, **m** = minor. Semua menerima `Brief`, `Triggers`, dan PRD; mengembalikan temuan terstruktur (id, bab, pesan, saran perbaikan).
| ID | Nama | Memeriksa | Sev |
|---|---|---|---|
| V12 | DesignToken | token wajib ada dengan HEX/HSL valid; pasangan font bernama; ukuran ketuk ≥ 44px; kontras teks/latar utama ≥ 4.5:1 (hitung WCAG); gaya "Bebas" sudah dikunci; tema gelap ganda bila diminta | M |
| V13 | PetaRute | setiap fitur punya ≥1 rute; path unik dan valid; peran di tabel ⊆ peran yang didefinisikan; ada rute 404/403; pola navigasi per peran ada | K bila fitur tanpa rute, selain itu M |
| V14 | DataDummy | tiap entitas yang dipakai punya dataset (≥3 baris bila berdaftar); kolom dataset cocok skema; semua placeholder template terisi di contoh; tugas "data tiruan" merujuk ID dataset yang ada; format uang konsisten | M |
| V15 | SkemaKode | blok kode ada bila `hasDatabase`; himpunan tabel kode = narasi = ER; uang bukan float; snapshot bila `hasPricedEntity`+`hasTransactionRecord`; `log_audit` bila `hasAdminMutation`; `user_id` nullable + kontak tamu bila `hasGuestFlow`; kebijakan akses bila stack mendukung | K |
| V16 | LayananPihakKetiga | setiap kebutuhan layanan terdeteksi (OTP, email, WA, pembayaran, penyimpanan, rate-limit) ada di tabel dengan vendor/mock; vendor konsisten dengan `stacks.json`; rate-limit serverless tidak memakai memori proses; semua variabel yang disebut ada di `.env.example`; tidak ada rahasia asli (pola kunci) | K untuk layanan tanpa keputusan atau variabel hilang |
| V17 | BebanKeamanan | aturan MVP ≤ 12; Hardening ≤ 8; pola terlarang di tingkat MVP (ambang `\d+\s*ms`, kata kuartal/bulanan/DR/sertifikasi) tidak ada; bagian per fitur ≤ 3 butir dan hanya merujuk ID yang ada | M |
| V18 | UrutanMilestone | semua tugas berawalan M1/M2/M3 dan berurutan per fitur; tugas M1 tidak menyebut kata terlarang (tabel, API, RLS, migrasi, vendor); tiap milestone punya Selesai bila dan CHECKPOINT; aturan checkpoint sesuai Cara Kerja AI; `CLAUDE.md` dan `PROGRESS.md` ada | K bila tanpa checkpoint, selain itu M |
| V19 | BatasTugas | ≤ 14 tugas per fitur dan ≤ 12 × jumlah fitur total; tidak ada tugas duplikat; Master Prompt ada dan merujuk berkas yang benar | m |

Validator lama yang disesuaikan: **StrukturLengkap** (bab 1–13 sesuai profil), **Keterlacakan** (catatan tambahan, gaya, dan stack pengguna harus muncul), **KecocokanStack** (pakai `stacks.json`), **Kejujuran** (biaya layanan di bab 12 harus konsisten dengan klaim "gratis/murah"), dan validator keamanan v1.1 (hanya menilai tingkat MVP).

## 8. Perubahan pada v1.1
- Ganti kewajiban keamanan per fitur dan banyak aturan menjadi dua tingkat (5.6). Validator Sec yang bersifat kritis tetap hanya untuk aturan MVP.
- Hapus ambang numerik mikro dari prompt dan contoh mock.
- Ambang skor keamanan dihitung dari aturan MVP saja.

## 9. Pipeline dan integrasi
- **Pass 1** menjadi 5 panggilan (PRD bab 1–5; bab 6–8; bab 9–11; bab 12–13; daftar bendera Triggers dalam JSON). **Pass 2** per fitur menghasilkan tugas bertanda milestone. **Pass 3** semua validator. **Pass 4** audit AI singkat atas hal yang tidak bisa deterministik (kelayakan pilihan vendor, kewajaran dataset). **Pass 5** perbaikan maksimal 2 putaran, memperbaiki hanya bab yang temuannya ditandai.
- Naikkan `DOC_MAX_CALLS_PER_PROJECT` sesuai kebutuhan (perkiraan 36) dan patuhi `AI_MIN_INTERVAL_SECONDS`; hentikan dengan pesan jelas bila batas tercapai.
- Pemanggil tetap menghasilkan potongan teks (bisa di-stream) agar cocok dengan alur UI workspace.
- ZIP (spec UI bagian 8) memuat: `PRD.md`, `TASKS.md`, `CLAUDE.md`, `PROGRESS.md`, `.env.example`, `db/schema.*`, `features/*.md`. "Prompt AI agent" memakai Master Prompt bab 13.
- **Skor** (bobot baru, jumlah 100): Konsistensi 15, Keterukuran 12, Kejujuran 12, Keamanan 10, Stack 8, Keterlacakan 8, Struktur 5, Keterbacaan 5, **Kesiapan Bangun 25** (V12–V19). **Lulus** bila skor ≥ 85, tanpa temuan kritis, Keamanan ≥ 80, Kesiapan Bangun ≥ 80. Dimensi yang tidak berlaku pada profil UI-only dikeluarkan dan bobotnya dinormalisasi.
- `Quality::evaluate` tetap dipakai bersama `benchmark/` dan `tools/evaluate.php`; laporan menampilkan tujuh kekurangan di atas sebagai daftar periksa lulus/gagal.

## 10. Tes
1. **Fixture negatif**: PRD katering lama (hasil audit) harus ditandai gagal pada V12–V18 untuk ketujuh kekurangan, dengan temuan yang menunjuk bab yang benar.
2. **Fixture positif**: PRD mode mock untuk 3 kasus (katering, jasa kebersihan, peminjaman alat lab) harus lulus semua validator pada stack berbeda (Full Supabase, React Vite + Node.js, mode UI-only).
3. **Uji mutasi** (satu per tes): hapus kolom snapshot → V15 gagal; hapus `log_audit` saat ada aksi admin → V15 gagal; ubah `user_id` jadi wajib saat ada alur tamu → V15 gagal; hapus satu rute fitur → V13 gagal; ganti warna jadi nilai tidak valid → V12 gagal; kontras rendah → V12 gagal; hapus vendor OTP → V16 gagal; hapus satu variabel dari `.env.example` → V16 gagal; tambah aturan "respons < 200 ms" ke MVP → V17 gagal; tugas M1 menyebut tabel → V18 gagal; hapus CHECKPOINT → V18 gagal; 20 tugas pada satu fitur → V19 gagal.
4. **Uji bendera**: PRD tanpa harga tidak dituntut snapshot; tanpa aksi admin tidak dituntut `log_audit`; tanpa alur tamu tidak dituntut kolom tamu; UI-only tidak dituntut skema atau layanan.
5. **Uji input baru**: catatan tambahan berisi instruksi jahat tidak memengaruhi prompt (diperlakukan sebagai data); batas 2000 karakter ditegakkan di sisi server; pilihan "Tulis Sendiri" kosong ditolak.
6. **Uji kontras**: fungsi hitung rasio kontras diuji dengan pasangan warna yang diketahui.

## 11. Di luar cakupan
Mengubah alur UI workspace, menambah stack di luar daftar `stacks.json` tanpa persetujuan, mengintegrasikan vendor sungguhan ke PlanKu, dan menjalankan PRD hasil generate (PlanKu hanya menghasilkan dokumen).

## 12. Tahap pengerjaan
- **B1 Audit dan rancangan** (baca saja): petakan kode v1/v1.1 yang sudah ada, tulis `docs/rencana-v1.2.md` berisi apa yang diubah, skema migrasi, struktur `stacks.json`, dan rencana panggilan AI per bab. Berhenti dan tunggu persetujuan.
- **B2 Input baru**: halaman Teknologi Bagian B, migrasi kolom, perluas `Brief`, `stacks.json`, penyaringan catatan tambahan.
- **B3 Pemicu dan prompt**: perluas `Triggers`, ubah `Prompts.php` dan data mock untuk bab 8–13 dan skema kode, `.env.example`, `CLAUDE.md`, `PROGRESS.md`, tugas bertanda milestone.
- **B4 Validator V12–V16**: desain, rute, dummy, skema, layanan, beserta tes mutasi.
- **B5 Validator V17–V19 dan penyederhanaan keamanan** (perubahan v1.1), beserta tes.
- **B6 Pipeline dan skor**: pass baru, bobot baru, Kesiapan Bangun, laporan kualitas dengan daftar periksa tujuh kekurangan, ZIP baru.
- **B7 Benchmark**: jalankan fixture negatif dan tiga kasus positif, tulis hasil ke `docs/hasil-benchmark-v1.2.md`, dan laporkan angka sebelum/sesudah.
