# Jadwal Anti-Dobel

Mencegah satu kapster kebagian dua booking di jam yang sama sehingga jadwal selalu akurat.

## Spesifikasi

### Tujuan
Menjamin tidak ada tabrakan jadwal pada kapster yang sama meski ada permintaan booking bersamaan.

### Selesai bila
- Slot jam yang ditampilkan ke pelanggan hanya yang benar-benar kosong untuk kapster terpilih
- Slot yang sudah terisi otomatis tidak bisa dipilih ulang di tampilan
- Dua permintaan booking bersamaan pada kapster dan jam yang sama tidak pernah keduanya berhasil tersimpan
- Validasi anti-dobel berjalan di level database, bukan hanya di tampilan

### Keamanan
Persyaratan Wajib MVP terkait (detail di PRD bab 8):
- **SEC-09** — Pembuatan booking divalidasi ulang di server terhadap constraint unik kapster_id dan mulai_pada di database sebelum dikembalikan sukses; jika terjadi percobaan booking bersamaan pada slot yang sama, hanya satu yang berhasil dan yang lain menerima pesan slot sudah terisi

## Sub-fitur: Cek slot kosong per kapster

### Tujuan
Menampilkan hanya slot jam yang kosong berdasarkan jam kerja, libur, dan booking yang ada.

### Selesai bila
- Slot yang ditampilkan menyesuaikan jam kerja dan hari libur kapster terpilih
- Slot yang sudah dibooking tidak muncul dalam daftar slot kosong

## Sub-fitur: Kunci jam yang sudah terisi

### Tujuan
Menonaktifkan slot jam yang sudah terisi booking lain agar tidak bisa dipilih ulang.

### Selesai bila
- Slot terisi tampil nonaktif atau tersembunyi di antarmuka pemilihan jam
- Pelanggan tidak bisa menekan/memilih slot yang sudah terkunci

## Sub-fitur: Validasi otomatis saat booking

### Tujuan
Menolak otomatis booking yang bertabrakan jam pada kapster yang sama saat disimpan.

### Selesai bila
- Sistem menolak booking yang bertabrakan jam meski dua permintaan datang hampir bersamaan
- Pelanggan yang ditolak mendapat pesan bahwa slot sudah terisi

## Task

Progres: 0/11 selesai

- [ ] [M1] Desain tampilan grid/list slot jam kosong per kapster pakai DUMMY-JAM_KERJA_KAPSTER _(Utama)_
- [ ] [M1] Desain tampilan slot terisi dalam keadaan nonaktif pakai DUMMY-BOOKING _(Utama)_
- [ ] [M1] Desain pesan error tampilan saat slot terpilih sudah terisi _(Penting)_
- [ ] [M2] Terapkan logika filter slot kosong berdasarkan jam kerja dan hari libur dari database _(Utama)_
- [ ] [M2] Buat API pengecekan slot tersedia untuk kapster dan tanggal tertentu _(Utama)_
- [ ] [M2] Tambahkan constraint unik kapster_id dan mulai_pada di skema database _(Utama)_
- [ ] [M2] Hubungkan tampilan slot terisi ke data booking nyata agar otomatis terkunci _(Utama)_
- [ ] [M2] Implementasikan penolakan booking bertabrakan lewat validasi server dan constraint _(Utama)_
- [ ] [M3] Validasi ulang slot kosong di server sebelum booking dikembalikan sukses (SEC-09) _(Utama)_
- [ ] [M3] Tulis unit test simulasi dua booking bersamaan pada slot yang sama _(Utama)_
- [ ] [M3] Tulis unit test memastikan slot kosong konsisten dengan jam kerja dan libur kapster _(Penting)_
