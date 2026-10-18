# TASKS — BookKapster

Kerjakan berurutan sesuai milestone ([M1] prototipe UI dengan data tiruan, [M2] backend & data, [M3] keamanan & uji) dan aturan checkpoint di CLAUDE.md. Ubah `[ ]` menjadi `[x]` setelah task selesai dan diuji.

Progres: 0/134 selesai

## Fase 1 · Inti

### Booking Tanpa Akun
- [ ] [M1] Desain halaman daftar layanan dengan harga dan durasi _(Lihat harga & durasi layanan · Utama)_
- [ ] [M1] Buat alur pilih layanan, kapster, dan jam kosong pakai DUMMY-LAYANAN dan DUMMY-KAPSTER _(Pilih layanan, kapster, jam kosong · Utama)_
- [ ] [M1] Buat form isi nama dan nomor WhatsApp dengan validasi tampilan _(Isi nama & nomor WhatsApp · Utama)_
- [ ] [M1] Buat halaman konfirmasi booking sukses pakai DUMMY-BOOKING _(Konfirmasi booking instan · Utama)_
- [ ] [M1] Rancang tampilan responsif seluruh alur booking di mobile dan desktop _(Penting)_
- [ ] [M2] Buat skema tabel layanan dan booking lalu migrasi ke database _(Utama)_
- [ ] [M2] Buat API untuk mengambil daftar layanan aktif beserta harga dan durasi _(Lihat harga & durasi layanan · Utama)_
- [ ] [M2] Buat API penyimpanan booking dengan nama tamu dan kontak tamu _(Isi nama & nomor WhatsApp · Utama)_
- [ ] [M2] Hubungkan alur pilih layanan-kapster-jam ke data asli dari database _(Pilih layanan, kapster, jam kosong · Utama)_
- [ ] [M2] Hubungkan halaman konfirmasi ke data booking hasil penyimpanan nyata _(Konfirmasi booking instan · Utama)_
- [ ] [M3] Terapkan validasi skema untuk seluruh input form booking publik (SEC-08) _(Isi nama & nomor WhatsApp · Utama)_
- [ ] [M3] Pastikan seluruh query booking memakai parameter Drizzle ORM tanpa SQL mentah (SEC-08) _(Utama)_
- [ ] [M3] Tulis uji end-to-end alur booking tamu dari pilih layanan sampai konfirmasi _(Penting)_
- [ ] [M3] Terapkan rate limit pada endpoint booking publik sesuai NFR-04 _(Penting)_

### Jadwal Anti-Dobel
- [ ] [M1] Desain tampilan grid/list slot jam kosong per kapster pakai DUMMY-JAM_KERJA_KAPSTER _(Cek slot kosong per kapster · Utama)_
- [ ] [M1] Desain tampilan slot terisi dalam keadaan nonaktif pakai DUMMY-BOOKING _(Kunci jam yang sudah terisi · Utama)_
- [ ] [M1] Desain pesan error tampilan saat slot terpilih sudah terisi _(Validasi otomatis saat booking · Penting)_
- [ ] [M2] Terapkan logika filter slot kosong berdasarkan jam kerja dan hari libur dari database _(Cek slot kosong per kapster · Utama)_
- [ ] [M2] Buat API pengecekan slot tersedia untuk kapster dan tanggal tertentu _(Cek slot kosong per kapster · Utama)_
- [ ] [M2] Tambahkan constraint unik kapster_id dan mulai_pada di skema database _(Validasi otomatis saat booking · Utama)_
- [ ] [M2] Hubungkan tampilan slot terisi ke data booking nyata agar otomatis terkunci _(Kunci jam yang sudah terisi · Utama)_
- [ ] [M2] Implementasikan penolakan booking bertabrakan lewat validasi server dan constraint _(Validasi otomatis saat booking · Utama)_
- [ ] [M3] Validasi ulang slot kosong di server sebelum booking dikembalikan sukses (SEC-09) _(Validasi otomatis saat booking · Utama)_
- [ ] [M3] Tulis unit test simulasi dua booking bersamaan pada slot yang sama _(Validasi otomatis saat booking · Utama)_
- [ ] [M3] Tulis unit test memastikan slot kosong konsisten dengan jam kerja dan libur kapster _(Cek slot kosong per kapster · Penting)_

### Kelola Layanan & Harga
- [ ] [M1] Desain halaman daftar layanan admin dengan aksi tambah/ubah/hapus pakai DUMMY-LAYANAN _(Tambah/ubah/hapus layanan · Utama)_
- [ ] [M1] Desain form input harga dan durasi layanan _(Atur harga & durasi · Utama)_
- [ ] [M1] Desain tampilan riwayat booking yang menunjukkan harga snapshot pakai DUMMY-BOOKING _(Riwayat booking pakai harga lama · Penting)_
- [ ] [M2] Buat skema tabel layanan dengan kolom harga dan durasi lalu migrasi _(Atur harga & durasi · Utama)_
- [ ] [M2] Buat API tambah, ubah, dan hapus layanan untuk admin _(Tambah/ubah/hapus layanan · Utama)_
- [ ] [M2] Implementasikan penyimpanan snapshot nama, harga, dan durasi saat booking dibuat _(Riwayat booking pakai harga lama · Utama)_
- [ ] [M2] Hubungkan halaman kelola layanan admin ke data layanan nyata dari database _(Tambah/ubah/hapus layanan · Utama)_
- [ ] [M2] Catat setiap perubahan harga layanan ke log audit dengan nilai sebelum dan sesudah _(Atur harga & durasi · Utama)_
- [ ] [M3] Terapkan pemeriksaan sesi admin pada endpoint kelola layanan (SEC-01, SEC-02) _(Tambah/ubah/hapus layanan · Utama)_
- [ ] [M3] Pastikan log audit mencatat aktor, aksi, entitas, dan ringkasan perubahan harga (SEC-07) _(Atur harga & durasi · Utama)_
- [ ] [M3] Tulis unit test memastikan booking lama tidak berubah harga setelah harga layanan diubah _(Riwayat booking pakai harga lama · Utama)_

### Kelola Status Booking
- [ ] [M1] Desain halaman daftar booking harian admin pakai DUMMY-BOOKING _(Lihat daftar booking harian · Utama)_
- [ ] [M1] Desain kontrol ubah status booking (dikonfirmasi, selesai, batal) _(Tandai dikonfirmasi, selesai, batal · Utama)_
- [ ] [M1] Desain tampilan riwayat perubahan status pakai DUMMY-RIWAYAT_STATUS_BOOKING _(Catatan siapa & kapan ubah status · Penting)_
- [ ] [M1] Desain filter tanggal pada daftar booking harian _(Lihat daftar booking harian · Penting)_
- [ ] [M2] Buat skema tabel riwayat status booking lalu migrasi ke database _(Catatan siapa & kapan ubah status · Utama)_
- [ ] [M2] Buat API daftar booking harian dengan filter tanggal dari data nyata _(Lihat daftar booking harian · Utama)_
- [ ] [M2] Buat API ubah status booking dan simpan riwayat status sebelum/sesudah _(Tandai dikonfirmasi, selesai, batal · Utama)_
- [ ] [M2] Hubungkan panel admin booking ke data nyata menggantikan data tiruan _(Lihat daftar booking harian · Utama)_
- [ ] [M2] Implementasikan login admin dan kapster memakai Auth.js _(Utama)_
- [ ] [M3] Terapkan pemeriksaan sesi dan peran admin pada endpoint ubah status (SEC-01, SEC-02) _(Tandai dikonfirmasi, selesai, batal · Utama)_
- [ ] [M3] Terapkan hash kata sandi dan aturan minimal karakter untuk akun admin/kapster (SEC-04) _(Utama)_
- [ ] [M3] Terapkan pembatasan percobaan login gagal per akun dan per IP (SEC-05) _(Utama)_
- [ ] [M3] Terapkan masa kedaluwarsa sesi dan aksi logout yang membatalkan sesi (SEC-06) _(Utama)_
- [ ] [M3] Pastikan log audit dan riwayat status booking tercatat lengkap untuk setiap perubahan status (SEC-07) _(Catatan siapa & kapan ubah status · Utama)_

### Jadwal Kapster
- [ ] [M1] Rancang halaman daftar kapster dengan ringkasan jadwal _(Utama)_
- [ ] [M1] Buat form atur jam kerja per hari pakai DUMMY-JAM_KERJA_KAPSTER _(Atur jam kerja tiap kapster · Utama)_
- [ ] [M1] Buat tampilan tambah dan hapus tanggal libur pakai DUMMY-HARI_LIBUR_KAPSTER _(Blokir tanggal libur · Utama)_
- [ ] [M1] Rancang tampilan kalender per kapster pakai DUMMY-KAPSTER dan DUMMY-BOOKING _(Lihat kalender per kapster · Utama)_
- [ ] [M1] Tambahkan state kosong saat kapster belum punya jam kerja atau libur _(Penting)_
- [ ] [M1] Pastikan tampilan jadwal responsif di layar ponsel dan desktop _(Penting)_
- [ ] [M2] Buat skema dan migrasi tabel jam_kerja_kapster dan hari_libur_kapster _(Utama)_
- [ ] [M2] Buat API simpan dan ubah jam kerja kapster per hari _(Atur jam kerja tiap kapster · Utama)_
- [ ] [M2] Buat API tambah dan hapus tanggal libur kapster _(Blokir tanggal libur · Utama)_
- [ ] [M2] Buat API kalender booking per kapster yang gabungkan jadwal dan libur _(Lihat kalender per kapster · Utama)_
- [ ] [M2] Hubungkan logika slot kosong booking dengan jam kerja dan hari libur nyata _(Utama)_
- [ ] [M2] Catat setiap perubahan jam kerja dan hari libur ke log_audit _(Utama)_
- [ ] [M3] Terapkan guard admin pada semua endpoint jadwal kapster sesuai SEC-01 dan SEC-02 _(Utama)_
- [ ] [M3] Tulis pengujian otomatis slot kosong sesuai jam kerja dan hari libur _(Utama)_

## Fase 2 · Penting

### Pengingat Otomatis WA
- [ ] [M1] Rancang tampilan daftar log pengingat WA untuk admin _(Log pengingat terkirim · Utama)_
- [ ] [M1] Tampilkan badge status terkirim/gagal pakai DUMMY-LOG_PENGINGAT_WA _(Log pengingat terkirim · Utama)_
- [ ] [M1] Buat pratinjau contoh pesan pengingat dengan data DUMMY-BOOKING _(Pesan otomatis via WhatsApp · Penting)_
- [ ] [M1] Tampilkan keterangan gagal kirim di detail log pakai DUMMY-LOG_PENGINGAT_WA _(Log pengingat terkirim · Penting)_
- [ ] [M2] Buat tabel log_pengingat_wa dan migrasi skemanya _(Log pengingat terkirim · Utama)_
- [ ] [M2] Bangun proses scheduler pencari booking 3 jam ke depan _(Kirim pengingat 3 jam sebelum · Utama)_
- [ ] [M2] Buat adapter tiruan WhatsApp yang menulis log status terkirim _(Pesan otomatis via WhatsApp · Utama)_
- [ ] [M2] Susun fungsi template pesan pengingat dari data booking _(Pesan otomatis via WhatsApp · Utama)_
- [ ] [M2] Buat endpoint cron terlindungi CRON_SECRET untuk memicu pengiriman _(Kirim pengingat 3 jam sebelum · Utama)_
- [ ] [M2] Tambahkan mekanisme cegah pengingat dobel per booking _(Kirim pengingat 3 jam sebelum · Utama)_
- [ ] [M2] Sambungkan vendor WhatsApp Cloud API asli menggantikan adapter tiruan _(Pesan otomatis via WhatsApp · Penting)_
- [ ] [M3] Pastikan pesan pengingat mencantumkan identitas BookKapster dan cara batal (SEC-10) _(Pesan otomatis via WhatsApp · Utama)_
- [ ] [M3] Pastikan log server tidak mencatat nomor WhatsApp mentah (SEC-12) _(Log pengingat terkirim · Utama)_
- [ ] [M3] Uji otomatis skenario pengingat terkirim, gagal, dan anti-dobel kirim _(Kirim pengingat 3 jam sebelum · Utama)_

### Jejak Perubahan
- [ ] [M1] Rancang halaman riwayat perubahan dengan filter booking/layanan _(Utama)_
- [ ] [M1] Tampilkan daftar perubahan harga/jadwal pakai DUMMY-LOG_AUDIT _(Catat siapa ubah harga/jadwal · Utama)_
- [ ] [M1] Tampilkan riwayat status booking pakai DUMMY-RIWAYAT_STATUS_BOOKING _(Riwayat perubahan status booking · Utama)_
- [ ] [M1] Tampilkan kolom waktu perubahan di tiap baris riwayat _(Catat waktu perubahan · Penting)_
- [ ] [M1] Rancang tampilan detail nilai sebelum dan sesudah per entri _(Riwayat perubahan status booking · Penting)_
- [ ] [M2] Buat tabel log_audit dan riwayat_status_booking beserta migrasinya _(Utama)_
- [ ] [M2] Tambahkan pencatatan log audit saat admin ubah harga/durasi layanan _(Catat siapa ubah harga/jadwal · Utama)_
- [ ] [M2] Tambahkan pencatatan log audit saat admin ubah jam kerja/hari libur kapster _(Catat siapa ubah harga/jadwal · Utama)_
- [ ] [M2] Tambahkan pencatatan riwayat_status_booking saat status booking berubah _(Riwayat perubahan status booking · Utama)_
- [ ] [M2] Pastikan tiap entri log menyimpan waktu dibuat otomatis dari server _(Catat waktu perubahan · Utama)_
- [ ] [M2] Buat endpoint admin untuk membaca riwayat perubahan dengan filter _(Utama)_
- [ ] [M3] Terapkan guard sesi admin pada endpoint riwayat perubahan (SEC-01, SEC-02) _(Utama)_
- [ ] [M3] Pastikan setiap mutasi penting tercatat lengkap aktor dan ringkasan (SEC-07) _(Catat siapa ubah harga/jadwal · Utama)_
- [ ] [M3] Uji otomatis pencatatan log audit dan riwayat status booking end-to-end _(Riwayat perubahan status booking · Utama)_

## Fase 3 · Pengembangan

### Dashboard Pemilik
- [ ] [M1] Rancang layout halaman dashboard dengan kartu ringkasan _(Utama)_
- [ ] [M1] Tampilkan kartu jumlah booking harian pakai DUMMY-BOOKING _(Ringkasan booking harian · Utama)_
- [ ] [M1] Tampilkan daftar jumlah booking per kapster pakai DUMMY-BOOKING dan DUMMY-KAPSTER _(Jumlah booking per kapster · Utama)_
- [ ] [M1] Buat komponen pemilih rentang tanggal untuk filter dashboard _(Jumlah booking per kapster · Utama)_
- [ ] [M1] Tampilkan kartu estimasi pendapatan pakai DUMMY-BOOKING berstatus selesai _(Pendapatan estimasi dari booking · Utama)_
- [ ] [M1] Tambahkan skeleton loading untuk kartu dan daftar dashboard _(Penting)_
- [ ] [M2] Buat query agregasi jumlah booking harian dari tabel booking _(Ringkasan booking harian · Utama)_
- [ ] [M2] Buat API endpoint ringkasan dashboard dengan parameter rentang tanggal _(Utama)_
- [ ] [M2] Buat query agregasi jumlah booking per kapster berdasarkan rentang tanggal _(Jumlah booking per kapster · Utama)_
- [ ] [M2] Buat query penjumlahan harga_snapshot booking berstatus selesai _(Pendapatan estimasi dari booking · Utama)_
- [ ] [M2] Hubungkan halaman dashboard ke API nyata dan hapus data tiruan _(Utama)_
- [ ] [M3] Terapkan guard sesi admin pada endpoint dashboard sesuai SEC-01 dan SEC-02 _(Utama)_
- [ ] [M3] Tulis unit test untuk perhitungan agregasi booking dan pendapatan _(Penting)_
- [ ] [M3] Uji dashboard menolak akses tanpa sesi admin dengan status 401/403 _(Utama)_

### Pencarian Jadwal
- [ ] [M1] Rancang halaman pencarian jadwal dengan form filter layanan dan tanggal _(Utama)_
- [ ] [M1] Tampilkan daftar kapster tercepat tersedia pakai DUMMY-KAPSTER dan DUMMY-BOOKING _(Cari kapster tersedia tercepat · Utama)_
- [ ] [M1] Buat dropdown filter layanan pakai DUMMY-LAYANAN _(Filter berdasarkan layanan · Utama)_
- [ ] [M1] Buat pemilih tanggal untuk filter pencarian jadwal _(Filter berdasarkan tanggal · Utama)_
- [ ] [M1] Tampilkan tampilan kosong saat tidak ada slot cocok dengan filter _(Penting)_
- [ ] [M2] Buat logika perhitungan slot kosong tercepat dari jam kerja dan booking kapster _(Cari kapster tersedia tercepat · Utama)_
- [ ] [M2] Buat API endpoint pencarian jadwal dengan parameter layanan dan tanggal _(Utama)_
- [ ] [M2] Terapkan filter query berdasarkan layanan_id pada endpoint pencarian _(Filter berdasarkan layanan · Utama)_
- [ ] [M2] Terapkan filter query berdasarkan tanggal pada endpoint pencarian _(Filter berdasarkan tanggal · Utama)_
- [ ] [M2] Kecualikan hari libur kapster dari hasil pencarian jadwal _(Utama)_
- [ ] [M2] Hubungkan halaman pencarian ke API nyata dan hapus data tiruan _(Utama)_
- [ ] [M3] Terapkan rate limit pada endpoint pencarian jadwal sesuai NFR-04 _(Penting)_
- [ ] [M3] Validasi input parameter layanan dan tanggal di server sesuai SEC-08 _(Utama)_
- [ ] [M3] Tulis uji end-to-end alur pencarian jadwal dan filter kombinasi _(Penting)_

## Fase 4 · Tambahan

### Riwayat Pelanggan
- [ ] [M1] Desain halaman form input nomor WhatsApp untuk cek histori _(Lihat histori booking by nomor WA · Utama)_
- [ ] [M1] Rancang tampilan daftar histori booking pakai DUMMY-BOOKING _(Lihat histori booking by nomor WA · Utama)_
- [ ] [M1] Tampilkan status kosong saat nomor WA belum punya booking _(Lihat histori booking by nomor WA · Penting)_
- [ ] [M1] Desain kartu booking lama dengan tombol booking ulang _(Booking ulang layanan favorit · Utama)_
- [ ] [M1] Rancang alur booking ulang dengan layanan dan kapster terisi otomatis dari DUMMY-BOOKING _(Booking ulang layanan favorit · Utama)_
- [ ] [M1] Pastikan tampilan histori dan booking ulang responsif di mobile _(Penting)_
- [ ] [M2] Buat API pencarian booking berdasarkan nomor WhatsApp _(Lihat histori booking by nomor WA · Utama)_
- [ ] [M2] Terapkan validasi server agar hasil pencarian difilter sesuai kontak_tamu _(Lihat histori booking by nomor WA · Utama)_
- [ ] [M2] Hubungkan tombol booking ulang ke alur booking dengan data layanan dan kapster asli _(Booking ulang layanan favorit · Utama)_
- [ ] [M2] Validasi ulang slot kosong saat pelanggan booking ulang di jam baru _(Booking ulang layanan favorit · Utama)_
- [ ] [M3] Terapkan validasi skema input nomor WhatsApp di server sesuai SEC-08 _(Lihat histori booking by nomor WA · Utama)_
- [ ] [M3] Pastikan endpoint histori booking tidak mengembalikan data tanpa filter kepemilikan sesuai SEC-03 _(Lihat histori booking by nomor WA · Utama)_
- [ ] [M3] Pastikan log server tidak mencatat nomor WhatsApp lengkap sesuai SEC-12 _(Penting)_
- [ ] [M3] Tulis uji otomatis untuk validasi kecocokan nomor WA dan alur booking ulang _(Utama)_
