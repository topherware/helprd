# Rencana v1.2: Siap Bangun (hasil B1)

Audit kode PlanKu sekarang terhadap `standar-kualitas-v1.2-siap-bangun.md`, plus rancangan B2–B7.
Dokumen ini belum mengubah kode apa pun. Bagian **A** berisi keputusan yang perlu disetujui pemilik sebelum B2.

---

## A. Keputusan yang perlu disetujui

Spec v1.2 mengasumsikan beberapa hal yang **belum ada** di kode. Tiap keputusan di bawah punya rekomendasi.

| # | Hal | Kondisi sekarang | Rekomendasi |
|---|---|---|---|
| D1 | Letak spec | Spec dan gambar ada di root (`standar-kualitas-v1.2-siap-bangun.md`, `ref-input/`), bukan `spec/`. Tidak ada `spec/standar-kualitas-v1.md` atau `v1.1-keamanan.md`. | Pakai lokasi sekarang. Standar v1.1 yang berlaku adalah `src/Quality/standard.json`. |
| D2 | "v1 selesai dan lolos tes" | Yang ada: aplikasi PlanKu + standar v1.1 (keamanan saja). Tidak ada `Brief.php`, `Quality::evaluate`, `benchmark/`, `tools/evaluate.php`, `src/Quality/Validators/`. Dari 8 dimensi skor, hanya **Keamanan** yang dihitung otomatis (`standard.json` → `dinilai_otomatis`). | Bangun bagian yang hilang di tahap yang relevan (Brief di B2, `evaluate` + CLI di B6, benchmark di B7). |
| D3 | Skor 9 dimensi | Validator lama "StrukturLengkap, Keterlacakan, KecocokanStack, Kejujuran" belum ada. Konsistensi, Keterukuran, dan Keterbacaan juga belum. | B6 membuat validator deterministik sederhana untuk **Struktur, Keterlacakan, Stack, Kejujuran, Konsistensi, Keterukuran** (aturan di bagian H). **Keterbacaan** (5) belum dinilai: dikeluarkan dan bobot dinormalisasi, ditulis jujur di QUALITY.md. |
| D4 | Jumlah panggilan Pass 1 | Spec: 5 panggilan. Sekarang PRD = 7 panggilan pendek (4 bab 1–7 + 3 bab 8) karena proxy sering memutus jawaban panjang. | **8 panggilan pendek** (bagian F). Gabungan bab 6–8 atau 9–11 dalam satu panggilan terlalu panjang untuk proxy sekarang. Mekanisme lanjut-per-potongan (`prd_parts`) tetap dipakai. |
| D5 | Urutan panggilan bendera | Spec menaruh bendera Triggers sebagai panggilan ke-5. | Jadikan **panggilan pertama**: bendera dibutuhkan untuk menulis aturan bab 6 (snapshot/audit/tamu), 8, dan 12. |
| D6 | Sumber validator | Validator v1.1 membaca PRD **JSON** (bukan markdown). | Tetap JSON sebagai sumber kebenaran. Suntingan manual markdown tidak dinilai ulang (sama dengan K6 sekarang; QUALITY.md diberi catatan). |
| D7 | Spesifikasi per fitur v1.1 | "Jalankan audit" membuat 1 panggilan AI per fitur (`featureDoc`: keamanan + task keamanan per fitur), terpisah dari task di Kanban. | **Hapus.** Bagian Keamanan per fitur (≤ 3 butir rujukan SEC) dirakit dari PRD bab 8 tanpa AI. Task keamanan jadi task `[M3]` dari Bikin Task. Audit turun dari ±N+2 menjadi 1–3 panggilan. |
| D8 | Penanda milestone task | Tabel `tasks` punya `phase` (fase fitur 1–4) dan `priority`, tanpa milestone. | Kolom baru `tasks.milestone` (M1/M2/M3). Prefiks `[M1]` dirender saat ekspor (TASKS.md, features, validator), tidak disimpan di judul. Kanban tidak diubah (spec melarang mengubah alur UI). |
| D9 | Stack "Standar PlanKu" | Gambar menyebut stack bermerek ("Standard Eduwebmu"). | Nama "Standar PlanKu": Next.js + Auth.js + PostgreSQL (Neon) + Drizzle ORM + Tailwind + shadcn/ui. Bahasa skema `ts` (Drizzle). |
| D10 | "Bebas" dan "Tulis Sendiri" pada stack | Validator butuh profil stack konkret. | Panggilan bendera mengunci stack: Bebas → AI memilih **salah satu id** di `stacks.json`. Tulis Sendiri → AI mengisi profil (`db`, `bahasa_skema`, `rls`, `serverless`) dari daftar nilai tetap, lalu divalidasi. |
| D11 | PRD lama | PRD project yang sudah ada berformat v1.1 (bab 1–8). | Tetap bisa dibuka dan diunduh. Audit v1.2 akan gagal (ini justru fixture negatif). Untuk format baru: "Buat ulang PRD". |
| D12 | Sidik PRD | `Documents::hash()` hanya dari peta fitur. | Tambah isian Bagian B ke sidik, supaya mengganti gaya/stack/catatan menandai PRD "basi". Efek samping: semua PRD lama ditandai basi sekali. |

---

## B. Peta kode sekarang (yang tersentuh v1.2)

| Area | Berkas | Isi sekarang |
|---|---|---|
| Input Teknologi | `public/step2.php` | 2 kartu: "Biarkan AI pilih" / "Pilih sendiri" + teks stack. Mengganti teknologi menghapus pertanyaan dan peta fitur. |
| Data project | `schema.sql` → `projects` | `tech_mode` (ai/manual), `tech_stack_pilihan`, `rekomendasi_stack` (diisi AI di langkah 4). |
| Prompt | `src/Prompts.php` | `SYSTEM`, `questions`, `features`, `prd` (`PRD_PARTS`, 4 potongan), `keamanan` (`KEAMANAN_PARTS`, 3 potongan), `featureDoc`, `buatTask`, `auditKeamanan`, `perbaikanKeamanan`, `AGENT_SYSTEM`/`agent`. |
| Normalisasi | `src/Validator.php` | `aiPrd`, `aiKeamanan`, `aiFeatureDoc`, `aiTasks`, `aiAudit`. |
| Mock | `src/MockData.php` | `prd`, `keamanan` (dinamis dari pemicu), `featureDoc`, `tasks` (8 task/fitur generik), `agentReply`. |
| Render & ekspor | `src/Documents.php` | `renderPrd` (bab 1–8, mermaid ER/sequence), `renderSecurity`, `renderFeature`, `exportZip` (PRD.md, TASKS.md, features/, QUALITY.md), budget panggilan (`ENDPOINTS`, `callsLeft`). |
| Streaming PRD | `api/stream_prd.php` | Bab 1–7 lalu bab 8, per potongan, snapshot markdown, resume via `prd_parts`. |
| Task | `api/tasks_generate.php` | Bikin Task: kelompok ≤ `TASK_FEATURES_PER_CALL` fitur, 8–15 task per fitur, `AI_MIN_INTERVAL_SECONDS`. |
| Audit | `api/generate_document.php` (`target`: fitur/audit), `api/quality_status.php`, `public/assets/js/implemen.js` | Spesifikasi fitur v1.1 → validator keamanan → audit AI → perbaikan bab 8 → `quality_reports`. |
| Kualitas | `src/Quality/standard.json`, `Report.php`, `Security/Triggers.php` (12 pemicu + MULTI + DASAR), `Security/Validators.php` (11 validator Sec) | Skor hanya Keamanan: 100 − 20×kritis − 5×peringatan. |
| Prompt AI agent | `api/prompt_agent.php` | Prompt dirakit dari PRD JSON + daftar fitur. |
| Tes | `tests/security_test.php` | 28 tes v1.1 (lulus). |

Temuan dari project 9 ("Katering Sekolah Online", PRD v1.1): **39 SEC**, **15–20 task per fitur** (170 task untuk 10 fitur), tanpa design system, rute, dummy, kode skema, keputusan vendor, atau milestone. Cocok dipakai sebagai fixture negatif (bagian I).

---

## C. Input baru (B2)

### C.1 Migrasi `migrations/003_v12_brief.sql` (aditif)

```sql
ALTER TABLE projects
  ADD COLUMN design_style  VARCHAR(20)  NOT NULL DEFAULT 'bebas' AFTER rekomendasi_stack,
  ADD COLUMN design_custom VARCHAR(1000) NULL AFTER design_style,
  ADD COLUMN stack_choice  VARCHAR(30)  NOT NULL DEFAULT 'bebas' AFTER design_custom,
  ADD COLUMN stack_custom  VARCHAR(500) NULL AFTER stack_choice,
  ADD COLUMN ui_only       TINYINT(1)   NOT NULL DEFAULT 0 AFTER stack_custom,
  ADD COLUMN ai_workflow   VARCHAR(10)  NOT NULL DEFAULT 'fase' AFTER ui_only,
  ADD COLUMN extra_notes   TEXT NULL AFTER ai_workflow;

-- Project lama: "Pilih sendiri" menjadi Tulis Sendiri dengan teks stack yang sama.
UPDATE projects SET stack_choice = 'tulis_sendiri', stack_custom = tech_stack_pilihan
 WHERE tech_mode = 'manual' AND tech_stack_pilihan IS NOT NULL;

ALTER TABLE tasks
  ADD COLUMN milestone ENUM('M1','M2','M3') NULL AFTER phase;   -- NULL = task lama (sebelum v1.2)
```

- Nilai yang sah: `design_style` ∈ bebas, bersih_modern, dark_premium, minimalis, korporat, tulis_sendiri. `stack_choice` ∈ id di `stacks.json` + bebas + tulis_sendiri. `ai_workflow` ∈ task, fase, sekaligus.
- `tech_mode`/`tech_stack_pilihan` tetap diisi supaya kode lama jalan: `bebas` → `ai`; selain itu → `manual` dengan nama stack (atau teks kustom).
- Hanya perubahan stack atau `ui_only` yang menghapus pertanyaan dan peta fitur (seperti sekarang). Mengubah gaya desain, cara kerja AI, atau catatan tidak menghapus apa pun; PRD cukup ditandai basi (D12).

### C.2 Halaman `step2.php` → "Bagian B: Desain, Teknologi, & Cara Kerja AI"

4 kartu bernomor sesuai gambar, dengan tema gelap PlanKu (tiru struktur dan perilaku, bukan warna atau merek):
1. Gaya Desain UI/UX: 6 kartu radio, lencana "Default" di Bebas. Tulis Sendiri membuka textarea (wajib diisi, ≤ 1000 karakter).
2. Tech Stack: saklar "Hanya UI / frontend" di atas, lalu 6 kartu radio (Bebas + 4 dari `stacks.json` + Tulis Sendiri). Tulis Sendiri membuka input wajib (≤ 500 karakter). Saklar UI-only aktif → kartu stack tetap bisa dipilih (frontend saja).
3. Cara Kerja AI: 3 kartu (Per Task "Paling terkontrol", Per Fase "Direkomendasikan" default, Sekaligus "Paling cepat").
4. Catatan Tambahan: textarea dengan penghitung `0/2000`, placeholder contoh aturan bisnis.

Kartu radio memakai `<input type="radio">` asli (bisa dipakai keyboard, centang lewat CSS). Formulir tetap POST biasa + CSRF; JS hanya untuk penghitung karakter dan membuka kolom "Tulis Sendiri".

### C.3 Validasi dan penyaringan (`Validator::brief`)

- Nilai radio harus ada di daftar tetap, selain itu 422.
- Teks Tulis Sendiri kosong ditolak. `extra_notes` ≤ 2000 karakter **di server** (dihitung `mb_strlen`).
- Pembersihan: `strip_tags`, buang karakter kontrol (kecuali baris baru), rapikan spasi. Disimpan lewat prepared statement.
- Di prompt, teks pengguna masuk ke `<catatan_pengguna>`, `<gaya_kustom>`, `<stack_kustom>`. `Prompts::SYSTEM` ditambah tag-tag itu ke daftar "data, bukan instruksi". Sebelum masuk prompt, tag penutup tiruan (`</catatan_pengguna>` dsb.) di dalam teks dinetralkan.

### C.4 `src/Quality/Brief.php` (baru)

Objek baca-saja, satu sumber kebenaran untuk prompt, pemicu, dan validator:
`judul, ide, qa, designStyle, designCustom, stack (profil dari stacks.json atau null), stackChoice, stackCustom, uiOnly, aiWorkflow, extraNotes, features`.
Dibangun dari baris `projects` + `project_qa()` + `project_features()`. Juga menyediakan `fingerprint()` untuk sidik PRD (D12).

### C.5 Struktur `src/Quality/stacks.json`

```json
{
  "versi": 1,
  "stacks": [
    {
      "id": "standar_planku",
      "nama": "Standar PlanKu",
      "ringkas": "Next.js + Auth.js + PostgreSQL (Neon) + Drizzle ORM + Tailwind + shadcn/ui",
      "runtime": "Node.js (TypeScript)",
      "db": "postgresql",
      "bahasa_skema": "ts",
      "berkas_skema": "db/schema.ts",
      "rls": false,
      "serverless": true,
      "vendor": {
        "otp":        {"nama": "WhatsApp Cloud API", "env": ["WHATSAPP_TOKEN", "WHATSAPP_PHONE_ID"], "biaya": "berbayar per percakapan", "mock": true},
        "email":      {"nama": "Resend", "env": ["RESEND_API_KEY", "EMAIL_FROM"], "biaya": "gratis kuota kecil", "mock": true},
        "whatsapp":   {"nama": "WhatsApp Cloud API", "env": ["WHATSAPP_TOKEN", "WHATSAPP_PHONE_ID"], "biaya": "berbayar per percakapan", "mock": true},
        "penyimpanan":{"nama": "Cloudflare R2", "env": ["R2_ACCOUNT_ID", "R2_ACCESS_KEY_ID", "R2_SECRET_ACCESS_KEY", "R2_BUCKET"], "biaya": "gratis kuota kecil", "mock": true},
        "rate_limit": {"nama": "Upstash Redis", "env": ["UPSTASH_REDIS_REST_URL", "UPSTASH_REDIS_REST_TOKEN"], "biaya": "gratis kuota kecil", "mock": true},
        "pembayaran": {"nama": "Midtrans", "env": ["MIDTRANS_SERVER_KEY", "MIDTRANS_CLIENT_KEY"], "biaya": "biaya per transaksi", "mock": true}
      }
    }
  ],
  "nilai_sah": {
    "db": ["postgresql", "mysql", "sqlite", "mongodb", "d1", "none"],
    "bahasa_skema": ["sql", "ts", "prisma"]
  }
}
```

Isi 4 stack (vendor default dicek ulang di B2):

| id | Ringkas | db | skema | RLS | serverless | Penyimpanan / rate-limit |
|---|---|---|---|---|---|---|
| `standar_planku` | Next.js + Auth.js + Neon + Drizzle + Tailwind + shadcn/ui | postgresql | ts | tidak | ya | R2 / Upstash |
| `full_supabase` | Next.js + Supabase Auth + PostgreSQL + Realtime/Storage | postgresql | sql | **ya** | ya | Supabase Storage / Upstash |
| `react_node` | React (Vite) + Express + PostgreSQL (Prisma) | postgresql | prisma | tidak | tidak | disk server atau S3-kompatibel / tabel DB atau memori dengan catatan |
| `react_cloudflare` | React + Cloudflare Workers + Hono + D1 | d1 (SQLite) | sql | tidak | ya | R2 / Workers KV atau Durable Object |

Vendor OTP, email, dan pembayaran sama untuk semua stack kecuali ditimpa per stack. Mode MVP default semua vendor: **adapter tiruan (mock)**, vendor asli di M2.

---

## D. Pemicu (B3)

`src/Quality/Triggers.php` (baru) menghasilkan 15 bendera dari tiga sumber, digabung dengan OR:
1. **Brief**: `uiOnly`, `serverless` (dari profil stack), `hasDarkTheme` (gaya Dark Premium atau kata gelap/dark di gaya kustom).
2. **Deklarasi AI** (panggilan P0, JSON, divalidasi tipe boolean): semua 15 bendera + alasan singkat.
3. **Jaring kata kunci** atas ide, jawaban, catatan, dan nama fitur/sub-fitur (mis. harga|tarif|diskon|biaya → `hasPricedEntity`; pesan|booking|pinjam|tagihan → `hasTransactionRecord`; tanpa akun|tamu|tanpa login → `hasGuestFlow`; admin|pemilik|kelola|setujui → `hasAdminMutation`).

Aturan: `uiOnly` mematikan `hasDatabase`, `usesOtp`, `usesPayment`, dan sejenisnya (semua layanan backend). `hasDatabase` = tidak `uiOnly` dan stack.db ≠ none. `multiRole` = jumlah peran > 1.
`SecurityTriggers` (v1.1) tetap dipakai untuk pemilihan kontrol keamanan, tetapi tiap kontrol diberi tingkat `mvp` atau `hardening` (bagian G).

---

## E. Struktur PRD baru

Bab 1–7 tetap bernomor sama. Perubahan isi JSON:

| Bab | Perubahan |
|---|---|
| 2 Requirements | FR/NFR jadi beridentitas: `{"id":"FR-01","teks":"...","fitur":["..."]}`. Tambah `pertanyaan_terbuka` (`OQ-01`). Tambah `catatan_diterapkan`: tiap aturan dari Catatan Tambahan → ID FR/NFR/OQ (untuk validator Keterlacakan). |
| 6 Database | Tambah `peran` (daftar peran, dipakai bab 8 dan 10), `kode_skema` (string, bahasa sesuai stack), `aturan_pemicu` (snapshot/log audit/tamu: tabel dan kolom yang dipakai), `kebijakan_akses` (bila RLS). Tabel `log_audit`, kolom `*_snapshot`, `user_id` nullable + `nama_tamu`/`kontak_tamu` muncul hanya bila benderanya aktif. |
| 7 Tech Stack | Tambah `keputusan_teknologi`: stack terkunci + alasan (wajib bila Bebas). |
| 8 Keamanan | Lihat bagian G. |
| **9** Design System | `alasan`, `token_warna` (11 token × terang/gelap), `tipografi` (font judul/isi, skala, tinggi baris, sumber), `ukuran` (ketuk, spasi, radius, bayangan, lebar maks, breakpoint), `komponen` (tombol 3 jenis × status, kartu, input, badge, tabel, dialog, toast), `nuansa` (3–5 sifat + 2 hindari), `keadaan` (loading/kosong/error), `aksesibilitas`. |
| **10** Rute | `rute` (`path, halaman, peran, layout, sumber`), `navigasi` per peran (komponen + item menu), `rute_khusus` (404, 403, error, masuk/keluar), `penjaga_akses`. |
| **11** Dummy | `dataset` (`id: DUMMY-X`, `entitas`/tabel, `kolom`, `baris`), `template` (nama, isi berplaceholder, contoh terisi), `copywriting` (nada, sapaan, istilah, microcopy kosong/error/sukses/konfirmasi). |
| **12** Layanan | `layanan` (`kebutuhan, vendor_atau_mock, alasan, biaya, env, mode_mvp`), `rate_limit_serverless` (mekanisme), `env_example` (grup → variabel + komentar, nilai `isi_dengan_...`). |
| **13** Milestone | `milestone` M0–M4 (`tujuan, selesai_bila, cakupan, checkpoint`), `aturan_checkpoint` (sesuai Cara Kerja AI), `master_prompt`, `claude_md` (`struktur_folder, konvensi, perintah: {jalan, uji}`). |

Mode UI-only: bab 6, 8, 12 dirender satu baris "Tidak berlaku (mode hanya-UI)". Bab 13 hanya M0 dan M1. Bab 11 tetap ada.

Berkas ZIP tambahan dirakit **tanpa AI** dari JSON: `CLAUDE.md` (stack, struktur folder, konvensi, perintah, aturan checkpoint, tautan bab 9/10/11/8), `PROGRESS.md` (tabel milestone + kolom status + catatan keputusan), `.env.example` (dari `env_example`), `db/schema.{sql|ts|prisma}` (dari `kode_skema`, bila `hasDatabase`).

---

## F. Rencana panggilan AI per bab

### Pass 1: PRD (streaming, per potongan, bisa dilanjutkan)

| Potongan | Isi | Bergantung pada | Perkiraan keluaran |
|---|---|---|---|
| P0 | Bendera Triggers + stack terkunci + daftar peran (JSON kecil, tidak dirender) | Brief, peta fitur | < 1K token |
| P1 | Bab 1–2 (overview, FR/NFR/OQ, catatan diterapkan) | P0 | 3–5K |
| P2 | Bab 3–5 (fitur, user flow, arsitektur) | P1 | 4–6K |
| P3 | Bab 6–7 (database + kode skema + tech stack + keputusan) | P0–P2 | 5–8K |
| P4 | Bab 8 (klasifikasi, hak akses, SEC MVP ≤ 12, hardening ≤ 8, OWASP singkat, risiko) | P0, P3 | 3–5K |
| P5 | Bab 9–10 (design system + rute) | P0–P2, P4 (peran) | 4–6K |
| P6 | Bab 11 (dataset sesuai skema P3, template, copywriting) | P3, P5 | 4–6K |
| P7 | Bab 12–13 (layanan + `.env.example` + milestone + master prompt + CLAUDE.md) | P0, P3, P4, P6 | 4–6K |

8 panggilan. Mode UI-only: P3, P4 diganti teks tetap tanpa AI, dan P7 hanya bab 13 (M0–M1), jadi 6 panggilan.
`PRD_PARTS`/`KEAMANAN_PARTS` diganti satu daftar `PRD_PARTS_V12`; mekanisme `prd_parts`, snapshot, retry, dan status "Menyusun bab X…" di workspace tetap sama.

### Pass 2: Bikin Task (tetap tombol yang sama)

Prompt `buatTask` diubah: tiap task punya `milestone` M1/M2/M3, urut M1 → M2 → M3 per fitur, ≤ 14 per fitur, total ≤ 12 × jumlah fitur. M1 dilarang menyebut tabel/API/RLS/migrasi/vendor dan wajib merujuk `DUMMY-…` bila memakai data tiruan. Task keamanan = `[M3]` yang merujuk SEC. Kelompok tetap `TASK_FEATURES_PER_CALL` (±3 panggilan untuk 10 fitur). Mode UI-only: hanya M1.

### Pass 3–5: Audit (tombol "Jalankan audit" di Implemen)

- **Pass 3**: semua validator deterministik (V12–V19 + Sec v1.1 yang disederhanakan + validator dimensi D3). Tanpa AI.
- **Pass 4**: 1 panggilan audit AI singkat (kelayakan vendor, kewajaran dataset, celah desain). Hanya laporan.
- **Pass 5**: perbaikan maksimal 2 putaran. Tiap temuan dipetakan ke potongan (P1–P7) lewat bab-nya; hanya potongan bertanda yang ditulis ulang, dengan temuan sebagai daftar perbaikan, lalu validator diulang. Hasilnya jadi versi PRD baru di riwayat (`prd_versions`, source `ai`). Temuan pada task (V18/V19) memicu tulis ulang task fitur terkait.

Perkiraan per project 10 fitur: 2 (pertanyaan, peta) + 8 (PRD) + 3 (task) + 1 (audit) + ≤ 6 (perbaikan) ≈ **20**, maksimal ±26. `DOC_MAX_CALLS_PER_PROJECT` dinaikkan ke **36**; endpoint baru masuk `Documents::ENDPOINTS`. Jeda `AI_MIN_INTERVAL_SECONDS` dipatuhi di audit dan perbaikan seperti di Bikin Task.

---

## G. Penyederhanaan keamanan (B5, menimpa v1.1)

- Bab 8 JSON: `persyaratan` (Wajib MVP, ≤ 12, `SEC-01..`, `fitur`, `kontrol`), `hardening` (≤ 8 string, tanpa ID), `klasifikasi_data`, `hak_akses`, `owasp` (ringkas: kode → SEC atau "tidak relevan"), `risiko_tersisa` (memuat catatan UU PDP).
- Bagian `ancaman` dan `pihak_ketiga` dihapus dari bab 8; pihak ketiga sudah tercakup di bab 12.
- Tiap kontrol di `SecurityTriggers::RULES` diberi tingkat. **MVP**: hash sandi, batas percobaan login/OTP, sesi, otorisasi server, matriks peran, log audit admin, validasi input server, batas tipe/ukuran unggah, akses privat berkas pribadi, rahasia di env, HTTPS, tokenisasi pembayaran + verifikasi webhook, persetujuan wali (data anak). **Hardening**: retensi, enumerasi akun, verifikasi isi berkas, nama acak, idempoten, header keamanan, dependensi, dll. Satu SEC MVP boleh memenuhi beberapa kontrol MVP supaya muat ≤ 12.
- `SecPemicuTerpenuhi` hanya menuntut kontrol MVP. Skor Keamanan dihitung dari temuan atas tingkat MVP saja.
- Bagian Keamanan per fitur: ≤ 3 butir, hanya rujukan `SEC-xx` yang ada (dirakit otomatis, D7).
- Ambang mikro ("< 200 ms", "per kuartal", dsb.) dihapus dari prompt dan mock; V17 menolaknya di tingkat MVP.
- `tests/security_test.php` disesuaikan.

---

## H. Validator dan skor (B4–B6)

Struktur baru: `src/Quality/Validators/` berisi satu berkas per validator (V12–V19) dengan antarmuka sama:
`static check(Brief $b, array $flags, array $prd, array $tasks): array` → temuan `{id, sev: K|M|m, bab, pesan, saran}`.

| Validator | Sumber data | Catatan implementasi |
|---|---|---|
| V12 DesignToken | bab 9 | Parser HEX (#rgb/#rrggbb) dan HSL; kontras WCAG 2.x (luminans relatif). Fungsi kontras diuji dengan pasangan diketahui (hitam/putih = 21:1, #767676/putih ≈ 4.54). |
| V13 PetaRute | bab 3, 6 (`peran`), 10 | Tiap fitur dirujuk minimal satu rute (lewat `sumber` = nama fitur atau FR milik fitur). Path `^/[a-z0-9\-/:\[\]]*$`, unik. |
| V14 DataDummy | bab 6, 11, tasks | Kolom dataset ⊆ kolom tabel; placeholder `{nama}` di template tidak tersisa di contoh; task M1 "data tiruan/dummy" wajib menyebut `DUMMY-` yang ada; format uang `Rp\d{1,3}(\.\d{3})*`. |
| V15 SkemaKode | bab 6 + bendera | Nama tabel diekstrak dari kode per bahasa (`CREATE TABLE`, `pgTable(`/`sqliteTable(`, `model X {`), lalu dibandingkan dengan narasi dan relasi ER. Kolom uang bertipe float/double/real = gagal. |
| V16 LayananPihakKetiga | bab 12 + bendera + stacks.json | Variabel yang disebut di bab 7/12/13 ⊆ `.env.example`; pola rahasia asli (`sk-`, `AKIA`, JWT, string acak panjang) = K. |
| V17 BebanKeamanan | bab 8, features | Regex terlarang di MVP: `\d+\s*ms`, `kuartal|triwulan|bulanan|tahunan`, `disaster recovery|\bDR\b`, `sertifikasi|ISO\s*27001|SOC\s*2`. |
| V18 UrutanMilestone | tasks, bab 13 | Prefiks dan urutan per fitur; kata terlarang M1 (`tabel|API|endpoint|RLS|migrasi|skema|vendor|webhook|database`); checkpoint sesuai `ai_workflow`. |
| V19 BatasTugas | tasks, bab 13 | Batas jumlah, duplikat (judul ternormalisasi), Master Prompt menyebut `CLAUDE.md`, `PRD.md`, `features/`, `PROGRESS.md`. |

Validator dimensi lama (D3, deterministik, ringan):
- **Struktur**: bab 1–13 ada sesuai profil.
- **Keterlacakan**: setiap aturan dari Catatan Tambahan muncul di `catatan_diterapkan` → ID FR/NFR/OQ yang ada; gaya desain dan stack pilihan pengguna muncul di bab 9/7.
- **Stack**: profil stack terkunci cocok dengan `stacks.json`; bahasa skema sesuai; tidak menjanjikan RLS bila `rls=false` (ambil alih `SecKonsistenStack`).
- **Kejujuran**: klaim berlebih (daftar lama) + klaim "gratis/murah" bertentangan dengan biaya di bab 12.
- **Konsistensi**: peran sama di bab 2/6/8/10; nama fitur sama di bab 3/10/13; tabel yang dirujuk dataset ada di skema.
- **Keterukuran**: setiap milestone dan fitur punya "Selesai bila"; NFR memuat kriteria yang bisa diuji (angka atau kondisi yang dapat diperiksa).

Skor (B6): tiap dimensi mulai 100, dikurangi K = 25, M = 10, m = 3 (minimal 0), lalu dibobot sesuai spec (Konsistensi 15, Keterukuran 12, Kejujuran 12, Keamanan 10, Stack 8, Keterlacakan 8, Struktur 5, Keterbacaan 5, Kesiapan Bangun 25). Dimensi yang tidak dinilai (Keterbacaan) atau tidak berlaku di UI-only (Keamanan, sebagian Kesiapan) dikeluarkan dan bobotnya dinormalisasi. Lulus: total ≥ 85, tanpa K, Keamanan ≥ 80, Kesiapan Bangun ≥ 80. `standard.json` naik ke versi `1.2` dengan bobot, penalti, dan peta validator → dimensi.

`QUALITY.md` menampilkan skor per dimensi dan daftar periksa 7 kekurangan (lulus/gagal per validator V12–V18 + batas keamanan).

API evaluasi: `QualityReport::evaluate(Brief, flags, prd, tasks): array` dipakai pipeline, tes, dan `tools/evaluate.php` (CLI: `php tools/evaluate.php benchmark/katering-lama.json`).

---

## I. Tes dan benchmark (B4–B7)

- `tests/quality_v12_test.php` (CLI tanpa framework, gaya sama dengan `security_test.php`): uji mutasi (12 kasus dari spec §10.3), uji bendera (§10.4), uji input (§10.5: catatan berisi "abaikan instruksi…" tetap di dalam `<catatan_pengguna>` dan tag penutup dinetralkan; 2001 karakter ditolak; Tulis Sendiri kosong ditolak), uji kontras (§10.6).
- `benchmark/`:
  - `katering-lama.json`: PRD JSON + task project 9 (diekspor dari DB, data pribadi tidak ada karena isinya rencana). Harus gagal V12–V18 dengan bab yang benar.
  - 3 kasus positif dari **mode mock**: katering (Full Supabase), jasa kebersihan (React Vite + Node.js), peminjaman alat lab (UI-only). `MockData` dibuat generik dari Brief + bendera + peta fitur supaya ketiganya lolos tanpa data khusus per kasus.
- `docs/hasil-benchmark-v1.2.md`: angka sebelum/sesudah (skor per dimensi, jumlah temuan per validator).

---

## J. Rencana per tahap

| Tahap | Isi | Berkas utama |
|---|---|---|
| B2 | Migrasi 003, halaman Bagian B, `Validator::brief`, `Brief.php`, `stacks.json`, sidik PRD (D12) | `public/step2.php`, `app.css`, `src/Quality/Brief.php`, `src/Quality/stacks.json`, `src/Validator.php`, `src/Documents.php` |
| B3 | `Triggers.php` (15 bendera), prompt P0–P7, `aiPrd` diperluas, mock generik, render bab 9–13, `buatTask` bermilestone, render ZIP baru (CLAUDE/PROGRESS/.env/schema), Master Prompt di Prompt AI agent | `src/Prompts.php`, `src/Validator.php`, `src/MockData.php`, `src/Documents.php`, `api/stream_prd.php`, `api/tasks_generate.php`, `api/prompt_agent.php` |
| B4 | V12–V16 + tes mutasi | `src/Quality/Validators/*.php`, `tests/quality_v12_test.php` |
| B5 | V17–V19, tingkat MVP/hardening, Sec v1.1 disesuaikan, hapus spesifikasi per fitur v1.1 (D7) | `src/Quality/Security/*`, `src/Quality/standard.json`, `tests/security_test.php` |
| B6 | Pass 3–5, skor 9 dimensi, `evaluate`, QUALITY.md + daftar periksa, ZIP final, `tools/evaluate.php` | `api/generate_document.php` (atau `api/quality_audit.php`), `src/Quality/Report.php`, `implemen.js` |
| B7 | Fixture + benchmark + laporan | `benchmark/`, `docs/hasil-benchmark-v1.2.md`, `.htaccess` (blokir `tools/` dan `benchmark/` dari web) |

Kode yang akan dihapus saat tidak dipakai lagi: `Prompts::featureDoc`, `Prompts::perbaikanKeamanan` (diganti perbaikan per potongan), `Validator::aiFeatureDoc`, `MockData::featureDoc`, `Documents::renderFeature` (dokumen fitur v1.1), target per-fitur di `generate_document.php`.

## K. Risiko

- **Proxy memutus jawaban panjang**: bab 6 (kode skema) dan bab 11 (dataset) adalah potongan terpanjang. Mitigasi: potongan terpisah, resume, dan kode skema dibatasi ke tabel inti.
- **Konsistensi lintas potongan**: dataset (P6) harus cocok dengan skema (P3). P6 menerima kolom tabel dari P3 sebagai konteks wajib; V14 menangkap sisanya dan Pass 5 memperbaiki P6 saja.
- **Biaya**: ±20 panggilan per project (sebelumnya ±12 tanpa audit). Bikin Task dan audit tetap tombol terpisah sehingga pengguna yang hanya butuh PRD tidak membayar semuanya.
- **Workspace**: hanya teks status potongan dan isi PRD yang berubah; tata letak, rute, dan tombol tidak berubah.
