# Booking Tanpa Akun

Memudahkan pelanggan membuat booking sendiri tanpa perlu daftar akun, cukup dengan nama dan nomor WhatsApp.

## Spesifikasi

### Tujuan
Menyediakan alur booking mandiri yang cepat bagi pengunjung anonim sehingga tidak lagi bergantung pada chat manual ke kasir.

### Selesai bila
- Pengunjung bisa menyelesaikan booking dari pilih layanan sampai konfirmasi tanpa membuat akun
- Form booking mewajibkan nama dan nomor WhatsApp sebelum booking disimpan
- Konfirmasi instan muncul di layar setelah booking berhasil, mencakup layanan, kapster, jam, dan harga
- Daftar layanan menampilkan harga dan durasi dengan benar sebelum pelanggan memilih

### Keamanan
Persyaratan Wajib MVP terkait (detail di PRD bab 8):
- **SEC-03** — Pelanggan yang mengecek status booking tanpa akun hanya dapat melihat booking yang cocok dengan nomor WhatsApp yang ia masukkan sendiri; server memvalidasi kecocokan kontak_tamu sebelum mengembalikan data booking, tidak ada endpoint yang mengembalikan daftar booking tanpa filter kepemilikan
- **SEC-08** — Semua input dari form booking publik (nama, nomor WhatsApp, pilihan layanan/kapster/jam) divalidasi tipenya dan panjangnya di server menggunakan skema validasi sebelum disimpan; seluruh query database menggunakan parameter lewat Drizzle ORM, tidak ada string SQL mentah
- **SEC-12** — Seluruh akses aplikasi (halaman booking publik dan panel admin) hanya melalui HTTPS, dan log server tidak pernah mencatat nomor WhatsApp lengkap, kata sandi, atau token sesi secara mentah, cukup ID referensi

## Sub-fitur: Isi nama & nomor WhatsApp

### Tujuan
Mengumpulkan identitas pemesan tanpa proses pendaftaran akun.

### Selesai bila
- Form menerima input nama dan nomor WhatsApp dengan validasi format dasar
- Data tidak bisa dikirim bila nama atau nomor WhatsApp kosong

## Sub-fitur: Pilih layanan, kapster, jam kosong

### Tujuan
Memungkinkan pelanggan memilih layanan, kapster, dan jam kosong dalam satu alur.

### Selesai bila
- Pelanggan dapat memilih satu layanan, satu kapster, dan satu slot jam kosong secara berurutan
- Pilihan yang sudah dibuat tersimpan sementara saat pindah ke langkah berikutnya

## Sub-fitur: Konfirmasi booking instan

### Tujuan
Menampilkan ringkasan booking langsung setelah booking berhasil disimpan.

### Selesai bila
- Halaman sukses menampilkan ringkasan layanan, kapster, jam, dan harga
- Ringkasan muncul tanpa perlu reload halaman penuh

## Sub-fitur: Lihat harga & durasi layanan

### Tujuan
Memberi informasi harga dan durasi layanan sebelum pelanggan memutuskan memilih.

### Selesai bila
- Setiap layanan menampilkan harga dan durasi dengan format yang mudah dibaca
- Daftar layanan hanya menampilkan layanan yang aktif

## Task

Progres: 0/14 selesai

- [ ] [M1] Desain halaman daftar layanan dengan harga dan durasi _(Utama)_
- [ ] [M1] Buat alur pilih layanan, kapster, dan jam kosong pakai DUMMY-LAYANAN dan DUMMY-KAPSTER _(Utama)_
- [ ] [M1] Buat form isi nama dan nomor WhatsApp dengan validasi tampilan _(Utama)_
- [ ] [M1] Buat halaman konfirmasi booking sukses pakai DUMMY-BOOKING _(Utama)_
- [ ] [M1] Rancang tampilan responsif seluruh alur booking di mobile dan desktop _(Penting)_
- [ ] [M2] Buat skema tabel layanan dan booking lalu migrasi ke database _(Utama)_
- [ ] [M2] Buat API untuk mengambil daftar layanan aktif beserta harga dan durasi _(Utama)_
- [ ] [M2] Buat API penyimpanan booking dengan nama tamu dan kontak tamu _(Utama)_
- [ ] [M2] Hubungkan alur pilih layanan-kapster-jam ke data asli dari database _(Utama)_
- [ ] [M2] Hubungkan halaman konfirmasi ke data booking hasil penyimpanan nyata _(Utama)_
- [ ] [M3] Terapkan validasi skema untuk seluruh input form booking publik (SEC-08) _(Utama)_
- [ ] [M3] Pastikan seluruh query booking memakai parameter Drizzle ORM tanpa SQL mentah (SEC-08) _(Utama)_
- [ ] [M3] Tulis uji end-to-end alur booking tamu dari pilih layanan sampai konfirmasi _(Penting)_
- [ ] [M3] Terapkan rate limit pada endpoint booking publik sesuai NFR-04 _(Penting)_
