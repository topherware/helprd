# SalonBook

---

## 1. Ringkasan & Tujuan Aplikasi
*Bagian ini menjelaskan gambaran umum proyek agar dipahami bersama oleh pemilik ide/klien dan tim pengembang.*
- **Nama Aplikasi**: SalonBook
- **Penjelasan Singkat**: SalonBook adalah website booking salon dan barbershop yang memungkinkan pelanggan melihat layanan, harga, durasi, serta jadwal kapster yang masih kosong, lalu memesan sendiri tanpa perlu mendaftar akun. Pemilik dan staf mengelola seluruh booking dan jadwal dari satu dashboard.
- **Masalah yang Diselesaikan**:
  - Pelanggan harus chat WhatsApp manual ke kasir dan sering menunggu lama dibalas.
  - Satu kapster kadang terjadwal ganda (double booking) pada jam yang sama karena pencatatan masih manual.
  - Pelanggan yang datang tanpa booking harus menunggu antrian tanpa kepastian waktu.
  - Pelanggan sering lupa jadwal booking sehingga terjadi no-show.
  - Pemilik kesulitan melihat rekap booking, status, dan ketersediaan kapster secara real-time.
- **Pengguna Aplikasi**:
  - **Pengunjung/Pelanggan (tanpa akun)**: Melihat layanan dan harga, memilih kapster dan jam kosong, memesan dengan cukup mengisi nama dan nomor WhatsApp, serta menerima pengingat WhatsApp 3 jam sebelum jadwal.
  - **Pemilik Salon (Owner)**: Mengelola layanan dan harga, data kapster, jadwal kerja kapster, status booking, serta akun staf. Memiliki akses penuh.
  - **Staf Admin (Kasir)**: Mengelola booking harian, mencatat booking walk-in, dan mengubah status booking. Tidak dapat mengelola akun pemilik.
- **Target Keberhasilan**:
  - Seluruh booking baru masuk melalui website, dan chat WhatsApp manual untuk booking berkurang signifikan dalam 1 bulan pertama.
  - Tidak ada lagi kejadian double booking kapster pada jam yang sama (dijamin oleh sistem, bukan manual).
  - Pengingat WhatsApp terkirim otomatis 3 jam sebelum setiap jadwal yang berstatus dikonfirmasi.
  - Pelanggan dapat menyelesaikan booking dalam waktu kurang dari 2 menit tanpa membuat akun.
  - Kasir dapat melihat dan mengubah status seluruh booking hari ini dalam satu layar.

---

## 2. Batasan Pembuatan Sistem (Versi Awal MVP)
*Menegaskan fitur apa yang dikerjakan di versi awal dan apa yang sengaja ditunda agar aplikasi cepat selesai dan tidak membengkak (mencegah scope creep).*

### ✅ Yang Dikerjakan:
- Halaman publik untuk melihat daftar layanan, harga, dan durasi.
- Alur booking tanpa akun: pilih layanan, pilih kapster, pilih tanggal dan jam kosong, isi nama dan nomor WhatsApp, lalu konfirmasi.
- Slot waktu otomatis terkunci begitu booking dibuat, sehingga kapster yang sama tidak bisa dipesan dua kali pada rentang waktu yang bertabrakan.
- Halaman cek booking untuk pelanggan dengan memasukkan kode booking dan nomor WhatsApp.
- Autentikasi Email & Password untuk Pemilik Salon dan Staf Admin.
- Dashboard admin: ringkasan hari ini, daftar booking dengan filter dan pencarian, dan ubah status (dikonfirmasi, selesai, batal).
- Booking manual (walk-in) oleh staf admin dari dashboard, menggunakan aturan slot yang sama.
- Manajemen layanan (nama, deskripsi, harga, durasi, status aktif/nonaktif) dan penugasan layanan ke kapster.
- Manajemen kapster (nama, foto, status aktif) dan pengaturan jadwal kerja mingguan per kapster.
- Pengaturan libur/cuti kapster per tanggal atau rentang tanggal.
- Pengingat otomatis via WhatsApp Gateway 3 jam sebelum jadwal untuk booking berstatus dikonfirmasi.
- Notifikasi WhatsApp konfirmasi saat booking dibuat dan saat status diubah menjadi batal.
- Pengaturan dasar salon: nama, alamat, nomor WhatsApp salon, dan jam operasional.
- Pembayaran dilakukan langsung di salon (tanpa payment gateway).

### ⛔ Yang Tidak Dikerjakan di Versi Awal:
- Pembayaran online, DP, atau integrasi payment gateway apa pun.
- Pelanggan membatalkan atau mengubah jadwal sendiri melalui website (pembatalan dilakukan oleh staf melalui dashboard atau pelanggan menghubungi salon).
- Login dan akun untuk pelanggan (pelanggan tetap tanpa akun).
- Sistem antrian real-time untuk pelanggan walk-in (hanya pencatatan booking walk-in oleh staf).
- Program loyalitas, poin, voucher, dan diskon otomatis.
- Penilaian (review) dan rating kapster.
- Multi-cabang salon dalam satu akun.
- Aplikasi mobile native (website responsif saja).
- Laporan keuangan dan rekonsiliasi pendapatan yang mendalam (hanya ringkasan jumlah booking).
- Notifikasi melalui Email atau SMS (hanya WhatsApp).
- Pengingat ulang tahun, promosi massal, dan broadcast WhatsApp.

---

## 3. Daftar Halaman & Struktur Menu (Pages & Routing)
*Daftar lengkap halaman yang harus dibuat, dikelompokkan berdasarkan area atau peran pengguna (Role).*

### A. Public Area (Tanpa Login)
- `/` (Beranda): Hero section nama salon, ringkasan layanan unggulan, daftar kapster, testimoni dummy, jam buka, dan tombol CTA "Booking Sekarang".
- `/layanan` (Daftar Layanan): Katalog layanan dengan filter kategori (Rambut, Janggut, Perawatan Wajah, Paket), kartu layanan berisi nama, deskripsi singkat, harga, dan durasi, serta tombol "Pilih Jadwal".
- `/layanan/[slug]` (Detail Layanan): Penjelasan lengkap layanan, daftar kapster yang menyediakan layanan ini, harga, durasi, dan CTA booking yang sudah membawa layanan terpilih.
- `/booking` (Alur Booking Multi-Langkah): Wizard 4 langkah dengan indikator progres.
  - Langkah 1: Pilih layanan (satu atau lebih).
  - Langkah 2: Pilih kapster (opsi "Siapa saja yang tersedia").
  - Langkah 3: Pilih tanggal dan jam. Kalender menampilkan jam kosong, jam penuh ditandai abu-abu dan tidak dapat dipilih.
  - Langkah 4: Isi nama lengkap dan nomor WhatsApp, lalu ringkasan dan tombol "Konfirmasi Booking".
- `/booking/sukses/[kode]` (Konfirmasi Booking): Kode booking, ringkasan jadwal, pengingat bahwa pembayaran dilakukan di salon, dan tombol "Tambahkan ke Kalender" (file .ics).
- `/cek-booking` (Cek Status Booking): Formulir kode booking dan nomor WhatsApp. Menampilkan status booking dan jadwal, tanpa login.
- `/tentang` (Tentang Salon): Cerita salon, nilai layanan, galeri dummy, dan alamat.
- `/kontak` (Kontak & Lokasi): Alamat, nomor WhatsApp salon (tombol chat langsung), jam operasional, dan peta statis.
- `/masuk` (Login Pemilik & Staf): Form Email dan Password untuk akses dashboard.

### B. Admin Area (Setelah Login, Role Owner & Staf)
- `/admin/dashboard` (Dasbor Utama): Kartu ringkasan (booking hari ini, booking minggu ini, selesai, batal), daftar antrean booking hari ini urut jam, dan kapster yang sedang bertugas.
- `/admin/booking` (Daftar Booking): Tabel booking dengan filter tanggal, kapster, status, dan pencarian nama atau nomor WhatsApp. Aksi per baris: lihat detail, ubah status, kirim ulang WhatsApp.
- `/admin/booking/[id]` (Detail Booking): Data pelanggan, layanan, kapster, jadwal, riwayat perubahan status, dan tombol ubah status (Dikonfirmasi, Selesai, Batal).
- `/admin/booking/baru` (Booking Walk-in / Manual): Form singkat untuk mencatat pelanggan yang datang langsung atau booking via telepon, menggunakan slot yang sama dengan website.
- `/admin/layanan` (Manajemen Layanan): Tabel layanan dengan tombol tambah, edit, nonaktifkan, serta modal form (nama, kategori, deskripsi, harga, durasi, status).
- `/admin/kapster` (Manajemen Kapster): Daftar kapster dengan kartu profil, tambah/edit, aktif/nonaktif, dan pemilihan layanan yang dikuasai.
- `/admin/jadwal` (Jadwal & Ketersediaan): Pengaturan jam kerja mingguan per kapster (grid hari dan jam), serta daftar libur/cuti kapster dengan rentang tanggal.
- `/admin/jadwal/kalender` (Kalender Harian): Tampilan timeline per kapster untuk satu hari, memperlihatkan blok booking dan blok libur.
- `/admin/pengaturan` (Pengaturan Salon): Nama salon, alamat, nomor WhatsApp salon, jam operasional default, dan template pesan WhatsApp (konfirmasi, pengingat, batal).
- `/admin/pengguna` (Manajemen Staf) *(khusus Owner)*: Daftar akun staf admin, tambah staf dengan Email dan Password sementara, nonaktifkan akun.
- `/admin/profil` (Profil Saya): Ubah nama dan password akun yang sedang login.

---

## 4. Pedoman UI/UX & Design System
*Panduan visual konkret agar AI coding assistant tidak membuat UI yang kaku atau default.*
- **Skema Warna**:
  - Primary (Hitam Tinta): HSL(240, 6%, 10%), dipakai untuk tombol utama, teks judul, dan ikon aktif.
  - Secondary (Abu Lembut): HSL(240, 5%, 96%), dipakai untuk latar kartu hover dan badge netral.
  - Accent (Hijau Sage Lembut): HSL(152, 35%, 42%), dipakai hanya untuk status sukses dan slot yang tersedia.
  - Background: HSL(0, 0%, 100%) untuk halaman, dan HSL(60, 10%, 99%) untuk area dashboard.
  - Border: HSL(240, 6%, 90%) dengan ketebalan 1px, selalu tipis.
  - Destructive (Merah Redup): HSL(0, 60%, 50%), hanya untuk status batal dan aksi hapus.
  - Teks Muted: HSL(240, 4%, 46%), untuk keterangan, durasi, dan label sekunder.
- **Tipografi**:
  - Heading menggunakan font `Plus Jakarta Sans` (bobot 600 dan 700) dengan letter-spacing sedikit rapat (-0.01em).
  - Body dan UI menggunakan font `Inter` (bobot 400 dan 500) dengan ukuran dasar 15px untuk keterbacaan.
  - Angka harga dan jam menggunakan `tabular-nums` agar rata saat ditampilkan dalam tabel.
  - Hierarki: H1 32px, H2 24px, H3 18px, body 15px, caption 13px.
- **Aturan Komponen**:
  - Sudut membulat sedang (`rounded-lg`) untuk kartu, input, dan tombol. Badge menggunakan `rounded-full`.
  - Kartu menggunakan border 1px `border-zinc-200` dan tanpa shadow pada kondisi normal. Saat hover, border berubah sedikit lebih gelap dan muncul `shadow-sm`.
  - Tombol utama berwarna hitam tinta dengan teks putih, tombol sekunder berbentuk ghost dengan border tipis.
  - Input menggunakan border 1px dan ring fokus tipis berwarna primary, tanpa warna fokus mencolok.
  - Tabel menggunakan garis pemisah horizontal tipis, header berukuran 13px uppercase dengan warna muted, dan tanpa garis vertikal.
  - Status booking ditampilkan sebagai badge teks dengan titik kecil berwarna: Dikonfirmasi (biru tipis), Selesai (hijau sage), Batal (merah redup).
  - Slot jam pada wizard booking berbentuk chip kecil dengan border tipis. Slot aktif berubah menjadi hitam tinta, slot penuh berwarna abu dengan teks coret tipis dan tidak dapat diklik.
  - Ikon menggunakan Lucide Icons dengan ketebalan garis 1.5 dan ukuran 16px hingga 18px.
  - Spasi antar elemen lega (whitespace luas), padding kartu 24px pada desktop dan 16px pada mobile.
  - Animasi transisi halus 150ms hingga 200ms, tanpa animasi yang mencolok.
- **Nuansa & Vibe**:
  - Minimalis, bersih, dan tenang. Banyak ruang kosong, garis tipis, dan tipografi sebagai elemen utama desain, bukan ilustrasi berlebihan.
  - Menggunakan foto kapster dan potongan rambut yang bersih sebagai aksen visual, dengan rasio gambar konsisten 4:5.
  - Mode terang sebagai default. Mode gelap opsional untuk dashboard (tidak wajib di MVP).
  - Pada layar mobile, wizard booking tampil satu langkah per layar dengan tombol "Lanjut" tetap di bagian bawah (sticky).
  - Responsif: desktop (sidebar tetap), tablet (sidebar tersembunyi dengan tombol menu), mobile (drawer dan bottom navigation untuk dashboard).

---

## 5. Pembagian Hak Akses Pengguna
*Tabel hak akses yang menentukan siapa saja yang boleh melihat, mengedit, atau mengelola data.*

| Menu / Halaman | Publik (Tanpa Login) | Staf Admin (Login) | Pemilik Salon / Owner (Login) |
| :--- | :---: | :---: | :---: |
| Beranda, Layanan, Detail Layanan | ✅ | ✅ | ✅ |
| Alur Booking Pelanggan (`/booking`) | ✅ | ✅ | ✅ |
| Cek Booking (`/cek-booking`) | ✅ | ✅ | ✅ |
| Dashboard Ringkasan | ❌ | ✅ | ✅ |
| Daftar & Detail Booking, Ubah Status | ❌ | ✅ | ✅ |
| Booking Walk-in / Manual | ❌ | ✅ | ✅ |
| Kelola Layanan & Harga | ❌ | ❌ | ✅ |
| Kelola Kapster | ❌ | ❌ | ✅ |
| Jadwal Kerja & Libur Kapster | ❌ | ✅ | ✅ |
| Pengaturan Salon & Template WhatsApp | ❌ | ❌ | ✅ |
| Manajemen Staf Admin | ❌ | ❌ | ✅ |
| Profil Saya (ubah password) | ❌ | ✅ | ✅ |

*Catatan: Staf Admin dapat melihat jadwal dan mengubah jadwal libur kapster untuk keperluan operasional harian, tetapi tidak dapat mengubah layanan, harga, atau pengaturan sistem.*

---

## 6. Alur Kerja dan Fitur Utama
*Menjelaskan cara kerja setiap fitur utama dalam bahasa yang mudah dipahami serta aturan logikanya.*

### A. Booking Pelanggan Tanpa Akun
1. **Cara Kerja**:
   1. Pelanggan membuka `/booking` atau memilih layanan dari halaman `/layanan`.
   2. Pelanggan memilih satu atau lebih layanan. Sistem menjumlahkan total durasi dan total harga.
   3. Pelanggan memilih kapster tertentu atau "Siapa saja yang tersedia". Jika "Siapa saja", sistem menampilkan jam yang kosong untuk setidaknya satu kapster yang menguasai semua layanan terpilih.
   4. Pelanggan memilih tanggal. Sistem menampilkan daftar jam dengan interval 30 menit, dimulai dari jam buka kapster dan hanya menampilkan jam yang muat untuk total durasi layanan.
   5. Pelanggan mengisi nama lengkap dan nomor WhatsApp, lalu menekan "Konfirmasi Booking".
   6. Sistem memvalidasi ulang ketersediaan di sisi server secara atomik. Jika slot sudah diambil orang lain, sistem menampilkan pesan "Maaf, jam ini baru saja dipesan. Silakan pilih jam lain" dan memuat ulang daftar jam.
   7. Jika berhasil, sistem membuat booking berstatus Dikonfirmasi, mengunci slot, menampilkan kode booking, dan mengirim pesan WhatsApp konfirmasi.
2. **Aturan Sistem**:
   - Nama lengkap wajib diisi, minimal 3 karakter dan maksimal 80 karakter.
   - Nomor WhatsApp wajib diisi, dinormalisasi ke format internasional `62xxxxxxxxxx` (awalan 0 atau +62 otomatis diubah), panjang 10 hingga 14 digit setelah kode negara.
   - Booking hanya dapat dibuat untuk tanggal hari ini atau di masa depan. Booking pada hari ini hanya boleh jika jam mulai minimal 60 menit dari waktu sekarang.
   - Booking maksimal 60 hari ke depan.
   - Satu pelanggan (nomor WhatsApp yang sama) tidak boleh memiliki lebih dari 3 booking berstatus Dikonfirmasi pada waktu bersamaan untuk mencegah penyalahgunaan slot.
   - Pembayaran tidak dilakukan di sistem. Halaman konfirmasi menampilkan keterangan "Pembayaran dilakukan langsung di salon".
   - Kode booking terdiri dari 6 karakter alfanumerik huruf besar tanpa karakter membingungkan (tanpa 0, O, 1, I), contoh `K7QF2M`.

### B. Penguncian Slot Otomatis (Anti Double Booking)
1. **Cara Kerja**:
   1. Saat booking dibuat, sistem menghitung waktu selesai = waktu mulai + total durasi layanan.
   2. Sistem menyimpan booking ke tabel `bookings` dengan rentang waktu `[start_at, end_at)`.
   3. Database menjaga aturan bahwa tidak ada dua booking berstatus Dikonfirmasi untuk kapster yang sama yang rentang waktunya saling tumpang tindih (exclusion constraint).
   4. Slot yang sudah dipesan otomatis hilang dari daftar pilihan untuk pelanggan lain, dan tampil sebagai "Penuh" pada kalender admin.
   5. Jika booking dibatalkan atau diubah menjadi Batal, slot langsung kembali tersedia.
2. **Aturan Sistem**:
   - Slot tersedia dihitung dari: jam kerja kapster pada hari tersebut, dikurangi booking aktif (status Dikonfirmasi), dikurangi tanggal libur atau cuti kapster.
   - Pembuatan booking menggunakan transaksi database dengan `SELECT ... FOR UPDATE` pada baris kapster sebagai lapisan kedua selain exclusion constraint.
   - Booking berstatus Selesai atau Batal tidak mengunci slot.
   - Perubahan jadwal kerja kapster tidak membatalkan booking yang sudah ada secara otomatis. Sistem menampilkan peringatan kepada admin jika ada booking yang berada di luar jam kerja baru.

### C. Manajemen Booking oleh Staf (Dashboard)
1. **Cara Kerja**:
   1. Staf login melalui `/masuk` dengan Email dan Password.
   2. Dashboard menampilkan ringkasan hari ini dan daftar booking urut jam.
   3. Staf dapat memfilter berdasarkan tanggal, kapster, dan status, atau mencari nama dan nomor WhatsApp.
   4. Staf membuka detail booking dan menekan tombol ubah status: Dikonfirmasi, Selesai, atau Batal.
   5. Saat status berubah menjadi Batal, sistem meminta alasan singkat (opsional) dan mengirim WhatsApp pemberitahuan kepada pelanggan.
   6. Setiap perubahan status dicatat di riwayat booking beserta nama staf dan waktu perubahan.
2. **Aturan Sistem**:
   - Status Selesai hanya dapat ditetapkan jika jam mulai booking sudah lewat.
   - Status Batal dapat ditetapkan kapan saja sebelum status Selesai.
   - Booking yang sudah Selesai atau Batal tidak dapat diubah kembali ke Dikonfirmasi, kecuali oleh Owner dengan alasan yang dicatat.
   - Jika booking dengan status Dikonfirmasi telah melewati jam selesai dan belum ditandai, sistem menampilkan badge "Perlu Ditutup" di dashboard sebagai pengingat untuk staf.

### D. Booking Walk-in (Pelanggan Datang Langsung)
1. **Cara Kerja**:
   1. Staf membuka `/admin/booking/baru`.
   2. Staf memilih layanan, kapster, dan jam mulai. Sistem menampilkan hanya slot yang kosong pada saat itu.
   3. Staf mengisi nama pelanggan dan nomor WhatsApp (opsional untuk walk-in, tetapi disarankan).
   4. Staf menekan "Simpan". Booking langsung berstatus Dikonfirmasi dengan penanda sumber "Walk-in".
2. **Aturan Sistem**:
   - Nomor WhatsApp untuk walk-in bersifat opsional. Jika diisi, sistem tetap mengirim pengingat.
   - Walk-in tidak dapat dibuat untuk jam yang sudah lewat lebih dari 15 menit.
   - Jika tidak ada slot kosong, sistem menyarankan jam kosong terdekat untuk kapster yang sama atau kapster lain yang tersedia.

### E. Pengingat WhatsApp 3 Jam Sebelum Jadwal
1. **Cara Kerja**:
   1. Vercel Cron memanggil endpoint `/api/cron/pengingat` setiap 10 menit.
   2. Sistem mencari booking berstatus Dikonfirmasi dengan jam mulai antara sekarang + 170 menit dan sekarang + 190 menit, dan `reminder_sent_at` masih kosong.
   3. Sistem mengirim pesan WhatsApp pengingat melalui WhatsApp Gateway.
   4. Jika berhasil, sistem mengisi `reminder_sent_at` dan mencatat hasilnya di `notification_logs`.
   5. Jika gagal, sistem mencoba ulang pada siklus cron berikutnya hingga maksimal 3 kali, lalu menandai gagal dan menampilkan peringatan di dashboard.
2. **Aturan Sistem**:
   - Pengingat hanya dikirim untuk booking berstatus Dikonfirmasi.
   - Jika booking dibuat kurang dari 3 jam sebelum jadwal, pengingat tidak dikirim, dan pesan konfirmasi sudah berfungsi sebagai pengingat.
   - Endpoint cron wajib memverifikasi header `Authorization: Bearer <CRON_SECRET>`.
   - Template pesan pengingat dapat diubah oleh Owner di `/admin/pengaturan`, dengan variabel `{nama}`, `{layanan}`, `{jam}`, `{kapster}`, `{kode}`, dan `{nama_salon}`.

### F. Pengaturan Jadwal & Ketersediaan Kapster
1. **Cara Kerja**:
   1. Owner atau staf membuka `/admin/jadwal`.
   2. Memilih kapster, lalu mengatur jam kerja per hari dalam bentuk grid mingguan (contoh: Senin 10.00 sampai 20.00, Minggu libur).
   3. Untuk libur, staf menambahkan entri libur dengan rentang tanggal dan alasan (contoh: "Cuti Lebaran 28 Maret sampai 2 April").
   4. Sistem langsung memperbarui ketersediaan slot di halaman publik.
2. **Aturan Sistem**:
   - Jam mulai harus lebih kecil dari jam selesai dan selisihnya minimal 1 jam.
   - Jam kerja dibulatkan ke kelipatan 15 menit.
   - Libur tidak boleh mencakup booking yang berstatus Dikonfirmasi. Jika ada, sistem menampilkan daftar booking yang terdampak dan meminta staf mengalihkan atau membatalkannya terlebih dahulu.
   - Tanggal libur tidak boleh di masa lalu.

### G. Manajemen Layanan & Harga
1. **Cara Kerja**:
   1. Owner membuka `/admin/layanan`.
   2. Menekan "Tambah Layanan", mengisi nama, kategori, deskripsi, harga, durasi, dan status.
   3. Menugaskan layanan ke kapster tertentu melalui menu di `/admin/kapster`.
   4. Layanan yang dinonaktifkan tidak tampil di halaman publik, tetapi tetap tersimpan di riwayat booking.
2. **Aturan Sistem**:
   - Nama layanan wajib diisi, unik, maksimal 60 karakter.
   - Harga dalam Rupiah, bilangan bulat, minimal Rp 10.000 dan maksimal Rp 10.000.000, dan harus kelipatan Rp 1.000.
   - Durasi dalam menit, kelipatan 15, minimal 15 dan maksimal 240.
   - Perubahan harga tidak mengubah harga booking yang sudah dibuat (harga disimpan sebagai snapshot pada booking).
   - Layanan tidak dapat dihapus permanen jika sudah pernah dipesan. Sistem hanya mengizinkan nonaktifkan.

### H. Manajemen Kapster & Staf Admin
1. **Cara Kerja**:
   1. Owner membuka `/admin/kapster` untuk menambah kapster dengan nama, foto, deskripsi singkat, dan daftar layanan yang dikuasai.
   2. Owner membuka `/admin/pengguna` untuk menambah staf admin dengan Email dan Password sementara.
   3. Staf yang baru pertama kali login diwajibkan mengganti Password sementara di `/admin/profil`.
2. **Aturan Sistem**:
   - Kapster minimal menguasai satu layanan agar dapat dipilih pelanggan.
   - Kapster nonaktif tidak tampil di halaman publik dan tidak dapat menerima booking baru, tetapi booking lama tetap berjalan.
   - Email staf wajib unik dan valid. Password minimal 8 karakter dengan kombinasi huruf dan angka.
   - Maksimal 5 akun staf admin pada paket MVP (dapat diubah di konfigurasi).
   - Akun yang dinonaktifkan tidak dapat login, tetapi riwayat aktivitasnya tetap tersimpan.

### I. Cek Status Booking oleh Pelanggan
1. **Cara Kerja**:
   1. Pelanggan membuka `/cek-booking`.
   2. Mengisi kode booking dan nomor WhatsApp yang digunakan saat booking.
   3. Sistem menampilkan layanan, kapster, jadwal, dan status booking.
2. **Aturan Sistem**:
   - Data hanya ditampilkan jika kode dan nomor WhatsApp cocok.
   - Setelah 5 kali percobaan gagal dalam 15 menit dari satu alamat IP, sistem menolak sementara selama 15 menit (rate limiting).
   - Pelanggan tidak dapat membatalkan booking dari halaman ini. Halaman menampilkan nomor WhatsApp salon untuk menghubungi kasir jika ingin membatalkan.

---

## 7. Alur Navigasi & Arsitektur Layout
*Peta navigasi alur halaman dan struktur tata letak (layout).*

### Arsitektur Layout (Persisten)
- **Public Layout**: Menggunakan Header (navbar statis di atas dengan logo SalonBook, menu Layanan, Tentang, Kontak, Cek Booking, dan tombol utama "Booking Sekarang") serta Footer (alamat, jam buka, nomor WhatsApp, dan tautan cepat). Pada mobile, navbar berubah menjadi menu hamburger.
- **Booking Layout**: Menggunakan Header minimal (logo dan tombol kembali) serta indikator langkah 1 sampai 4 di bagian atas. Ringkasan booking (total harga dan total durasi) ditampilkan sebagai sticky bar di bagian bawah pada mobile.
- **Dashboard Layout**: Menggunakan Sidebar di kiri (fixed) dengan menu Dashboard, Booking, Jadwal, Layanan, Kapster, Pengaturan, dan Pengguna (hanya Owner). Header kecil di atas berisi judul halaman, pencarian cepat, dan menu profil. Pada mobile, sidebar berubah menjadi drawer.
- **Auth Layout**: Layout tanpa navigasi, hanya logo dan formulir terpusat, digunakan pada `/masuk`.

### Bagan Alur (Flowchart)
```mermaid
flowchart TD
    A[Pengunjung] --> B[Beranda /]
    B --> C[Daftar Layanan /layanan]
    B --> D[Kontak /kontak]
    B --> CB[Cek Booking /cek-booking]
    C --> E[Detail Layanan /layanan/slug]
    C --> F[Mulai Booking /booking]
    E --> F
    F --> G[Langkah 1: Pilih Layanan]
    G --> H[Langkah 2: Pilih Kapster]
    H --> I[Langkah 3: Pilih Tanggal & Jam]
    I --> J{Slot Masih Kosong?}
    J -- Tidak --> K[Tampilkan Pesan Penuh & Muat Ulang Jam]
    K --> I
    J -- Ya --> L[Langkah 4: Isi Nama & WhatsApp]
    L --> M{Validasi Server & Kunci Slot}
    M -- Gagal Konflik --> K
    M -- Berhasil --> N[Booking Dikonfirmasi]
    N --> O[Kirim WA Konfirmasi]
    N --> P[Halaman Sukses /booking/sukses/kode]
    O --> Q[Cron Pengingat 3 Jam Sebelum]
    Q --> R[Kirim WA Pengingat]
    CB --> S{Kode & WA Cocok?}
    S -- Ya --> T[Tampilkan Status Booking]
    S -- Tidak --> U[Pesan Data Tidak Ditemukan]
    A --> V[Login /masuk]
    V --> W{Kredensial Valid?}
    W -- Tidak --> V
    W -- Ya --> X{Role?}
    X -- Owner --> Y[Dashboard Admin]
    X -- Staf --> Y
    Y --> Z[Daftar Booking]
    Y --> AA[Jadwal & Libur Kapster]
    Y --> AB[Layanan & Kapster]
    Y --> AC[Pengaturan Salon]
    Y --> AD[Walk-in Manual]
    Z --> AE{Ubah Status}
    AE -- Selesai --> AF[Tandai Selesai]
    AE -- Batal --> AG[Batalkan & Kirim WA]
    AG --> AH[Slot Kembali Tersedia]
    AD --> J
    X -- Owner Saja --> AI[Manajemen Staf]
    AB --> AJ{Layanan Sudah Pernah Dipesan?}
    AJ -- Ya --> AK[Hanya Nonaktifkan]
    AJ -- Tidak --> AL[Boleh Hapus Permanen]
```

---

## 8. Kebutuhan Non-Fungsional (SEO, Keamanan, & Performa)
*Syarat wajib agar website siap rilis ke publik (production-ready).*
- **SEO**:
  - Wajib menggunakan tag `<title>` dinamis dan meta description di setiap halaman publik. Contoh: "Daftar Layanan Barbershop & Salon | SalonBook".
  - Wajib Open Graph (OG) tags dan Twitter Card di halaman publik, dengan gambar OG dinamis untuk halaman detail layanan.
  - Struktur data JSON-LD bertipe `HairSalon` atau `BarberShop` pada halaman beranda dan kontak, berisi alamat, jam buka, dan nomor telepon.
  - Sitemap otomatis (`sitemap.xml`) dan `robots.txt`.
  - URL bersih dan berbasis slug untuk layanan (`/layanan/potong-rambut-classic`).
  - Halaman dashboard, login, dan cek booking diberi `noindex`.
- **Keamanan**:
  - Autentikasi Email dan Password dengan hashing menggunakan bcrypt atau argon2 (cost factor memadai).
  - Sesi menggunakan cookie `httpOnly`, `secure`, dan `sameSite=lax`, dengan masa berlaku 7 hari dan perpanjangan otomatis.
  - Proteksi CSRF pada seluruh mutasi (Server Actions sudah menyediakan proteksi origin, ditambah validasi token untuk endpoint API).
  - Sanitasi input untuk mencegah XSS. Seluruh input teks dirender sebagai teks, bukan HTML, dan nama pelanggan di-escape pada template WhatsApp.
  - Validasi sisi server menggunakan Zod pada seluruh Server Actions dan route handler.
  - Rate limiting pada endpoint login (5 percobaan gagal per 15 menit per IP), cek booking, dan pembuatan booking (maksimal 10 request per menit per IP).
  - Endpoint cron wajib dilindungi `CRON_SECRET`.
  - Nomor WhatsApp pelanggan tidak ditampilkan secara publik. Hanya tampil terlindungi (contoh: 0812-xxxx-1234) di halaman mana pun selain dashboard admin.
  - Penerapan kontrol akses berbasis peran (RBAC) di level middleware dan di level Server Action (double check).
  - Header keamanan: Content-Security-Policy dasar, X-Frame-Options DENY, Referrer-Policy strict-origin-when-cross-origin.
  - Audit log untuk perubahan status booking, perubahan harga, dan perubahan akun staf.
- **Performa**:
  - Optimasi gambar menggunakan komponen `<Image>` dengan format WebP atau AVIF dan ukuran responsif.
  - Lazy loading untuk komponen berat seperti kalender dan grafik dashboard menggunakan `next/dynamic`.
  - Caching untuk data publik (layanan, kapster, pengaturan salon) menggunakan revalidasi `revalidate` 300 detik dan revalidasi manual saat data diubah admin.
  - Ketersediaan slot dihitung di server dan di-cache per kapster per tanggal dengan invalidasi saat ada booking atau perubahan jadwal baru.
  - Target Core Web Vitals: LCP di bawah 2,5 detik, INP di bawah 200 ms, CLS di bawah 0,1.
  - Indeks database pada kolom `bookings(barber_id, start_at)`, `bookings(status, start_at)`, dan `bookings(customer_phone)`.

---

## 9. Panduan Bahasa, Copywriting, & Data Dummy
*Panduan nada bicara (Tone of Voice) dan contoh data agar prototipe terasa nyata.*
- **Gaya Bahasa**: Profesional, ramah, dan membumi. Menggunakan kata "Anda" untuk pelanggan dan "Kami" untuk salon. Kalimat singkat, jelas, dan tidak menggunakan istilah teknis di halaman publik. Di dashboard, bahasa tetap ringkas dan operasional.
- **Contoh Copywriting**:
  - Hero: "Booking potong rambut tanpa antre dan tanpa ribet chat."
  - Subjudul: "Pilih layanan, pilih kapster, pilih jam kosong. Tidak perlu daftar akun."
  - CTA: "Booking Sekarang", "Lihat Jadwal Kosong", "Cek Status Booking".
  - Pesan sukses: "Booking Anda sudah kami terima. Kode booking Anda: K7QF2M. Pembayaran dilakukan langsung di salon."
  - Pesan slot penuh: "Maaf, jam ini baru saja dipesan. Silakan pilih jam lain."
  - Pesan konfirmasi WhatsApp: "Halo Budi, booking Anda di SalonBook sudah dikonfirmasi. Layanan: Potong Rambut Classic, Kapster: Dimas, Jadwal: Sabtu, 14 Juni 2025 pukul 13.00 WIB. Kode: K7QF2M. Pembayaran dilakukan di salon. Sampai jumpa!"
  - Pesan pengingat WhatsApp: "Pengingat: Booking Anda pukul 13.00 WIB hari ini di SalonBook bersama Dimas. Kode: K7QF2M. Jika berhalangan, silakan hubungi kami di 0812-3456-7890."
- **Instruksi Data Dummy**: JANGAN PERNAH MENGGUNAKAN "Lorem Ipsum". Selalu gunakan data dummy berbahasa Indonesia yang relevan dengan konteks aplikasi.
  - **Layanan (Kategori Rambut)**:
    - Potong Rambut Classic, durasi 45 menit, harga Rp 50.000, deskripsi "Potong rapi dengan teknik klasik dan finishing wax ringan."
    - Potong Rambut Fade, durasi 60 menit, harga Rp 75.000, deskripsi "Gradasi halus dari sisi samping hingga belakang, dengan styling sesuai bentuk wajah."
    - Cuci Blow Keramas, durasi 30 menit, harga Rp 35.000, deskripsi "Keramas, pijat kepala ringan, dan blow kering rapi."
  - **Layanan (Kategori Janggut)**:
    - Rapikan Janggut, durasi 30 menit, harga Rp 40.000, deskripsi "Rapikan garis janggut dan kumis menggunakan pisau cukur tajam."
    - Creambath Relaksasi, durasi 45 menit, harga Rp 90.000, deskripsi "Perawatan rambut dan kulit kepala dengan krim nutrisi, dilengkapi pijat relaksasi."
  - **Layanan (Paket)**:
    - Paket Gentleman, durasi 90 menit, harga Rp 130.000, deskripsi "Potong rambut Fade, rapikan janggut, dan cuci blow keramas."
  - **Kapster**:
    - Dimas Pratama, spesialis Fade dan Classic, tersedia Senin sampai Sabtu.
    - Rizky Ananda, spesialis Creambath dan perawatan janggut, tersedia Selasa sampai Minggu.
    - Fajar Nugroho, spesialis Classic dan Paket Gentleman, tersedia Senin sampai Sabtu.
  - **Pelanggan (Data Booking)**:
    - Budi Santoso, 0812-3456-7801, booking Potong Rambut Fade bersama Dimas Pratama.
    - Andi Wijaya, 0856-7788-9902, booking Paket Gentleman bersama Fajar Nugroho.
    - Reza Firmansyah, 0821-9900-1134, booking Rapikan Janggut bersama Rizky Ananda.
    - Dimas Saputra (walk-in), tanpa nomor WhatsApp, booking Cuci Blow Keramas.
  - **Staf Admin**:
    - Owner: Hendra Gunawan, email `owner@salonbook.id`.
    - Kasir: Sari Lestari, email `kasir@salonbook.id`.
  - **Pengaturan Salon**:
    - Nama: SalonBook Barbershop Kebayoran, alamat Jl. Kebayoran Lama No. 27, Jakarta Selatan, nomor WhatsApp 0812-3456-7890, jam buka 10.00 sampai 21.00 WIB.

---

## 10. Fondasi Teknis (Untuk Tim Pengembang / Programmer & AI)
*Petunjuk arsitektur teknis spesifik.*
- **Bahasa & Framework**: Next.js 15 (App Router) dengan TypeScript, Server Components sebagai default, dan Server Actions untuk seluruh mutasi data. Route Handler digunakan khusus untuk endpoint cron dan file .ics.
- **Tampilan Antarmuka (UI)**: Tailwind CSS v4, shadcn/ui sebagai base component (Button, Card, Input, Dialog, Table, Badge, Dropdown Menu, Tabs, Select, Calendar, Sheet, Toast), Lucide Icons, dan font Plus Jakarta Sans serta Inter melalui `next/font`.
- **Autentikasi**: Auth.js v5 (NextAuth) dengan Credentials Provider (Email dan Password), strategi sesi JWT, dan password di-hash menggunakan bcryptjs. Pelanggan tidak memiliki akun dan tidak melewati autentikasi.
- **Basis Data (Database)**: Neon PostgreSQL (serverless) dengan Drizzle ORM dan Drizzle Kit untuk migrasi. Ekstensi `btree_gist` diaktifkan untuk exclusion constraint pada slot booking.
- **Validasi**: Zod untuk seluruh input form dan Server Actions, dibagikan antara client dan server.
- **Notifikasi WhatsApp**: Fonnte (WhatsApp Gateway berbasis API berbahasa Indonesia) melalui HTTP POST ke endpoint resmi `https://api.fonnte.com/send` dengan header `Authorization` berisi token. Logika pengiriman dibungkus dalam modul `lib/wa/` agar mudah diganti ke provider lain (misal Wablas).
- **Penjadwalan (Cron)**: Vercel Cron Jobs dengan konfigurasi `vercel.json` yang memanggil `/api/cron/pengingat` setiap 10 menit.
- **Hosting**: Vercel untuk aplikasi, Neon untuk database.
- **Manajemen Zona Waktu**: Seluruh waktu disimpan dalam UTC (`timestamptz`) dan ditampilkan dalam zona `Asia/Jakarta` (WIB) menggunakan library `date-fns-tz`.
- **Lainnya**: TanStack Query tidak digunakan pada MVP karena mayoritas data dirender di server. Kalender menggunakan `react-day-picker`.

### Struktur Skema Database Nyata
*(Tuliskan blok kode skema database nyata yang siap pakai sesuai database yang dipilih, misal TypeScript Drizzle ORM `schema.ts`)*
```typescript
// db/schema.ts
import {
  pgTable,
  pgEnum,
  uuid,
  varchar,
  text,
  integer,
  boolean,
  timestamp,
  time,
  smallint,
  uniqueIndex,
  index,
  check,
} from "drizzle-orm/pg-core";
import { sql } from "drizzle-orm";

// ENUM
export const userRoleEnum = pgEnum("user_role", ["owner", "staf"]);
export const bookingStatusEnum = pgEnum("booking_status", [
  "dikonfirmasi",
  "selesai",
  "batal",
]);
export const bookingSourceEnum = pgEnum("booking_source", [
  "website",
  "walk_in",
]);
export const reminderStatusEnum = pgEnum("reminder_status", [
  "menunggu",
  "terkirim",
  "gagal",
  "dilewati",
]);
export const serviceCategoryEnum = pgEnum("service_category", [
  "rambut",
  "janggut",
  "perawatan",
  "paket",
]);

// 1. Pengguna Admin (Owner & Staf)
export const users = pgTable(
  "users",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    name: varchar("name", { length: 80 }).notNull(),
    email: varchar("email", { length: 160 }).notNull(),
    passwordHash: text("password_hash").notNull(),
    role: userRoleEnum("role").notNull().default("staf"),
    isActive: boolean("is_active").notNull().default(true),
    mustChangePassword: boolean("must_change_password").notNull().default(false),
    lastLoginAt: timestamp("last_login_at", { withTimezone: true }),
    createdAt: timestamp("created_at", { withTimezone: true }).notNull().defaultNow(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).notNull().defaultNow(),
  },
  (t) => [uniqueIndex("users_email_unique").on(sql`lower(${t.email})`)]
);

// 2. Pengaturan Salon (satu baris saja)
export const salonSettings = pgTable(
  "salon_settings",
  {
    id: smallint("id").primaryKey().default(1),
    name: varchar("name", { length: 100 }).notNull().default("SalonBook Barbershop Kebayoran"),
    address: text("address").notNull().default("Jl. Kebayoran Lama No. 27, Jakarta Selatan"),
    whatsappNumber: varchar("whatsapp_number", { length: 20 }).notNull().default("6281234567890"),
    openTime: time("open_time").notNull().default("10:00:00"),
    closeTime: time("close_time").notNull().default("21:00:00"),
    reminderHoursBefore: smallint("reminder_hours_before").notNull().default(3),
    tplKonfirmasi: text("tpl_konfirmasi").notNull(),
    tplPengingat: text("tpl_pengingat").notNull(),
    tplBatal: text("tpl_batal").notNull(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).notNull().defaultNow(),
  },
  (t) => [check("salon_settings_single_row", sql`${t.id} = 1`)]
);

// 3. Layanan
export const services = pgTable(
  "services",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    slug: varchar("slug", { length: 80 }).notNull(),
    name: varchar("name", { length: 60 }).notNull(),
    category: serviceCategoryEnum("category").notNull(),
    description: text("description").notNull(),
    priceIdr: integer("price_idr").notNull(),
    durationMinutes: smallint("duration_minutes").notNull(),
    imageUrl: text("image_url"),
    isActive: boolean("is_active").notNull().default(true),
    createdAt: timestamp("created_at", { withTimezone: true }).notNull().defaultNow(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).notNull().defaultNow(),
  },
  (t) => [
    uniqueIndex("services_slug_unique").on(t.slug),
    uniqueIndex("services_name_unique").on(t.name),
    check("services_price_range", sql`${t.priceIdr} >= 10000 AND ${t.priceIdr} <= 10000000`),
    check("services_price_step", sql`${t.priceIdr} % 1000 = 0`),
    check("services_duration_valid", sql`${t.durationMinutes} >= 15 AND ${t.durationMinutes} <= 240 AND ${t.durationMinutes} % 15 = 0`),
  ]
);

// 4. Kapster
export const barbers = pgTable("barbers", {
  id: uuid("id").primaryKey().defaultRandom(),
  name: varchar("name", { length: 80 }).notNull(),
  bio: text("bio"),
  photoUrl: text("photo_url"),
  isActive: boolean("is_active").notNull().default(true),
  createdAt: timestamp("created_at", { withTimezone: true }).notNull().defaultNow(),
  updatedAt: timestamp("updated_at", { withTimezone: true }).notNull().defaultNow(),
});

// 5. Relasi kapster dan layanan yang dikuasai
export const barberServices = pgTable(
  "barber_services",
  {
    barberId: uuid("barber_id").notNull().references(() => barbers.id, { onDelete: "cascade" }),
    serviceId: uuid("service_id").notNull().references(() => services.id, { onDelete: "cascade" }),
  },
  (t) => [
    uniqueIndex("barber_services_pk").on(t.barberId, t.serviceId),
  ]
);

// 6. Jadwal kerja mingguan per kapster (weekday: 0 = Minggu, 6 = Sabtu)
export const barberSchedules = pgTable(
  "barber_schedules",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    barberId: uuid("barber_id").notNull().references(() => barbers.id, { onDelete: "cascade" }),
    weekday: smallint("weekday").notNull(),
    startTime: time("start_time").notNull(),
    endTime: time("end_time").notNull(),
    isOff: boolean("is_off").notNull().default(false),
  },
  (t) => [
    uniqueIndex("barber_schedules_unique_day").on(t.barberId, t.weekday),
    check("barber_schedules_weekday", sql`${t.weekday} >= 0 AND ${t.weekday} <= 6`),
    check("barber_schedules_time_order", sql`${t.isOff} = true OR ${t.endTime} > ${t.startTime}`),
  ]
);

// 7. Libur / cuti kapster
export const barberTimeOff = pgTable(
  "barber_time_off",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    barberId: uuid("barber_id").notNull().references(() => barbers.id, { onDelete: "cascade" }),
    startDate: timestamp("start_date", { withTimezone: true, mode: "date" }).notNull(),
    endDate: timestamp("end_date", { withTimezone: true, mode: "date" }).notNull(),
    reason: varchar("reason", { length: 120 }),
    createdAt: timestamp("created_at", { withTimezone: true }).notNull().defaultNow(),
  },
  (t) => [
    check("barber_time_off_range", sql`${t.endDate} >= ${t.startDate}`),
    index("barber_time_off_barber_idx").on(t.barberId, t.startDate),
  ]
);

// 8. Booking
export const bookings = pgTable(
  "bookings",
  {
    id: uuid("id").primaryKey().defaultRandom(),
    code: varchar("code", { length: 6 }).notNull(),
    barberId: uuid("barber_id").notNull().references(() => barbers.id, { onDelete: "restrict" }),
    customerName: varchar("customer_name", { length: 80 }).notNull(),
    customerPhone: varchar("customer_phone", { length: 16 }),
    startAt: timestamp("start_at", { withTimezone: true }).notNull(),
    endAt: timestamp("end_at", { withTimezone: true }).notNull(),
    totalPriceIdr: integer("total_price_idr").notNull(),
    totalDurationMinutes: smallint("total_duration_minutes").notNull(),
    status: bookingStatusEnum("status").notNull().default("dikonfirmasi"),
    source: bookingSourceEnum("source").notNull().default("website"),
    cancelReason: varchar("cancel_reason", { length: 200 }),
    notes: text("notes"),
    reminderStatus: reminderStatusEnum("reminder_status").notNull().default("menunggu"),
    reminderAttempts: smallint("reminder_attempts").notNull().default(0),
    reminderSentAt: timestamp("reminder_sent_at", { withTimezone: true }),
    createdByUserId: uuid("created_by_user_id").references(() => users.id, { onDelete: "set null" }),
    createdAt: timestamp("created_at", { withTimezone: true }).notNull().defaultNow(),
    updatedAt: timestamp("updated_at", { withTimezone: true }).notNull().defaultNow(),
  },
  (t) => [
    uniqueIndex("bookings_code_unique").on(t.code),
    // Lapisan 1 anti double booking: hanya booking aktif yang mengunci slot.
    // Exclusion constraint berbasis tstzrange ditambahkan lewat migrasi SQL kustom
    // (lihat catatan di bawah), karena Drizzle belum menyediakan builder-nya.
    uniqueIndex("bookings_active_slot_unique")
      .on(t.barberId, t.startAt)
      .where(sql`${t.status} = 'dikonfirmasi'`),
    index("bookings_status_start_idx").on(t.status, t.startAt),
    index("bookings_phone_idx").on(t.customerPhone),
    check("bookings_time_order", sql`${t.endAt} > ${t.startAt}`),
  ]
);

// 9. Riwayat perubahan status booking (audit log)
export const bookingStatusLogs = pgTable("booking_status_logs", {
  id: uuid("id").primaryKey().defaultRandom(),
  bookingId: uuid("booking_id").notNull().references(() => bookings.id, { onDelete: "cascade" }),
  fromStatus: bookingStatusEnum("from_status"),
  toStatus: bookingStatusEnum("to_status").notNull(),
  note: varchar("note", { length: 200 }),
  changedByUserId: uuid("changed_by_user_id").references(() => users.id, { onDelete: "set null" }),
  createdAt: timestamp("created_at", { withTimezone: true }).notNull().defaultNow(),
});

// 10. Log notifikasi WhatsApp
export const notificationLogs = pgTable("notification_logs", {
  id: uuid("id").primaryKey().defaultRandom(),
  bookingId: uuid("booking_id").references(() => bookings.id, { onDelete: "set null" }),
  type: varchar("type", { length: 20 }).notNull(), // konfirmasi | pengingat | batal
  target: varchar("target", { length: 16 }).notNull(),
  message: text("message").notNull(),
  providerResponse: text("provider_response"),
  success: boolean("success").notNull().default(false),
  createdAt: timestamp("created_at", { withTimezone: true }).notNull().defaultNow(),
});

// RELASI (ringkas)
// bookings.barberId -> barbers.id
// barberServices: penghubung many-to-many barbers <-> services
// barberSchedules & barberTimeOff: milik satu barber
// bookingStatusLogs & notificationLogs: milik satu booking
```

**Catatan Migrasi Khusus (wajib dijalankan sekali di file SQL kustom setelah `drizzle-kit generate`):**

```sql
-- migrations/0001_exclusion_slot.sql
CREATE EXTENSION IF NOT EXISTS btree_gist;

ALTER TABLE bookings
  ADD CONSTRAINT bookings_no_overlap
  EXCLUDE USING gist (
    barber_id WITH =,
    tstzrange(start_at, end_at, '[)') WITH &&
  )
  WHERE (status = 'dikonfirmasi');
```

**Catatan Logika Ketersediaan Slot:**
- Slot dihitung di server dengan langkah 30 menit dari jam mulai jadwal kapster sampai jam selesai dikurangi total durasi layanan terpilih.
- Slot dikeluarkan jika bertabrakan dengan booking berstatus `dikonfirmasi` milik kapster yang sama, atau jatuh pada rentang `barber_time_off`.
- Saat submit, Server Action membuka transaksi, mengunci baris `barbers` dengan `SELECT ... FOR UPDATE`, memvalidasi ulang slot, lalu menyimpan booking. Jika constraint melempar error 23P01 (exclusion violation) atau 23505 (unique violation), sistem mengembalikan pesan slot penuh.

### Variabel Lingkungan (`.env.example`)
```env
# Aplikasi
NEXT_PUBLIC_APP_URL=http://localhost:3000
NEXT_PUBLIC_SALON_NAME=SalonBook
APP_TIMEZONE=Asia/Jakarta

# Database (Neon PostgreSQL)
DATABASE_URL=postgresql://user:password@ep-xxxx.ap-southeast-1.aws.neon.tech/salonbook?sslmode=require

# Autentikasi (Auth.js v5)
AUTH_SECRET=isi_dengan_string_acak_minimal_32_karakter
AUTH_URL=http://localhost:3000
AUTH_SESSION_MAX_AGE_DAYS=7

# WhatsApp Gateway (Fonnte)
WA_PROVIDER=fonnte
FONNTE_API_TOKEN=isi_token_dari_dashboard_fonnte
FONNTE_API_URL=https://api.fonnte.com/send
WA_SENDER_NUMBER=6281234567890

# Pengingat & Cron
REMINDER_HOURS_BEFORE=3
REMINDER_WINDOW_MINUTES=20
CRON_SECRET=isi_string_acak_untuk_otorisasi_cron

# Rate Limiting (Upstash Redis, opsional untuk MVP)
UPSTASH_REDIS_REST_URL=
UPSTASH_REDIS_REST_TOKEN=

# Booking
BOOKING_MAX_DAYS_AHEAD=60
BOOKING_MIN_LEAD_MINUTES=60
BOOKING_MAX_ACTIVE_PER_PHONE=3
SLOT_INTERVAL_MINUTES=30
```

---

## 11. Tahapan Pengerjaan & Task Breakdown (Actionable Work Breakdown Structure)
*Daftar tugas terstruktur dan terurut (Atomic Tasks) dengan format checklist markdown `- [ ] **Task X.Y`. Dirancang khusus agar pengguna dapat menginstruksikan AI Coding Assistant (Antigravity, Cursor, Claude Code, Roo Code, dll.) untuk mengeksekusi proyek langkah demi langkah secara terukur, modular, dan bebas dari kehabisan context window.*

### Tahap 1: Fondasi Proyek, UI/UX, & Semua Halaman (Dummy Data)
*Tujuan: Membangun seluruh antarmuka visual secara 100% lengkap dan responsif menggunakan data dummy sebelum menyentuh database.*
- [ ] **Task 1.1 (Foundations & Design System)**: Setup Next.js 15 App Router + TypeScript, konfigurasi Tailwind CSS v4 dengan token warna Minimalist (Hitam Tinta, Abu Lembut, Hijau Sage, Destructive) sebagai CSS variable, load font Plus Jakarta Sans dan Inter via `next/font`, instal Lucide Icons, dan instal base shadcn/ui (Button, Card, Input, Dialog, Table, Badge, Dropdown Menu, Tabs, Select, Calendar, Sheet, Toast).
- [ ] **Task 1.2 (Layouts & Persistent Navigation)**: Buat `app/layout.tsx` (root), `app/(public)/layout.tsx` (Header navbar SalonBook dengan menu Layanan, Tentang, Kontak, Cek Booking, dan tombol "Booking Sekarang"; Footer dengan jam buka dan nomor WhatsApp), `app/booking/layout.tsx` (header minimal, indikator langkah 1 sampai 4, sticky ringkasan total di mobile), `app/admin/layout.tsx` (Sidebar fixed dengan menu Dashboard, Booking, Jadwal, Layanan, Kapster, Pengaturan, Pengguna; header kecil; drawer di mobile), dan `app/masuk/layout.tsx` (auth layout terpusat).
- [ ] **Task 1.3 (Public Pages dengan Dummy Data)**: Buat halaman `/` (hero, layanan unggulan, daftar kapster, testimoni dummy, jam buka), `/layanan` (grid kartu dengan filter kategori Rambut, Janggut, Perawatan, Paket dari data dummy), `/layanan/[slug]` (detail layanan dengan kapster yang menguasai dan CTA booking), `/tentang`, `/kontak` (alamat, tombol chat WhatsApp, peta statis), dan `/cek-booking` (formulir kode dan WhatsApp, tampilan hasil dummy). Gunakan data dummy dari Bab 9.
- [ ] **Task 1.4 (Wizard Booking Multi-Langkah Dummy)**: Buat halaman `/booking` dengan 4 langkah: pilih layanan (multi-select dengan total harga dan durasi), pilih kapster (kartu kapster dan opsi "Siapa saja yang tersedia"), pilih tanggal dan jam (komponen Calendar dan chip slot dengan status kosong, terpilih, dan penuh berdasarkan data dummy), serta isi nama dan WhatsApp dengan validasi Zod sisi klien. Buat `/booking/sukses/[kode]` dengan kode dummy, ringkasan jadwal, catatan pembayaran di salon, dan tombol unduh .ics dummy.
- [ ] **Task 1.5 (Halaman Login & Dashboard Admin dengan Dummy)**: Buat `/masuk` (formulir Email dan Password, tanpa logika autentikasi nyata), `/admin/dashboard` (kartu ringkasan booking hari ini, daftar antrean urut jam, kapster bertugas), `/admin/booking` (tabel dengan filter tanggal, kapster, status, pencarian, dan dropdown aksi ubah status dummy), `/admin/booking/[id]` (detail, riwayat status dummy, tombol ubah status), dan `/admin/booking/baru` (form walk-in dengan pilihan slot dummy).
- [ ] **Task 1.6 (Halaman Manajemen Admin dengan Dummy)**: Buat `/admin/layanan` (tabel dengan modal tambah dan edit, toggle aktif), `/admin/kapster` (kartu kapster dengan modal edit dan pemilihan layanan), `/admin/jadwal` (grid mingguan jam kerja per kapster dan daftar libur), `/admin/jadwal/kalender` (timeline harian per kapster dengan blok booking dummy dan blok libur), `/admin/pengaturan` (form nama salon, alamat, WhatsApp, jam buka, dan editor template pesan dengan variabel), `/admin/pengguna` (tabel staf dengan modal tambah, khusus tampilan Owner), dan `/admin/profil` (form ubah nama dan password).
- [ ] **Task 1.7 (Responsive QA & Komponen Bersama)**: Buat komponen bersama `StatusBadge`, `PriceTag`, `DurationChip`, `SlotChip`, `EmptyState`, dan `ConfirmDialog`. Pastikan seluruh halaman dari Task 1.3 hingga 1.6 responsif pada lebar 360px, 768px, dan 1280px, dengan whitespace konsisten dan border tipis.

### Tahap 2: Database, Autentikasi, & Integrasi Data Dinamis
*Tujuan: Menghidupkan aplikasi dengan database Neon PostgreSQL, autentikasi Email & Password, dan seluruh logika booking nyata.*
- [ ] **Task 2.1 (Database Schema & Migrations)**: Buat `db/schema.ts` sesuai Bab 10 (tabel `users`, `salon_settings`, `services`, `barbers`, `barber_services`, `barber_schedules`, `barber_time_off`, `bookings`, `booking_status_logs`, `notification_logs`), konfigurasi `drizzle.config.ts`, jalankan `drizzle-kit generate` dan `drizzle-kit migrate` ke Neon, lalu terapkan migrasi SQL kustom `0001_exclusion_slot.sql` untuk exclusion constraint `bookings_no_overlap` dengan ekstensi `btree_gist`.
- [ ] **Task 2.2 (Seed Data Awal)**: Buat `db/seed.ts` yang mengisi tabel `salon_settings` (satu baris), `services` (sembilan layanan dari Bab 9 dengan slug unik), `barbers` (Dimas Pratama, Rizky Ananda, Fajar Nugroho) beserta `barber_services` dan `barber_schedules` default, `users` (Hendra Gunawan sebagai owner dan Sari Lestari sebagai staf dengan password hash bcrypt), serta beberapa `bookings` dummy dengan status campuran untuk hari ini dan minggu ini.
- [ ] **Task 2.3 (Autentikasi Email & Password)**: Pasang Auth.js v5 dengan Credentials Provider di `auth.ts`, strategi JWT, sesi 7 hari, validasi kredensial dengan Zod, cek `is_active`, pengisian `last_login_at`, dan logika `must_change_password` yang mengarahkan ke `/admin/profil`. Hubungkan form `/masuk` ke Server Action login.
- [ ] **Task 2.4 (Middleware Proteksi Rute & RBAC)**: Buat `middleware.ts` untuk memproteksi seluruh rute `/admin/*` (redirect ke `/masuk` jika belum login), serta pembatasan rute `/admin/layanan`, `/admin/kapster`, `/admin/pengaturan`, dan `/admin/pengguna` hanya untuk role `owner`. Terapkan double check di setiap Server Action yang sensitif.
- [ ] **Task 2.5 (Modul Ketersediaan Slot)**: Buat `lib/booking/availability.ts` yang menghitung slot per kapster per tanggal dengan langkah 30 menit, mempertimbangkan jadwal mingguan, durasi total layanan, booking aktif, dan `barber_time_off`, serta zona waktu `Asia/Jakarta`. Tambahkan unit test untuk kasus tabrakan, libur, dan batas jam buka.
- [ ] **Task 2.6 (Server Action Pembuatan Booking & Penguncian Slot)**: Buat `app/booking/actions.ts` dengan `createBookingAction` yang memvalidasi input dengan Zod (nama, normalisasi WhatsApp ke `62...`, layanan, kapster, tanggal, jam), menerapkan aturan lead time 60 menit, maksimal 60 hari ke depan, dan batas 3 booking aktif per nomor. Logika berjalan dalam transaksi dengan `SELECT ... FOR UPDATE` pada baris `barbers`, menghasilkan kode booking unik 6 karakter tanpa karakter ambigu, menyimpan snapshot harga dan durasi, dan menangkap error exclusion (23P01) atau unique (23505) untuk pesan "Maaf, jam ini baru saja dipesan".
- [ ] **Task 2.7 (Server Action Dashboard Booking)**: Buat Server Actions `updateBookingStatusAction` (aturan transisi status Dikonfirmasi ke Selesai atau Batal, validasi jam selesai untuk status Selesai, alasan pembatalan opsional, pencatatan `booking_status_logs`), `createWalkInBookingAction` (menggunakan logika ketersediaan yang sama dengan sumber `walk_in`), dan `getBookingsAction` (filter tanggal, kapster, status, dan pencarian).
- [ ] **Task 2.8 (Server Action Layanan, Kapster, Jadwal, & Pengaturan)**: Buat CRUD untuk `services` (dengan validasi harga kelipatan 1.000, durasi kelipatan 15, dan aturan hanya nonaktifkan jika sudah pernah dipesan), `barbers` dan `barber_services`, `barber_schedules` (validasi jam mulai dan selesai, pembulatan 15 menit), `barber_time_off` (validasi tidak mencakup booking aktif), `salon_settings` (update baris tunggal), dan `users` untuk staf (hash password, `must_change_password` true). Setiap mutasi memanggil `revalidatePath` atau `revalidateTag` yang sesuai.
- [ ] **Task 2.9 (Frontend Data Binding & Mutations)**: Hubungkan seluruh halaman dari Tahap 1 dengan data dinamis dari database. Halaman publik menggunakan Server Components dengan `revalidate` 300 detik, wizard booking memanggil endpoint ketersediaan dan Server Action pembuatan booking, halaman sukses dan cek booking membaca data nyata, dan dashboard memanggil Server Actions untuk daftar, detail, perubahan status, walk-in, serta seluruh modul manajemen. Gunakan `useActionState` dan Toast untuk feedback.
- [ ] **Task 2.10 (Pencatatan Audit & Cek Booking Aman)**: Terapkan `/cek-booking` dengan pencocokan kode dan nomor WhatsApp yang dinormalisasi, rate limiting 5 percobaan gagal per 15 menit per IP (menggunakan Upstash Redis atau fallback tabel sementara), serta pencatatan audit untuk perubahan harga, perubahan jadwal, dan perubahan akun staf.

### Tahap 3: Integrasi WhatsApp, Pengingat, Keamanan, SEO, & Deployment
*Tujuan: Menyempurnakan notifikasi WhatsApp otomatis, optimasi performa, keamanan, dan rilis ke production.*
- [ ] **Task 3.1 (Modul WhatsApp Gateway)**: Buat `lib/wa/` dengan antarmuka `WaProvider` dan implementasi `fonnte.ts` yang melakukan HTTP POST ke `FONNTE_API_URL` dengan header `Authorization: FONNTE_API_TOKEN`. Tambahkan fungsi render template dengan variabel `{nama}`, `{layanan}`, `{jam}`, `{kapster}`, `{kode}`, dan `{nama_salon}` serta escape karakter berbahaya. Setiap pengiriman dicatat di `notification_logs`.
- [ ] **Task 3.2 (Notifikasi Konfirmasi & Pembatalan)**: Kirim WhatsApp konfirmasi otomatis setelah `createBookingAction` berhasil (jika nomor WhatsApp diisi) dan WhatsApp pembatalan saat `updateBookingStatusAction` mengubah status menjadi Batal. Tangani kegagalan kirim tanpa menggagalkan transaksi booking utama.
- [ ] **Task 3.3 (Cron Pengingat 3 Jam Sebelum Jadwal)**: Buat `app/api/cron/pengingat/route.ts` yang memverifikasi `Authorization: Bearer CRON_SECRET`, memilih booking Dikonfirmasi dengan `start_at` antara sekarang + 170 menit dan sekarang + 190 menit serta `reminder_status` menunggu, mengirim pesan pengingat, memperbarui `reminder_status`, `reminder_attempts`, dan `reminder_sent_at`, dan menandai gagal setelah 3 kali percobaan. Tambahkan `vercel.json` dengan jadwal `*/10 * * * *`. Booking yang dibuat kurang dari 3 jam sebelum jadwal ditandai `dilewati`.
- [ ] **Task 3.4 (Dashboard Peringatan Operasional)**: Tambahkan badge "Perlu Ditutup" untuk booking Dikonfirmasi yang jam selesainya sudah lewat, dan panel peringatan untuk pengingat yang gagal kirim pada dashboard admin, dengan tombol kirim ulang manual.
- [ ] **Task 3.5 (Unduh Kalender .ics)**: Buat `app/booking/sukses/[kode]/ics/route.ts` yang menghasilkan file kalender .ics untuk booking dengan zona waktu `Asia/Jakarta`, judul layanan, lokasi salon, dan pengingat 3 jam sebelum.
- [ ] **Task 3.6 (Non-Functional Requirements & Security)**: Pasang dynamic SEO tags (`generateMetadata` untuk setiap halaman publik dengan `<title>`, meta description, Open Graph, dan Twitter Card), JSON-LD `HairSalon` di beranda dan kontak, `sitemap.ts`, `robots.ts`, `noindex` pada dashboard dan login, header keamanan (CSP dasar, X-Frame-Options, Referrer-Policy), rate limiting endpoint login, pembuatan booking, dan cek booking, serta sanitasi input dan masking nomor WhatsApp di halaman publik.
- [ ] **Task 3.7 (Optimasi Performa)**: Terapkan `<Image>` dengan format WebP atau AVIF untuk seluruh foto kapster dan layanan, `next/dynamic` untuk komponen Calendar dan kalender admin, caching data publik dengan `revalidate` dan `revalidateTag`, serta indeks database `bookings(barber_id, start_at)`, `bookings(status, start_at)`, dan `bookings(customer_phone)`. Verifikasi target LCP di bawah 2,5 detik.
- [ ] **Task 3.8 (End-to-End Testing & Bugfix)**: Uji coba seluruh alur pengguna: booking tanpa akun hingga konfirmasi WhatsApp, dua pelanggan memilih slot yang sama secara bersamaan (memastikan hanya satu berhasil), perubahan status Selesai dan Batal, booking walk-in, libur kapster yang memblokir slot, pengingat cron (simulasi), login staf dengan password sementara, pembatasan akses staf ke menu Owner, serta responsivitas pada 360px, 768px, dan 1280px. Perbaiki seluruh bug dan glitch yang ditemukan.
- [ ] **Task 3.9 (Production Build & Deployment)**: Konfigurasi environment production di Vercel (salin seluruh variabel dari `.env.example` dengan nilai production), jalankan migrasi ke Neon production, verifikasi kelulusan `npm run build` tanpa error TypeScript maupun lint, konfigurasi Vercel Cron, pasang domain, dan lakukan smoke test pada halaman publik, alur booking, login, dan pengiriman WhatsApp uji.

---

## 12. Master Starter Prompt (Siap Coding untuk AI Agent)
*Salin prompt di bawah ini ke AI Coding Assistant (Google Antigravity / Cursor / Claude Code / GitHub Copilot / Roo Code / dll.) untuk memulai pengerjaan:*

```markdown
Halo! Kamu berperan sebagai Senior Fullstack Architect dan Lead Developer.
Saya ingin membangun aplikasi "SalonBook" (booking salon dan barbershop) berdasarkan dokumen PRD ini.

Silakan baca file @PRD.md

TECH STACK WAJIB (JANGAN DIUBAH):
- Next.js 15 App Router + TypeScript, Server Components dan Server Actions
- Tailwind CSS v4 + shadcn/ui + Lucide Icons
- Font: Plus Jakarta Sans (heading) dan Inter (body) via next/font
- Auth.js v5 dengan Credentials Provider (Email & Password), sesi JWT 7 hari
- Neon PostgreSQL + Drizzle ORM, dengan exclusion constraint slot dari migrasi 0001_exclusion_slot.sql
- Zod untuk validasi
- Fonnte sebagai WhatsApp Gateway melalui modul lib/wa/
- Vercel Cron untuk pengingat 3 jam sebelum jadwal
- Pembayaran dilakukan langsung di salon (JANGAN membuat integrasi payment gateway)

ATURAN EKSEKUSI (WAJIB DIPATUHI):
1. Eksekusi dilakukan per FASE (Milestone). Kerjakan seluruh Task dalam satu fase secara mandiri dan tuntas dalam satu putaran kerja. Jangan menulis kode untuk fase berikutnya.
2. Fase 1 (Tahap 1: Task 1.1 sampai 1.7): Bangun seluruh UI/UX dan semua halaman (publik, wizard booking, login, dan admin) lengkap dengan data dummy Bahasa Indonesia dari Bab 9. Setelah selesai, laporkan ringkasan hasil, daftar file yang dibuat, dan cara menjalankannya. Lalu BERHENTI dan MENUNGGU konfirmasi saya sebelum lanjut ke Fase 2.
3. Fase 2 (Tahap 2: Task 2.1 sampai 2.10): Hanya dimulai setelah saya memberi izin. Kerjakan database, autentikasi, dan logika booking nyata secara tuntas. Setelah selesai, laporkan hasil dan BERHENTI untuk menunggu konfirmasi saya.
4. Fase 3 (Tahap 3: Task 3.1 sampai 3.9): Hanya dimulai setelah saya memberi izin. Kerjakan integrasi WhatsApp, cron pengingat, keamanan, SEO, performa, pengujian, dan deployment secara tuntas. Setelah selesai, laporkan hasil.
5. DILARANG KERAS membuat "Halaman Placeholder" atau teks "Sedang dalam pengembangan". Setiap halaman di Bab 3 wajib dibuat penuh dengan UI dan data dummy yang relevan.
6. DILARANG KERAS membuat halaman atau fitur pembayaran online. Pembayaran hanya dilakukan di salon.
7. Pastikan slot booking tidak pernah bisa ganda. Gunakan exclusion constraint di database, transaksi dengan SELECT FOR UPDATE, dan pesan error yang ramah pengguna.
8. Patuhi PRD secara ketat untuk skema database (Bab 10), nama route (Bab 3), hak akses (Bab 5), aturan bisnis (Bab 6), dan Design System Minimalist (Bab 4).
9. Gunakan Bahasa Indonesia untuk seluruh teks antarmuka, pesan error, dan komentar penting.

Jika kamu sudah membaca dan memahami PRD, berikan ringkasan singkat pemahamanmu (maksimal 10 poin), sebutkan rencana file untuk Fase 1, lalu tanyakan kesiapan saya untuk mulai mengerjakan Fase 1 (Task 1.1 sampai 1.7)!
```