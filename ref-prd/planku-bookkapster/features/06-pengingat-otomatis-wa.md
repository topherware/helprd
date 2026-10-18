# Pengingat Otomatis WA

Mengurangi pelanggan lupa jadwal booking lewat pengingat otomatis via WhatsApp 3 jam sebelum waktunya.

## Spesifikasi

### Tujuan
Memastikan setiap booking yang dikonfirmasi mendapat pengingat tepat waktu secara otomatis dan tercatat statusnya, tanpa campur tangan manual admin.

### Selesai bila
- Scheduler berjalan rutin dan menemukan booking dikonfirmasi yang jadwalnya 3 jam ke depan
- Pesan pengingat terkirim memakai template yang mencantumkan layanan, kapster, dan jam booking
- Setiap pengiriman tercatat di log dengan status terkirim atau gagal
- Tidak ada pengingat dobel untuk booking yang sama
- Pengiriman memakai adapter tiruan di M1/M2 dan dapat disambungkan ke vendor WhatsApp asli di M3

### Keamanan
Persyaratan Wajib MVP terkait (detail di PRD bab 8):
- **SEC-10** — Pengingat WhatsApp otomatis hanya dikirim ke nomor yang pelanggan isikan sendiri saat booking (dianggap persetujuan implisit untuk booking tersebut) dan pesan menyebutkan jelas bahwa ini pengingat booking BookKapster beserta cara menghubungi salon bila ingin membatalkan
- **SEC-11** — Kredensial DATABASE_URL, AUTH_SECRET, WHATSAPP_API_KEY, WHATSAPP_API_URL, dan CRON_SECRET hanya disimpan sebagai environment variable di server/platform deployment, tidak pernah dikirim ke bundel frontend atau disimpan di repositori kode
- **SEC-12** — Seluruh akses aplikasi (halaman booking publik dan panel admin) hanya melalui HTTPS, dan log server tidak pernah mencatat nomor WhatsApp lengkap, kata sandi, atau token sesi secara mentah, cukup ID referensi

## Sub-fitur: Kirim pengingat 3 jam sebelum

### Tujuan
Mengirim pengingat otomatis tepat 3 jam sebelum jadwal booking yang berstatus dikonfirmasi.

### Selesai bila
- Proses terjadwal menemukan booking dikonfirmasi yang mulai_pada 3 jam ke depan
- Pengingat hanya dikirim sekali per booking meski scheduler berjalan berkali-kali

## Sub-fitur: Pesan otomatis via WhatsApp

### Tujuan
Menyusun dan mengirim pesan pengingat dari template yang mencantumkan detail booking.

### Selesai bila
- Template pesan mencantumkan nama tamu, layanan, kapster, dan jam booking
- Pesan dikirim lewat adapter WhatsApp (tiruan atau vendor asli) ke nomor kontak_tamu

## Sub-fitur: Log pengingat terkirim

### Tujuan
Mencatat setiap pengiriman pengingat beserta status berhasil atau gagal.

### Selesai bila
- Setiap percobaan kirim tercatat ke log dengan status terkirim atau gagal
- Admin dapat melihat daftar log pengingat beserta keterangan gagal bila ada

## Task

Progres: 0/14 selesai

- [ ] [M1] Rancang tampilan daftar log pengingat WA untuk admin _(Utama)_
- [ ] [M1] Tampilkan badge status terkirim/gagal pakai DUMMY-LOG_PENGINGAT_WA _(Utama)_
- [ ] [M1] Buat pratinjau contoh pesan pengingat dengan data DUMMY-BOOKING _(Penting)_
- [ ] [M1] Tampilkan keterangan gagal kirim di detail log pakai DUMMY-LOG_PENGINGAT_WA _(Penting)_
- [ ] [M2] Buat tabel log_pengingat_wa dan migrasi skemanya _(Utama)_
- [ ] [M2] Bangun proses scheduler pencari booking 3 jam ke depan _(Utama)_
- [ ] [M2] Buat adapter tiruan WhatsApp yang menulis log status terkirim _(Utama)_
- [ ] [M2] Susun fungsi template pesan pengingat dari data booking _(Utama)_
- [ ] [M2] Buat endpoint cron terlindungi CRON_SECRET untuk memicu pengiriman _(Utama)_
- [ ] [M2] Tambahkan mekanisme cegah pengingat dobel per booking _(Utama)_
- [ ] [M2] Sambungkan vendor WhatsApp Cloud API asli menggantikan adapter tiruan _(Penting)_
- [ ] [M3] Pastikan pesan pengingat mencantumkan identitas BookKapster dan cara batal (SEC-10) _(Utama)_
- [ ] [M3] Pastikan log server tidak mencatat nomor WhatsApp mentah (SEC-12) _(Utama)_
- [ ] [M3] Uji otomatis skenario pengingat terkirim, gagal, dan anti-dobel kirim _(Utama)_
