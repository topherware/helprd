<?php

/**
 * P3 KepemilikanData (kritis): pada alur tanpa akun, data milik pengguna tidak boleh dicari hanya dengan satu pengenal publik
 * (nomor, email, nama); wajib pasangan pengenal + kode rahasia. Kontak tersamar di luar panel admin (mayor).
 */
final class P03KepemilikanData
{
    /** Objek data milik pengguna (riwayat/status/milik) yang dicari lewat penghubung lalu langsung pengenal publik. */
    public const CARI = '/\b(riwayat|histori|history|status\s+\w+|data saya|pesanan saya|milik\w*)\b.{0,60}?\b(berdasarkan|dengan|lewat|via|pakai|memakai|menggunakan|by|cukup|cocok dengan)\s+(?:[\p{L}-]+\s+){0,2}?(nomor|no\.? ?hp|whatsapp|wa|email|e-mail|nama|telepon|kontak\w*)\b/iu';
    public const RAHASIA = '/\b(kode|token|pin|otp|rahasia|tautan unik|magic link|kata sandi|password|login|masuk akun)\b/iu';
    public const PENGELOLA = '/\b(admin|staf|pengelola|dashboard|dasbor|panel|pemilik|kasir)\b/iu';
    public const LARANGAN = '/\b(tidak boleh|dilarang|jangan|tidak ada|tidak bisa|tidak dapat|ditolak)\b/iu';
    public const SAMAR = '/tersamar|disamarkan|samarkan|penyamaran|masking|dimask|x{3,}|\*{3,}/iu';

    public static function berlaku(array $c): bool
    {
        return $c['flags']['hasGuestFlow'] && !$c['flags']['uiOnly'];
    }

    public static function check(array $c): array
    {
        $p = $c['paket'];
        $text = $p->prd() . "\n" . implode("\n", $p->featureFiles());
        $hits = [];
        foreach (preg_split('/\R|(?<=[.;!?])\s+/u', $text) as $clause) {
            $clause = trim($clause);
            if ($clause !== '' && preg_match(self::CARI, $clause) && !preg_match(self::RAHASIA, $clause)
                && !preg_match(self::PENGELOLA, $clause) && !preg_match(self::LARANGAN, $clause)) {
                $hits[] = mb_strimwidth(trim($clause, " |-*"), 0, 110, '…');
            }
        }
        $out = [];
        if ($hits) {
            $out[] = QV::finding('P3', 'K', 10, 'Data milik pengguna dicari hanya dengan satu pengenal publik: "' . implode('"; "', array_slice(array_unique($hits), 0, 3)) . '".',
                'Wajibkan pasangan pengenal + kode rahasia yang diterbitkan saat transaksi, dengan pembatasan laju percobaan.');
        }
        if (!preg_match(self::SAMAR, $p->prd())) {
            $out[] = QV::finding('P3', 'M', 8, 'Kontak pengguna belum ditetapkan tampil tersamar di luar panel admin.', 'Tambahkan aturan: kontak ditampilkan tersamar (mis. 0812-xxxx-1234) selain di panel admin.');
        }
        return $out;
    }
}
