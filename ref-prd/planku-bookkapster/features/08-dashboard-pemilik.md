# Dashboard Pemilik

Memberi pemilik salon gambaran cepat performa harian tanpa perlu menghitung manual satu per satu.

## Spesifikasi

### Tujuan
Menyediakan satu halaman ringkas yang menampilkan jumlah booking, performa tiap kapster, dan estimasi pendapatan agar admin bisa memantau bisnis tanpa buka banyak menu.

### Selesai bila
- Admin bisa melihat ringkasan jumlah booking harian di satu halaman
- Admin bisa melihat jumlah booking per kapster dalam rentang tanggal yang dipilih
- Admin bisa melihat estimasi pendapatan dari booking berstatus selesai
- Data dashboard menyesuaikan otomatis saat rentang tanggal diubah
- Dashboard hanya bisa diakses oleh admin yang sudah login

## Sub-fitur: Ringkasan booking harian

### Tujuan
Admin melihat total booking hari ini sekilas tanpa membuka daftar lengkap.

### Selesai bila
- Jumlah booking harian ditampilkan sebagai angka ringkas di dashboard
- Angka ringkas berubah sesuai tanggal yang sedang dilihat

## Sub-fitur: Jumlah booking per kapster

### Tujuan
Admin membandingkan jumlah booking antar kapster dalam rentang tanggal tertentu.

### Selesai bila
- Admin bisa pilih rentang tanggal untuk melihat jumlah booking tiap kapster
- Hasil ditampilkan dalam bentuk daftar atau grafik per kapster

## Sub-fitur: Pendapatan estimasi dari booking

### Tujuan
Admin memperkirakan pendapatan dari booking yang sudah selesai dikerjakan.

### Selesai bila
- Estimasi pendapatan dihitung hanya dari booking berstatus selesai
- Admin bisa melihat total estimasi pendapatan untuk rentang tanggal yang dipilih

## Task

Progres: 0/14 selesai

- [ ] [M1] Rancang layout halaman dashboard dengan kartu ringkasan _(Utama)_
- [ ] [M1] Tampilkan kartu jumlah booking harian pakai DUMMY-BOOKING _(Utama)_
- [ ] [M1] Tampilkan daftar jumlah booking per kapster pakai DUMMY-BOOKING dan DUMMY-KAPSTER _(Utama)_
- [ ] [M1] Buat komponen pemilih rentang tanggal untuk filter dashboard _(Utama)_
- [ ] [M1] Tampilkan kartu estimasi pendapatan pakai DUMMY-BOOKING berstatus selesai _(Utama)_
- [ ] [M1] Tambahkan skeleton loading untuk kartu dan daftar dashboard _(Penting)_
- [ ] [M2] Buat query agregasi jumlah booking harian dari tabel booking _(Utama)_
- [ ] [M2] Buat API endpoint ringkasan dashboard dengan parameter rentang tanggal _(Utama)_
- [ ] [M2] Buat query agregasi jumlah booking per kapster berdasarkan rentang tanggal _(Utama)_
- [ ] [M2] Buat query penjumlahan harga_snapshot booking berstatus selesai _(Utama)_
- [ ] [M2] Hubungkan halaman dashboard ke API nyata dan hapus data tiruan _(Utama)_
- [ ] [M3] Terapkan guard sesi admin pada endpoint dashboard sesuai SEC-01 dan SEC-02 _(Utama)_
- [ ] [M3] Tulis unit test untuk perhitungan agregasi booking dan pendapatan _(Penting)_
- [ ] [M3] Uji dashboard menolak akses tanpa sesi admin dengan status 401/403 _(Utama)_
