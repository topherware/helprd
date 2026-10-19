<?php

/**
 * Pemicu keamanan: deteksi dari ide, jawaban, nama fitur/sub fitur, tabel database, dan tech stack,
 * lalu tetapkan kontrol yang WAJIB muncul sebagai SEC-xx di PRD bagian 8.
 */
final class SecurityTriggers
{
    /**
     * kode => label, regex (dicocokkan ke teks), kontrol (kode => [kelompok, teks]),
     * data_sensitif (opsional): regex nama data yang wajib diklasifikasikan "sensitif".
     */
    public const RULES = [
        'akun' => [
            'label' => 'Akun & login',
            'regex' => '/\b(login|log in|akun|daftar akun|registrasi|register|sign[ -]?(up|in)|kata sandi|password|otp)\b/iu',
            'kontrol' => [
                'akun.sandi' => ['Autentikasi & Sesi', 'Kebijakan kata sandi (panjang minimal, disimpan sebagai hash yang kuat) atau OTP.'],
                'akun.batas_percobaan' => ['Autentikasi & Sesi', 'Pembatasan percobaan masuk yang gagal per akun dan per IP.'],
                'akun.sesi' => ['Autentikasi & Sesi', 'Sesi punya masa kedaluwarsa, bisa logout, dan token sesi diganti setelah login.'],
                'akun.pemulihan' => ['Autentikasi & Sesi', 'Pemulihan akun lewat tautan atau kode sekali pakai yang cepat kedaluwarsa.'],
                'akun.tanpa_enumerasi' => ['Autentikasi & Sesi', 'Pesan login, daftar, dan lupa sandi tidak membocorkan apakah sebuah akun terdaftar.'],
            ],
        ],
        'admin' => [
            'label' => 'Peran admin/pemilik',
            'regex' => '/\b(admin|administrator|pemilik|owner|dasbor|dashboard|moderator|moderasi|staf|operator)\b/iu',
            'kontrol' => [
                'admin.matriks_peran' => ['Otorisasi', 'Matriks peran tertulis dan diterapkan; tiap peran hanya punya hak minimum.'],
                'admin.otorisasi_server' => ['Otorisasi', 'Setiap aksi admin dicek perannya di server pada setiap permintaan, bukan hanya disembunyikan di tampilan.'],
                'admin.log_audit' => ['Logging & Audit', 'Aksi admin (ubah, hapus, setujui) dicatat di log audit: siapa, apa, kapan.'],
                'admin.area_terlindungi' => ['Otorisasi', 'Area admin wajib login dengan peran admin dan tidak mengandalkan URL yang dirahasiakan.'],
            ],
        ],
        'unggah' => [
            'label' => 'Unggah foto/berkas',
            'regex' => '/\b(unggah|upload|foto|gambar|berkas|file|lampiran|avatar|bukti transfer)\b/iu',
            'kontrol' => [
                'unggah.batas' => ['Unggah File', 'Batas ukuran dan daftar tipe berkas yang diizinkan, divalidasi di server.'],
                'unggah.verifikasi_isi' => ['Unggah File', 'Isi berkas diverifikasi (tipe sebenarnya), bukan hanya ekstensi.'],
                'unggah.nama_acak' => ['Unggah File', 'Nama berkas diacak; nama asli tidak dipakai sebagai path.'],
                'unggah.penyimpanan_terpisah' => ['Unggah File', 'Berkas disimpan terpisah dari kode aplikasi dan tidak bisa dieksekusi.'],
                'unggah.akses_privat' => ['Unggah File', 'Berkas berisi data pribadi hanya bisa diakses pemilik atau peran berwenang (mis. tautan bertanda tangan berumur pendek).'],
            ],
        ],
        'foto_pengguna' => [
            'label' => 'Foto pengguna',
            'regex' => '/(foto (pengguna|profil|diri|tubuh|wajah|anak|ktp)|selfie|avatar|try-?on|kamera)/iu',
            'kontrol' => [
                'foto.persetujuan' => ['Privasi', 'Persetujuan eksplisit sebelum foto pengguna diunggah atau diproses.'],
                'foto.hapus' => ['Privasi', 'Pengguna bisa menghapus fotonya kapan saja dan berkasnya ikut terhapus dari penyimpanan.'],
                'unggah.akses_privat' => ['Unggah File', 'Berkas berisi data pribadi hanya bisa diakses pemilik atau peran berwenang (mis. tautan bertanda tangan berumur pendek).'],
            ],
        ],
        'anak' => [
            'label' => 'Data anak/di bawah umur',
            'regex' => '/\b(anak|anaknya|siswa|murid|pelajar|balita|bayi|remaja|di bawah umur|orang ?tua|wali murid)\b/iu',
            'data_sensitif' => '/anak|siswa|murid|pelajar|balita|bayi|di bawah umur/iu',
            'kontrol' => [
                'anak.persetujuan_wali' => ['Privasi', 'Persetujuan orang tua/wali sebelum data anak dikumpulkan.'],
                'anak.minimisasi' => ['Privasi', 'Data anak dibatasi pada yang benar-benar diperlukan.'],
                'anak.retensi_pendek' => ['Privasi', 'Data anak punya retensi pendek dan dihapus setelah tidak diperlukan.'],
                'anak.tanpa_promosi' => ['Privasi', 'Data anak tidak dipakai untuk promosi tanpa izin orang tua/wali.'],
            ],
        ],
        'kesehatan' => [
            'label' => 'Data kesehatan/alergi',
            'regex' => '/\b(kesehatan|alergi|medis|penyakit|obat|rekam medis|kehamilan|hamil|gizi|disabilitas)\b/iu',
            'data_sensitif' => '/kesehatan|alergi|medis|penyakit|obat|kehamilan|gizi|disabilitas/iu',
            'kontrol' => [
                'sehat.sensitif' => ['Privasi', 'Data kesehatan/alergi diklasifikasikan sensitif dan hanya bisa diakses peran yang membutuhkannya.'],
                'sehat.tanpa_log' => ['Logging & Audit', 'Data kesehatan/alergi tidak pernah ditulis ke log.'],
            ],
        ],
        'bayar' => [
            'label' => 'Pembayaran',
            'regex' => '/\b(bayar online|pembayaran|payment|kartu kredit|kartu debit|virtual account|qris|e-?wallet|dompet digital|midtrans|xendit|stripe|transfer bank)\b/iu',
            'data_sensitif' => '/pembayaran|kartu|rekening|transaksi/iu',
            'kontrol' => [
                'bayar.tokenisasi' => ['Pihak Ketiga & Dependensi', 'Data kartu tidak disimpan di sistem sendiri; tokenisasi lewat penyedia pembayaran.'],
                'bayar.webhook_tanda_tangan' => ['Validasi Input & Output', 'Tanda tangan webhook pembayaran diverifikasi sebelum diproses.'],
                'bayar.idempoten' => ['Validasi Input & Output', 'Webhook pembayaran diproses idempoten: event yang sama tidak diproses dua kali.'],
                'bayar.rekonsiliasi' => ['Validasi Input & Output', 'Status pembayaran direkonsiliasi berkala dengan penyedia pembayaran.'],
            ],
        ],
        'notifikasi' => [
            'label' => 'Notifikasi/pengingat',
            'regex' => '/\b(notifikasi|pengingat|reminder|newsletter|push notif\w*|langganan email|email promo|broadcast|blast)\b/iu',
            'kontrol' => [
                'notif.optin' => ['Privasi', 'Notifikasi hanya dikirim setelah pengguna setuju (opt-in) dan kanalnya disebut jelas.'],
                'notif.berhenti' => ['Privasi', 'Setiap notifikasi menyediakan cara berhenti berlangganan.'],
                'notif.frekuensi' => ['Pembatasan Laju & Anti-Penyalahgunaan', 'Batas frekuensi pengiriman notifikasi per pengguna.'],
            ],
        ],
        'publik' => [
            'label' => 'Formulir/konten publik',
            'regex' => '/\b(formulir|form|komentar|forum|ulasan|review|testimoni|rating|diskusi|komunitas|tanya jawab)\b/iu',
            'kontrol' => [
                'publik.antispam' => ['Pembatasan Laju & Anti-Penyalahgunaan', 'Anti-spam (mis. honeypot atau CAPTCHA ringan) pada formulir dan konten publik.'],
                'publik.batas_laju' => ['Pembatasan Laju & Anti-Penyalahgunaan', 'Pembatasan laju pengiriman per pengguna/IP.'],
                'publik.moderasi' => ['Otorisasi', 'Konten pengguna bisa dilaporkan, disembunyikan, dan dihapus oleh peran berwenang.'],
                'publik.sanitasi' => ['Validasi Input & Output', 'Konten pengguna di-escape/disanitasi saat ditampilkan untuk mencegah XSS.'],
            ],
        ],
        'lokasi' => [
            'label' => 'Lokasi/peta',
            'regex' => '/\b(lokasi|peta|gps|maps|koordinat|terdekat|area pengantaran)\b/iu',
            'kontrol' => [
                'lokasi.izin' => ['Privasi', 'Lokasi pengguna hanya diambil dengan izin eksplisit.'],
                'lokasi.tidak_publik' => ['Privasi', 'Lokasi presisi pengguna tidak dipublikasikan; tampilan publik memakai lokasi umum.'],
            ],
        ],
        'api' => [
            'label' => 'API/endpoint publik',
            'regex' => '/\b(api|endpoint|webhook|integrasi)\b/iu',
            'kontrol' => [
                'api.batas_laju' => ['Pembatasan Laju & Anti-Penyalahgunaan', 'Pembatasan laju pada endpoint API publik.'],
                'api.validasi_skema' => ['Validasi Input & Output', 'Validasi skema permintaan di server.'],
                'api.error_aman' => ['Validasi Input & Output', 'Pesan error tidak membocorkan detail internal (stack trace, query, path).'],
            ],
        ],
        'ai' => [
            'label' => 'Fitur AI',
            'regex' => '/\b(ai|kecerdasan buatan|chatbot|asisten virtual|rekomendasi (pintar|cerdas)|smart curation|llm|gpt|claude|gemini|openrouter|model gateway|try-?on)\b/iu',
            'kontrol' => [
                'ai.data_dikirim' => ['Fitur AI', 'Data yang dikirim ke penyedia AI tertulis jelas; data sensitif tidak dikirim tanpa izin.'],
                'ai.injeksi_prompt' => ['Fitur AI', 'Masukan pengguna diperlakukan sebagai data, bukan instruksi (penanganan prompt injection).'],
                'ai.keluaran_tidak_dipercaya' => ['Fitur AI', 'Keluaran AI tidak dipercaya sebagai perintah dan divalidasi sebelum dipakai atau ditampilkan.'],
            ],
        ],
    ];

    /** Kalimat/klausa pembayaran luring yang tidak memicu kontrol pembayaran online. */
    public const BAYAR_LURING = '/[^.\n;]*\b(pembayaran|bayar|dibayar)\b[^.\n;]*\b(langsung di|di tempat|di lokasi|di kasir|tunai|cash|cod|tanpa (pembayaran|bayar) online|tidak (ada|memproses|melalui) pembayaran)\b[^.\n;]*/iu';

    /** Pemicu khusus: aktif bila ada akun atau tabel yang menyimpan data per pengguna. */
    public const MULTI = [
        'label' => 'Database multi-pengguna',
        'kontrol' => [
            'multi.akses_baris' => ['Otorisasi', 'Setiap tabel berisi data pribadi punya aturan akses per baris: pemeriksaan kepemilikan di server, atau fitur akses per baris bawaan database bila didukung.'],
        ],
    ];

    /** Kontrol dasar yang selalu wajib. */
    public const DASAR = [
        'label' => 'Kontrol dasar',
        'kontrol' => [
            'dasar.otorisasi' => ['Otorisasi', 'Otorisasi dicek di server pada setiap permintaan, bukan hanya disembunyikan di tampilan.'],
            'dasar.validasi' => ['Validasi Input & Output', 'Validasi input di server, query berparameter (anti injeksi), escape keluaran (anti XSS), dan token CSRF bila memakai cookie sesi.'],
            'dasar.rahasia' => ['Rahasia & Konfigurasi', 'Kunci dan rahasia hanya di server (variabel lingkungan atau pengelola rahasia), tidak di klien atau repositori, dan dirotasi bila bocor.'],
            'dasar.https' => ['Transport & Header', 'Seluruh lalu lintas memakai HTTPS.'],
            'dasar.header' => ['Transport & Header', 'Header keamanan dasar dan kebijakan CORS yang membatasi asal.'],
            'dasar.batas_laju' => ['Pembatasan Laju & Anti-Penyalahgunaan', 'Pembatasan laju pada aksi sensitif (login, formulir, pencarian).'],
            'dasar.log_bersih' => ['Logging & Audit', 'Kejadian keamanan dicatat tanpa kata sandi, token, atau data pribadi mentah.'],
            'dasar.backup' => ['Backup & Pemulihan', 'Backup berkala dengan frekuensi usulan dan uji pemulihan terjadwal.'],
            'dasar.privasi' => ['Privasi', 'Halaman kebijakan privasi, minimisasi data, retensi, dan penghapusan data atas permintaan.'],
            'dasar.dependensi' => ['Pihak Ketiga & Dependensi', 'Daftar layanan pihak ketiga yang menerima data pengguna dan pembaruan dependensi berkala.'],
        ],
    ];

    /**
     * v1.2: kontrol tingkat Wajib MVP (dapat dikerjakan dan diuji di M2/M3). Kontrol lain masuk Hardening setelah MVP.
     * Satu SEC MVP boleh memenuhi beberapa kontrol, supaya bab 8 muat dalam maksimal 12 aturan.
     */
    public const MVP = [
        'dasar.otorisasi', 'dasar.validasi', 'dasar.rahasia', 'dasar.https', 'dasar.log_bersih',
        'akun.sandi', 'akun.batas_percobaan', 'akun.sesi',
        'admin.matriks_peran', 'admin.otorisasi_server', 'admin.log_audit',
        'multi.akses_baris', 'unggah.batas', 'unggah.akses_privat', 'foto.persetujuan', 'anak.persetujuan_wali',
        'sehat.sensitif', 'sehat.tanpa_log', 'bayar.tokenisasi', 'bayar.webhook_tanda_tangan',
        'publik.sanitasi', 'publik.antispam', 'ai.injeksi_prompt', 'notif.optin', 'lokasi.izin',
    ];

    /** Kontrol hasil detect() dibagi dua tingkat: ['mvp' => kode => [kelompok, teks, fitur], 'hardening' => ...]. */
    public static function tiers(array $triggers): array
    {
        $all = self::controls($triggers);
        $mvp = array_intersect_key($all, array_flip(self::MVP));
        return ['mvp' => $mvp, 'hardening' => array_diff_key($all, $mvp)];
    }

    /** Kolom yang menandakan data pribadi. */
    public const PERSONAL_COLUMN = '/email|surel|telepon|phone|no_hp|nomor_hp|^hp$|whatsapp|alamat|address|lokasi|latitude|longitude|koordinat|foto|avatar|tanggal_lahir|birth|^nik$|password|kata_sandi|token|alergi|kesehatan|medis|nomor_kartu|rekening|anak|siswa/iu';

    /**
     * @param array $prd data PRD (dipakai: database.tabel, tech_stack)
     * @return array<string, array{label: string, fitur: string[], kontrol: array<string, array{0: string, 1: string}>, data_sensitif?: string}>
     */
    public static function detect(array $project, array $features, array $qa, array $prd): array
    {
        $global = $project['ide_awal'] . "\n";
        foreach ($qa as $row) {
            $global .= $row['jawaban'] . "\n";
        }
        foreach ($prd['database']['tabel'] ?? [] as $t) {
            $global .= $t['nama'] . ' ' . $t['deskripsi'] . "\n";
        }
        foreach ($prd['tech_stack'] ?? [] as $row) {
            $global .= $row['pilihan'] . "\n";
        }

        $found = [];
        foreach (self::RULES as $code => $rule) {
            // Pembayaran luring ("dibayar langsung di tempat", "tanpa pembayaran online") bukan pemicu kontrol pembayaran online.
            $text = fn (string $t) => $code === 'bayar' ? preg_replace(self::BAYAR_LURING, ' ', $t) : $t;
            $fitur = [];
            foreach ($features as $f) {
                if (preg_match($rule['regex'], $text($f['nama'] . ' ' . implode(' ', $f['sub_fitur'])))) {
                    $fitur[] = $f['nama'];
                }
            }
            if ($fitur || preg_match($rule['regex'], $text($global))) {
                $found[$code] = $rule + ['fitur' => $fitur];
                unset($found[$code]['regex']);
            }
        }

        $hasUserRows = false;
        foreach ($prd['database']['tabel'] ?? [] as $t) {
            foreach ($t['kolom'] as $c) {
                if (preg_match('/^(user|pengguna|akun|member|pelanggan)_?id$/i', $c['nama'])) {
                    $hasUserRows = true;
                }
            }
        }
        if (isset($found['akun']) || $hasUserRows) {
            $found['multi'] = self::MULTI + ['fitur' => $found['akun']['fitur'] ?? []];
        }
        $found['dasar'] = self::DASAR + ['fitur' => []];
        return $found;
    }

    /** Semua kontrol wajib (kode => [kelompok, teks, fitur yang disarankan]) dari hasil detect(). */
    public static function controls(array $triggers): array
    {
        $out = [];
        foreach ($triggers as $t) {
            foreach ($t['kontrol'] as $code => [$group, $text]) {
                $out[$code] ??= [$group, $text, []];
                $out[$code][2] = array_values(array_unique(array_merge($out[$code][2], $t['fitur'])));
            }
        }
        return $out;
    }

    /** Nama tabel yang berisi data pribadi (berdasarkan nama kolom). */
    public static function personalTables(array $prd): array
    {
        $tables = [];
        foreach ($prd['database']['tabel'] ?? [] as $t) {
            foreach ($t['kolom'] as $c) {
                if (preg_match(self::PERSONAL_COLUMN, $c['nama'])) {
                    $tables[] = $t['nama'];
                    break;
                }
            }
        }
        return $tables;
    }
}
