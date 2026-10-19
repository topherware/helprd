# Keputusan Penting

Catatan keputusan selama membangun workspace (`alur-ui-ngodingpakeai.md`). Rincian latar belakang ada di `docs/rencana-ui.md`.

## U0 — disetujui sebelum U1

| No | Keputusan | Alasan |
|---|---|---|
| K1 | Streaming PRD memakai prompt JSON yang ada. Server memperbaiki JSON parsial lalu mengirim snapshot markdown hasil `Documents::renderPrd()`. | Prompt tetap sama dan validator Standar Kualitas v1.1 tetap bekerja; renderer markdown hanya satu (PHP). |
| K2 | PRD §8 Keamanan di-stream sebagai fase kedua setelah bagian 1–7. | §8 memang panggilan terpisah di v1.1. |
| K3 | "Bikin Task" = satu panggilan AI. Spesifikasi fitur v1.1 dan audit QUALITY.md dipindah ke alur Implemen → Download ZIP. | Langkah 5 lama diganti workspace; hasil v1.1 tidak boleh hilang. |
| K4 | Dokumen yang tidak cocok lagi dengan peta fitur tidak dihapus, hanya dianggap basi (ditampilkan dengan penanda). | Mengedit atau menghapus fitur di Roadmap tidak boleh menghilangkan PRD. |
| K5 | Status fitur dihitung dari task setelah fitur punya task; `features.status` menjadi cache yang disinkronkan. | Spec meminta status dari task, tanpa memutus editor status langkah 4. |
| K6 | Tampilan, Download, dan ZIP memakai versi terbaru di `prd_versions` (termasuk edit manual). QUALITY.md tetap merujuk versi AI. | Edit manual berupa markdown bebas yang tidak bisa divalidasi ulang. |
| K7 | Nama aplikasi **tetap PlanKu** (permintaan pemilik). Cookie `planku_token` dan database `planku` tidak diubah. | Spec meminta "PlanKan", tetapi pemilik memutuskan tetap PlanKu. |
| K8 | Unduhan memakai `fetch` POST + CSRF lalu disimpan lewat Blob. | Spec mewajibkan CSRF di semua endpoint. |
| K9 | Batas laju agent terpisah dari batas laju dokumen; `AI_MIN_INTERVAL_SECONDS` ditambahkan saat dibutuhkan (U4). | Chat agent tidak boleh menghabiskan kuota pembuatan dokumen. |
| K10 | Library front-end (marked 12.0.2, DOMPurify 3.1.6, mermaid) disalin ke `public/vendor/`. | Bisa dipakai offline di XAMPP. |

## U1

- Spec dan gambar referensi ada di root project (`alur-ui-ngodingpakeai.md`, `ref-ui/`), bukan di `spec/`.
- Migrasi memakai `migrations/NNN_*.sql` + `php migrate.php`, dicatat di tabel `schema_migrations`. `schema.sql` tetap menjadi baseline instalasi awal.
- Kolom spec `phase`, `icon`, `sort_order` memakai kolom yang sudah ada (`fase`, `ikon`, `urutan`); tidak ada rename.
- Langkah 5 lama (`step5.php`, `step5.js`, `api/download.php`) dihapus. Logika pipeline dokumen (`api/generate_document.php`, `Documents`, `Quality/`) dipertahankan untuk U7.
- File contoh format (`rr-design-build-PRD.md`, `sz-n-fashion-upcycled.zip`) dihapus; formatnya sudah tertanam di `Documents.php`.
- Tautan Wiki tidak ditampilkan. Bikin Task, Implemen, Edit, Export, Berbagi, dan input agent tampil nonaktif dengan keterangan sampai tahapnya dibangun.

## U2

- `api/stream_prd.php` memakai SSE lewat **POST** (dibaca dengan `fetch` + `ReadableStream`), bukan `EventSource`, supaya header CSRF dan pola `api_request()` tetap berlaku.
- Snapshot markdown dikirim paling cepat setiap 350 ms dan hanya bila isinya berubah. Bagian PRD yang belum mulai ditulis tidak dirender; diagram di bagian yang masih ditulis diganti `diagram-pending` ("Menggambar diagram…").
- Versi PRD dibuat sejak snapshot pertama (`selesai=0`) dan diperbarui setiap ±2 detik, lalu ditandai `selesai=1`. Dengan begitu refresh di tengah streaming selalu menampilkan hasil sebagian.
- "Lanjutkan / coba lagi" melanjutkan per fase: bila bagian 1–7 sudah tersimpan di `documents`, hanya bagian 8 yang disusun ulang. Melanjutkan JSON setengah jadi tidak dicoba karena tidak andal.
- PRD lama yang dibuat lewat pipeline dokumen (sebelum workspace) otomatis dicatat sebagai versi pertama di `prd_versions` saat workspace dibuka, bila sudah lengkap (punya bagian 8).
- Mermaid 10.9.1 dimuat hanya saat dokumen punya diagram (±3 MB). Hasil render di-cache per kode diagram supaya snapshot berikutnya tidak menggambar ulang. Diagram yang gagal digambar ditampilkan sebagai kode mentah.
- Pembatalan saat klien pergi memakai `CURLOPT_PROGRESSFUNCTION`; `CURLOPT_XFERINFOFUNCTION` tidak tersedia di PHP 8.0 (XAMPP).
- Gemini tidak didukung lagi, jadi `AiClient::stream()` hanya untuk Claude.

## U3

- **Download .md** dan **Salin teks** dibuat di browser dari PRD yang sedang tampil (`Blob`, Clipboard API dengan cadangan `execCommand`). Isinya dijamin sama dengan layar; tidak perlu endpoint. Di Windows, clipboard otomatis mengubah `\n` menjadi `\r\n` (isi tetap sama). `api/prd_export.php` baru dibuat di U7 untuk ZIP.
- Edit memakai textarea monospace tanpa pewarnaan. Simpan versi (tombol atau Ctrl+S) membuat baris baru `prd_versions` bersumber `manual` lewat `api/prd_save.php`. Keluar dari mode edit dengan perubahan belum disimpan meminta konfirmasi; menutup tab juga diperingatkan.
- Riwayat versi (`api/prd_versions.php`): 30 versi terakhir yang lengkap. Versi parsial dari streaming yang terputus disembunyikan. "Pulihkan" menyalin versi lama menjadi versi terbaru; tidak ada versi yang dihapus atau diubah.
- Selama streaming, tombol Edit dan Export nonaktif; streaming tidak bisa dimulai selama mode edit.

## U4

- **Bikin Task dipecah per fase sejak awal** (fase dengan lebih dari `TASK_FEATURES_PER_CALL`=4 fitur dibagi lagi), bukan satu panggilan untuk semua fitur. Spec membolehkan pemecahan per fase bila hasil terlalu besar; dengan proxy yang sering putus, panggilan pendek jauh lebih andal. Jeda antar panggilan `AI_MIN_INTERVAL_SECONDS`.
- Satu request `api/tasks_generate.php` = satu kelompok = satu panggilan AI. Browser memanggil berulang sampai `done` sambil menampilkan "Menyusun task (k/n)…". Kelompok yang sudah punya task dilewati, jadi "Coba lagi" melanjutkan sisanya.
- Input AI = PRD terbaru dari `prd_versions` (termasuk edit manual, K6) + daftar fitur & sub-fitur. Keluaran per fitur: deskripsi, Tujuan, Selesai bila, Tujuan/Selesai bila per sub-fitur, dan 8–15 task (judul perintah, sub-fitur opsional, fase, prioritas). Disimpan ke `features`, `sub_features`, dan `tasks`. Bila spesifikasi fitur v1.1 sudah ada, Tujuan & Selesai bila diambil dari sana.
- Idempoten: menekan Bikin Task lagi meminta konfirmasi "Buat ulang task? Status lama akan hilang."; bila setuju, semua task dihapus dulu. Tidak ada penggandaan.
- Pesan "Selesai! Aku sudah menyusun N fitur, M sub-fitur, dan K task…" disimpan di `agent_messages` (role `system`) dan tampil di panel agent setelah refresh. Setelah selesai, tampilan pindah ke `#/roadmap`.

## U5

- Kanvas peta fitur dipindah ke modul bersama `public/assets/js/canvas.js` (`createCanvas`, `canvasIcon`): pan, zoom, fit, garis bezier, ikon. Langkah 4 (`step4.js`, 300 → 189 baris) dan Roadmap (`roadmap.js`) memakai modul yang sama.
- Pan baru dimulai setelah pointer bergeser > 4 px; sebelumnya `setPointerCapture` langsung saat `pointerdown` membuat klik pada kartu non-tombol (kartu TASKS) tidak pernah sampai.
- Roadmap: 4 kolom (akar → fitur → SUB FITUR → TASKS). Kolom TASKS hanya muncul setelah Bikin Task. Garis akar→fitur berwarna sesuai status; fitur→sub dan sub→task putus-putus. Tampilan awal `fit` dengan zoom minimal 0,55 supaya teks tetap terbaca.
- Panel kiri dikendalikan URL (`#/roadmap?panel=rencana`, `#/roadmap?fitur=ID`), sehingga tombol Back bekerja. Panel berada di samping kanvas (kanvas menyempit), bukan menimpanya; di layar < 900px panel menutupi kanvas.
- Panel Perencanaan menampilkan PRD terbaru dalam mode pratinjau atau kode; diagram mermaid tidak digambar di panel (tampil sebagai kode) supaya ringan.
- API: `feature_get.php` (data roadmap dan nanti halaman fitur; status dihitung dari task lewat `feature_status()`; deskripsi jatuh ke PRD JSON bila belum ada), `feature_update.php` (menggantikan `update_feature.php`; juga dipakai langkah 4; status manual diabaikan bila fitur sudah punya task), `feature_delete.php` (sub-fitur & task ikut terhapus lewat FK; teks PRD tidak diubah).
- Mengubah fase fitur ikut mengubah `tasks.phase` fitur itu.

## U6

- Halaman Fitur (`fitur.js`) dan Kanban (`kanban.js`) adalah modul terpisah seperti `roadmap.js`, dikendalikan router di `workspace.js`. Form edit/hapus fitur dipakai bersama lewat `Roadmap.editForm()` / `Roadmap.removeFeature()`.
- Sinkronisasi progres: setiap perubahan data memanggil `notifyDataChanged()` (event `ws:data`); Roadmap, Halaman Fitur, dan Kanban membuang cache-nya dan memuat ulang saat dibuka berikutnya. Kanban sendiri memperbarui datanya di tempat tanpa memuat ulang.
- `api/task_update.php` mengubah 1–500 task sekaligus (dibatasi `project_id`) dan menyinkronkan `features.status` sebagai cache dari status task (K5).
- `api/tasks_list.php`: filter fitur dan urutan di server (urutan roadmap, terakhir diperbarui, terbaru dibuat, judul A–Z, prioritas tertinggi; hanya nilai dari daftar putih yang masuk SQL). Tampilan Daftar bisa diurutkan lagi dengan klik judul kolom (di browser).
- Pilihan tampilan (Kanban/Daftar) dan urutan diingat per browser (`localStorage`); filter fitur ada di URL (`#/kanban?fitur=ID`) supaya tombol Back bekerja.
- Status sub-fitur di sidebar Halaman Fitur: centang hijau bila semua task sub-fitur itu selesai.
- Drag-and-drop antar kolom belum dibuat (opsional di spec); status diubah lewat menu klik atau aksi massal.

## U7

- **Mengubah K3 sebagian:** Download ZIP dibuat langsung dari database tanpa panggilan AI (cepat, tidak bergantung proxy). Spesifikasi fitur v1.1 + audit QUALITY.md tetap tersedia, tetapi lewat tombol terpisah **Jalankan audit** di popup Implemen (±N+2 panggilan AI, memakai `api/generate_document.php` dengan progres dan bisa dilanjutkan). Setelah audit, ZIP otomatis memuat QUALITY.md dan bagian Keamanan v1.1 per fitur.
- Isi ZIP (`Documents::exportZip`): `PRD.md` = versi terbaru yang lengkap di `prd_versions` (sama dengan Download PRD); `TASKS.md` = semua task bercentang per fase lalu per fitur, dengan status terkini; `features/NN-slug.md` = Tujuan, Selesai bila, sub-fitur, task (bercentang, + progres), dan bagian Keamanan: dari spesifikasi v1.1 bila ada, bila belum dari SEC di PRD §8 yang dipetakan ke fitur. Bila PRD diedit manual setelah audit, QUALITY.md diberi catatan (K6).
- Unduhan lewat `api/prd_export.php` (POST + CSRF) lalu disimpan sebagai Blob (K8).
- `api/prompt_agent.php`: prompt berdiri sendiri (ringkasan, tech stack dari PRD, urutan fitur & file, aturan satu task per giliran, centang di TASKS.md, uji sebelum lanjut, ikuti SEC PRD §8, rahasia hanya di variabel lingkungan, tanya bila ambigu). Tidak ada CLI, token, atau kunci di dalamnya.
- `Documents::zip()` lama (langkah 5) dihapus karena tidak dipakai lagi.

## U8

- `api/agent.php` (SSE lewat POST, event `delta`/`reset`/`done`/`error`). Konteks: PRD aktif terbaru (dipotong di `AGENT_PRD_CHARS`, default 60.000 karakter), fitur + sub-fitur + jumlah task per status, tampilan yang sedang dibuka, fitur terpilih beserta daftar task-nya, dan 10 pesan terakhir. Semua teks pengguna dikirim di dalam tag sebagai data, bukan instruksi.
- Prompt sistem sendiri (`Prompts::AGENT_SYSTEM`, jawaban markdown, bukan JSON): hanya tanya-jawab, tidak bisa mengubah data; bila diminta mengubah, agent menjelaskan caranya di UI. Jawaban ±200 kata, `AGENT_MAX_TOKENS` (default 2000). `ai_stream()` menerima parameter `$system` opsional.
- Pertanyaan dan jawaban disimpan di `agent_messages` hanya setelah jawaban selesai. Bila gagal, tidak ada yang tersimpan; bubble galat menampilkan "Coba lagi" dan teks dikembalikan ke kotak input.
- Koneksi AI putus di tengah jawaban: diulang dari awal (`AI_NET_RETRIES`) dengan event `reset` supaya teks di browser dikosongkan dulu.
- K9: `RateLimiter::check($ip, 'agent')` menghitung hanya baris `endpoint = 'agent'` dengan batas `AGENT_RATE_LIMIT_PER_HOUR` (default 40). Batas dokumen (`RATE_LIMIT_PER_HOUR`) tidak lagi menghitung baris agent. Pesan 429 yang ramah tampil di bubble chat.
- `api/agent_history.php` ({sebelum: id}) memuat 30 pesan lebih lama; tombol "Muat pesan sebelumnya" muncul bila ada.
- Kartu kosong berisi 4 contoh pertanyaan yang bisa diklik. Enter mengirim, Shift+Enter baris baru. Lampiran dan mikrofon tetap nonaktif.
- Mode demo: `MockData::agentReply()` merangkum progres dari database, di-stream per 40 karakter.

## Proxy Claude yang sering memutus koneksi (pilihan 2 dari pemilik)

- Diuji dengan `curl` langsung (tanpa PHP): `api.mwapi.dev` memutus streaming panjang secara acak (`curl exit 56`, `SSL_read: Connection was reset, errno 10054`), pernah setelah 26 detik. Penyebabnya di proxy, bukan aplikasi. Solusi tuntas: API key resmi Anthropic.
- Mitigasi yang dipasang:
  - `AI_EFFORT` (default `low`) dikirim sebagai `output_config.effort`, supaya waktu berpikir lebih singkat dan jawaban panjang lebih cepat selesai.
  - `AI_NET_RETRIES` (default 2): koneksi yang putus (kode `AiClient::NETWORK` = 599) dicoba ulang otomatis, baik di `AiClient::json()` maupun streaming PRD. Pembatalan karena klien pergi tidak dicoba ulang.
  - Saat streaming dicoba ulang, snapshot baru baru dikirim setelah lebih panjang dari yang sudah tampil, supaya teks tidak "mengetik ulang" dari awal.
- Setiap percobaan ulang tetap memakai token; tercatat di `api_logs`.

### PRD dipecah menjadi panggilan pendek (disetujui pemilik)

Mengubah keputusan K1 sebagian: prompt PRD tidak lagi dikirim utuh dalam satu panggilan.
- `Prompts::PRD_PARTS`: bagian 1–7 jadi 4 panggilan (overview+requirements, fitur+user_flow, arsitektur+database, tech_stack). `Prompts::KEAMANAN_PARTS`: bagian 8 jadi 3 panggilan (klasifikasi+prinsip+hak akses, persyaratan+ancaman, pihak ketiga+OWASP+risiko). Persyaratan SEC ditulis sebelum bagian yang merujuk ID SEC.
- Isi aturan dan format JSON tidak berubah; tiap panggilan hanya menerima aturan & format untuk kuncinya, plus JSON bagian yang sudah ditulis sebagai konteks. `Prompts::prd()`/`keamanan()` tanpa `$keys` tetap menghasilkan prompt satu panggilan seperti dulu (dipakai pipeline dokumen).
- Potongan yang selesai disimpan di `prd_parts` (migrasi 002) dan dilewati saat "Lanjutkan"; bila koneksi putus, hanya potongan yang sedang ditulis yang diulang. Tabel dibersihkan setelah fase selesai.
- "Buat ulang PRD" menghapus PRD JSON lama di `documents` dan laporan kualitasnya, supaya "Lanjutkan" sesudahnya tidak kembali ke versi lama. Teks lama tetap ada di `prd_versions`.
- `PartialJson::sanitize()` mengubah baris baru/tab mentah di dalam string JSON menjadi escape sebelum dibaca. Ini menyelesaikan PRD yang selesai 30.066 karakter tetapi ditolak `json_decode` (Syntax error).
- `config.php`: variabel lingkungan proses menimpa `.env` (perilaku dotenv standar), mis. `USE_MOCK_AI=true php -S ...` untuk uji tanpa mengubah `.env`.
- `migrate.php` membuang komentar `--` sebelum memecah per `;` (sebelumnya komentar bertitik koma merusak migrasi 002).
- Proxy juga bisa menahan koneksi tanpa mengirim apa pun (draft PRD tidak bertambah selama 5+ menit). `CURLOPT_LOW_SPEED_LIMIT=1` + `CURLOPT_LOW_SPEED_TIME=AI_STALL_SECONDS` (default 60) membuat koneksi diam dianggap error jaringan (599) lalu dicoba ulang.

## Standar Kualitas v1.2 (B1–B7, disetujui pemilik: "lakukan semua sesuai rekomendasi")

Rancangan lengkap di `docs/rencana-v1.2.md`. Keputusan D1–D12 di sana diterapkan apa adanya; ringkasnya:
- **D1/D2**: spec dan gambar dibaca dari root (`standar-kualitas-v1.2-siap-bangun.md`, `ref-input/`). Bagian yang diasumsikan spec tetapi belum ada dibangun: `Brief.php`, `QualityReport::evaluate`, `tools/evaluate.php`, `benchmark/`, `src/Quality/Validators/`.
- **D3**: validator dimensi lama dibuat ringan dan deterministik (`Validators/Dimensi.php`). Keterbacaan belum dinilai otomatis; dikeluarkan dan bobot dinormalisasi (ditulis di QUALITY.md).
- **D4/D5**: Pass 1 = 8 potongan pendek (`Prompts::PRD_PARTS`): profil dulu (bendera, stack terkunci, peran), lalu bab 1–2, 3–5, 6–7, 8, 9–10, 11, 12–13. Lebih banyak dari 5 di spec karena proxy sering memutus jawaban panjang; tiap potongan disimpan di `prd_parts` (`v12:<potongan>`) dan dilewati saat dilanjutkan. Tiap potongan hanya menerima bagian yang relevan sebagai konteks (`Prompts::DEPENDS`).
- **D6**: validator membaca PRD JSON; suntingan manual markdown tidak dinilai ulang (catatan di QUALITY.md, sama dengan K6).
- **D7**: spesifikasi per fitur v1.1 (1 panggilan AI per fitur) dihapus. Bagian Keamanan per fitur = maks. 3 rujukan SEC dari bab 8 (`Documents::featureSecurity`), task keamanan = task `[M3]`. `api/generate_document.php` diganti `api/quality_audit.php` (langkah `nilai` / `perbaiki`, dipanggil berulang oleh browser, maks. 2 putaran perbaikan).
- **D8**: kolom `tasks.milestone` (migrasi 003). Awalan `[M1]` dirender saat ekspor; Kanban tidak diubah.
- **D9**: "Standar PlanKu" = Next.js + Auth.js + Neon PostgreSQL + Drizzle + Tailwind + shadcn/ui (tanpa nama merek dari gambar).
- **D10**: "Bebas" dikunci ke salah satu id di `stacks.json`; "Tulis Sendiri" memakai profil yang dideklarasikan AI (nilai di luar daftar sah diabaikan). PRD lama tanpa profil: stack ditebak dari kata kunci Tech Stack (`Brief::guessStack`).
- **D11/D12**: sidik PRD (`Documents::hash`) kini memuat isian Bagian B; PRD lama ditandai basi dengan banner yang menyarankan "Buat ulang PRD".
- Step 2 (Bagian B): hanya perubahan stack atau mode hanya-UI yang menghapus pertanyaan & peta fitur; gaya desain, cara kerja AI, dan catatan cukup menandai PRD basi. Kolom lama `tech_mode`/`tech_stack_pilihan` tetap diisi supaya prompt pertanyaan & peta fitur tidak berubah.
- Catatan tambahan & teks "Tulis Sendiri": `strip_tags` + buang karakter kontrol + batas panjang di server (`Validator::brief`); di prompt dibungkus `<catatan_pengguna>`/`<gaya_kustom>`/`<stack_kustom>` dengan tanda kurung sudut dinetralkan (`Brief::dataTag`), dan `Prompts::SYSTEM` menyatakannya sebagai data.
- Keamanan v1.2: kontrol pemicu dibagi `SecurityTriggers::MVP` dan Hardening. Validator keamanan yang tersisa: SecBagianLengkap, SecPemicuTerpenuhi, SecMatriksAkses, SecKeterlacakan, SecRahasia, SecDataSensitifTerlindungi, SecOwasp, SecLogBersih. Klaim berlebih pindah ke Kejujuran, konsistensi RLS ke KecocokanStack (membaca `stacks.json`), pihak ketiga ke V16.
- Skor: 100 − 25 (K) − 10 (M) − 3 (m) per dimensi, dibobot sesuai spec. Penalti v1.1 (20/5) diganti.
- Mode tiruan menghasilkan PRD v1.2 generik dari Brief + bendera + stack (`MockData::prd`) yang lolos semua validator untuk 3 kasus benchmark; dipakai juga oleh tes (`tests/_lib.php`) lewat `Pipeline::mockPrd`.
- `api/prd_versions.php` mendapat aksi `latest` supaya workspace memuat ulang PRD setelah audit memperbaiki bab.
- Uji AI sungguhan (claude-sonnet-5 lewat proxy) menemukan dan memperbaiki: `PartialJson::sanitize` kini juga memperbaiki kutip yang lupa di-escape, escape tidak sah, dan koma sebelum `}`/`]` (`tests/json_repair_test.php`); JSON potongan PRD yang tetap rusak ditulis ulang sekali (`AiClient::BAD_JSON`). False positive validator: ID dataset bergaris bawah (`DUMMY-ANAK_TERSIMPAN`) kini sah (V14); rate-limit serverless yang menyebut adapter tiruan berbasis memori **dan** penyimpanan terpusat untuk produksi lolos V16 (sesuai spec 5.5). Pemicu keamanan "Pembayaran" tidak lagi dipicu kata "checkout"/"bayar" saja (pesanan tanpa bayar online tidak dituntut tokenisasi/webhook).

## Sidebar "Semua Plan" (permintaan pemilik)

- Halaman awal punya sidebar riwayat plan seperti ngodingpakeai.com/plan: cari, urutkan (terbaru/terlama/A–Z), folder per project berisi "Plan 1", tombol "Buat plan baru", buka/tutup sidebar (diingat di localStorage; drawer di layar < 900 px), dan hapus plan dengan konfirmasi.
- Tanpa login: project dikelompokkan lewat cookie `planku_owner` (acak, httpOnly, 1 tahun) dan kolom `projects.owner_token` (migrasi 004). Membuka plan (`public/open.php`, GET + CSRF) hanya untuk project milik owner yang sama, lalu mengganti cookie project aktif. Hapus (`api/plan_delete.php`, POST + CSRF) juga dicek kepemilikannya; data turunan ikut terhapus lewat ON DELETE CASCADE, `api_logs` tetap (SET NULL) untuk batas laju.
- Project lama (sebelum migrasi 004) tidak punya pemilik. Agar tidak "dicuri" browser lain, project lama hanya diklaim browser yang cookie project-nya menunjuk project tanpa pemilik; di XAMPP lokal (akses 127.0.0.1/::1) browser itu sekaligus mengklaim semua project lama tanpa pemilik.

## Standar Kualitas v1.2.1 (Paritas, G1–G6)

Pemilik meminta G1–G6 dikerjakan berurutan tanpa konfirmasi. Rancangan di `docs/rencana-v1.2.1.md`, progres di `docs/progress-paritas.md`.
- **Cadangan**: proyek belum memakai git; dibuat `git init` + commit snapshot (`.env` dan `logs/` tetap diabaikan lewat `.gitignore`). Alasan: aturan "commit atau salinan cadangan sebelum mengubah kode"; git paling mudah dipulihkan.
- **Spec di root, bukan `spec/`** (sama seperti v1.2): `standar-kualitas-v1.2.1-paritas.md` dan `ref-prd/` dibaca dari root.
- **Validator paritas membaca paket dokumen markdown** (bukan hanya JSON) supaya satu implementasi P1–P10 bisa menilai PRD acuan dan PRD PlanKu. PRD PlanKu dirakit menjadi berkas yang sama dengan ZIP sebelum dinilai; data terstruktur (`asal`, invarian, rencana tugas) dipakai lebih dulu bila ada.
- **Brief fixture**: input asli kasus salon diambil dari project #34 di database (ide, 5 jawaban, catatan, stack Bebas, cara kerja per Fase) dan disimpan di `tests/fixtures/paritas/salon.brief.json`. Kata salon/kapster/booking hanya ada di fixture tes, tidak di kode validator atau prompt.
- **Bab 13 dipisah dari bab 12** (potongan PRD ke-9) karena rencana tugas terkoordinasi membuat jawaban panjang; alasan sama dengan D4 v1.2 (proxy memutus jawaban panjang).
- **Cek batas (P8) dipindah dari V17/V19** ke validator Batas (kritis) agar satu pelanggaran tidak dihitung dua kali.
- **G2 — fitur usulan**: disimpan sebagai baris `features` dengan `disertakan = 0` (bukan dihapus/tabel baru) supaya pengguna bisa menyalakannya dari peta fitur. `project_features()` bawaan hanya mengembalikan fitur yang disertakan (PRD, task, sidik, ZIP); peta fitur langkah 4 memakai daftar lengkap. Roadmap workspace tidak menampilkan fitur usulan nonaktif (alur UI workspace tidak diubah, sesuai aturan 9).
- **G2 — peran tetap daftar string**; asal peran disimpan terpisah di `profil.peran_asal` agar validator v1.2 tidak perlu diubah.
- **G2 — invarian**: gabungan deklarasi AI (potongan profil) dan pola kata di `Triggers::INVARIAN` (rentang, kuota, stok, unik); jaring kata kunci memastikan invarian yang jelas di input tidak terlewat walau AI lupa menyatakannya.
- **G3 — rencana pengerjaan sebagai potongan sendiri (`p8`)** dengan ketergantungan pada profil, fitur, database, keamanan, rute; dibatasi ≤ 10 tugas per milestone dan ≤ 30 total supaya jawaban AI tidak terputus.
- **G3 — tabel pengaturan dari angka input**: angka di input pengguna (`Triggers::angka`, mis. jam, menit, persen) menjadi kolom tabel tunggal `pengaturan` dengan nilai bawaan, bukan konstanta di kode; templat pesan ikut di tabel itu bila ada penjadwal.
- **G3 — akses tamu**: bila bendera tamu aktif, PRD menambah FR "cek status dengan kode + kontak", rute `/cek-status`, dan SEC kode acak + penyamaran data. Tanpa akun, kode rahasia paling sederhana dan tidak butuh tabel baru selain kolom `kode`.
- **G3 — resep invarian per DB** di `stacks.json` (PostgreSQL: exclusion constraint `btree_gist` + `tstzrange`; MySQL/SQLite/D1: transaksi atau `INSERT … SELECT … WHERE NOT EXISTS`; stok: `CHECK (stok >= 0)` + update bersyarat). Prompt hanya mengutip resep stack terkunci, tanpa kata domain.
- **G3 — task ≤ 8 per fitur** dan tiap task wajib `koordinasi` ke ID rencana bab 13; task tanpa rujukan sah dibiarkan kosong (tidak dibuang) agar data pengguna tidak hilang.
- **G4 — satu pembaca markdown untuk semua PRD**: bagian dicari lewat judul (bukan nomor bab) karena Eduwebmu dan PlanKu memakai penomoran berbeda; PRD PlanKu dirakit dengan renderer ZIP lalu dinilai dengan validator yang sama. Data terstruktur (rencana tugas, task + koordinasi, asal fitur, peran_asal) dipakai lebih dulu bila ada.
- **G4 — asal tanpa field `asal` (PRD acuan, data lama)**: heuristik umum `Telusur` (varian stem bahasa Indonesia + kelompok sinonim umum, ≥ 60% kata inti berdekatan dalam satu baris masukan). Hanya jawaban pengguna yang dipakai, bukan teks pertanyaan buatan AI. Peran pengelola sah bila masukan menyebut pengelola; peran lain harus muncul sebagai pelaku (langsung diikuti kata kerja). Halaman pelengkap (tentang, kontak, testimoni, …) hanya minor.
- **G4 — fixture benchmark `benchmark/kasus.json` diberi `asal`**: fitur yang terlacak = `ide`, sisanya `keputusan` (fitur itu dipertahankan pengguna di peta fitur v1.2). Tanpa ini fixture lama selalu gagal P2 walau PRD-nya benar. Untuk kasus salon (fixture asli project #34) tes memakai simulasi peta fitur v1.2.1: fitur tak terlacak menjadi usulan nonaktif, sesuai aturan G2.
- **G4 — status aturan**: lulus (tanpa temuan), sebagian (hanya minor), gagal (ada mayor/kritis), tidak berlaku (pemicu mati). Kelengkapan bagian 3 dicatat terpisah sebagai minor (`PK`), bukan status P1–P10.
- **G4 — P8 menggantikan cek batas V17/V19**: V17 tetap memeriksa MVP ≤ 12 dan ambang mikro; V19 tetap memeriksa judul ganda & rujukan Master Prompt. Batas Hardening ≤ 8, task ≤ 8/fitur dan ≤ 8 × fitur, rencana ≤ 30 dan ≤ 10/milestone kini kritis di P8.
- **G4 — P9 gaya desain** hanya diperiksa untuk PRD PlanKu (label gaya pilihan harus muncul di design system); PRD acuan tidak menyimpan pilihan gaya Bagian B sehingga tidak bisa diperiksa.
- **G4 — kata "booking" dihapus dari regex `Triggers` dan `V15SkemaKode`** (aturan 5: tidak boleh ada kata khusus domain di kode validator/prompt); "reservasi", "pesan", "jadwal" tetap menangkap konsep yang sama.
- **G5 — skor**: dimensi baru `paritas` (bobot 10) di samping Kesiapan Bangun (kini 15); penalti sama (K 25, M 10, m 3). Syarat lulus tetap: skor ≥ 85, tanpa temuan kritis (P1, P3, P8, P9, dan peran tanpa asal di P2 kritis), Keamanan ≥ 80, Kesiapan Bangun ≥ 80. Versi standar naik ke 1.2.1 sehingga laporan lama dianggap basi dan dinilai ulang.
- **G5 — Pass 5 batas task tanpa AI**: task bertetangga dengan milestone sama (rujukan Task Mx.y sama lebih dulu) digabung "A; b" selama judul ≤ 200 karakter (batas kolom `tasks.title`), sampai ≤ 8 per fitur. Hanya untuk fitur yang semua task-nya belum dikerjakan; teks task tidak dibuang (digabung), status dan task yang sudah dikerjakan tidak disentuh. Paling aman dan tanpa biaya panggilan AI dibanding menulis ulang task.
- **G5 — temuan asal fitur/peran (P2) tidak memicu penulisan ulang PRD** (bab kosong): asal diperbaiki di peta fitur (lencana Usulan), bukan di teks PRD; menulis ulang bab 3 tidak bisa menghapus fitur dari peta.
- **G5 — fixture `benchmark/mock-*.json` dibuat ulang** oleh `tools/benchmark.php` (keluaran mock sesudah G3); `benchmark/ai-katering.json` (keluaran AI lama, sebelum v1.2.1) sengaja tidak diubah sebagai pembanding "sebelum".
- **G6 — penegakan deterministik `Pipeline::finalize`**: uji AI sungguhan menunjukkan AI melanggar aturan prompt (usulan masuk rute/milestone, 38 tugas rencana, Master Prompt tanpa pertanyaan kesiapan). Diputuskan menegakkan setelah perakitan PRD dan setelah tiap perbaikan Pass 5: rute/navigasi/cakupan/rencana yang hanya melayani usulan dibuang, rencana digabung (isi berkas/FR/tabel disatukan, bukan dibuang) sampai ≤ 10 per milestone dan ≤ 30, dan butir gerbang yang belum ada ditambahkan ke Master Prompt. Lebih andal daripada mengandalkan prompt saja, tanpa panggilan AI tambahan.
- **G6 — pemicu keamanan "Pembayaran"** tidak lagi aktif oleh kalimat pembayaran luring ("dibayar langsung di tempat", "tunai", "tanpa pembayaran online").
- **G6 — uji AI sungguhan dibatasi satu project** (22 panggilan). Pass 5 yang gagal karena galat layanan tidak diulang demi kuota; dicatat sebagai belum selesai.

## Redesain tampilan terang (permintaan pemilik)

- **Tema**: latar putih, aksen oranye. Gaya Minimalism & Swiss dari skill ui-ux-pro-max (Inter, ruang lega, bayangan halus); palet skill (ungu) diganti sesuai permintaan. Tombol memakai `#CF470B` (kontras teks putih 4.61:1, lulus AA); `#EA580C` hanya untuk aksen/ikon, `#C2410C` untuk teks oranye. Semua warna lewat token `:root`; warna gelap yang di-hardcode dihapus, tema mermaid ikut terang.
- **Fungsi tidak diubah**: tidak ada tombol yang ditambah atau dihapus. Contoh ide di halaman awal dipindah ke bawah kotak ide sebagai daftar berikon. Glyph teks (×, +, −) diganti ikon SVG supaya ukurannya seragam.
- **Halaman awal**: kanvas titik pada cincin konsentris; posisi berdenyut seperti ombak, opasitas hilang-timbul, dan titik menyibak menjauhi kursor. Dekoratif (`aria-hidden`, tanpa event pointer), berhenti saat tab tersembunyi, statis bila `prefers-reduced-motion`.
- **Tanpa alert/confirm bawaan**: `ui.confirm()` / `ui.notify()` (dialog modal, Promise) menggantikan 4 `confirm` dan 3 `alert`. Aksi hapus memakai nada bahaya dengan fokus awal di "Batal".
- **Dropdown kustom**: semua `<select>` (termasuk yang dibuat lewat JS) otomatis diberi tombol + listbox kustom. `<select>` asli tetap ada sebagai sumber nilai, form, dan event `change`, sehingga kode lama tidak berubah. Mendukung keyboard (panah, Home/End, Enter, Esc, ketik huruf awal), ARIA listbox, dan posisi naik/turun otomatis.
- **Checkbox kustom** lewat CSS `appearance: none`, elemen dan perilaku tetap sama.

## Pertanyaan dinamis, urutan langkah, mode gelap (permintaan pemilik)

- **Pertanyaan berbasis celah**: `Triggers::celah()` memeriksa ide dan hanya menanyakan yang belum jelas (pengguna, aturan, akun, pengelola, angka, notifikasi, luring, pembayaran, prioritas). Jumlahnya 3–8; ide yang rinci mendapat lebih sedikit pertanyaan. Batas bawah tetap 3 (bukan 2) karena validator pertanyaan sudah mensyaratkan minimal 3. Label celah disimpan di kolom baru `questions.celah` (migrasi aditif 006) dan ikut ke prompt PRD. Jawaban "(dilewati)" pada celah penting wajib menjadi pertanyaan terbuka (OQ), tidak ditebak AI.
- **Urutan langkah**: Ide → Pertanyaan → Teknologi → Peta Fitur → Workspace. Nama berkas tetap (`step3.php` = Pertanyaan, `step2.php` = Teknologi) supaya tautan lama tidak rusak; nilai status lama tetap dipakai: `teknologi` = boleh menjawab, `pertanyaan` = boleh memilih teknologi. Mengganti stack kini hanya membuat ulang peta fitur; jawaban pertanyaan tidak lagi dihapus.
- **Tombol konsisten di kanan atas**: tombol aksi halaman (Kembali/Lanjut) dipindah ke kepala halaman bagian kanan, bersama toggle mode gelap; header Peta Fitur dikembalikan ke flex (sebelumnya tombolnya jatuh ke kolom tengah grid). Toggle selalu paling kanan.
- **Mode gelap**: token warna di `:root[data-theme="dark"]`; pilihan disimpan di localStorage dan dibaca di `<head>` supaya tidak berkedip. Bawaan tetap terang (putih + oranye).

## Bahasa antarmuka, saklar tema, grid kanvas (permintaan pemilik)

- **Bahasa**: antarmuka bawaan bahasa Inggris, bisa diganti ke Indonesia lewat pilihan EN/ID di kanan atas (cookie `planku_lang`). Diterapkan sebagai kamus di sisi browser (`src/lang/en.php` → `I18N` di `app.js`): teks UI dan atribut yang cocok persis diterjemahkan, termasuk yang dibuat JS belakangan (MutationObserver), dengan pola regex untuk teks berangka/bernama. Dipilih supaya tidak perlu menulis ulang semua string di 12 berkas JS dan 6 halaman PHP; teks sumber tetap bahasa Indonesia sehingga mode ID tidak berubah. Konten pengguna dan buatan AI (PRD, pertanyaan, peta fitur, task, chat agent) tidak diterjemahkan; prompt AI tetap berbahasa Indonesia karena validator kualitas memakai heuristik bahasa Indonesia.
- **Saklar tema** berbentuk pil dengan kenop matahari/bulan (`role="switch"`), menggantikan tombol ikon.
- **Grid kanvas** peta fitur & roadmap: garis kecil tiap 20px dan garis besar tiap 100px, ikut pan & zoom seperti editor diagram; versi terang dan gelap lewat token.
- **Audit di popup Implemen dihapus** dari antarmuka; endpoint `api/quality_audit.php` dan validatornya tetap ada (tidak menghapus fungsi yang sudah teruji).

## Peta Fitur = editor, Roadmap = tampilan final (permintaan pemilik, opsi B)

- **Peta Fitur (langkah 4) tempat mengedit**: tambah fitur (`api/feature_create.php`, asal `keputusan`), ubah nama/fase/sub fitur (sub fitur disinkronkan menurut nama: yang tetap dipertahankan beserta task-nya, yang baru ditambah, yang hilang dihapus), hapus fitur, dan nyalakan usulan AI. Pilihan status dihapus dari editor karena status kini dihitung dari task.
- **Tombol utama mengikuti status PRD** (dibandingkan lewat sidik peta + Teknologi, `Documents::hash`): "Buat PRD" (belum ada) → "Buka PRD" (sesuai) → "Buat ulang PRD" (peta/Teknologi berubah). "Buat ulang" meminta konfirmasi lalu membuka `workspace.php?regen=1`, yang langsung menyusun ulang PRD; versi lama tetap di riwayat.
- **Roadmap & halaman fitur di workspace hanya tampilan**: tombol edit/hapus diganti satu tombol "Ubah di Peta Fitur", supaya hanya ada satu tempat mengedit dan PRD selalu dibuat dari peta yang sama.
