# Pencarian Jadwal

Membantu pelanggan menemukan kapster dengan jam kosong tercepat tanpa perlu cek satu-satu secara manual.

## Spesifikasi

### Tujuan
Memudahkan pelanggan menemukan slot booking tercepat sesuai layanan dan tanggal yang diinginkan sehingga proses booking lebih cepat.

### Selesai bila
- Pelanggan bisa mencari kapster dengan jam kosong paling cepat berdasarkan layanan pilihan
- Pelanggan bisa memfilter hasil pencarian berdasarkan layanan tertentu
- Pelanggan bisa memfilter hasil pencarian berdasarkan tanggal tertentu
- Hasil pencarian hanya menampilkan slot yang benar-benar kosong
- Pencarian dapat diakses tanpa perlu login atau akun

## Sub-fitur: Cari kapster tersedia tercepat

### Tujuan
Pelanggan langsung mendapat rekomendasi kapster dengan slot kosong paling awal.

### Selesai bila
- Sistem menampilkan kapster dengan jam kosong tercepat sesuai layanan dipilih
- Hasil diurutkan dari jam tercepat ke yang lebih lambat

## Sub-fitur: Filter berdasarkan layanan

### Tujuan
Pelanggan menyaring pencarian sesuai layanan yang ingin dipesan.

### Selesai bila
- Pelanggan bisa pilih satu layanan dari daftar untuk memfilter hasil
- Hasil pencarian hanya menampilkan kapster yang bisa melakukan layanan tersebut

## Sub-fitur: Filter berdasarkan tanggal

### Tujuan
Pelanggan menyaring pencarian sesuai tanggal yang diinginkan.

### Selesai bila
- Pelanggan bisa pilih tanggal tertentu untuk memfilter hasil
- Hasil pencarian menampilkan slot kosong hanya pada tanggal yang dipilih

## Task

Progres: 0/14 selesai

- [ ] [M1] Rancang halaman pencarian jadwal dengan form filter layanan dan tanggal _(Utama)_
- [ ] [M1] Tampilkan daftar kapster tercepat tersedia pakai DUMMY-KAPSTER dan DUMMY-BOOKING _(Utama)_
- [ ] [M1] Buat dropdown filter layanan pakai DUMMY-LAYANAN _(Utama)_
- [ ] [M1] Buat pemilih tanggal untuk filter pencarian jadwal _(Utama)_
- [ ] [M1] Tampilkan tampilan kosong saat tidak ada slot cocok dengan filter _(Penting)_
- [ ] [M2] Buat logika perhitungan slot kosong tercepat dari jam kerja dan booking kapster _(Utama)_
- [ ] [M2] Buat API endpoint pencarian jadwal dengan parameter layanan dan tanggal _(Utama)_
- [ ] [M2] Terapkan filter query berdasarkan layanan_id pada endpoint pencarian _(Utama)_
- [ ] [M2] Terapkan filter query berdasarkan tanggal pada endpoint pencarian _(Utama)_
- [ ] [M2] Kecualikan hari libur kapster dari hasil pencarian jadwal _(Utama)_
- [ ] [M2] Hubungkan halaman pencarian ke API nyata dan hapus data tiruan _(Utama)_
- [ ] [M3] Terapkan rate limit pada endpoint pencarian jadwal sesuai NFR-04 _(Penting)_
- [ ] [M3] Validasi input parameter layanan dan tanggal di server sesuai SEC-08 _(Utama)_
- [ ] [M3] Tulis uji end-to-end alur pencarian jadwal dan filter kombinasi _(Penting)_
