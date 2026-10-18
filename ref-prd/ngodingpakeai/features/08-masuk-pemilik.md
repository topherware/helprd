# Masuk Pemilik

Pemilik salon masuk dengan akunnya agar hanya dia yang bisa mengelola salon.

## Spesifikasi

### Tujuan
Memastikan hanya pemilik salon yang sah bisa membuka dan mengelola halaman pengelolaan salon, sehingga data layanan, jadwal, dan booking aman dari orang lain.
### Selesai bila
- Pemilik bisa masuk dengan email dan kata sandi, lalu langsung diarahkan ke halaman pengelolaan salon.
- Bila email atau kata sandi salah, muncul pesan yang jelas bahwa kombinasi tidak cocok, dan pemilik tetap di halaman masuk.
- Halaman pengelolaan salon tidak bisa dibuka oleh orang yang belum masuk; mereka diarahkan ke halaman masuk.
- Pemilik bisa keluar dari akun, dan setelah keluar halaman pengelolaan tidak bisa dibuka lagi tanpa masuk ulang.
- Pemilik yang lupa kata sandi bisa memulihkan akses akunnya sendiri lewat email tanpa bantuan teknis.

## Sub-fitur: Login Pemilik

Pemilik masuk memakai akunnya untuk membuka halaman pengelolaan salon.

### Tujuan
Memberi jalan bagi pemilik salon untuk masuk memakai akunnya sendiri sebagai kunci membuka halaman pengelolaan salon.
### Selesai bila
- Halaman masuk menampilkan kolom email dan kata sandi dengan label jelas serta tombol Masuk.
- Bila data benar, pemilik langsung diarahkan ke halaman pengelolaan salon.
- Bila data salah, muncul pesan singkat bahwa email atau kata sandi tidak cocok, tanpa memberi tahu bagian mana yang salah.
- Halaman masuk punya tautan ke pemulihan kata sandi bagi yang lupa.

## Sub-fitur: Keluar Akun

Pemilik bisa keluar dengan aman dari perangkat yang dipakai bersama.

### Tujuan
Memungkinkan pemilik keluar dari akunnya dengan aman, terutama saat memakai perangkat bersama atau milik orang lain.
### Selesai bila
- Di halaman pengelolaan tersedia tombol Keluar Akun yang mudah ditemukan.
- Setelah menekan Keluar Akun, pemilik kembali ke halaman masuk dan halaman pengelolaan tidak bisa dibuka lagi tanpa masuk ulang.
- Menekan tombol kembali di browser setelah keluar tidak menampilkan halaman pengelolaan.

## Sub-fitur: Lupa Kata Sandi

Pemilik bisa memulihkan akses akunnya sendiri tanpa bantuan teknis.

### Tujuan
Memungkinkan pemilik yang lupa kata sandinya memulihkan akses akunnya sendiri lewat email, tanpa perlu bantuan teknis.
### Selesai bila
- Halaman lupa kata sandi punya kolom email dan tombol Kirim Tautan Pemulihan.
- Setelah mengirim, muncul pesan bahwa tautan pemulihan sudah dikirim (tanpa memberi tahu apakah email terdaftar atau tidak).
- Tautan pemulihan mengarah ke halaman untuk membuat kata sandi baru dengan kolom Kata Sandi Baru dan Ulangi Kata Sandi Baru.
- Bila kata sandi baru tersimpan, muncul pesan berhasil dan pemilik bisa masuk memakai kata sandi baru.
- Tautan pemulihan yang sudah kedaluwarsa menampilkan pesan bahwa tautan tidak berlaku lagi dan menawarkan untuk meminta tautan baru.
