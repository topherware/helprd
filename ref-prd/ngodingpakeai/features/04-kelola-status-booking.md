# Kelola Status Booking

Pemilik salon mengatur status setiap booking dari dikonfirmasi, selesai, sampai batal.

## Spesifikasi

### Tujuan
Memberi pemilik salon satu tempat untuk memantau dan mengubah status setiap booking — dikonfirmasi, selesai, atau batal — agar jadwal dan catatan salon selalu akurat.

### Selesai bila
- Pemilik bisa melihat booking hari ini dan mengubah status tiap booking ke dikonfirmasi, selesai, atau batal.
- Status terbaru langsung terlihat di layar setelah diubah, tanpa perlu memuat ulang.
- Setiap perubahan status tercatat lengkap dengan nama pemilik dan waktu perubahan.
- Hanya pemilik yang sudah login yang bisa mengubah status booking; pengunjung biasa tidak diberi tombol aksi apa pun.
- Booking yang sudah selesai atau batal tidak bisa berpindah status tanpa konfirmasi tambahan.

## Sub-fitur: Daftar Booking Harian

Pemilik melihat semua booking hari ini beserta jam dan kapsterya dalam satu layar.

### Tujuan
Pemilik melihat semua booking hari ini beserta jam dan kapsterya dalam satu layar tanpa perlu mencari satu per satu.

### Selesai bila
- Satu layar menampilkan semua booking hari ini, masing-masing dengan jam, nama pelanggan, kapster, layanan, dan status.
- Daftar diurutkan berdasarkan jam mulai dari yang paling awal.
- Tiap status punya label dan warna berbeda yang mudah dibedakan (menunggu, dikonfirmasi, selesai, batal).
- Bila tidak ada booking hari ini, tampil pesan kosong yang jelas.

## Sub-fitur: Konfirmasi & Selesai

Pemilik menandai booking sebagai dikonfirmasi atau sudah selesai.

### Tujuan
Pemilik bisa menandai booking sebagai dikonfirmasi lalu sebagai selesai setelah pelanggan dilayani.

### Selesai bila
- Tiap booking menampilkan tombol aksi yang sesuai statusnya (mis. "Konfirmasi" untuk booking menunggu, "Selesai" untuk yang sudah dikonfirmasi).
- Setelah tombol ditekan, status booking langsung berubah dan tampil di daftar.
- Tombol aksi yang tidak relevan dengan status saat ini tidak ditampilkan.

## Sub-fitur: Batalkan Booking

Pemilik membatalkan booking yang tidak bisa dijalankan.

### Tujuan
Pemilik bisa membatalkan booking yang tidak bisa dijalankan agar jamnya terbuka lagi untuk pelanggan lain.

### Selesai bila
- Pemilik bisa menekan "Batalkan" dan diminta konfirmasi sebelum booking benar-benar dibatalkan.
- Booking yang dibatalkan berstatus batal dan jamnya kembali tampil sebagai tersedia bagi pelanggan lain.
- Pemilik bisa mengisi alasan pembatalan (opsional) yang tersimpan pada booking tersebut.
- Booking yang sudah selesai tidak bisa dibatalkan.

## Sub-fitur: Catatan Perubahan

Setiap perubahan status tercatat siapa yang mengubah dan kapan.

### Tujuan
Setiap perubahan status tercatat siapa yang mengubah dan kapan, agar riwayat booking bisa ditelusuri.

### Selesai bila
- Setiap perubahan status menghasilkan satu catatan berisi status lama, status baru, nama pemilik, dan waktu perubahan.
- Riwayat perubahan bisa dilihat pada detail booking, dengan yang terbaru di paling atas.
- Catatan riwayat tidak bisa dihapus atau diubah dari antarmuka pemilik.

## Task

### 1. Buat halaman daftar booking harian

### 2. Buat komponen label warna status booking

### 3. Tampilkan pesan kosong daftar booking

### 4. Buat tombol aksi konfirmasi dan selesai

### 5. Perbarui status di layar tanpa muat ulang

### 6. Buat modal konfirmasi batalkan booking

### 7. Buat panel riwayat perubahan booking

### 8. Sambungkan tampilan ke kontrak API booking

### 9. Buat skema tabel booking dan riwayat status

### 10. Buat endpoint daftar booking harian

### 11. Buat endpoint ubah status konfirmasi dan selesai

### 12. Buat endpoint batalkan booking dengan alasan

### 13. Buat service pencatatan riwayat perubahan status

### 14. Buat endpoint detail riwayat perubahan booking

### 15. Validasi transisi status booking

### 16. Batasi aksi ubah status hanya pemilik login
