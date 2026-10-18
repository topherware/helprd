# Pengingat Sebelum Jadwal

Pelanggan mendapat pengingat otomatis 3 jam sebelum jadwalnya agar tidak lupa datang.

## Spesifikasi

### Tujuan
Pelanggan tidak lupa datang ke salon karena sistem otomatis mengirim pengingat 3 jam sebelum jadwalnya lewat WhatsApp, sehingga jadwal tidak hangus gara-gara pelanggan lupa.

### Selesai bila
- Setiap booking yang masih berlaku menerima tepat satu pengingat saat jadwalnya tinggal 3 jam lagi, tidak kurang dan tidak dobel.
- Isi pengingat menyebut nama pelanggan, tanggal dan jam jadwal, nama kapster, layanan, dan kode booking, sehingga pelanggan tahu persis harus datang kapan dan ke mana.
- Booking yang dibatalkan atau sudah selesai tidak pernah diingatkan.
- Pemilik dapat melihat, dalam satu daftar, booking mana yang pengingatnya sudah terkirim, belum terkirim, atau gagal terkirim.
- Pengingat yang gagal terkirim tetap terlihat oleh pemilik, tidak hilang diam-diam.

## Sub-fitur: Pengingat 3 Jam Sebelum

Sistem mengirim pengingat otomatis 3 jam sebelum jam booking pelanggan.

### Tujuan
Sistem mengirim pengingat secara otomatis sekitar 3 jam sebelum jam booking pelanggan, tanpa pemilik atau pelanggan perlu melakukan apa pun.

### Selesai bila
- Booking yang jam mulainya tinggal 3 jam lagi menerima pengingat (toleransi ±5 menit dari waktu tersebut).
- Pemeriksaan berjalan berulang sepanjang hari, sehingga booking yang baru dibuat pada hari yang sama tetap kebagian pengingat.
- Booking yang sudah pernah diingatkan tidak dikirimi pengingat kedua walaupun sistem memeriksa ulang.
- Booking berstatus batal atau selesai tidak pernah masuk daftar yang diingatkan.

## Sub-fitur: Kirim ke WhatsApp

Pengingat dikirim ke nomor WhatsApp yang diisi pelanggan saat memesan.

### Tujuan
Pengingat benar-benar sampai ke pelanggan lewat WhatsApp pada nomor yang mereka isi sendiri saat memesan.

### Selesai bila
- Pesan dikirim ke nomor WhatsApp yang tersimpan pada booking tersebut, dengan penulisan nomor Indonesia yang benar.
- Isi pesan memuat nama pelanggan, tanggal dan jam jadwal, nama kapster, layanan yang dipesan, kode booking, dan kalimat pengingat bahwa pembayaran dilakukan di salon.
- Nomor yang kosong atau tidak bisa dihubungi dicatat sebagai gagal terkirim, bukan dianggap berhasil.

## Sub-fitur: Status Pengingat

Pemilik bisa melihat pengingat mana yang sudah terkirim ke pelanggan.

### Tujuan
Pemilik dapat memantau pengingat mana yang sudah sampai ke pelanggan, sehingga ia tahu siapa yang masih perlu dihubungi manual.

### Selesai bila
- Daftar booking harian menampilkan penanda status pengingat dengan label yang jelas: sudah terkirim, belum terkirim, atau gagal.
- Untuk booking yang sudah diingatkan, pemilik bisa melihat waktu pengingat itu terkirim.
- Status yang tampil selalu sesuai kondisi terbaru saat halaman dibuka, termasuk perubahan setelah pengingat baru saja dikirim.

## Task

### 1. Bangun halaman daftar booking harian dengan data tiruan

### 2. Buat komponen badge status pengingat dengan label jelas

### 3. Tampilkan waktu pengingat terkirim per booking

### 4. Buat pratinjau isi pesan pengingat WhatsApp

### 5. Sediakan filter dan penyegaran status pengingat

### 6. Buat skema kolom status pengingat dan migrasinya

### 7. Buat util normalisasi nomor WhatsApp Indonesia

### 8. Integrasikan layanan pengiriman pesan WhatsApp

### 9. Buat service penyusun isi pesan pengingat

### 10. Buat penjadwal berkala pemeriksa booking 3 jam lagi

### 11. Jamin idempotensi pengingat agar tidak terkirim dobel

### 12. Buat endpoint daftar booking harian dengan status pengingat

### 13. Catat dan tampilkan pengingat yang gagal terkirim
