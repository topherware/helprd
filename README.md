# PlanKu

Aplikasi web untuk mengubah ide aplikasi/website menjadi **peta fitur** dan **dokumen PRD siap bangun**, lewat 5 langkah:

1. Input ide (halaman awal punya sidebar **Semua Plan**: cari, urutkan, buka, tutup, dan hapus plan milik browser ini)
2. **Bagian B: Desain, Teknologi, & Cara Kerja AI** — gaya desain UI/UX, tech stack (atau mode hanya-UI), cara kerja AI coding assistant, dan catatan tambahan (maks. 2000 karakter)
3. Pertanyaan klarifikasi buatan AI
4. Peta fitur berbentuk kanvas node (bisa digeser, di-zoom, dan diubah status/fasenya)
5. **Workspace** (`public/workspace.php`): PRD, Roadmap, Kanban, dan halaman Fitur dalam satu halaman, dengan Workspace Agent di kanan. Tampilan disimpan di URL hash (`#/prd`, `#/roadmap`, `#/kanban?fitur=12`, `#/fitur/12`, `#/fitur/12/sub/40`).

Workspace dibangun bertahap (U1–U8, lihat `alur-ui-ngodingpakeai.md` dan `docs/rencana-ui.md`). Standar Kualitas v1.2 dibangun di tahap B1–B7 (`standar-kualitas-v1.2-siap-bangun.md`, rancangan di `docs/rencana-v1.2.md`). Keputusan penting dicatat di `docs/keputusan.md`.

Pipeline dokumen (Standar Kualitas v1.2):
1. **PRD** (streaming, 8 panggilan pendek, bisa dilanjutkan bila putus): profil (bendera pemicu, stack terkunci, peran) → bab 1–2 → 3–5 → 6–7 (termasuk kode skema) → 8 (keamanan Wajib MVP + Hardening) → 9–10 (design system, peta rute) → 11 (data dummy & copywriting) → 12–13 (layanan & `.env.example`, milestone & Master Prompt). Mode hanya-UI: 6 panggilan; bab 6, 8, 12 "Tidak berlaku".
2. **Bikin Task**: task bertanda milestone `[M1]` (UI dengan data tiruan) → `[M2]` (backend & data) → `[M3]` (keamanan & uji), maks. 14 per fitur.
3. **Audit** (opsional, di popup Implemen): semua validator deterministik, audit AI singkat, lalu perbaikan bab bertemuan maks. 2 putaran.

Peta 10 fitur butuh sekitar 20 panggilan (pertanyaan, peta, PRD, task, audit, perbaikan). Batas dokumen per project diatur `DOC_MAX_CALLS_PER_PROJECT` (default 36).
Bila peta fitur atau isian Bagian B diubah, PRD lama tidak dihapus; ia ditandai basi dan ditimpa saat dibuat ulang.

ZIP Implemen berisi `PRD.md`, `TASKS.md`, `features/*.md`, `CLAUDE.md`, `PROGRESS.md`, `.env.example`, `db/schema.*` (bila ada database), dan `QUALITY.md` (setelah audit). "Prompt AI agent" memakai Master Prompt bab 13.

Nama aplikasi bisa diganti di `src/config.php` (konstanta `APP_NAME`).

## Standar Kualitas v1.2 (Siap Bangun)

Aturan ada di `src/Quality/standard.json` (bobot 9 dimensi, penalti, syarat lulus, tingkat validator keamanan, daftar tujuh kekurangan) dan `src/Quality/stacks.json` (daftar stack, bahasa skema, dukungan RLS, serverless, vendor default tiap layanan).

- `src/Quality/Brief.php`: sumber kebenaran isian pengguna. `src/Quality/Triggers.php`: 15 bendera (mis. `hasPricedEntity`, `hasGuestFlow`, `usesOtp`, `serverless`) dari Brief + deklarasi AI + kata kunci. Aturan hanya berlaku bila benderanya aktif.
- `src/Quality/Validators/`: V12 DesignToken, V13 PetaRute, V14 DataDummy, V15 SkemaKode, V16 LayananPihakKetiga, V17 BebanKeamanan, V18 UrutanMilestone, V19 BatasTugas (= dimensi Kesiapan Bangun), plus `Dimensi.php` (Struktur, Keterlacakan, KecocokanStack, Kejujuran, Konsistensi, Keterukuran). Keterbacaan belum dinilai otomatis.
- `src/Quality/Security/`: pemicu keamanan dengan tingkat Wajib MVP (maks. 12 SEC) dan Hardening (maks. 8 butir); validator keamanan hanya menilai tingkat MVP.
- Skor: tiap dimensi 100 − 25 per temuan kritis − 10 per mayor − 3 per minor, lalu dibobot. **Lulus** bila skor ≥ 85, tanpa temuan kritis, Keamanan ≥ 80, Kesiapan Bangun ≥ 80. Dimensi yang tidak berlaku (mis. Keamanan di mode hanya-UI) dikeluarkan dan bobotnya dinormalisasi.

Uji dan benchmark (tanpa database dan API):
```
C:\xampp\php\php.exe C:\xampp\htdocs\plankan\tests\quality_v12_test.php
C:\xampp\php\php.exe C:\xampp\htdocs\plankan\tests\security_test.php
C:\xampp\php\php.exe C:\xampp\htdocs\plankan\tools\benchmark.php --tulis
C:\xampp\php\php.exe C:\xampp\htdocs\plankan\tools\evaluate.php benchmark\katering-lama.json --quality
```
`tools/export_fixture.php <project_id> <file.json>` mengekspor project jadi fixture benchmark.

## Kebutuhan

- XAMPP dengan PHP 8.0 atau lebih baru (disarankan 8.1+) dan MySQL/MariaDB
- Ekstensi PHP: `pdo_mysql`, `curl`, `mbstring` (aktif secara default di XAMPP)
- Browser modern (Chrome, Edge, Firefox, Safari versi terbaru)

## Instalasi di XAMPP (Windows)

1. Salin folder project ke `C:\xampp\htdocs\plankan`.
2. Buka **XAMPP Control Panel**, lalu jalankan **Apache** dan **MySQL**.
3. Buat database dengan mengimpor `schema.sql`:
   - Lewat phpMyAdmin: buka `http://localhost/phpmyadmin`, pilih tab **Import**, pilih file `schema.sql`, lalu klik **Go**.
   - Atau lewat terminal:
     ```
     C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\plankan\schema.sql
     ```
   File ini membuat database `planku` beserta semua tabelnya.
4. Jalankan migrasi tambahan (setiap file di `migrations/` hanya dijalankan sekali):
   ```
   C:\xampp\php\php.exe C:\xampp\htdocs\plankan\migrate.php
   ```
5. Salin `.env.example` menjadi `.env` di folder yang sama, lalu sesuaikan isinya (lihat bagian Konfigurasi).
6. Buka `http://localhost/plankan/public/` di browser.

Secara default `USE_MOCK_AI=true`, jadi aplikasi langsung bisa dicoba tanpa API key memakai data contoh.

## Konfigurasi `.env`

| Kunci | Keterangan |
| --- | --- |
| `USE_MOCK_AI` | `true` = data contoh tanpa memanggil AI. `false` = panggil AI sungguhan. |
| `MAX_TOKENS` | Batas token output per panggilan AI (pertanyaan & peta fitur). |
| `AI_EFFORT` | Effort Claude: `low`, `medium`, atau `high` (kosong = bawaan model). Lebih rendah = lebih cepat selesai. |
| `AI_NET_RETRIES` | Percobaan ulang otomatis bila koneksi ke AI putus di tengah jalan (default 2). |
| `AI_STALL_SECONDS` | Koneksi ke AI dianggap macet bila tidak ada data sama sekali selama sekian detik (default 60). |
| `TASK_FEATURES_PER_CALL` | Bikin Task: jumlah fitur maksimal per panggilan AI; fitur dikelompokkan per fase (default 4). |
| `AI_MIN_INTERVAL_SECONDS` | Bikin Task dan audit: jeda minimum antar panggilan AI (default 2). |
| `API_KEY`, `API_MODEL`, `API_BASE_URL` | API key, model (default `claude-opus-5`), dan alamat API Claude. |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Koneksi database. Default XAMPP: user `root` tanpa password. |
| `RATE_LIMIT_PER_HOUR` | Batas panggilan AI per alamat IP per jam (default 30), di luar chat agent. Mode demo tidak dihitung. |
| `AGENT_RATE_LIMIT_PER_HOUR` | Batas pertanyaan ke Workspace Agent per alamat IP per jam (default 40), terpisah dari batas di atas. |
| `AGENT_MAX_TOKENS` | Panjang maksimal satu jawaban agent dalam token (default 2000). |
| `DOC_MAX_CALLS_PER_PROJECT` | Batas panggilan AI untuk dokumen (PRD, audit, perbaikan) per project (default 36). |

## Memakai AI sungguhan

1. Isi `API_KEY` (dari https://console.anthropic.com) di `.env`.
2. Uji koneksi:
   ```
   C:\xampp\php\php.exe C:\xampp\htdocs\plankan\test_api.php
   ```
   atau buka `http://localhost/plankan/test_api.php` (hanya bisa dari komputer ini).
3. Kalau berhasil, ubah `USE_MOCK_AI=false`.

## Struktur folder

```
public/     Halaman (index.php, step2-4.php, workspace.php), aset CSS/JS, vendor/ (marked, DOMPurify)
src/        Logika backend: Database, AiClient, MockData, Prompts, Validator, RateLimiter, Documents, helpers, config
src/Quality Standar Kualitas v1.2: standard.json, stacks.json, Brief, Triggers, Pipeline, Report, Validators/, Security/
tests/      Pengujian validator (quality_v12_test.php, security_test.php)
tools/      CLI: evaluate.php, benchmark.php, export_fixture.php
benchmark/  Fixture benchmark (kasus.json, katering-lama.json, mock-*.json, ai-*.json)
migrations/ Migrasi database aditif (jalankan dengan migrate.php)
docs/       Rencana UI & v1.2, hasil benchmark, dan catatan keputusan (keputusan.md)
api/        Endpoint JSON yang dipanggil JavaScript
referensi/  Gambar acuan tampilan
logs/       Log error (app.log, php-error.log)
schema.sql  Skema database
```

## Catatan keamanan

- Tidak ada login. Setiap project diidentifikasi dengan token acak di cookie (berlaku 30 hari). Membuat ide baru menggantikan project di cookie.
- `.htaccess` di root memblokir akses browser ke `.env`, `src/`, `logs/`, `tools/`, `benchmark/`, dan `schema.sql`. Pastikan Apache mengizinkan `.htaccess` (`AllowOverride All`, default di XAMPP).
- Semua query memakai prepared statement, semua request API memakai token CSRF.

## Masalah umum

- **"Database tidak bisa diakses"**: pastikan MySQL di XAMPP jalan dan `schema.sql` sudah diimpor.
- **"Layanan AI sedang bermasalah"**: lihat detailnya di `logs/app.log`, lalu jalankan `test_api.php`.
- **Hasil AI terpotong**: naikkan `MAX_TOKENS` (pertanyaan, peta fitur, task) atau `PRD_MAX_TOKENS` di `src/Documents.php`.
- **Sudah punya database dari versi lama**: impor ulang `schema.sql` (aman, hanya menambah tabel yang belum ada: `documents`, `quality_reports`).
- **Biaya**: satu alur lengkap = sekitar 2 + 8 (PRD) + jumlah fitur/4 (task) + 1–7 (audit) panggilan AI. Pemakaian token tercatat di tabel `api_logs`.
- **Audit menolak PRD lama**: PRD buatan versi sebelum v1.2 tetap bisa dibuka dan diunduh, tetapi audit v1.2 butuh "Buat ulang PRD".
