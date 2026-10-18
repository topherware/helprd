# Jejak Perubahan

Memberi transparansi dan akuntabilitas atas setiap perubahan data penting yang dilakukan admin.

## Spesifikasi

### Tujuan
Memastikan setiap perubahan harga layanan, jadwal kapster, dan status booking tercatat lengkap siapa yang mengubah, kapan, serta nilai sebelum dan sesudahnya.

### Selesai bila
- Setiap perubahan harga layanan atau jadwal kapster tercatat ke log audit dengan aktor dan waktu
- Setiap perubahan status booking tercatat riwayat status sebelum dan sesudah
- Admin dapat membuka halaman riwayat perubahan dan melihat detail siapa, kapan, dan apa yang berubah
- Pencatatan jejak berjalan otomatis tanpa perlu aksi tambahan dari admin

### Keamanan
Persyaratan Wajib MVP terkait (detail di PRD bab 8):
- **SEC-07** — Setiap aksi admin yang mengubah harga layanan, jadwal kapster, hari libur, atau status booking dicatat ke log_audit dengan aktor_id, aksi, entitas, entitas_id, dan ringkasan sebelum/sesudah; perubahan status booking juga dicatat terstruktur di riwayat_status_booking

## Sub-fitur: Catat siapa ubah harga/jadwal

### Tujuan
Mencatat admin yang melakukan perubahan harga layanan atau jadwal kapster.

### Selesai bila
- Setiap mutasi harga layanan dan jadwal kapster menyimpan id dan peran admin pelaku
- Admin dapat melihat nama pelaku perubahan di halaman riwayat

## Sub-fitur: Catat waktu perubahan

### Tujuan
Mencatat waktu persis setiap perubahan data penting terjadi.

### Selesai bila
- Setiap entri log audit memiliki waktu dibuat secara otomatis oleh server
- Waktu perubahan ditampilkan secara konsisten di halaman riwayat

## Sub-fitur: Riwayat perubahan status booking

### Tujuan
Menyediakan riwayat status sebelum dan sesudah untuk setiap booking yang berubah statusnya.

### Selesai bila
- Admin dapat melihat urutan perubahan status suatu booking beserta siapa dan kapan
- Status sebelum dan sesudah ditampilkan jelas untuk tiap entri riwayat

## Task

Progres: 0/14 selesai

- [ ] [M1] Rancang halaman riwayat perubahan dengan filter booking/layanan _(Utama)_
- [ ] [M1] Tampilkan daftar perubahan harga/jadwal pakai DUMMY-LOG_AUDIT _(Utama)_
- [ ] [M1] Tampilkan riwayat status booking pakai DUMMY-RIWAYAT_STATUS_BOOKING _(Utama)_
- [ ] [M1] Tampilkan kolom waktu perubahan di tiap baris riwayat _(Penting)_
- [ ] [M1] Rancang tampilan detail nilai sebelum dan sesudah per entri _(Penting)_
- [ ] [M2] Buat tabel log_audit dan riwayat_status_booking beserta migrasinya _(Utama)_
- [ ] [M2] Tambahkan pencatatan log audit saat admin ubah harga/durasi layanan _(Utama)_
- [ ] [M2] Tambahkan pencatatan log audit saat admin ubah jam kerja/hari libur kapster _(Utama)_
- [ ] [M2] Tambahkan pencatatan riwayat_status_booking saat status booking berubah _(Utama)_
- [ ] [M2] Pastikan tiap entri log menyimpan waktu dibuat otomatis dari server _(Utama)_
- [ ] [M2] Buat endpoint admin untuk membaca riwayat perubahan dengan filter _(Utama)_
- [ ] [M3] Terapkan guard sesi admin pada endpoint riwayat perubahan (SEC-01, SEC-02) _(Utama)_
- [ ] [M3] Pastikan setiap mutasi penting tercatat lengkap aktor dan ringkasan (SEC-07) _(Utama)_
- [ ] [M3] Uji otomatis pencatatan log audit dan riwayat status booking end-to-end _(Utama)_
