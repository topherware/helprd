# Rencana UI Workspace — Audit U0

Audit kode PlanKan terhadap `alur-ui-ngodingpakeai.md` (13 gambar di `ref-ui/`). Tahap ini hanya membaca kode; tidak ada kode yang diubah.

Catatan lokasi: spec menyebut `spec/alur-ui-ngodingpakeai.md` dan `spec/ref-ui/`, tetapi file ada di root project (`alur-ui-ngodingpakeai.md`, `ref-ui/`). Dokumen ini memakai lokasi sebenarnya.

---

## 1. Ringkasan

- Alur langkah 1–4 dan kanvas peta fitur sudah ada dan bisa dipakai ulang hampir seluruhnya.
- **Temuan terbesar:** PRD sekarang **tidak** dihasilkan sebagai markdown. AI mengembalikan JSON terstruktur, lalu `Documents::renderPrd()` merakit markdown dengan template tetap. Struktur JSON ini dipakai oleh validator Standar Kualitas v1.1. Spec meminta "prompt PRD dipakai apa adanya, hanya pemanggilannya yang menjadi streaming", jadi streaming harus dirancang di atas JSON, bukan markdown mentah (lihat §5, K1).
- Langkah 5 sekarang menjalankan pipeline dokumen v1.1: PRD 1–7, PRD §8 Keamanan, spesifikasi per fitur, lalu audit dan QUALITY.md. Spec meminta langkah 5 diarahkan ke workspace dan "Bikin Task" memakai **satu** panggilan AI. Keduanya harus diselaraskan supaya hasil v1.1 tidak hilang (K3).
- Beberapa kolom yang diminta spec sudah ada dengan nama Indonesia (`fase`, `ikon`, `urutan`, `status`). Migrasi cukup menambah kolom baru tanpa mengganti nama kolom lama.
- Streaming di XAMPP bisa dijalankan, tapi ada 5 pengaturan yang wajib ditangani (§7).

---

## 2. Peta kode sekarang

| Langkah | Halaman | API | Inti logika |
|---|---|---|---|
| 1 Ide | `public/index.php` | — (form POST) | buat `projects`, cookie `planku_token` |
| 2 Teknologi | `public/step2.php` | — (form POST) | `tech_mode`, `tech_stack_pilihan` |
| 3 Pertanyaan | `public/step3.php` + `assets/js/step3.js` | `generate_questions.php`, `save_answers.php` | `Prompts::questions`, `Validator::aiQuestions` |
| 4 Peta Fitur | `public/step4.php` + `assets/js/step4.js` | `generate_features.php`, `update_feature.php` | `Prompts::features`, kanvas pan/zoom |
| 5 Dokumen | `public/step5.php` + `assets/js/step5.js` | `generate_document.php`, `download.php` | `Documents`, `Quality/*`, `MockData` |

Komponen pendukung:

- `src/AiClient.php` — **khusus Claude** (Gemini sudah dihapus atas permintaan pemilik). Request ke Claude sudah memakai streaming SSE secara internal (`self::stream()` + callback per event), lalu hasilnya dikumpulkan menjadi JSON utuh. Spec menyebut `streamGenerateContent` Gemini; bagian itu tidak relevan lagi.
- `src/helpers.php` — `api_request()` (POST + CSRF header + cek project lewat cookie + `session_write_close()`), `ai_generate()` (mock, RateLimiter, `api_logs`), `page_header()`.
- `src/Documents.php` — `hash()` peta fitur, `current()` (sekaligus **menghapus** dokumen basi), `renderPrd()`, `renderFeature()`, `zip()`, `callsLeft()`.
- `src/Quality/` — `standard.json` v1.1, `Report.php`, `Security/Triggers.php`, `Security/Validators.php`.
- `tests/security_test.php` — 28 uji validator.

---

## 3. Struktur data fitur & sub-fitur sekarang

### Tabel

| Tabel | Kolom |
|---|---|
| `features` | `id`, `project_id`, `nama`, `fase` (1–4), `ikon`, `status` ENUM(direncanakan, dikerjakan, selesai), `urutan` |
| `sub_features` | `id`, `feature_id`, `nama`, `urutan` |
| `documents` | `project_id`, `feature_id` (0 = PRD), `peta_hash`, `data` JSON |

`features.status` sekarang diisi **manual** lewat editor di langkah 4.

### Isi JSON di `documents`

- **PRD** (`feature_id = 0`): `overview`, `requirements`, `fitur[i].deskripsi`, `fitur[i].sub_fitur[j]` (deskripsi tiap sub-fitur), `user_flow`, `arsitektur` (termasuk `sequence_mermaid`), `database` (`tabel`, `relasi` → ER mermaid), `tech_stack`, dan `keamanan` (§8). Field `fitur` dicocokkan ke peta fitur **berdasarkan urutan**.
- **Spesifikasi fitur** (`feature_id = id fitur`): `ringkasan`, `tujuan`, `selesai_bila[]`, `sub_fitur[j]` berisi `{ringkasan, tujuan, selesai_bila[]}` (juga berdasarkan urutan), `task[]`, `keamanan`, dan `task_keamanan[]`.

### Pemetaan ke kebutuhan spec

| Kebutuhan spec | Sumber yang sudah ada | Kurang |
|---|---|---|
| Fitur: nama, ikon, fase, urutan | `features.nama/ikon/fase/urutan` | — |
| Fitur: deskripsi | `documents[PRD].fitur[i].deskripsi` | belum tersimpan di tabel |
| Fitur: Tujuan, Selesai bila | `documents[fitur].tujuan/selesai_bila` | hanya ada setelah langkah 5 |
| Sub-fitur: deskripsi, Tujuan, Selesai bila | PRD `sub_fitur[j]`, spesifikasi `sub_fitur[j]` | belum tersimpan di tabel, dicocokkan lewat urutan |
| Status fitur | `features.status` (manual) | spec: dihitung dari task |
| Task | `documents[fitur].task[]` + `task_keamanan[]` (teks saja) | tabel `tasks`, prioritas, status, fase, sub-fitur |
| PRD markdown | dirender ulang dari JSON setiap kali | tabel `prd_versions` |

Seperti diminta spec, peta fitur langkah 4 (fitur + sub-fitur) dipertahankan sebagai data awal Roadmap.

---

## 4. Yang bisa dipakai ulang

- **Kanvas** (`step4.js`, ±300 baris): pan, zoom roda/tombol, `fit()`, kurva bezier SVG, titik ujung garis, bingkai grup per fase, ikon SVG, editor fitur, layout dua tahap (render lalu ukur). Untuk Roadmap tinggal menambah kolom ke-4 (TASKS), progres task, "Lihat semua (n)", dan aksi klik (§5 spec). Bingkai per fase bisa dipertahankan atau diganti badge FASE di node seperti referensi.
- **Tema & komponen CSS** (`app.css`): token warna navy/oranye, tombol, chip, panel, badge fase, titik status, spinner, `[hidden]`.
- **`api()` dan `el()`** di `app.js`: fetch JSON dengan CSRF dan pembuat elemen DOM yang aman dari XSS.
- **`api_request()`**: pola keamanan endpoint (POST, CSRF, cek kepemilikan, lepas kunci sesi).
- **`Documents::renderPrd()` dan `renderFeature()`**: sumber markdown untuk PRD.md, features/*.md, dan Download.
- **`Documents::zip()`**: tinggal ditambah `TASKS.md` dan status task.
- **`MockData`**: PRD, keamanan, dan spesifikasi fitur tiruan sudah ada. Untuk streaming mock, markdown hasil render cukup dipotong per ±40 karakter.
- **RateLimiter + `api_logs`**, Validator normalisasi JSON, dan percobaan ulang sekali di `generate_document.php`.

---

## 5. Konflik spec ↔ kode yang perlu diputuskan

**K1 — Streaming PRD saat PRD berupa JSON.**
Usulan: tetap pakai prompt JSON yang ada. Endpoint `stream_prd.php` menerima potongan teks JSON dari Claude. Setiap ±300 ms, server memperbaiki JSON parsial (menutup string, array, dan objek yang masih terbuka), mengisi field yang belum ada dengan nilai kosong, merender dengan `renderPrd()`, lalu mengirim **snapshot markdown** ke browser. Hasilnya, teks bertambah per bagian seperti diketik, renderer tetap satu (PHP), dan validator v1.1 tetap bekerja.
Alternatif: prompt baru yang langsung menghasilkan markdown. Pilihan ini lebih "alami" untuk streaming, tetapi melanggar "prompt dipakai apa adanya" dan memutus validator v1.1. Tidak disarankan.

**K2 — PRD §8 Keamanan adalah panggilan kedua.**
Streaming dijalankan dalam dua fase berurutan dalam satu sesi: bagian 1–7, lalu bagian 8. Daftar isi menambah "8. Keamanan & Privasi" saat fase kedua mulai. Total ±8–10 menit dengan Claude Opus 5 (bagian 8 tadi butuh ±6 menit).

**K3 — Pipeline v1.1 langkah 5 vs "Bikin Task satu panggilan".**
Spesifikasi per fitur (Tujuan, Selesai bila, Keamanan, task) dan audit/QUALITY.md sekarang dibuat di langkah 5, dengan N+4 panggilan. Usulan:
- Workspace streaming hanya membuat PRD (bagian 1–8).
- **Bikin Task** = satu panggilan `buat_task` sesuai spec, yang menghasilkan untuk tiap fitur dan sub-fitur: Tujuan, Selesai bila, dan task (judul, sub-fitur, fase, prioritas). Kalau spesifikasi fitur v1.1 sudah ada, Tujuan dan Selesai bila diambil dari sana.
- Spesifikasi fitur v1.1 lengkap (bagian Keamanan per fitur, task keamanan) dan audit QUALITY.md tetap dibuat, tetapi dipindahkan ke tombol **Implemen → Download ZIP**. Bila belum ada, ZIP memicu pipeline yang sama dengan progres, seperti langkah 5 sekarang. Dengan begitu Standar v1.1 tidak berubah dan tidak hilang.
Ini perlu persetujuan, karena spec tidak menyebut di mana QUALITY.md dibuat dalam alur baru.

**K4 — `peta_hash` menghapus dokumen saat peta berubah.**
`Documents::current()` menghapus PRD dan spesifikasi fitur bila nama, fase, atau sub-fitur berubah. Di workspace, U5 mengizinkan edit dan hapus fitur, sehingga mengedit satu nama fitur akan **menghapus PRD**. Usulan: `prd_versions` menjadi sumber tampilan PRD dan tidak pernah dihapus otomatis. Dokumen JSON yang basi hanya **ditandai** (`peta_berubah`), dan tampil bersama tombol "Buat ulang PRD", bukan dihapus.

**K5 — Status fitur.**
Spec meminta status dihitung dari task. Usulan: setelah fitur punya task, status dihitung dari task dan editor status manual disembunyikan. Sebelum ada task, status manual di langkah 4 tetap dipakai. Kolom `features.status` tetap ada dan diisi otomatis sebagai cache yang disinkronkan setiap kali status task berubah.

**K6 — Edit manual PRD vs JSON.**
Versi `manual` di `prd_versions` adalah markdown bebas dan tidak bisa divalidasi ulang oleh v1.1, yang membaca JSON. Usulan: tampilan, Download .md, dan ZIP memakai versi terbaru di `prd_versions`. QUALITY.md tetap merujuk versi AI, dan ZIP menambahkan catatan "PRD diedit manual setelah audit" bila versi terbaru bersumber `manual`. Bikin Task membaca markdown versi terbaru, karena teksnya bisa diubah pengguna.

**K7 — Nama aplikasi.**
UI diganti ke **PlanKan** cukup lewat `APP_NAME` di `src/config.php` dan `standard.json` ("Standar Kualitas PlanKan"). Nama teknis `planku_token` (cookie) dan database `planku` **tidak** diganti, karena mengganti keduanya membuat sesi dan instalasi yang ada putus.

**K8 — Endpoint unduhan dan CSRF.**
`download.php` sekarang memakai GET tanpa CSRF (cukup cookie). Spec meminta CSRF di semua endpoint. Usulan: unduhan dijalankan lewat `fetch` POST dengan header CSRF, lalu hasilnya disimpan dengan `Blob` dan `URL.createObjectURL`.

**K9 — Batas laju.**
`RATE_LIMIT_PER_HOUR=30` berlaku untuk semua panggilan AI per IP. Chat agent akan cepat menghabiskannya. Usulan: tambah batas khusus agent, misalnya `AGENT_RATE_LIMIT_PER_HOUR=20`, terpisah dari dokumen. Spec juga menyebut `AI_MIN_INTERVAL_SECONDS`, yang belum ada; akan ditambahkan di U4 untuk Bikin Task bertahap.

**K10 — Proxy Claude.**
Key memakai `api.mwapi.dev`, yang memutus koneksi diam lebih dari ±100 detik (Cloudflare 524) dan punya kuota 5 jam. Streaming ke Claude sudah mengatasi 524. Kuota tetap menjadi risiko saat uji mode nyata.

---

## 6. Tabel & kolom yang perlu ditambah

Semua lewat migrasi aditif baru, `migrations/001_workspace.sql`. Belum ada folder `migrations/` maupun runner, karena `schema.sql` selama ini menjadi baseline. Usulan: tambahkan `migrate.php` (CLI) dan tabel `schema_migrations(nama, dijalankan_pada)` supaya setiap file hanya dijalankan sekali. `schema.sql` tetap dibiarkan untuk instalasi awal.

```sql
CREATE TABLE prd_versions (
  id, project_id FK, content_md LONGTEXT,
  source ENUM('ai','manual'),
  selesai TINYINT(1) DEFAULT 1,     -- tambahan: 0 = hasil streaming parsial (uji penerimaan #2)
  created_at
);

ALTER TABLE features
  ADD deskripsi TEXT NULL,
  ADD tujuan TEXT NULL,
  ADD selesai_bila JSON NULL;
-- fase, ikon, status, urutan sudah ada (setara phase, icon, status, sort_order)

ALTER TABLE sub_features
  ADD deskripsi TEXT NULL,
  ADD tujuan TEXT NULL,
  ADD selesai_bila JSON NULL;

CREATE TABLE tasks (
  id, project_id FK, feature_id FK, sub_feature_id NULL FK,
  title VARCHAR(200), phase TINYINT,
  priority ENUM('utama','penting','opsional'),
  status ENUM('belum_mulai','dikerjakan','selesai','gagal') DEFAULT 'belum_mulai',
  sort_order, created_at, updated_at,
  INDEX (project_id, feature_id, status)
);

CREATE TABLE agent_messages (
  id, project_id FK,
  role ENUM('user','assistant','system'),
  content TEXT, created_at
);
```

Semua FK memakai `ON DELETE CASCADE` ke `projects` atau `features`. Hapus fitur di U5 akan ikut menghapus sub-fitur dan task-nya, sesuai spec.

---

## 7. Risiko streaming di XAMPP (Windows)

Hasil pemeriksaan konfigurasi lokal:

| Pengaturan | Nilai sekarang | Dampak | Penanganan |
|---|---|---|---|
| `output_buffering` (php.ini) | `4096` | Potongan teks tertahan sampai 4 KB | Tidak bisa diubah lewat `ini_set` (PERDIR). Panggil `while (ob_get_level()) ob_end_clean();` di awal endpoint, lalu `flush()` per potongan |
| `zlib.output_compression` | `Off` | aman | tetap set `ini_set('zlib.output_compression', '0')` |
| `mod_deflate` (Apache) | tidak dimuat | aman | bila kelak diaktifkan: `apache_setenv('no-gzip', '1')` dan header `Content-Encoding: none` |
| `max_execution_time` | `120` | PRD (±8–10 menit) terputus | `set_time_limit(0)` |
| Kunci sesi PHP | aktif selama request | request lain dari tab yang sama menunggu | `session_write_close()` sebelum streaming (sudah ada di `api_request()`) |
| Apache `Timeout` | `300` | koneksi diam lebih dari 5 menit diputus | kirim komentar SSE `: ping` setiap ±15 detik, terutama saat Claude masih "berpikir" sebelum token pertama |
| `ThreadsPerChild` | `25` | maksimal ±25 request bersamaan | cukup untuk lokal |

Risiko lain:

- **EventSource hanya bisa GET** dan tidak bisa mengirim header CSRF. Usulan: `fetch()` POST dengan `ReadableStream` lalu parse format SSE di JS. Header CSRF dan pola `api_request()` tetap bisa dipakai.
- **`connection_aborted()`** baru terdeteksi saat server menulis. Ping berkala membuat pemutusan cepat terdeteksi, lalu teks parsial disimpan sebagai `prd_versions` dengan `selesai=0`.
- **Refresh di tengah streaming** menghentikan request PHP, karena `ignore_user_abort` mati. Panggilan ke Claude ikut berhenti, dan token yang sudah dibuat tetap ditagih. Setelah refresh, halaman menampilkan PRD parsial + tombol "Lanjutkan / coba lagi". "Lanjutkan" membuat ulang dari awal, karena melanjutkan JSON setengah jadi tidak andal.
- **Antivirus atau proxy lokal** di Windows kadang menahan respons streaming. Kalau ini terjadi, gejalanya teks muncul sekaligus di akhir. Perlu dicek manual di browser saat U2.
- **Library CDN** (marked, DOMPurify, mermaid) butuh internet. Usulan: salin ke `public/vendor/` dengan versi dipin, supaya bisa dipakai offline di XAMPP. Mermaid ±3 MB dan hanya dimuat di halaman workspace.

---

## 8. Rencana file per tahap

| Tahap | File baru / diubah |
|---|---|
| U1 | `public/workspace.php`, `assets/js/workspace.js` (router hash), `assets/css/app.css`, `src/config.php` (PlanKan), `step5.php` → redirect, `docs/keputusan.md`, `migrations/001_workspace.sql`, `migrate.php` |
| U2 | `src/AiClient.php` (`stream()` publik), `src/PartialJson.php`, `api/stream_prd.php`, `public/vendor/*`, render + daftar isi live |
| U3 | `api/prd_save.php`, `api/prd_export.php`, mode edit, riwayat versi |
| U4 | `api/tasks_generate.php`, `Prompts::buatTask`, `Validator::aiTasks`, `MockData::tasks` |
| U5 | perluasan kanvas ke 4 kolom, `api/feature_get.php`, `feature_update.php`, `feature_delete.php`, panel kiri |
| U6 | halaman Fitur + Kanban, `api/tasks_list.php`, `task_update.php` |
| U7 | popup Implemen, ZIP + `TASKS.md`, `api/prompt_agent.php` |
| U8 | `api/agent.php` (SSE), `agent_history.php` |

---

## 9. Pertanyaan sebelum U1

1. **K1:** setuju streaming PRD lewat JSON parsial + snapshot markdown dari server, dengan prompt yang sama?
2. **K3:** setuju spesifikasi fitur v1.1 dan audit QUALITY.md dipindah ke alur **Implemen → Download ZIP**, sementara Bikin Task tetap satu panggilan?
3. **K4:** setuju dokumen basi hanya ditandai, bukan dihapus?
4. **K7:** setuju nama teknis (`planku_token`, database `planku`) tidak diganti?
5. **Library:** salin ke `public/vendor/` (offline) atau pakai CDN dengan versi dipin?
