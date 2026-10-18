# Kelola Layanan & Harga

Pemilik salon mengatur layanan, harga, dan durasi sendiri tanpa bantuan teknis.

## Spesifikasi

### Tujuan
Memberi pemilik salon kendali penuh untuk mengatur daftar layanan, harga, dan durasi sendiri lewat satu dasbor, tanpa bantuan teknis, sehingga katalog yang dilihat pelanggan selalu akurat.

### Selesai bila
- Pemilik dapat membuka satu halaman "Kelola Layanan" dari dasbor yang menampilkan seluruh layanan beserta harga dan durasinya dalam satu daftar.
- Dari halaman itu pemilik bisa menambah layanan baru, mengubah nama dan durasi, mengubah harga, serta menonaktifkan layanan — semuanya tanpa berpindah aplikasi.
- Harga selalu tampil dalam format rupiah (mis. "Rp 50.000") dan durasi dalam menit (mis. "45 menit"), baik di dasbor pemilik maupun di katalog pelanggan.
- Perubahan yang disimpan pemilik langsung terlihat di katalog pelanggan, sementara booking lama tetap menampilkan harga yang berlaku saat dipesan.
- Setiap perubahan layanan tercatat siapa pelakunya dan kapan, dan bisa dilihat pemilik di riwayat perubahan.

## Sub-fitur: Tambah & Ubah Layanan

Pemilik menambah layanan baru atau mengubah nama dan durasinya.

### Tujuan
Memungkinkan pemilik menambahkan layanan baru serta memperbarui nama dan durasi layanan yang sudah ada dengan cepat dan tanpa bantuan teknis.

### Selesai bila
- Ada tombol "Tambah Layanan" yang membuka formulir berisi nama layanan, durasi (menit), dan deskripsi singkat; setelah disimpan, layanan baru langsung muncul di daftar.
- Pemilik dapat mengubah nama dan durasi layanan yang sudah ada, dan perubahan langsung terlihat di daftar setelah disimpan.
- Formulir menolak penyimpanan bila nama kosong atau durasi bukan angka lebih dari 0, dengan pesan kesalahan yang mudah dimengerti.

## Sub-fitur: Atur Harga

Pemilik menetapkan dan memperbarui harga tiap layanan.

### Tujuan
Memberi pemilik cara menetapkan dan memperbarui harga tiap layanan agar katalog selalu sesuai dengan harga yang benar-benar dijual.

### Selesai bila
- Setiap layanan punya kolom harga yang bisa diubah langsung, ditampilkan sebagai rupiah tanpa desimal (mis. "Rp 50.000").
- Setelah harga disimpan, katalog pelanggan langsung menampilkan harga baru dan pemilik melihat konfirmasi singkat bahwa harga berhasil diperbarui.
- Harga tidak bisa dikosongkan atau diisi angka negatif; pesan kesalahan muncul bila isian tidak wajar.

## Sub-fitur: Nonaktifkan Layanan

Layanan yang tidak dijual lagi disembunyikan dari pelanggan tanpa menghapus riwayatnya.

### Tujuan
Menyembunyikan layanan yang tidak dijual lagi dari pelanggan tanpa menghapus data maupun riwayat booking-nya.

### Selesai bila
- Setiap layanan punya tombol "Nonaktifkan", dan setelah dinonaktifkan layanan tersebut tidak lagi muncul di katalog pelanggan.
- Layanan nonaktif tetap terlihat di dasbor pemilik dengan penanda "Nonaktif" dan bisa diaktifkan kembali kapan saja.
- Booking lama yang memuat layanan tersebut tetap utuh dan bisa dibuka seperti biasa.

## Sub-fitur: Harga Lama Tetap Tersimpan

Booking lama tetap menampilkan harga yang berlaku saat dipesan.

### Tujuan
Memastikan booking lama selalu menampilkan harga dan nama layanan yang berlaku saat dipesan, meski harga master layanan berubah kemudian.

### Selesai bila
- Saat pemilik mengubah harga sebuah layanan, rincian booking lama tidak ikut berubah dan tetap menampilkan harga saat dipesan dalam format rupiah.
- Halaman cek status booking oleh pelanggan dan daftar booking di dasbor pemilik sama-sama menampilkan harga lama tersebut, bukan harga terbaru.
- Harga lama tetap tampil benar walaupun layanan terkait kemudian dinonaktifkan.
