# Pilih Kapster & Jam

Pelanggan melihat jam yang masih kosong lalu memilih kapster dan waktu yang cocok untuknya.

## Spesifikasi

### Tujuan
Pelanggan bisa melihat jam yang masih kosong, memilih kapster dan waktu yang cocok, lalu langsung tahu harga dan durasi pilihannya — tanpa perlu bertanya atau mengantre.
### Selesai bila
- Pelanggan dapat memilih kapster lalu memilih tanggal dan melihat daftar jam yang masih tersedia untuk kapster tersebut.
- Jam yang sudah terisi booking lain atau ditutup (istirahat/cuti) tidak muncul sebagai pilihan.
- Pelanggan dapat memilih tepat satu jam dari daftar yang tersedia.
- Setelah kapster, tanggal, dan jam dipilih, pelanggan langsung melihat ringkasan berisi layanan, kapster, tanggal, jam, harga, dan durasi.
- Bila kapster atau jam belum dipilih, tombol lanjut memesan tidak bisa ditekan dan ada petunjuk singkat apa yang masih kurang.

## Sub-fitur: Lihat Jam Kosong

Pelanggan melihat daftar jam yang masih tersedia untuk kapster yang dipilih.

### Tujuan
Pelanggan melihat daftar jam yang masih bisa dipesan untuk kapster dan tanggal yang dipilihnya, sehingga ia bisa memilih waktu tanpa bertanya ke salon.
### Selesai bila
- Setelah memilih kapster dan tanggal, tampil daftar jam yang masih tersedia dalam format jam yang mudah dibaca (mis. 09:00, 10:30).
- Jam yang sudah terisi booking lain atau berada di dalam jam tutup (istirahat/cuti) tidak ditampilkan sebagai pilihan.
- Bila tidak ada jam tersedia, muncul pesan jelas (mis. "Tidak ada jam kosong di tanggal ini") beserta saran memilih tanggal atau kapster lain.

## Sub-fitur: Pilih Kapster

Pelanggan memilih kapster favoritnya atau kapster yang jamnya paling pas.

subSpecs_placeholder

## Sub-fitur: Ringkasan Pilihan

Pelanggan langsung melihat harga dan durasi dari pilihan sebelum lanjut memesan.

## Task

### 1. Bangun halaman pilih kapster dan jam

### 2. Buat komponen kartu pilih kapster

### 3. Buat pemilih tanggal jadwal kapster

### 4. Tampilkan daftar jam tersedia dengan data tiruan

### 5. Tangani keadaan tanpa jam kosong

### 6. Kelola state pemilihan kapster tanggal jam

### 7. Tampilkan ringkasan pilihan harga dan durasi

### 8. Nonaktifkan tombol lanjut dan tampilkan petunjuk

### 9. Buat skema jadwal kapster dan booking

### 10. Buat endpoint daftar kapster aktif

### 11. Buat endpoint jam tersedia per kapster

### 12. Terapkan aturan jam tutup dan bentrok booking

### 13. Buat endpoint ringkasan harga dan durasi
