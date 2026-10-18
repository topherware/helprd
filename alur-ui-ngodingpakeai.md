# Spesifikasi Alur Kerja & UI PlanKan (mengikuti alur ngodingpakeai)

Fokus dokumen ini: **alur kerja dan tampilan** saja. Kualitas isi PRD (Standar Kualitas v1/v1.1) TIDAK diubah di tahap ini.
Referensi gambar: `spec/ref-ui/01..13-*.png`. Lihat gambarnya sebelum membangun tiap layar.

## 0. Aturan kerja
- Kerjakan satu tahap (U0–U8) per giliran. Setelah tiap tahap: ringkas apa yang berubah, cara mengujinya di browser, lalu berhenti dan tunggu "lanjut".
- Tetap PHP native + MySQL (XAMPP), tanpa build step. Library front-end (marked, mermaid, DOMPurify) boleh dari CDN dengan versi dipin, atau disalin ke `public/vendor/`.
- Hanya meniru **alur dan tata letak**. Jangan menyalin logo, nama, atau teks merek ngodingpakeai. Pakai nama dan logo PlanKan (ganti semua "PlanKu" yang tersisa di UI).
- Perubahan database harus aditif lewat file migrasi baru (`migrations/NNN_*.sql`). Jangan menghapus tabel atau kolom yang ada.
- Semua endpoint baru: CSRF, PDO prepared statements, cek kepemilikan project lewat `session_token`, RateLimiter, dan pencatatan token ke `api_logs`.
- `USE_MOCK_AI=true` harus tetap bisa menjalankan seluruh alur (streaming, tugas, agent) dengan data tiruan.
- Catat keputusan penting ke `docs/keputusan.md`.

## 1. Alur akhir yang diinginkan
```
Ide → Teknologi → Pertanyaan → Peta Fitur (rencana) → Lanjutkan
   → WORKSPACE: PRD dibuat live (streaming)  ← layar utama baru
   → [Bikin Task] → Roadmap (pohon)  → klik node → panel / halaman fitur / kanban
   → [Implemen] → popup: Download PRD | Download ZIP | Prompt AI agent
```
Workspace adalah satu halaman (`public/workspace.php`) dengan 3 tampilan: **PRD**, **Roadmap**, **Kanban**, ditambah halaman **Fitur** penuh. State tampilan disimpan di URL hash (`#/prd`, `#/roadmap`, `#/fitur/12`, `#/fitur/12/sub/40`, `#/kanban?fitur=12`) supaya tombol Back dan refresh berfungsi.

Catatan: kalau Peta Fitur (langkah 4) saat ini sudah memuat fitur + sub-fitur, pertahankan dan jadikan itu data awal Roadmap. Roadmap sebelum "Bikin Task" hanya menampilkan kolom Fitur dan Sub-fitur; setelah "Bikin Task" muncul kolom Tasks. Konfirmasikan struktur data yang ada di tahap U0.

## 2. Layout global workspace (gambar 01)
- **Topbar**: logo PlanKan · nama project (ikon folder) / nama dokumen · ikon bagikan. Di kanan: tombol oranye **Bikin Task** (di tampilan PRD) atau **Implemen** (di tampilan Roadmap/Kanban/Fitur), plus tautan **Wiki** dan **Roadmap** sesuai tampilan, plus tombol ikon untuk membuka/menutup Workspace Agent.
  - Wiki: sembunyikan atau nonaktifkan dulu (di luar cakupan), jangan tampilkan tautan mati.
- **Rail kiri sempit** (tampilan PRD): ikon pensil (edit/selesai edit, tooltip "Selesai edit" saat aktif) dan ikon dokumen (menu export).
- **Daftar isi** (kiri, di bawah rail): judul dokumen + daftar heading H2, dengan sub-daftar "Fase 1..n" di bawah Core Features. Item di-klik → scroll halus ke bagian itu; item aktif ikut tersorot saat scroll (IntersectionObserver).
- **Konten tengah**: markdown dirender, lebar baca maksimal ±760px, font besar.
- **Panel Workspace Agent** (kanan, ±600px, bisa ditutup): header "Workspace Agent" + subjudul konteks ("PRD · PRD aktif", "Roadmap · Fitur terpilih", "Task · Fitur terpilih"), area pesan, input di bawah (ikon lampiran, mikrofon, tombol kirim). Lampiran dan mikrofon: tampilkan nonaktif dulu.
- Tema gelap navy dengan aksen oranye (lihat gambar). Responsif: di lebar <900px, daftar isi dan agent menjadi drawer.

## 3. Tampilan PRD (gambar 01, 06, 07, 08, 09)
**3.1 Streaming live.** Saat workspace dibuka dari "Lanjutkan" dan PRD belum ada, markdown muncul bertahap seperti diketik.
- Endpoint SSE `api/stream_prd.php`. Matikan output buffering dan kompresi, `header('X-Accel-Buffering: no')`, `flush()` per potongan; `set_time_limit(0)`; hentikan bila klien putus (`connection_aborted`).
- Tambahkan `AiClient::stream($prompt, $onChunk)`: Gemini `streamGenerateContent?alt=sse`, Anthropic `stream:true` (parse event SSE). Mode mock: pecah teks mock per ±40 karakter dengan jeda ±30ms.
- Prompt PRD yang ada (`Prompts.php`) dipakai apa adanya; hanya cara pemanggilannya yang berubah menjadi streaming. Rancang generator PRD sebagai fungsi yang menghasilkan potongan teks, agar pipeline Standar Kualitas v1 nanti bisa dipasang tanpa menulis ulang UI.
- Saat streaming: tombol Bikin Task dan Edit nonaktif, ada indikator "Menyusun PRD…", daftar isi bertambah otomatis (debounce ±300ms), konten tetap bisa di-scroll. Setelah selesai simpan ke `prd_versions` (sumber `ai`).
- Render markdown dengan marked + DOMPurify. Blok ```mermaid dirender **hanya setelah pagar kode tertutup**; sebelum itu tampilkan placeholder "Menggambar diagram…". Jika sintaks mermaid gagal, tampilkan blok kode mentah dengan pesan kecil, jangan merusak halaman.
- Jika PRD sudah ada, langsung tampil tanpa streaming. Sediakan tombol "Buat ulang PRD" (konfirmasi dulu) yang menyimpan versi lama.
- Jika streaming terputus: tampilkan teks yang sudah masuk dan tombol "Lanjutkan / coba lagi"; jangan hilangkan hasil sebagian.

**3.2 Export (gambar 06).** Klik ikon dokumen di rail → menu kecil: **Download .md** dan **Salin teks** (toast "Tersalin").

**3.3 Edit PRD (gambar 07).** Klik ikon pensil → konten tengah berubah menjadi editor markdown mentah (textarea monospace dengan pewarnaan sederhana atau tanpa), tombol mengambang **Simpan versi** di kanan atas. Simpan membuat baris baru di `prd_versions` (sumber `manual`). Klik pensil lagi = "Selesai edit" (jika ada perubahan belum disimpan, tanya dulu). Daftar isi dan diagram dirender ulang dari versi terbaru. Sediakan daftar riwayat versi sederhana (pilih untuk mengembalikan) bila sempat; opsional.

**3.4 Diagram (gambar 08, 09).** Diagram sequence dan ER dirender langsung di dalam dokumen, tema gelap, dalam kartu berbingkai dengan ruang lega. Klik diagram membuka tampilan besar (opsional).

## 4. Bikin Task
- Tombol **Bikin Task** di topbar tampilan PRD. Klik → agent menampilkan status "Menyusun task…", lalu pesan akhir seperti: "Selesai! Aku sudah menyusun **N fitur, M sub-fitur, dan K task** dari PRD kamu. Klik fitur mana pun di peta untuk lihat detail atau langsung mulai dikerjakan." Setelah itu pindah ke tampilan **Roadmap**.
- **Hemat panggilan AI**: gunakan SATU panggilan dengan JSON schema `buat_task` untuk semua fitur (judul tugas pendek, bukan paragraf). Kembalian: untuk tiap fitur → daftar tugas `{judul, sub_fitur (opsional), fase, prioritas: utama|penting|opsional}`. Targetnya ±8–15 tugas per fitur. Jika hasil terlalu besar untuk `MAX_TOKENS`, pecah per kelompok fase, berurutan, dengan `AI_MIN_INTERVAL_SECONDS`.
- Tiap fitur dan sub-fitur juga butuh teks **Tujuan** dan **Selesai bila**. Ambil dari file `features/NN-*.md` yang sudah dihasilkan langkah dokumen yang ada; kalau belum ada, buat dalam panggilan yang sama.
- Aturan tugas: judul berupa kalimat perintah ("Buat…", "Tampilkan…", "Tulis uji…"); urutan tugas mengikuti ketergantungan (data tiruan/UI dulu, lalu backend, lalu uji). Jangan membuat tugas yang tidak berasal dari fitur di PRD.
- Idempoten: menekan Bikin Task kedua kali tidak menggandakan data (tanyakan "Buat ulang task? Status lama akan hilang").

## 5. Tampilan Roadmap / pohon (gambar 02)
Gunakan ulang kanvas peta fitur yang sudah ada (pan/zoom, garis bezier, kontrol + − fit), diperluas menjadi 4 kolom, kiri ke kanan:
1. **Node akar**: nama project + status "Perencanaan".
2. **Node fitur**: ikon, nama (dipotong dengan "…" hanya jika sangat panjang; tampilkan penuh saat hover), badge **FASE n** di pojok kanan atas, status ("Direncanakan / Dikerjakan / Selesai"), progres `selesai/total` task.
3. **Kartu SUB FITUR**: 3 item pertama + tautan "Lihat semua (n)".
4. **Kartu TASKS**: judul, progres `x/n`, 3 task pertama (terpotong) + "Lihat semua (n)".

Perilaku klik:
| Klik | Hasil |
|---|---|
| Node akar | Panel kiri terbuka berjudul **Perencanaan** dengan toggle pratinjau/kode (ikon mata/`</>`), menampilkan PRD (render atau mentah), teks "N fitur dari rencana ini.", tombol buka-layar-penuh dan tutup |
| Node fitur | Panel kiri terbuka: penghitung `2/7` dengan panah sebelumnya/berikutnya, ikon edit, hapus, perbesar, tutup; isi: ikon + judul, deskripsi, chip status, `0/0 tugas selesai`, **Tujuan**, **Selesai bila** (daftar), dan seterusnya |
| Kartu/ item sub-fitur, atau "Lihat semua" sub-fitur | Halaman **Fitur** penuh dengan sub-fitur itu terbuka |
| Kartu task, atau "Lihat semua" task | Halaman **Kanban** terfilter ke fitur itu |
| Tombol perbesar di panel | Halaman Fitur penuh |

- Hapus fitur: konfirmasi, lalu hapus fitur beserta sub-fitur dan tasknya (di DB, bukan di PRD). Edit fitur: ubah nama, deskripsi, fase, Tujuan, Selesai bila.
- Posisi node dihitung otomatis dari data; tampilan awal otomatis "fit".
- Header tampilan Roadmap: tombol **Implemen** (oranye), ikon daftar-task menuju Kanban, tombol kembali ke PRD (ikon dokumen) bila perlu.

## 6. Halaman Fitur penuh (gambar 03, 10)
- Kiri: sidebar **Fitur n** berisi daftar bernomor (ikon + nama). Item aktif tersorot dan terbuka (accordion) menampilkan sub-fitur dengan tanda centang jika selesai.
- Atas konten: penghitung `1/7` dengan panah, ikon edit/hapus/ciutkan.
- Konten: ikon + judul fitur, deskripsi, chip status, `0/0 tugas selesai`, **Tujuan**, **Selesai bila**, lalu kartu **Task (n)** yang memuat 2 task pertama, "+N task lainnya", dan tautan **Buka di Kanban →**.
- Klik sub-fitur di sidebar → konten berganti ke sub-fitur itu (Tujuan, Selesai bila, task miliknya).
- Agent di kanan menampilkan konteks "Roadmap · Fitur terpilih".

## 7. Halaman Kanban (gambar 04, 11, 12)
- Bar progres global `x/total selesai`.
- Kontrol: toggle tampilan **Kanban / Daftar**, dropdown fitur dengan kotak cari dan opsi "Semua fitur" (centang pada yang aktif), dropdown **Urutkan**: Urutan roadmap (default), Terakhir diperbarui, Terbaru dibuat, Judul A–Z, Prioritas tertinggi. Di kanan: **Pilih semua**.
- 4 kolom: **Belum mulai**, **Dikerjakan**, **Selesai**, **Gagal**, dengan jumlah dan teks "Kosong" bila tidak ada.
- Kartu task: titik status, chip nama fitur (terpotong), chip **Fase n**, penanda prioritas (**Utama** oranye, **Penting** kuning, opsional abu), judul, kotak centang pilih.
- Klik kartu → menu ubah status (4 pilihan). Pilih beberapa kartu → bar aksi massal "Ubah status ke…". Drag-and-drop antar kolom: nilai tambah, bukan wajib.
- Perubahan status disimpan via API dan langsung memperbarui progres di Roadmap dan Halaman Fitur.
- Tampilan Daftar: tabel/daftar dengan kolom yang sama, bisa diurutkan.

## 8. Tombol Implemen (gambar 05, 13)
Popup "Mulai implementasi" dengan 3 pilihan:
1. **Download PRD** — file `PRD.md`.
2. **Download ZIP** — `PRD.md` + `features/NN-slug.md` (Tujuan, Selesai bila, sub-fitur, task) + `TASKS.md` (daftar tugas bercentang per fase). Pakai `ZipArchive`; format file fitur mengikuti yang sudah ada di `Documents.php`, ditambah status task terkini.
3. **Prompt AI agent** — modal berisi petunjuk 3 langkah (salin prompt, buka AI agent seperti Claude Code/Cursor, tempel dan kirim) dan kotak kode dengan tombol **Copy** dan perbesar.
   - **Berbeda dari referensi**: PlanKan tidak punya CLI/npx/token. Prompt yang dihasilkan berdiri sendiri: ringkasan project, tech stack, perintah "baca PRD.md dan folder features/ (dari ZIP yang sudah diunduh dan diletakkan di root project)", urutan kerja per fase, aturan "kerjakan satu task per giliran, centang di TASKS.md, jalankan uji sebelum lanjut, tanya bila PRD ambigu".
   - Tambahkan catatan di modal: "Unduh ZIP dulu dan letakkan di folder project sebelum menempel prompt."
   - Jangan pernah menaruh API key atau token apa pun ke dalam prompt.

## 9. Workspace Agent (kanan)
- Keadaan kosong: kartu "Tanya apa saja soal PRD ini." dengan contoh pertanyaan ("Fitur apa yang paling berisiko?", "Di mana data anak disimpan?", "Apa yang harus dibangun lebih dulu?").
- Chat streaming (SSE, `api/agent.php`). Konteks dikirim ke AI: PRD aktif + daftar fitur/sub-fitur + status task + tampilan yang sedang terbuka (fitur terpilih bila ada). Riwayat disimpan di `agent_messages`.
- v1 hanya tanya-jawab dan pesan sistem (mis. hasil "Bikin Task"). Jangan memberi agent kemampuan mengubah data dulu.
- Hemat biaya: batasi riwayat yang dikirim (±10 pesan terakhir), batasi panjang jawaban, kena RateLimiter, tampilkan pesan ramah saat kena batas.

## 10. Database (migrasi aditif; sesuaikan dengan skema yang sudah ada setelah U0)
- `prd_versions(id, project_id, content_md LONGTEXT, source ENUM('ai','manual'), created_at)`
- Fitur dan sub-fitur: pakai tabel yang ada; tambahkan bila belum ada `phase`, `status ENUM('direncanakan','dikerjakan','selesai')`, `tujuan TEXT`, `selesai_bila JSON/TEXT`, `icon`, `sort_order`.
- `tasks(id, project_id, feature_id, sub_feature_id NULL, title, phase TINYINT, priority ENUM('utama','penting','opsional'), status ENUM('belum_mulai','dikerjakan','selesai','gagal') DEFAULT 'belum_mulai', sort_order, created_at, updated_at)`; indeks pada `(project_id, feature_id, status)`.
- `agent_messages(id, project_id, role ENUM('user','assistant','system'), content TEXT, created_at)`.
- Status fitur dihitung dari task (semua belum = direncanakan, ada yang jalan = dikerjakan, semua selesai = selesai); jangan disimpan ganda tanpa sinkronisasi.

## 11. API (JSON, semua POST/GET dengan CSRF)
`stream_prd.php` (SSE), `prd_save.php`, `prd_export.php` (md/zip), `tasks_generate.php`, `tasks_list.php`, `task_update.php` (satu atau banyak), `feature_get.php`, `feature_update.php`, `feature_delete.php`, `agent.php` (SSE), `agent_history.php`, `prompt_agent.php` (teks prompt).

## 12. Tahap pengerjaan
- **U0 — Audit (baca saja, tanpa mengubah kode)**: petakan kode yang ada (langkah 4 dan 5, `Documents.php`, `Prompts.php`, `AiClient.php`, skema DB, JS kanvas). Tulis `docs/rencana-ui.md`: struktur data fitur/sub-fitur sekarang, apa yang bisa dipakai ulang, tabel/kolom yang perlu ditambah, risiko streaming di XAMPP. Berhenti dan tunggu persetujuan.
- **U1 — Rangka workspace**: `workspace.php`, topbar, rail, daftar isi, panel agent (kosong), routing hash, tema; langkah 5 lama mengarah ke sini.
- **U2 — PRD streaming + render**: `AiClient::stream`, SSE, marked/DOMPurify/mermaid, daftar isi live, simpan versi pertama.
- **U3 — Export + edit PRD**: menu export, mode edit, simpan versi, riwayat versi sederhana.
- **U4 — Bikin Task**: migrasi tasks, panggilan AI tunggal + mock, pesan agent, idempoten.
- **U5 — Roadmap pohon**: 4 kolom, panel Perencanaan, panel fitur dengan `n/N`, edit/hapus.
- **U6 — Halaman Fitur + Kanban**: sidebar, sub-fitur, filter, urutan, status massal, sinkron progres.
- **U7 — Implemen**: popup, ZIP, TASKS.md, prompt agent.
- **U8 — Workspace Agent**: chat streaming dengan konteks, riwayat, batas laju.

## 13. Uji penerimaan (jalankan setelah tiap tahap yang relevan, mode mock dan mode nyata)
1. Dari "Lanjutkan", PRD muncul bertahap, daftar isi terisi saat menulis, diagram baru muncul setelah blok selesai.
2. Refresh di tengah streaming tidak merusak data; PRD parsial terlihat dan bisa dilanjutkan.
3. Download .md dan Salin teks menghasilkan isi yang sama dengan yang tampil.
4. Edit lalu Simpan versi → refresh → isi hasil edit yang tampil; versi lama masih ada.
5. Bikin Task dua kali tidak menggandakan task; jumlah di pesan agent sama dengan jumlah di Kanban.
6. Klik node akar/fitur/sub-fitur/task membuka tepat layar yang ditabelkan di bagian 5; tombol Back browser kembali ke tampilan sebelumnya.
7. Ubah status satu task di Kanban → progres di Roadmap dan Halaman Fitur ikut berubah tanpa refresh manual.
8. ZIP berisi `PRD.md`, `TASKS.md`, dan `features/*.md` untuk semua fitur; prompt agent tidak memuat kunci rahasia.
9. Semua endpoint menolak project milik pengguna/sesi lain dan permintaan tanpa CSRF.
10. Dengan `USE_MOCK_AI=true` seluruh alur selesai tanpa satu pun panggilan keluar.

## 14. Di luar cakupan (jangan dikerjakan sekarang)
Wiki, lampiran dan mikrofon di agent, agent yang bisa mengubah data, kolaborasi/berbagi nyata (ikon bagikan boleh dinonaktifkan), CLI sinkronisasi task, dan perubahan apa pun pada Standar Kualitas v1/v1.1.
