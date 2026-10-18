# Kelola Jadwal Kapster

Pemilik salon mengatur jam kerja kapster agar pelanggan hanya bisa memesan jam yang benar-benar kosong.

## Spesifikasi

### Tujuan
Memungkinkan pemilik salon mengatur jam kerja dan waktu tutup tiap kapster sehingga pelanggan hanya bisa memesan jam yang benar-benar kosong dan tidak terjadi dobel jadwal.

### Selesai bila
- Pemilik bisa melihat dan mengubah hari serta jam kerja setiap kapster dari satu halaman pengelolaan.
- Jam istirahat atau cuti yang ditutup pemilik tidak lagi muncul sebagai pilihan bagi pelanggan.
- Sistem menolak pesanan yang jamnya bertabrakan dengan booking lain pada kapster yang sama, disertai pesan yang jelas ke pelanggan.
- Setiap perubahan jam kerja, penutupan jam, dan penolakan bentrok tercatat siapa pelakunya dan kapan terjadinya.
- Jam kerja yang berlaku tampil konsisten antara halaman pemilik dan halaman pemilihan jam pelanggan.

## Sub-fitur: Jam Kerja Kapster

Pemilik mengatur hari dan jam kerja tiap kapster.

### Tujuan
Memberi pemilik cara menetapkan hari dan jam kerja tiap kapster agar jadwal layanan salon tercermin dengan benar di halaman pelanggan.

### Selesai bila
- Pemilik dapat memilih kapster lalu melihat daftar hari kerja beserta jam mulai dan jam selesainya.
- Pemilik dapat menambah, mengubah, dan menghapus jam kerja pada hari tertentu untuk kapster tersebut.
- Perubahan jam kerja langsung memengaruhi jam yang tersedia bagi pelanggan pada tanggal terkait.
- Setiap perubahan jam kerja tercatat siapa yang mengubah dan kapan.

## Sub-fitur: Tutup Jam Tertentu

Pemilik menutup jam istirahat atau cuti agar tidak bisa dipesan.

### Tujuan
Memungkinkan pemilik menutup jam tertentu (istirahat atau cuti) sehingga pelanggan tidak bisa memesan di jam tersebut.

### Selesai bila
- Pemilik dapat menambahkan penutupan jam pada tanggal, jam mulai, jam selesai, dan alasan opsional (mis. istirahat, cuti) untuk kapster tertentu.
- Jam yang sudah ditutup tidak muncul sebagai pilihan di halaman pemilihan jam pelanggan.
- Pemilik dapat melihat daftar penutupan yang berlaku dan menghapusnya bila sudah tidak perlu.
- Setiap penambahan dan penghapusan penutupan jam tercatat siapa pelakunya dan kapan.

## Sub-fitur: Cegah Jadwal Bentrok

Satu kapster tidak bisa menerima dua booking di jam yang sama.

### Tujuan
Mencegah satu kapster menerima dua pesanan pada jam yang sama agar pelanggan tidak datang ke salon dan mendapati jadwalnya bertabrakan.

### Selesai bila
- Saat pelanggan memesan jam yang sudah terisi untuk kapster yang sama, pesanan ditolak dengan pesan yang jelas bahwa jam tersebut tidak tersedia.
- Jam yang sudah dipesan tidak lagi tampil sebagai pilihan kosong bagi pelanggan lain pada kapster dan tanggal yang sama.
- Pemilik dapat melihat bahwa penolakan bentrok terjadi, lengkap dengan waktu dan kapster terkait.
- Dua pesanan pada kapster dan jam yang sama tidak pernah tersimpan bersamaan meski dikirim hampir berbarengan.
