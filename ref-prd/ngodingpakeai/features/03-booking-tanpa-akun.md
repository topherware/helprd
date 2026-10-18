# Booking Tanpa Akun

Pelanggan bisa menyelesaikan pemesanan hanya dengan nama dan nomor WhatsApp, tanpa mendaftar akun.

## Spesifikasi

### Tujuan
Pelanggan bisa menyelesaikan pemesanan hanya dengan nama dan nomor WhatsApp, tanpa membuat akun, sehingga tidak ada hambatan mendaftar sebelum memesan.

### Selesai bila
- Pelanggan dapat menyelesaikan pemesanan tanpa diminta membuat akun, kata sandi, atau verifikasi email.
- Formulir pemesanan hanya meminta dua data: nama dan nomor WhatsApp, dan pesanan tidak bisa dilanjutkan bila salah satunya kosong.
- Nomor WhatsApp yang tidak wajar (bukan angka, terlalu pendek) ditolak dengan pesan yang jelas dan mudah dimengerti.
- Setelah pesanan berhasil, pelanggan langsung melihat kode booking beserta ringkasan pesanannya di layar yang sama.
- Seluruh alur dari memilih layanan sampai pesanan terkirim selesai dalam maksimal 4 langkah dan nyaman dipakai di layar ponsel.

## Sub-fitur: Isi Nama & WhatsApp

Pelanggan mengisi nama dan nomor WhatsApp sebagai data pemesanan.

### Tujuan
Pelanggan mengisi nama dan nomor WhatsApp sebagai satu-satunya data yang dibutuhkan untuk memesan.

### Selesai bila
- Formulir hanya menampilkan dua kolom: nama dan nomor WhatsApp, dengan contoh pengisian nomor yang jelas (mis. 08xxxxxxxxxx).
- Tombol lanjut tidak aktif atau menampilkan pesan bila nama atau nomor WhatsApp masih kosong.
- Nomor WhatsApp yang tidak valid (mengandung huruf, terlalu pendek/panjang) ditolak dengan pesan singkat yang mudah dipahami.
- Data yang sudah diisi tetap tersimpan saat pelanggan kembali ke langkah sebelumnya, sehingga tidak perlu mengetik ulang.

## Sub-fitur: Konfirmasi Pesanan

Pelanggan meninjau ulang layanan, kapster, jam, dan harga sebelum memesan.

### Tujuan
Pelanggan meninjau ulang layanan, kapster, jam, dan harga sebelum menekan tombol pesan, agar yakin dengan pilihannya.

### Selesai bila
- Ringkasan menampilkan nama layanan yang dipilih, nama kapster, tanggal dan jam, total durasi, serta total harga dalam format rupiah (mis. Rp75.000).
- Dari ringkasan, pelanggan bisa kembali mengubah layanan, kapster, atau jam tanpa kehilangan data yang sudah diisi.
- Tersedia satu tombol pesan yang jelas sebagai langkah terakhir, disertai keterangan bahwa pembayaran dilakukan langsung di salon.
- Bila pesanan gagal karena jam sudah terisi, pelanggan melihat pesan yang menjelaskan sebabnya dan bisa memilih jam lain.

## Sub-fitur: Kode Booking & Cek Status

Pelanggan menerima kode booking dan bisa memakainya untuk melihat status pesanannya.

### Tujuan
Pelanggan menerima kode booking setelah memesan dan bisa memakai kode itu untuk melihat status pesanannya kapan saja tanpa login.

### Selesai bila
- Setelah pesanan berhasil, layar menampilkan kode booking yang mudah dibaca dan disalin, beserta ringkasan pesanan (layanan, kapster, tanggal, jam, total harga).
- Tersedia kolom untuk memasukkan kode booking dan melihat status pesanan (menunggu, dikonfirmasi, selesai, batal) beserta detail pesanannya.
- Bila kode booking salah atau tidak ditemukan, muncul pesan yang jelas dan pelanggan bisa mencoba lagi.
- Status yang tampil selalu mengikuti perubahan terbaru dari pemilik salon saat halaman dibuka ulang.
