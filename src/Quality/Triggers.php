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
    ];

    /** Keterangan singkat tiap bendera (untuk prompt dan laporan). */
    public const LABELS = [
        'uiOnly' => 'mode hanya-UI', 'hasDatabase' => 'memakai database', 'hasPricedEntity' => 'ada harga/tarif/diskon/biaya',
        'hasTransactionRecord' => 'ada catatan transaksi (pesanan, booking, pinjam, tagihan)', 'hasAdminMutation' => 'pemilik/admin mengubah data atau status',
        'hasGuestFlow' => 'transaksi bisa tanpa akun (tamu)', 'usesOtp' => 'memakai OTP', 'usesEmail' => 'mengirim email',
        'usesWhatsappApi' => 'mengirim WhatsApp otomatis (API)', 'usesPayment' => 'pembayaran online', 'usesFileStorage' => 'menyimpan berkas/foto',
        'usesRateLimit' => 'butuh pembatasan laju (login, OTP, formulir publik)', 'serverless' => 'berjalan di lingkungan serverless',
        'hasDarkTheme' => 'tema gelap', 'multiRole' => 'lebih dari satu peran pengguna',
    ];

    /** Jaring pengaman kata kunci; dicocokkan ke ide, jawaban, catatan, dan nama fitur/sub fitur. */
    public const KEYWORDS = [
        'hasPricedEntity' => '/\b(harga|tarif|diskon|biaya|ongkir|ongkos kirim|paket berbayar|price|pricing)\b|\brp\s?\d/iu',
        'hasTransactionRecord' => '/\b(pesan|pesanan|memesan|order|booking|reservasi|pinjam|peminjaman|sewa|tagihan|invoice|checkout|transaksi)\b/iu',
        'hasAdminMutation' => '/\b(admin|administrator|pemilik|pengelola|kelola|mengelola|moderasi|setujui|menyetujui|approve|dashboard|dasbor)\b/iu',
        'hasGuestFlow' => '/\b(tanpa akun|tanpa login|tanpa daftar|tanpa registrasi|tamu|guest)\b/iu',
        'usesOtp' => '/\b(otp|kode verifikasi|verifikasi nomor|kode sekali pakai)\b/iu',
        'usesEmail' => '/\b(kirim|mengirim|notifikasi|konfirmasi|pengingat|reset)\b[^.\n]{0,30}\b(email|e-mail|surel)\b|\bnewsletter\b/iu',
        'usesWhatsappApi' => '/\b(whatsapp|wa)\b[^.\n]{0,25}\b(api|otomatis|notifikasi|broadcast|bot|pengingat)\b|\bnotifikasi\s+(via |lewat |ke )?(wa|whatsapp)\b/iu',
        'usesPayment' => '/\b(pembayaran online|bayar online|payment gateway|midtrans|xendit|stripe|qris|virtual account|e-?wallet|dompet digital|kartu kredit)\b/iu',
        'usesFileStorage' => '/\b(unggah|upload|foto|gambar|berkas|lampiran|avatar|bukti transfer|galeri)\b/iu',
        'usesRateLimit' => '/\b(login|masuk akun|daftar akun|registrasi|otp|formulir|komentar|ulasan|testimoni|kontak)\b/iu',
        'hasDarkTheme' => '/\b(gelap|dark)\b/iu',
    ];

    /** Bendera yang hanya bermakna bila ada backend; dimatikan pada mode hanya-UI. */
    public const BACKEND = ['hasDatabase', 'hasGuestFlow', 'usesOtp', 'usesEmail', 'usesWhatsappApi', 'usesPayment', 'usesFileStorage', 'usesRateLimit', 'serverless'];

    /**
     * @param array $declared bendera dari potongan profil (nama bendera => bool); nilai non-boolean diabaikan
     * @param array $stack profil stack terkunci (Brief::resolveStack)
     * @param array $roles daftar peran (dari profil); dipakai untuk multiRole
     */
    public static function flags(Brief $b, array $declared, array $stack, array $roles = []): array
    {
        $text = $b->ide . "\n" . $b->extraNotes . "\n";
        foreach ($b->qa as $row) {
            $text .= ($row['jawaban'] ?? '') . "\n";
        }
        foreach ($b->features as $f) {
            $text .= $f['nama'] . ' ' . implode(' ', $f['sub_fitur'] ?? []) . "\n";
        }

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

        $flags['uiOnly'] = $b->uiOnly;
        if ($b->uiOnly) {
            foreach (self::BACKEND as $flag) {
                $flags[$flag] = false;
            }
        }
        return $flags;
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
