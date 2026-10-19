<?php

/**
 * Penelusuran asal (P2) untuk PRD tanpa field `asal`: apakah nama fitur/peran berasal dari masukan pengguna.
 * Heuristik umum (stem sederhana bahasa Indonesia + sinonim umum), tanpa kata domain tertentu.
 */
final class Telusur
{
    /** Kata nama fitur yang terlalu umum untuk dijadikan bukti asal. */
    public const UMUM = ['kelola', 'kelolaan', 'pengelolaan', 'manajemen', 'fitur', 'sistem', 'otomatis', 'otomatisasi', 'halaman', 'data', 'anti',
        'tanpa', 'dengan', 'untuk', 'dan', 'atau', 'oleh', 'dari', 'yang', 'mudah', 'cepat', 'online', 'daring', 'utama', 'baru', 'aplikasi', 'menu',
        'modul', 'pengaturan', 'atur', 'lihat', 'daftar', 'katalog', 'instan', 'semua', 'per', 'via', 'secara', 'tiap', 'setiap', 'sebelum', 'sesudah', 'setelah',
        'masuk', 'login', 'keluar', 'logout', 'autentikasi', 'auth', 'akses'];

    /** Kelompok sinonim umum: kata mana pun dalam satu kelompok dianggap sama. */
    public const SINONIM = [
        ['dashboard', 'dasbor', 'ringkasan', 'rekap', 'laporan', 'statistik'],
        ['riwayat', 'histori', 'history', 'jejak', 'log', 'catat', 'rekam', 'audit', 'tercatat'],
        ['cari', 'pencarian', 'filter', 'temukan', 'search'],
        ['jadwal', 'jam', 'slot', 'waktu', 'kalender'],
        ['ingat', 'pengingat', 'notifikasi', 'reminder', 'notif'],
        ['harga', 'tarif', 'biaya'],
        ['dobel', 'ganda', 'double', 'bentrok', 'tumpang', 'dua'],
        ['wa', 'whatsapp'],
        ['staf', 'staff', 'kasir', 'karyawan', 'pegawai', 'admin', 'petugas', 'operator'],
        ['pemilik', 'owner', 'pengelola'],
        ['bayar', 'pembayaran', 'payment'],
        ['ubah', 'perubahan', 'edit', 'ganti'],
        ['status', 'konfirmasi', 'dikonfirmasi'],
    ];

    /** Peran pengelola: sah bila masukan menyebut salah satu kata pengelola. */
    public const PENGELOLA = '/admin|pemilik|owner|pengelola|staf|staff|kasir|operator|pengurus|petugas|karyawan|pegawai/iu';

    /** Halaman pelengkap umum yang sering ditambahkan tanpa asal (cukup dicatat sebagai usulan). */
    public const PELENGKAP = ['tentang', 'about', 'kontak', 'contact', 'testimoni', 'faq', 'galeri', 'gallery', 'blog', 'karir', 'promo'];


    public const AWALAN = ['meng', 'meny', 'mem', 'men', 'me', 'peng', 'peny', 'pem', 'pen', 'per', 'pe', 'ber', 'ter', 'di', 'ke', 'se'];
    /** Peluluhan awalan: huruf awal kata dasar yang hilang (memesan → pesan, menulis → tulis, mengirim → kirim, menyimpan → simpan). */
    public const LULUH = ['mem' => 'p', 'pem' => 'p', 'men' => 't', 'pen' => 't', 'meng' => 'k', 'peng' => 'k', 'meny' => 's', 'peny' => 's'];

    /**
     * Semua kemungkinan bentuk dasar sebuah kata (stem sederhana bahasa Indonesia, sisa ≥ 3 huruf), ditambah kunci kelompok sinonim.
     * Dua kata dianggap sama bila himpunan variannya beririsan.
     */
    public static function variants(string $word): array
    {
        static $cache = [];
        $w = mb_strtolower($word);
        if (isset($cache[$w])) {
            return $cache[$w];
        }
        $bases = [$w];
        foreach (['nya', 'kan', 'an', 'i'] as $suffix) {
            if (str_ends_with($w, $suffix) && mb_strlen($w) - mb_strlen($suffix) >= 3) {
                $bases[] = mb_substr($w, 0, -mb_strlen($suffix));
            }
        }
        $out = $bases;
        foreach ($bases as $base) {
            foreach (self::AWALAN as $prefix) {
                if (str_starts_with($base, $prefix) && mb_strlen($base) - mb_strlen($prefix) >= 3) {
                    $rest = mb_substr($base, mb_strlen($prefix));
                    $out[] = $rest;
                    if (isset(self::LULUH[$prefix]) && preg_match('/^[aiueo]/u', $rest)) {
                        $out[] = self::LULUH[$prefix] . $rest;
                    }
                }
            }
        }
        $out = array_values(array_unique($out));
        foreach (self::groups() as $form => $key) {
            if (in_array($form, $out, true)) {
                $out[] = $key;
            }
        }
        return $cache[$w] = array_values(array_unique($out));
    }

    /** Bentuk kata dalam kelompok sinonim => kunci kelompok. */
    private static function groups(): array
    {
        static $map = null;
        if ($map === null) {
            $map = [];
            foreach (self::SINONIM as $i => $group) {
                foreach ($group as $w) {
                    $map[$w] = "#$i";
                }
            }
        }
        return $map;
    }

    /** Himpunan varian per kata, berurutan (untuk jendela kedekatan). */
    public static function tokens(string $text): array
    {
        preg_match_all('/[\p{L}\p{N}]+/u', mb_strtolower($text), $m);
        return array_map([self::class, 'variants'], array_values(array_filter($m[0], fn ($w) => mb_strlen($w) >= 2)));
    }

    /** Kata inti sebuah nama (tanpa kata umum), sebagai himpunan varian. */
    public static function core(string $name): array
    {
        preg_match_all('/[\p{L}\p{N}]+/u', mb_strtolower($name), $m);
        $words = array_unique(array_filter($m[0], fn ($w) => !in_array($w, self::UMUM, true) && (mb_strlen($w) >= 3 || isset(self::groups()[$w]))));
        return array_map([self::class, 'variants'], array_values($words));
    }

    /**
     * Apakah nama terlacak ke masukan: ≥ 60% kata inti muncul berdekatan (jendela 10 kata) dalam satu baris masukan.
     * Nama dalam kurung dianggap nama alternatif.
     */
    public static function traced(string $name, string $input): bool
    {
        $alts = [preg_replace('/\([^)]*\)/u', ' ', $name)];
        if (preg_match_all('/\(([^)]*)\)/u', $name, $m)) {
            $alts = array_merge($alts, $m[1]);
        }
        $lines = array_map([self::class, 'tokens'], preg_split('/\R/u', $input));
        foreach ($alts as $alt) {
            $core = self::core($alt);
            if (!$core) {
                continue;
            }
            $need = max(1, (int) ceil(count($core) * 0.6));
            foreach ($lines as $tokens) {
                for ($i = 0, $n = count($tokens); $i < max(1, $n - 9); $i++) {
                    $window = array_merge([], ...array_slice($tokens, $i, 10));
                    if (count(array_filter($core, fn ($v) => array_intersect($v, $window))) >= $need) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /** Peran login terlacak: pengelola disebut di masukan, atau peran disebut sebagai pelaku (langsung diikuti kata kerja me-/ber-). */
    public static function roleTraced(string $role, string $input): bool
    {
        if (Triggers::isAnonymous($role) || preg_match('/pelanggan|pembeli|pengguna|user|member|anggota|pelapor|peserta|pemesan/iu', $role)) {
            return true;
        }
        if (preg_match(self::PENGELOLA, $role)) {
            return (bool) preg_match(self::PENGELOLA, $input);
        }
        $core = self::core($role);
        if (!$core) {
            return true;
        }
        preg_match_all('/[\p{L}]+/u', mb_strtolower($input), $m);
        $words = $m[0];
        foreach ($words as $i => $w) {
            if (array_intersect(self::variants($w), $core[0]) && preg_match('/^(me|ber|login|masuk|bisa|dapat)/u', $words[$i + 1] ?? '')) {
                return true;
            }
        }
        return false;
    }
}
