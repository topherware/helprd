<?php

/**
 * Bendera v1.2 yang menentukan aturan mana yang berlaku untuk sebuah PRD.
 * Sumber digabung dengan OR: Brief (pilihan pengguna), deklarasi AI (potongan profil, JSON tervalidasi),
 * dan jaring kata kunci atas ide, jawaban, catatan, serta nama fitur/sub fitur.
 */
final class Triggers
{
    public const FLAGS = [
        'uiOnly', 'hasDatabase', 'hasPricedEntity', 'hasTransactionRecord', 'hasAdminMutation', 'hasGuestFlow',
        'usesOtp', 'usesEmail', 'usesWhatsappApi', 'usesPayment', 'usesFileStorage', 'usesRateLimit',
        'serverless', 'hasDarkTheme', 'multiRole',
        // v1.2.1 (paritas)
        'hasTimeFields', 'hasScheduler', 'hasPublicPages', 'hasOfflineChannel', 'hasTimeRange',
    ];

    /** Keterangan singkat tiap bendera (untuk prompt dan laporan). */
    public const LABELS = [
        'uiOnly' => 'mode hanya-UI', 'hasDatabase' => 'memakai database', 'hasPricedEntity' => 'ada harga/tarif/diskon/biaya',
        'hasTransactionRecord' => 'ada catatan transaksi (pesanan, reservasi, pinjam, tagihan)', 'hasAdminMutation' => 'pemilik/admin mengubah data atau status',
        'hasGuestFlow' => 'transaksi bisa tanpa akun (tamu)', 'usesOtp' => 'memakai OTP', 'usesEmail' => 'mengirim email',
        'usesWhatsappApi' => 'mengirim WhatsApp otomatis (API)', 'usesPayment' => 'pembayaran online', 'usesFileStorage' => 'menyimpan berkas/foto',
        'usesRateLimit' => 'butuh pembatasan laju (login, OTP, formulir publik)', 'serverless' => 'berjalan di lingkungan serverless',
        'hasDarkTheme' => 'tema gelap', 'multiRole' => 'lebih dari satu peran pengguna',
        'hasTimeFields' => 'ada data waktu (jadwal, tanggal, jam, tenggat)', 'hasScheduler' => 'ada pekerjaan terjadwal (pengingat, tugas berkala)',
        'hasPublicPages' => 'ada halaman publik tanpa login', 'hasOfflineChannel' => 'ada kanal luring (datang langsung, telepon) yang dicatat pengelola',
        'hasTimeRange' => 'ada aturan rentang waktu/jumlah yang tidak boleh tumpang tindih',
    ];

    /**
     * Pola invarian bisnis dalam masukan pengguna (P1). Kunci = jenis invarian.
     * rentang: sumber daya yang sama tidak boleh dipakai pada rentang yang bertabrakan; kuota: batas jumlah;
     * stok: jumlah tidak boleh negatif; unik: nilai tidak boleh kembar.
     */
    public const INVARIAN = [
        'rentang' => '/(tidak boleh|jangan|tanpa|cegah|mencegah|anti)[^.\n]{0,60}\b(bentrok|tumpang tindih|dobel|double|ganda|bertabrakan|tabrakan)\b|\b(dua|2)\s+\w+[^.\n]{0,40}\b(di|pada)\s+(jam|waktu|tanggal|slot|jadwal|sesi)\s+yang sama\b|\b(dobel|ganda|bentrok|bertabrakan)\b[^.\n]{0,20}\b(jadwal|jam|slot|waktu|sesi)\b/iu',
        'kuota' => '/\b(maksimal|maks\.?|paling banyak|batas|kuota|kapasitas)\s+\d+\s*\w+/iu',
        'stok' => '/\bstok\b/iu',
        'unik' => '/\b(harus unik|tidak boleh (sama|kembar|duplikat)|satu\s+\w+\s+(hanya|cuma)\s+(boleh|bisa)\s+(punya|memiliki)\s+satu)\b/iu',
    ];

    /** Jaring pengaman kata kunci; dicocokkan ke ide, jawaban, catatan, dan nama fitur/sub fitur. */
    public const KEYWORDS = [
        'hasPricedEntity' => '/\b(harga|tarif|diskon|biaya|ongkir|ongkos kirim|paket berbayar|price|pricing)\b|\brp\s?\d/iu',
        'hasTransactionRecord' => '/\b(pesan|pesanan|memesan|order|reservasi|pinjam|peminjaman|sewa|tagihan|invoice|checkout|transaksi)\b/iu',
        'hasAdminMutation' => '/\b(admin|administrator|pemilik|pengelola|kelola|mengelola|moderasi|setujui|menyetujui|approve|dashboard|dasbor)\b/iu',
        'hasGuestFlow' => '/\b(tanpa akun|tanpa login|tanpa daftar|tanpa registrasi|tamu|guest)\b/iu',
        'usesOtp' => '/\b(otp|kode verifikasi|verifikasi nomor|kode sekali pakai)\b/iu',
        'usesEmail' => '/\b(kirim|mengirim|notifikasi|konfirmasi|pengingat|reset)\b[^.\n]{0,30}\b(email|e-mail|surel)\b|\bnewsletter\b/iu',
        'usesWhatsappApi' => '/\b(whatsapp|wa)\b[^.\n]{0,25}\b(api|otomatis|notifikasi|broadcast|bot|pengingat)\b|\bnotifikasi\s+(via |lewat |ke )?(wa|whatsapp)\b/iu',
        'usesPayment' => '/\b(pembayaran online|bayar online|payment gateway|midtrans|xendit|stripe|qris|virtual account|e-?wallet|dompet digital|kartu kredit)\b/iu',
        'usesFileStorage' => '/\b(unggah|upload|foto|gambar|berkas|lampiran|avatar|bukti transfer|galeri)\b/iu',
        'usesRateLimit' => '/\b(login|masuk akun|daftar akun|registrasi|otp|formulir|komentar|ulasan|testimoni|kontak)\b/iu',
        'hasDarkTheme' => '/\b(gelap|dark)\b/iu',
        'hasTimeFields' => '/\b(jadwal|jam|tanggal|waktu|pengingat|tenggat|deadline|batas waktu|reservasi|janji temu|sesi|shift|periode|kalender)\b/iu',
        'hasScheduler' => '/\b(pengingat|reminder|terjadwal|cron|otomatis\s+(terkirim|dikirim|mengirim)|setiap\s+(hari|minggu|jam|pagi|malam)|\d+\s*(jam|hari|menit)\s+sebelum)\b/iu',
        'hasPublicPages' => '/\b(tanpa akun|tanpa login|tanpa daftar|pengunjung|publik|katalog|landing page|toko online|halaman (depan|utama)|website)\b/iu',
        'hasOfflineChannel' => '/\b(datang langsung|walk-?in|lewat telepon|via telepon|offline|luring|tatap muka)\b/iu',
    ];

    /** Bendera yang hanya bermakna bila ada backend; dimatikan pada mode hanya-UI. */
    public const BACKEND = ['hasDatabase', 'hasGuestFlow', 'usesOtp', 'usesEmail', 'usesWhatsappApi', 'usesPayment', 'usesFileStorage', 'usesRateLimit', 'serverless', 'hasScheduler', 'hasOfflineChannel', 'hasTimeRange'];

    /**
     * @param array $declared bendera dari potongan profil (nama bendera => bool); nilai non-boolean diabaikan
     * @param array $stack profil stack terkunci (Brief::resolveStack)
     * @param array $roles daftar peran (dari profil); dipakai untuk multiRole
     */
    public static function flags(Brief $b, array $declared, array $stack, array $roles = [], array $invarian = []): array
    {
        $text = $b->inputText();
        $roles = self::roleNames($roles);

        $flags = array_fill_keys(self::FLAGS, false);
        foreach (self::FLAGS as $flag) {
            $flags[$flag] = ($declared[$flag] ?? null) === true;
        }
        foreach (self::KEYWORDS as $flag => $regex) {
            $source = $flag === 'hasDarkTheme' ? $b->designCustom : $text;
            $flags[$flag] = $flags[$flag] || (bool) preg_match($regex, $source);
        }
        $flags['hasDarkTheme'] = $flags['hasDarkTheme'] || $b->designStyle === 'dark_premium';
        $flags['usesRateLimit'] = $flags['usesRateLimit'] || $flags['usesOtp'];
        $flags['serverless'] = !empty($stack['serverless']);
        $flags['hasDatabase'] = ($stack['db'] ?? 'none') !== 'none';
        $named = array_filter($roles, fn ($r) => !self::isAnonymous($r));
        $flags['multiRole'] = $flags['multiRole'] || count($named) > 1 || $flags['hasAdminMutation'];
        $flags['hasTimeRange'] = $flags['hasTimeRange'] || in_array('rentang', array_column(self::invariants($b, $invarian), 'jenis'), true);
        $flags['hasTimeFields'] = $flags['hasTimeFields'] || $flags['hasTimeRange'] || $flags['hasScheduler'];

        $flags['uiOnly'] = $b->uiOnly;
        if ($b->uiOnly) {
            foreach (self::BACKEND as $flag) {
                $flags[$flag] = false;
            }
        }
        return $flags;
    }

    /**
     * Invarian bisnis (P1): gabungan deklarasi AI pada potongan profil dan kalimat masukan pengguna yang cocok
     * dengan pola INVARIAN. @return array<int, array{jenis: string, aturan: string, asal: string}>
     */
    public static function invariants(Brief $b, array $declared = []): array
    {
        $out = [];
        foreach ($declared as $inv) {
            if (is_array($inv) && isset(self::INVARIAN[$inv['jenis'] ?? '']) && trim($inv['aturan'] ?? '') !== '') {
                $out[] = ['jenis' => $inv['jenis'], 'aturan' => trim($inv['aturan']), 'asal' => $inv['asal'] ?? 'keputusan'];
            }
        }
        $sources = ['ide' => $b->ide, 'catatan' => $b->extraNotes, 'jawaban' => implode("\n", array_column($b->qa, 'jawaban'))];
        foreach ($sources as $asal => $text) {
            foreach (preg_split('/(?<=[.!?])\s+|\R+/u', (string) $text) as $sentence) {
                foreach (self::INVARIAN as $jenis => $regex) {
                    if (preg_match($regex, $sentence) && !in_array($jenis, array_column($out, 'jenis'), true)) {
                        $out[] = ['jenis' => $jenis, 'aturan' => trim($sentence), 'asal' => $asal];
                    }
                }
            }
        }
        return $out;
    }

    /** Satuan angka di masukan pengguna yang mungkin berubah dan harus menjadi pengaturan (P7). */
    public const SATUAN_ANGKA = 'jam|hari|menit|minggu|bulan|tahun|persen|%|kali|orang|porsi|item|slot|sesi|km|kg';

    /**
     * Nilai yang disebut pengguna dan bisa berubah (P7): angka bersatuan dan nominal rupiah.
     * @return array<int, array{nilai: string, satuan: string, kalimat: string, kunci: string}>
     */
    public static function angka(Brief $b): array
    {
        $out = [];
        $text = $b->ide . "\n" . $b->extraNotes . "\n" . implode("\n", array_column($b->qa, 'jawaban'));
        foreach (preg_split('/(?<=[.!?])\s+|\R+/u', $text) as $sentence) {
            if (!preg_match_all('/\b(\d+(?:[.,]\d+)?)\s*(' . self::SATUAN_ANGKA . ')\b|\bRp\s?(\d{1,3}(?:\.\d{3})+|\d+)/iu', $sentence, $m, PREG_SET_ORDER)) {
                continue;
            }
            foreach ($m as $hit) {
                $nilai = $hit[1] !== '' ? $hit[1] : str_replace('.', '', $hit[3]);
                $satuan = $hit[1] !== '' ? mb_strtolower($hit[2]) : 'rupiah';
                // Kunci pengaturan: kata bermakna pertama kalimat + satuan, mis. "pengingat_jam".
                $words = array_values(array_filter(preg_split('/[^\p{L}]+/u', mb_strtolower($sentence)), fn ($w) => mb_strlen($w) >= 4
                    && !in_array($w, ['yang', 'untuk', 'dengan', 'setiap', 'harus', 'boleh', 'tidak', 'maksimal', 'minimal', 'paling', 'banyak', 'sebelum', 'sesudah', 'setelah', 'dikirim', 'dalam'], true)));
                $kunci = preg_replace('/[^a-z0-9_]/', '', ($words[0] ?? 'nilai') . '_' . ($satuan === '%' ? 'persen' : $satuan));
                if (!in_array($kunci, array_column($out, 'kunci'), true)) {
                    $out[] = ['nilai' => $nilai, 'satuan' => $satuan, 'kalimat' => trim($sentence), 'kunci' => $kunci];
                }
            }
        }
        return $out;
    }

    /** Nama peran dari daftar peran profil (string, atau objek {nama, asal} sejak v1.2.1). */
    public static function roleNames(array $roles): array
    {
        return array_values(array_filter(array_map(fn ($r) => is_array($r) ? (string) ($r['nama'] ?? '') : (string) $r, $roles), 'strlen'));
    }

    public static function isAnonymous(string $role): bool
    {
        return (bool) preg_match('/anonim|pengunjung|publik|tamu|guest/iu', $role);
    }

    /** Kebutuhan layanan (kode bab 12) yang dituntut bendera. */
    public static function services(array $flags): array
    {
        $map = ['usesOtp' => 'otp', 'usesEmail' => 'email', 'usesWhatsappApi' => 'whatsapp', 'usesPayment' => 'pembayaran', 'usesFileStorage' => 'penyimpanan', 'usesRateLimit' => 'rate_limit'];
        return array_values(array_map(fn ($f) => $map[$f], array_filter(array_keys($map), fn ($f) => $flags[$f])));
    }
}
