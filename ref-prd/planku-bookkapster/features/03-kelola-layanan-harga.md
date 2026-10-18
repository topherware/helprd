# Kelola Layanan & Harga

Memberi admin kendali penuh atas daftar layanan dan harga tanpa mengganggu riwayat booking lama.

## Spesifikasi

### Tujuan
Memungkinkan pemilik salon mengatur layanan dan harga secara mandiri sambil menjaga riwayat booking tetap akurat.

### Selesai bila
- Admin dapat menambah, mengubah, dan menghapus layanan beserta harga dan durasinya
- Perubahan harga layanan tidak mengubah harga pada booking lama yang sudah tersimpan
- Setiap perubahan harga layanan tercatat siapa pengubah dan kapan
- Hanya admin yang bisa mengakses fitur kelola layanan

### Keamanan
Persyaratan Wajib MVP terkait (detail di PRD bab 8):
- **SEC-11** — Kredensial DATABASE_URL, AUTH_SECRET, WHATSAPP_API_KEY, WHATSAPP_API_URL, dan CRON_SECRET hanya disimpan sebagai environment variable di server/platform deployment, tidak pernah dikirim ke bundel frontend atau disimpan di repositori kode
- **SEC-01** — Setiap endpoint yang mengubah layanan, jadwal kapster, atau status booking wajib memeriksa sesi Auth.js dan peran 'admin' di server sebelum query database dieksekusi; permintaan tanpa sesi admin valid ditolak dengan status 401/403
- **SEC-02** — Matriks hak akses per peran (pengunjung anonim, pelanggan, kapster, admin) didokumentasikan dan diterapkan sebagai middleware/guard yang sama di semua API route, bukan logika ad-hoc per endpoint

## Sub-fitur: Tambah/ubah/hapus layanan

### Tujuan
Memberi admin kemampuan mengelola daftar layanan secara penuh.

### Selesai bila
- Admin bisa menambah layanan baru dengan nama, harga, dan durasi
- Admin bisa mengubah atau menghapus layanan yang sudah ada

## Sub-fitur: Atur harga & durasi

### Tujuan
Memungkinkan admin menyesuaikan harga dan durasi tiap layanan.

### Selesai bila
- Admin bisa mengubah nilai harga dan durasi layanan tertentu
- Nilai harga tersimpan sebagai angka rupiah tanpa desimal

## Sub-fitur: Riwayat booking pakai harga lama

### Tujuan
Menjamin riwayat booking lama tetap memakai harga saat booking dibuat.

### Selesai bila
- Booking yang sudah ada tetap menampilkan harga snapshot meski harga layanan berubah
- Booking baru memakai harga layanan yang berlaku saat booking dibuat

## Task

Progres: 0/11 selesai

- [ ] [M1] Desain halaman daftar layanan admin dengan aksi tambah/ubah/hapus pakai DUMMY-LAYANAN _(Utama)_
- [ ] [M1] Desain form input harga dan durasi layanan _(Utama)_
- [ ] [M1] Desain tampilan riwayat booking yang menunjukkan harga snapshot pakai DUMMY-BOOKING _(Penting)_
- [ ] [M2] Buat skema tabel layanan dengan kolom harga dan durasi lalu migrasi _(Utama)_
- [ ] [M2] Buat API tambah, ubah, dan hapus layanan untuk admin _(Utama)_
- [ ] [M2] Implementasikan penyimpanan snapshot nama, harga, dan durasi saat booking dibuat _(Utama)_
- [ ] [M2] Hubungkan halaman kelola layanan admin ke data layanan nyata dari database _(Utama)_
- [ ] [M2] Catat setiap perubahan harga layanan ke log audit dengan nilai sebelum dan sesudah _(Utama)_
- [ ] [M3] Terapkan pemeriksaan sesi admin pada endpoint kelola layanan (SEC-01, SEC-02) _(Utama)_
- [ ] [M3] Pastikan log audit mencatat aktor, aksi, entitas, dan ringkasan perubahan harga (SEC-07) _(Utama)_
- [ ] [M3] Tulis unit test memastikan booking lama tidak berubah harga setelah harga layanan diubah _(Utama)_
