# Kelola Status Booking

Membantu admin memantau dan mengelola status setiap booking harian secara tertib.

## Spesifikasi

### Tujuan
Memberi admin alat untuk mengubah status booking dan memantau jadwal harian dengan jejak perubahan yang jelas.

### Selesai bila
- Admin dapat melihat daftar booking harian lengkap dengan data pelanggan dan layanan
- Admin dapat mengubah status booking menjadi dikonfirmasi, selesai, atau batal
- Setiap perubahan status tercatat siapa admin yang mengubah dan kapan
- Hanya sesi admin yang valid yang bisa mengubah status booking

### Keamanan
Persyaratan Wajib MVP terkait (detail di PRD bab 8):
- **SEC-04** — Akun admin dan kapster memakai kata sandi minimal 10 karakter yang disimpan sebagai hash kuat (bcrypt/argon2) melalui Auth.js, tidak pernah disimpan atau dicatat dalam bentuk teks biasa
- **SEC-05** — Percobaan login gagal dibatasi maksimal 5 kali per akun dalam 15 menit dan dibatasi juga per alamat IP, lalu login diblokir sementara sebelum bisa dicoba lagi
- **SEC-06** — Sesi login admin dan kapster memiliki masa kedaluwarsa, token sesi diterbitkan ulang setelah login berhasil, dan tersedia aksi logout yang membatalkan sesi di server

## Sub-fitur: Tandai dikonfirmasi, selesai, batal

### Tujuan
Memungkinkan admin mengubah status booking sesuai kondisi aktual.

### Selesai bila
- Admin bisa memilih salah satu dari tiga status booking yang tersedia
- Status yang dipilih langsung tersimpan dan terlihat di daftar booking

## Sub-fitur: Lihat daftar booking harian

### Tujuan
Menampilkan seluruh booking hari tertentu beserta detail pelanggan dan layanan.

### Selesai bila
- Daftar booking menampilkan nama pelanggan, nomor WhatsApp, layanan, kapster, jam, dan status
- Admin bisa memilih tanggal untuk melihat booking hari lain

## Sub-fitur: Catatan siapa & kapan ubah status

### Tujuan
Mencatat jejak setiap perubahan status booking untuk kebutuhan audit.

### Selesai bila
- Setiap perubahan status menyimpan id admin pengubah dan waktu perubahan
- Admin bisa melihat riwayat perubahan status pada detail booking

## Task

Progres: 0/14 selesai

- [ ] [M1] Desain halaman daftar booking harian admin pakai DUMMY-BOOKING _(Utama)_
- [ ] [M1] Desain kontrol ubah status booking (dikonfirmasi, selesai, batal) _(Utama)_
- [ ] [M1] Desain tampilan riwayat perubahan status pakai DUMMY-RIWAYAT_STATUS_BOOKING _(Penting)_
- [ ] [M1] Desain filter tanggal pada daftar booking harian _(Penting)_
- [ ] [M2] Buat skema tabel riwayat status booking lalu migrasi ke database _(Utama)_
- [ ] [M2] Buat API daftar booking harian dengan filter tanggal dari data nyata _(Utama)_
- [ ] [M2] Buat API ubah status booking dan simpan riwayat status sebelum/sesudah _(Utama)_
- [ ] [M2] Hubungkan panel admin booking ke data nyata menggantikan data tiruan _(Utama)_
- [ ] [M2] Implementasikan login admin dan kapster memakai Auth.js _(Utama)_
- [ ] [M3] Terapkan pemeriksaan sesi dan peran admin pada endpoint ubah status (SEC-01, SEC-02) _(Utama)_
- [ ] [M3] Terapkan hash kata sandi dan aturan minimal karakter untuk akun admin/kapster (SEC-04) _(Utama)_
- [ ] [M3] Terapkan pembatasan percobaan login gagal per akun dan per IP (SEC-05) _(Utama)_
- [ ] [M3] Terapkan masa kedaluwarsa sesi dan aksi logout yang membatalkan sesi (SEC-06) _(Utama)_
- [ ] [M3] Pastikan log audit dan riwayat status booking tercatat lengkap untuk setiap perubahan status (SEC-07) _(Utama)_
