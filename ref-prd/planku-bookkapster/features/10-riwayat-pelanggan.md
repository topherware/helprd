# Riwayat Pelanggan

Memudahkan pelanggan setia booking ulang layanan favoritnya tanpa mengisi ulang dari awal setiap kali datang.

## Spesifikasi

### Tujuan
Memberi pelanggan cara cepat melihat histori booking mereka sendiri lewat nomor WhatsApp dan membuat booking baru dengan menyalin detail booking sebelumnya.

### Selesai bila
- Pelanggan bisa memasukkan nomor WhatsApp dan melihat daftar booking yang pernah dibuat dengan nomor itu
- Pelanggan hanya bisa melihat booking yang kontak_tamu-nya cocok dengan nomor yang dimasukkan, tidak bisa melihat booking milik nomor lain
- Pelanggan bisa memilih satu booking lama dan melanjutkan ke alur booking baru dengan layanan serta kapster yang sama sudah terisi otomatis
- Fitur berfungsi penuh di atas data booking nyata dari database, bukan data tiruan
- Validasi kecocokan nomor WhatsApp diuji dan terbukti menolak akses ke booking milik orang lain

## Sub-fitur: Lihat histori booking by nomor WA

### Tujuan
Pelanggan dapat memeriksa semua booking yang pernah dibuat dengan satu nomor WhatsApp.

### Selesai bila
- Form input nomor WhatsApp menampilkan daftar booking terkait beserta layanan, kapster, jam, dan status
- Daftar booking hanya menampilkan hasil yang kontak_tamu-nya cocok dengan nomor yang diinput

## Sub-fitur: Booking ulang layanan favorit

### Tujuan
Pelanggan dapat membuat booking baru dengan cepat menyalin layanan dan kapster dari booking sebelumnya.

### Selesai bila
- Tersedia tombol 'booking ulang' pada tiap item histori yang membawa pelanggan ke alur booking dengan layanan dan kapster terisi otomatis
- Pelanggan tetap bisa mengubah jam atau kapster sebelum konfirmasi booking ulang

## Task

Progres: 0/14 selesai

- [ ] [M1] Desain halaman form input nomor WhatsApp untuk cek histori _(Utama)_
- [ ] [M1] Rancang tampilan daftar histori booking pakai DUMMY-BOOKING _(Utama)_
- [ ] [M1] Tampilkan status kosong saat nomor WA belum punya booking _(Penting)_
- [ ] [M1] Desain kartu booking lama dengan tombol booking ulang _(Utama)_
- [ ] [M1] Rancang alur booking ulang dengan layanan dan kapster terisi otomatis dari DUMMY-BOOKING _(Utama)_
- [ ] [M1] Pastikan tampilan histori dan booking ulang responsif di mobile _(Penting)_
- [ ] [M2] Buat API pencarian booking berdasarkan nomor WhatsApp _(Utama)_
- [ ] [M2] Terapkan validasi server agar hasil pencarian difilter sesuai kontak_tamu _(Utama)_
- [ ] [M2] Hubungkan tombol booking ulang ke alur booking dengan data layanan dan kapster asli _(Utama)_
- [ ] [M2] Validasi ulang slot kosong saat pelanggan booking ulang di jam baru _(Utama)_
- [ ] [M3] Terapkan validasi skema input nomor WhatsApp di server sesuai SEC-08 _(Utama)_
- [ ] [M3] Pastikan endpoint histori booking tidak mengembalikan data tanpa filter kepemilikan sesuai SEC-03 _(Utama)_
- [ ] [M3] Pastikan log server tidak mencatat nomor WhatsApp lengkap sesuai SEC-12 _(Penting)_
- [ ] [M3] Tulis uji otomatis untuk validasi kecocokan nomor WA dan alur booking ulang _(Utama)_
