# Jadwal Kapster

Memastikan jam kerja dan hari libur tiap kapster tercatat rapi sehingga jadwal booking selalu sesuai kenyataan.

## Spesifikasi

### Tujuan
Memberi admin alat untuk mengatur jam kerja dan hari libur tiap kapster, serta memantau jadwalnya lewat kalender.

### Selesai bila
- Admin dapat mengatur jam kerja per hari untuk tiap kapster dan tersimpan dengan benar
- Admin dapat menandai tanggal libur kapster sehingga tanggal itu tidak muncul sebagai slot booking
- Admin dapat melihat kalender booking per kapster untuk memantau jadwalnya
- Perubahan jam kerja dan hari libur hanya bisa dilakukan oleh sesi admin yang valid
- Setiap perubahan jam kerja atau hari libur tercatat siapa dan kapan mengubahnya

### Keamanan
Persyaratan Wajib MVP terkait (detail di PRD bab 8):
- **SEC-01** — Setiap endpoint yang mengubah layanan, jadwal kapster, atau status booking wajib memeriksa sesi Auth.js dan peran 'admin' di server sebelum query database dieksekusi; permintaan tanpa sesi admin valid ditolak dengan status 401/403
- **SEC-02** — Matriks hak akses per peran (pengunjung anonim, pelanggan, kapster, admin) didokumentasikan dan diterapkan sebagai middleware/guard yang sama di semua API route, bukan logika ad-hoc per endpoint

## Sub-fitur: Atur jam kerja tiap kapster

### Tujuan
Admin bisa menentukan jam mulai dan selesai kerja tiap kapster per hari dalam seminggu.

### Selesai bila
- Admin dapat menambah dan mengubah jam mulai/selesai kerja kapster per hari
- Jam kerja yang tersimpan langsung memengaruhi slot jam yang bisa dibooking

## Sub-fitur: Blokir tanggal libur

### Tujuan
Admin bisa menandai tanggal tertentu sebagai libur kapster agar tidak muncul sebagai slot booking.

### Selesai bila
- Admin dapat menambah dan menghapus tanggal libur kapster beserta alasannya
- Tanggal yang ditandai libur tidak muncul sebagai slot kosong di halaman booking

## Sub-fitur: Lihat kalender per kapster

### Tujuan
Admin bisa memantau jadwal booking dan hari libur tiap kapster dalam tampilan kalender.

### Selesai bila
- Admin dapat memilih kapster tertentu dan melihat kalender booking serta hari liburnya
- Kalender menampilkan dengan jelas slot yang terisi, kosong, dan libur

## Task

Progres: 0/14 selesai

- [ ] [M1] Rancang halaman daftar kapster dengan ringkasan jadwal _(Utama)_
- [ ] [M1] Buat form atur jam kerja per hari pakai DUMMY-JAM_KERJA_KAPSTER _(Utama)_
- [ ] [M1] Buat tampilan tambah dan hapus tanggal libur pakai DUMMY-HARI_LIBUR_KAPSTER _(Utama)_
- [ ] [M1] Rancang tampilan kalender per kapster pakai DUMMY-KAPSTER dan DUMMY-BOOKING _(Utama)_
- [ ] [M1] Tambahkan state kosong saat kapster belum punya jam kerja atau libur _(Penting)_
- [ ] [M1] Pastikan tampilan jadwal responsif di layar ponsel dan desktop _(Penting)_
- [ ] [M2] Buat skema dan migrasi tabel jam_kerja_kapster dan hari_libur_kapster _(Utama)_
- [ ] [M2] Buat API simpan dan ubah jam kerja kapster per hari _(Utama)_
- [ ] [M2] Buat API tambah dan hapus tanggal libur kapster _(Utama)_
- [ ] [M2] Buat API kalender booking per kapster yang gabungkan jadwal dan libur _(Utama)_
- [ ] [M2] Hubungkan logika slot kosong booking dengan jam kerja dan hari libur nyata _(Utama)_
- [ ] [M2] Catat setiap perubahan jam kerja dan hari libur ke log_audit _(Utama)_
- [ ] [M3] Terapkan guard admin pada semua endpoint jadwal kapster sesuai SEC-01 dan SEC-02 _(Utama)_
- [ ] [M3] Tulis pengujian otomatis slot kosong sesuai jam kerja dan hari libur _(Utama)_
