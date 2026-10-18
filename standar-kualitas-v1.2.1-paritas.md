# Standar Kualitas PlanKu v1.2.1: Paritas dengan Eduwebmu

Melengkapi `spec/standar-kualitas-v1.2-siap-bangun.md`. Tujuan: PRD apa pun yang dihasilkan PlanKu, untuk jenis aplikasi apa pun, kualitasnya **minimal setara** dengan PRD Eduwebmu pada kasus booking salon, dan tetap mempertahankan kelebihan PlanKu (file per fitur, `CLAUDE.md`, `PROGRESS.md`, keterlacakan FR/NFR).
Bahan acuan di `spec/ref-prd/`:
- `eduwebmu-salonbook.md`: tolok ukur kualitas.
- `planku-bookkapster/`: hasil PlanKu setelah v1.2 (sudah baik, ada celah di bagian 1).
- `ngodingpakeai/`: hasil pembanding paling dasar.

## 0. Aturan kerja
- Satu tahap (G1–G6) per giliran; laporkan lalu berhenti. Catat keputusan ke `docs/keputusan.md`.
- Semua aturan **tidak boleh khusus salon**. Tidak boleh ada kata "salon", "kapster", "booking", dsb. di kode validator atau prompt generator. Contoh dari ref-prd hanya untuk tes.
- Aturan hanya berlaku bila pemicunya aktif (lihat `Triggers`), agar app tanpa konsep itu tidak dipaksa.
- Jangan ubah alur UI workspace. Mode `USE_MOCK_AI=true` tetap harus lulus semua validator baru.
- Jangan menulis rahasia asli ke berkas mana pun.

## 1. Temuan audit (dasar aturan)
| Temuan | Contoh | Aturan |
|---|---|---|
| Aturan "tidak boleh bentrok" hanya dijaga pada titik awal, bukan rentang waktu | `unique(kapster_id, mulai_pada)` tidak mencegah 10:00–10:30 dan 10:15–10:45 | P1 |
| Fitur muncul tanpa asal dari input pengguna | Dashboard pendapatan, Pencarian Jadwal, Riwayat Pelanggan, peran kapster | P2 |
| Data milik pengguna bisa dilihat hanya dengan nomor kontak | `/riwayat` berdasarkan nomor WA | P3 |
| Tugas hanya berupa judul; tidak ada berkas, fungsi, atau kriteria selesai | Eduwebmu menulis berkas dan perilaku tiap tugas | P4 |
| Detail teknis stack dangkal (tanpa versi, zona waktu, konfigurasi cron, validasi) | Eduwebmu: Next.js 15, Zod, `date-fns-tz`, `vercel.json` cron tiap 10 menit | P5 |
| Tidak ada syarat SEO/performa untuk halaman publik | Eduwebmu punya SEO, OG, sitemap, caching | P6 |
| Angka dari input (3 jam, 3 hari, denda) tidak dipetakan ke pengaturan yang bisa diubah | Eduwebmu punya `salon_settings` | P7 |
| Batas yang ditulis di spec tidak ditegakkan | hardening 11 (batas 8); tugas ±137 (batas 120) | P8 |
| Pilihan stack pengguna tidak dikunci | Pengguna memilih satu stack, PRD memakai stack lain | P9 |
| Master Prompt belum menuntut ringkasan pemahaman dan izin antarfase secara eksplisit | Eduwebmu: ringkas ≤10 poin, tanya kesiapan, "hanya mulai setelah izin" | P10 |

## 2. Aturan paritas (P1–P10)

### P1. Invarian bisnis dijaga di database, termasuk rentang
- Dari Brief, catatan, dan keputusan, ekstrak **invarian** (tidak boleh dobel/bentrok, kuota maksimal, stok tidak negatif, nilai unik, batas durasi). Daftar kata pemicu dikelola di `Triggers`, ditambah daftar invarian yang dideklarasikan AI pada Pass 1 (JSON terstruktur).
- Bab 6 wajib memuat untuk tiap invarian: mekanisme di database sesuai stack + strategi konkurensi + tugas uji balapan (dua permintaan bersamaan).
- Khusus **tidak boleh tumpang tindih rentang waktu/jumlah**: constraint harus mencakup rentang (mulai dan selesai), bukan hanya titik mulai. Resep per stack di `stacks.json`:
  - PostgreSQL/Supabase/Neon: exclusion constraint (`btree_gist`, rentang waktu) + transaksi, penguncian baris sumber daya bila perlu.
  - MySQL/MariaDB: transaksi + `SELECT ... FOR UPDATE` + kueri tumpang tindih sebelum simpan (tanpa exclusion).
  - SQLite: transaksi `BEGIN IMMEDIATE` + kueri tumpang tindih.
  - Dokumen/NoSQL: dokumen slot unik per satuan waktu, atau operasi atomik bersyarat.
- Validator **Invarian** (kritis): setiap invarian punya mekanisme yang cocok dengan resep stack; constraint pada rentang tidak boleh hanya `unique(sumber_daya, waktu_mulai)`; ada tugas uji konkurensi.

### P2. Penjaga lingkup (tidak ada fitur tanpa asal)
- Setiap fitur, sub-fitur, entitas, dan peran punya **asal**: `ide`, `jawaban`, `keputusan`, `catatan`, atau `usulan_ai`. Field `asal` ada di skema JSON peta fitur dan di Brief.
- Fitur `usulan_ai` **tidak** masuk milestone inti. Letakkan di bab 2 bagian **Usulan Tambahan (belum dijadwalkan)** dengan satu kalimat alasan, maksimal 3 butir. Pengguna bisa mengubahnya menjadi fitur nyata.
- Entitas atau peran baru tanpa asal (mis. peran baru, akun baru) dilarang di skema dan bab hak akses.
- Di peta fitur (UI): beri lencana kecil **Usulan** pada node `usulan_ai` dan nonaktifkan secara default; pengguna menyalakannya untuk memasukkan ke PRD. Jangan ubah alur lain.
- Validator **Lingkup** (mayor; kritis bila peran atau tabel baru tanpa asal): tiap fitur terlacak ke input; tidak ada fitur `usulan_ai` di milestone.

### P3. Data milik pengguna butuh bukti kepemilikan
- Jika ada alur tanpa akun (`hasGuestFlow`), pencarian data lewat satu pengenal publik (nomor HP, email, nama) **dilarang**. Wajib pasangan pengenal + kode rahasia yang diterbitkan saat transaksi.
- Kontak pelanggan ditampilkan tersamar di mana pun selain panel admin.
- Validator **KepemilikanData** (kritis): tidak ada rute atau fitur "lihat riwayat/data saya" yang hanya memakai satu pengenal.

### P4. Tugas yang spesifik dan bisa dikerjakan
- Tambahkan **Rencana Pengerjaan Terkoordinasi** di bab 13: tugas bernomor `Task M<n>.<k>` yang gemuk (maksimal 10 per milestone, total ≤ 30). Tiap tugas berisi: judul, berkas/folder yang dibuat atau diubah, perilaku inti (fungsi, rute, tabel), data dummy yang dipakai (ID dataset), dan **Selesai bila** satu kalimat.
- Tugas M1 menyebut halaman/rute dan dataset; tugas M2 menyebut berkas, fungsi/aksi, tabel; tugas M3 menyebut aturan SEC dan skenario uji.
- Daftar centang di `features/*.md` tetap ada tetapi dibatasi **maksimal 8 per fitur** dan **8 × jumlah fitur** total, tiap butir merujuk `Task M<n>.<k>` yang menaunginya.
- Validator **KekhususanTugas** (mayor): tiap tugas terkoordinasi mengandung minimal satu jalur berkas atau nama modul, kriteria selesai, dan rujukan dataset (M1); semua butir fitur terpetakan ke tugas terkoordinasi.

### P5. Kedalaman teknis stack
Bab 7 wajib berisi, sesuai stack terpilih: versi mayor paket inti; strategi autentikasi (jenis sesi, hashing); alat ORM dan migrasi; pustaka validasi input; **kebijakan zona waktu** bila ada data waktu (simpan UTC, tampilkan zona lokal pengguna, pustaka yang dipakai); konfigurasi penjadwal konkret bila ada pekerjaan terjadwal (berkas konfigurasi, jalur endpoint, interval, perlindungan rahasia, idempotensi agar tidak mengirim ganda); alat uji; deployment. Dilarang frasa "mis. A / B" sebagai keputusan akhir.
Validator **KedalamanStack** (mayor): versi ada, kebijakan zona waktu ada bila `hasTimeFields`, konfigurasi cron ada bila `hasScheduler`, pustaka validasi ada.

### P6. SEO dan performa untuk halaman publik
Bila `hasPublicPages`: NFR wajib memuat judul/meta dinamis, tag sosial, sitemap/robots, penandaan `noindex` untuk halaman privat/admin, optimasi gambar, dan strategi cache/revalidasi data publik. Ringkas, maksimal 8 butir; bukan bagian aturan keamanan MVP.
Validator **PublikSEO** (minor).

### P7. Angka dari input menjadi pengaturan yang bisa diubah
Setiap nilai yang disebut pengguna dan bisa berubah (jam sebelum pengingat, batas hari, denda, jam operasional, nomor kontak, template pesan) dipetakan ke entri **Konfigurasi** (di bab 6 sebagai tabel/baris pengaturan, atau di bab 12 sebagai variabel) dengan nilai bawaan dan siapa yang boleh mengubah.
Validator **Konfigurasi** (mayor): angka dari Brief/catatan muncul sebagai pengaturan beserta nilai bawaannya; template pesan disimpan sebagai pengaturan, bukan hanya di kode.

### P8. Batas harus benar-benar ditegakkan
Validator V17 dan V19 dari v1.2 harus menggagalkan, bukan sekadar memperingatkan: Hardening ≤ 8 butir (tanpa duplikat aturan MVP), tugas per fitur ≤ 8 dan total ≤ 8 × fitur, tugas terkoordinasi ≤ 30. Pass 5 memperbaiki dengan menggabung/memangkas, bukan menambah.

### P9. Pilihan stack pengguna dikunci
Pilihan stack dan gaya desain dari Bagian B wajib dipakai apa adanya di bab 7, 9, `CLAUDE.md`, `.env.example`, dan skema. "Bebas (Auto-Pilot)" diputuskan sekali dan dicatat. Validator **KunciPilihan** (kritis): paket, ORM, auth, dan penyedia di PRD cocok dengan `stacks.json` untuk pilihan itu; satu stack saja di seluruh dokumen.

### P10. Master Prompt dengan gerbang izin
Blok Master Prompt di bab 13 wajib berisi: perintah membaca berkas yang benar; **ringkasan pemahaman maksimal 10 poin** sebelum mulai; rencana berkas untuk milestone pertama; pertanyaan kesiapan; aturan "milestone berikutnya hanya dimulai setelah izin"; larangan mengarang data di luar bab 11; kepatuhan pada skema, rute, hak akses, aturan bisnis, dan design system. Sesuaikan gerbang izin dengan Cara Kerja AI (per Task / per Fase / Sekaligus).
Validator **MasterPrompt** (mayor).

## 3. Kelengkapan lain yang harus ada (cek, bukan fitur baru)
- **Cakupan admin**: tiap entitas yang dibuat oleh alur publik punya jalur kelola (daftar, detail, ubah status) untuk peran pengelola; bila Brief menyebut kanal luring (datang langsung, telepon), ada pencatatan manual oleh pengelola (`hasOfflineChannel`) memakai aturan slot yang sama.
- **Contoh microcopy**: judul utama, CTA, pesan sukses, pesan gagal/penuh, keadaan kosong, dan template pesan, semuanya dalam bahasa Indonesia, tanpa "Lorem ipsum".
- **Benih data**: tugas M2 pertama membuat `db/seed.ts` dari dataset bab 11.
- **Keterlacakan**: setiap FR terhubung ke rute, tabel, dan tugas; tabel keterlacakan menampilkan tiga kolom itu.

## 4. Alat paritas
- `tools/parity.php <folder-prd>` menjalankan P1–P10 dan mencetak tabel lulus/gagal per aturan beserta temuan dan bab terkait. Memakai `Quality::evaluate` yang sama.
- Skor Kesiapan Bangun (bobot 25 di v1.2) dibagi ulang: V12–V19 total 15 + P1–P10 total 10. Syarat lulus tambahan: **semua validator kritis lulus**.

## 5. Tes
1. **Fixture eduwebmu-salonbook**: P1, P3, P5, P7, P10 lulus; P2 lulus sebagian (halaman Tentang/Kontak/Testimoni tercatat sebagai usulan); aturan khusus PlanKu (file per fitur, ZIP) ditandai tidak berlaku karena formatnya satu dokumen. Pastikan alat tidak salah menggagalkan hal yang memang benar.
2. **Fixture planku-bookkapster**: harus gagal pada P1 (constraint titik), P2 (Dashboard Pemilik, Pencarian Jadwal, Riwayat Pelanggan, peran kapster), P3 (`/riwayat` via nomor WA), P8 (hardening 11, tugas ±137), dan mungkin P9 bila stack berbeda dari pilihan.
3. **Fixture ngodingpakeai**: gagal pada sebagian besar P4–P7 dan P10.
4. **Uji mutasi**: ganti constraint rentang menjadi `unique(sumber_daya, mulai)` → P1 gagal; hapus uji konkurensi → P1 gagal; tambah fitur tanpa asal → P2 gagal; tambah rute riwayat hanya dengan nomor → P3 gagal; hapus berkas dari tugas → P4 gagal; hapus kebijakan zona waktu pada app berwaktu → P5 gagal; hapus nilai bawaan pengaturan → P7 gagal; tambah butir hardening ke-9 → P8 gagal; ubah ORM menjadi berbeda dari pilihan → P9 gagal; hapus gerbang izin pada Master Prompt → P10 gagal.
5. **Uji bendera**: app tanpa data waktu tidak dituntut zona waktu; tanpa rentang waktu tidak dituntut exclusion; tanpa alur tamu tidak dituntut P3; tanpa halaman publik tidak dituntut SEO; mode UI-only melewati P1, P3, P5 (sebagian), P7.
6. **Uji generalisasi**: jalankan pipeline mock untuk 3 jenis app berbeda (booking, manajemen tugas tim, toko online kecil) di tiga stack berbeda; semuanya lulus tanpa temuan kritis.

## 6. Di luar cakupan
Mengubah alur UI workspace, menambah stack di luar `stacks.json`, dan mengubah isi fitur yang sah dari input pengguna.

## 7. Tahap pengerjaan
- **G1 Audit (baca saja)**: baca spec ini, v1.2, kode pipeline saat ini, dan `ref-prd/`. Tulis `docs/rencana-v1.2.1.md`: apa yang diubah per aturan P1–P10, perubahan skema JSON (`asal`, invarian), isi `stacks.json` tambahan (resep invarian, versi paket, resep cron), dan rencana panggilan AI. Berhenti dan tunggu persetujuan.
- **G2 Pemicu, `asal`, dan invarian**: ekstraksi invarian, field `asal` di peta fitur dan Brief, lencana Usulan di peta fitur, perluasan `Triggers`.
- **G3 Prompt dan data mock**: perbarui `Prompts.php` dan mock untuk P1–P7, P10 (resep invarian, tugas terkoordinasi, kedalaman stack, SEO, konfigurasi, Master Prompt, usulan tambahan, kepemilikan data).
- **G4 Validator** Invarian, Lingkup, KepemilikanData, KekhususanTugas, KedalamanStack, PublikSEO, Konfigurasi, KunciPilihan, MasterPrompt; ubah V17 dan V19 menjadi penggagal tegas.
- **G5 Pipeline dan skor**: Pass 5 yang memangkas, bobot baru, `tools/parity.php`, Laporan Kualitas menampilkan P1–P10.
- **G6 Benchmark**: jalankan semua tes bagian 5, tulis `docs/hasil-paritas.md` berisi angka sebelum/sesudah dan daftar aturan yang masih gagal. Laporkan jujur bila belum setara.
