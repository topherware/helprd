<?php

/** Data contoh saat USE_MOCK_AI=true, supaya alur bisa dicoba tanpa API key. Bentuknya sama dengan hasil AI. */
final class MockData
{
    /** Ringkasan referensi tiruan: daftar lampiran + potongan isi berkas teks. */
    public static function referensi(array $files): array
    {
        $lines = [];
        foreach ($files as $f) {
            $kind = ATTACH_TYPES[$f['mime']] ?? 'teks';
            $line = "- {$f['nama_asli']} ($kind)";
            if ($kind === 'teks') {
                $line .= ': ' . mb_strimwidth(preg_replace('/\s+/u', ' ', (string) @file_get_contents(UPLOAD_DIR . '/' . $f['path'])), 0, 300, '…');
            } else {
                $line .= ': contoh referensi tampilan dan alur dari pengguna.';
            }
            $lines[] = $line;
        }
        return ['ringkasan' => implode("\n", $lines)];
    }

    /** Pertanyaan tiruan: satu per celah dari Triggers::celah (jumlahnya ikut ide, 3–8). */
    public static function questions(array $project): array
    {
        $topik = self::topik($project['ide_awal']);
        $bank = [
            'pengguna' => ['teks' => "Ceritakan seseorang yang butuh $topik ini. Sekarang mereka ngapain buat mengatasi masalahnya? Tulis bebas ya.", 'tipe' => 'teks_bebas'],
            'aturan' => ['teks' => 'Ada aturan yang tidak boleh dilanggar sistem?', 'tipe' => 'pilihan', 'opsi' => ['Jadwal tidak boleh bentrok', 'Ada batas kuota', 'Stok tidak boleh minus', 'Data tidak boleh kembar', 'Tidak ada'], 'boleh_pilih_banyak' => true],
            'akun' => ['teks' => 'Pengguna harus punya akun dulu, atau boleh langsung pakai?', 'tipe' => 'pilihan', 'opsi' => ['Wajib akun', 'Boleh tanpa akun', 'Dua-duanya bisa']],
            'pengelola' => ['teks' => 'Siapa yang mengelola data dan statusnya?', 'tipe' => 'pilihan', 'opsi' => ['Pemilik saja', 'Pemilik dan staf', 'Beberapa admin'], 'boleh_pilih_banyak' => false],
            'angka' => ['teks' => 'Ada angka penting yang jadi aturan? Misalnya batas waktu, jumlah maksimal, atau jam buka.', 'tipe' => 'teks_bebas'],
            'notifikasi' => ['teks' => 'Perlu pemberitahuan otomatis ke pengguna? Lewat apa?', 'tipe' => 'pilihan', 'opsi' => ['WhatsApp', 'Email', 'Di aplikasi saja', 'Tidak perlu'], 'boleh_pilih_banyak' => true],
            'luring' => ['teks' => 'Ada transaksi di luar aplikasi (datang langsung, telepon) yang tetap perlu dicatat?', 'tipe' => 'pilihan', 'opsi' => ['Ya, dicatat pengelola', 'Tidak ada']],
            'pembayaran' => ['teks' => 'Pembayarannya bagaimana?', 'tipe' => 'pilihan', 'opsi' => ['Bayar di tempat', 'Transfer manual', 'Pembayaran online', 'Gratis']],
            'prioritas' => ['teks' => 'Dari fitur di bawah, pilih 3 yang paling wajib ada.', 'tipe' => 'pilihan', 'opsi' => ['Daftar produk + harga', 'Form pemesanan', 'Chat WhatsApp', 'Pembayaran online', 'Galeri foto', 'Testimoni'], 'boleh_pilih_banyak' => true],
            'kembali' => ['teks' => 'Apa yang bikin orang mau balik lagi, bukan cuma buka sekali terus lupa?', 'tipe' => 'pilihan', 'opsi' => ['Konten baru rutin', 'Diskon pelanggan setia', 'Pesan ulang cepat', 'Notifikasi promo'], 'boleh_pilih_banyak' => true],
        ];
        return ['pertanyaan' => array_map(fn ($g) => ['celah' => $g] + $bank[$g], Triggers::celah(Prompts::ideWithReference($project)))];
    }

    public static function features(array $project): array
    {
        return [
            'judul' => mb_strimwidth(ucfirst(self::topik($project['ide_awal'])), 0, 40, '…'),
            'rekomendasi_stack' => $project['tech_mode'] === 'ai'
                ? "Frontend: HTML + Tailwind CSS, ringan dan cepat dibuat\nBackend: PHP 8 (Laravel) untuk logika pemesanan dan admin\nDatabase: MySQL, cocok untuk data produk dan pesanan\nHosting: shared hosting atau VPS kecil, murah untuk mulai"
                : null,
            'fitur' => [
                ['nama' => 'Beranda', 'fase' => 1, 'ikon' => 'rumah', 'asal' => 'ide', 'sub_fitur' => ['Banner & Tagline', 'Ringkasan Layanan', 'Tombol Aksi Utama']],
                ['nama' => 'Daftar Produk', 'fase' => 1, 'ikon' => 'dokumen', 'asal' => 'ide', 'sub_fitur' => ['Kategori', 'Kartu Produk & Harga', 'Detail Produk', 'Pencarian']],
                ['nama' => 'Pemesanan', 'fase' => 1, 'ikon' => 'keranjang', 'asal' => 'ide', 'sub_fitur' => ['Form Pesanan', 'Pilih Tanggal', 'Pesan via WhatsApp']],
                ['nama' => 'Pembayaran', 'fase' => 2, 'ikon' => 'uang', 'asal' => 'jawaban', 'sub_fitur' => ['Pilih Metode Bayar', 'Upload Bukti Transfer', 'Status Pembayaran']],
                ['nama' => 'Akun Saya', 'fase' => 2, 'ikon' => 'pengguna', 'asal' => 'jawaban', 'sub_fitur' => ['Daftar & Masuk', 'Profil', 'Riwayat Pesanan', 'Pesan Ulang']],
                ['nama' => 'Dashboard Admin', 'fase' => 3, 'ikon' => 'grafik', 'asal' => 'ide', 'sub_fitur' => ['Kelola Produk', 'Kelola Pesanan', 'Laporan Penjualan']],
                ['nama' => 'Promo & Diskon', 'fase' => 3, 'ikon' => 'notifikasi', 'asal' => 'usulan_ai', 'alasan' => 'Diskon sering dipakai untuk menarik pelanggan kembali.', 'sub_fitur' => ['Kode Voucher', 'Diskon Pelanggan Setia']],
                ['nama' => 'Info & Kontak', 'fase' => 4, 'ikon' => 'lokasi', 'asal' => 'ide', 'sub_fitur' => ['Alamat & Jam Buka', 'Peta Lokasi', 'FAQ']],
                ['nama' => 'Testimoni', 'fase' => 4, 'ikon' => 'bintang', 'asal' => 'usulan_ai', 'alasan' => 'Ulasan pelanggan menambah kepercayaan calon pembeli.', 'sub_fitur' => ['Ulasan & Rating', 'Galeri Foto']],
            ],
        ];
    }

    /* ---------- PRD v1.2 tiruan ---------- */

    /** Potongan profil tiruan: bendera dari kata kunci (lewat Triggers), stack default, peran umum. */
    public static function profil(Brief $b): array
    {
        $admin = (bool) preg_match(Triggers::KEYWORDS['hasAdminMutation'], $b->ide . ' ' . $b->extraNotes . ' ' . json_encode($b->features, JSON_UNESCAPED_UNICODE));
        return ['profil' => [
            'bendera' => [],
            'stack_id' => $b->stackChoice === 'bebas' || $b->stackChoice === 'tulis_sendiri' ? 'standar_planku' : $b->stackChoice,
            'stack_kustom' => $b->stackChoice === 'tulis_sendiri' ? ['mirip' => 'standar_planku', 'db' => 'postgresql', 'bahasa_skema' => 'sql', 'rls' => false, 'serverless' => false] : new stdClass(),
            'peran' => array_merge([['nama' => 'pengunjung anonim', 'asal' => 'ide'], ['nama' => 'pelanggan', 'asal' => 'ide']], $admin ? [['nama' => 'admin', 'asal' => 'ide']] : []),
            'invarian' => [], // pola kata di Triggers::INVARIAN yang menangkap invarian dari masukan
            'alasan_stack' => 'Stack ini paling cepat dipakai untuk MVP web yang ramah HP dan mudah dikelola pemilik.',
        ]];
    }

    /**
     * PRD v1.2 tiruan lengkap (bab 1–13) yang dibangun dari Brief, bendera, stack, dan peran,
     * sehingga lolos semua validator untuk ide apa pun.
     * @param array $ctx stack, flags, roles, controls (SecurityTriggers::tiers), security (SecurityTriggers::detect)
     */
    public static function prd(Brief $b, array $ctx): array
    {
        [$stack, $flags, $roles] = [$ctx['stack'], $ctx['flags'], $ctx['roles']];
        $names = array_column($b->features, 'nama');
        $invariants = $ctx['invariants'] ?? Triggers::invariants($b);
        $angka = Triggers::angka($b);
        $tables = self::tables($flags, $ctx['security'] ?? [], $invariants, $angka);
        $fr = [];
        foreach ($b->features as $i => $f) {
            $fr[] = ['id' => sprintf('FR-%02d', $i + 1), 'teks' => "Pengguna dapat memakai {$f['nama']}: " . implode(', ', $f['sub_fitur']) . '.', 'fitur' => [$f['nama']]];
        }
        if ($flags['hasGuestFlow'] && !$b->uiOnly) {
            // P3: data milik tamu hanya dengan pasangan kode rahasia + nomor kontak.
            $fr[] = ['id' => sprintf('FR-%02d', count($fr) + 1), 'teks' => 'Pengunjung tanpa akun dapat mengecek status transaksinya dengan kode transaksi yang diterbitkan saat transaksi dan nomor kontak yang dipakai; satu pengenal saja ditolak.', 'fitur' => [$names[0]]];
        }
        $applied = [];
        foreach ($b->noteItems() as $rule) {
            $id = sprintf('FR-%02d', count($fr) + 1);
            $fr[] = ['id' => $id, 'teks' => "Aturan bisnis dari pemilik: $rule.", 'fitur' => [$names[0]]];
            $applied[] = ['aturan' => $rule, 'ref' => [$id]];
        }

        $prd = [
            'overview' => [
                'deskripsi' => "**{$b->judul}** adalah " . self::topik($b->ide) . ' yang dirancang supaya mudah dipakai di ponsel maupun desktop.',
                'masalah' => 'Rina, calon pengguna, saat ini masih mengandalkan chat WhatsApp dan catatan manual. Informasi tersebar, sering terlewat, dan ia harus bertanya hal yang sama berulang kali.',
                'tujuan' => ['Menjadi satu tempat yang jelas untuk kebutuhan utama pengguna.', 'Mempercepat proses yang sekarang masih manual.', 'Memberi pemilik kendali penuh atas data dan konten.'],
                'prinsip' => ['Tidak ada klaim atau data palsu.', 'Data pribadi hanya dipakai dengan izin pengguna.'],
                'sukses' => 'Pengguna bisa menyelesaikan tujuan utamanya dalam 3 menit tanpa harus bertanya lewat chat.',
            ],
            'requirements' => [
                'fungsional' => $fr,
                'non_fungsional' => [
                    ['id' => 'NFR-01', 'teks' => 'Halaman utama tampil dalam 3 detik pada koneksi 4G.', 'fitur' => []],
                    ['id' => 'NFR-02', 'teks' => 'Tampilan nyaman dipakai pada lebar layar 360 px sampai 1440 px.', 'fitur' => []],
                    ['id' => 'NFR-03', 'teks' => 'Kontras teks minimal 4.5:1 dan area ketuk minimal 44×44 px.', 'fitur' => []],
                ],
                'di_luar_cakupan' => ['Aplikasi mobile native.', 'Integrasi pihak ketiga di luar yang disebut di bab 12.'],
                'pertanyaan_terbuka' => [['id' => 'OQ-01', 'teks' => 'Berapa batas jumlah item per transaksi yang diinginkan pemilik?', 'fitur' => []]],
                'catatan_diterapkan' => $applied,
                'usulan_tambahan' => array_map(fn ($u) => ['fitur' => $u['nama'], 'alasan' => $u['alasan'] !== '' ? $u['alasan'] : 'Usulan AI; belum diminta pengguna.'], array_slice($b->usulan, 0, 3)),
                'seo' => $flags['hasPublicPages'] ? [
                    'Judul dan meta description dinamis di setiap halaman publik.',
                    'Tag Open Graph dan Twitter Card untuk halaman publik.',
                    'sitemap.xml dan robots.txt dibuat otomatis.',
                    'Halaman privat, login, dan admin diberi noindex.',
                    'Gambar dioptimalkan (format WebP/AVIF, ukuran responsif, lazy loading).',
                    'Data publik di-cache dengan revalidasi berkala dan revalidasi saat pengelola mengubah data.',
                ] : [],
            ],
            'fitur' => array_map(fn ($f) => [
                'deskripsi' => "Membantu pengguna lewat {$f['nama']}.",
                'sub_fitur' => array_map(fn ($s) => "Pengguna dapat memakai $s dengan mudah.", $f['sub_fitur']),
            ], $b->features),
            'user_flow' => [
                ['judul' => 'Menemukan informasi', 'fase' => '1', 'langkah' => 'Buka beranda → lihat daftar → buka detail → hubungi pemilik'],
                ['judul' => 'Melakukan transaksi', 'fase' => '1 → 2', 'langkah' => 'Pilih item → isi form → konfirmasi → terima pesan konfirmasi'],
                ['judul' => 'Mengelola data (admin)', 'fase' => '3', 'langkah' => 'Masuk dasbor → kelola data → lihat riwayat perubahan'],
            ],
            'arsitektur' => [
                'ringkasan' => $b->uiOnly ? 'Prototipe frontend statis dengan data tiruan dari bab 11, tanpa backend.' : 'Aplikasi web dengan frontend responsif, logika di sisi server, dan database relasional.',
                'komponen' => $b->uiOnly
                    ? [['nama' => 'Frontend', 'deskripsi' => 'Semua halaman dan komponen dengan data tiruan.']]
                    : [['nama' => 'Frontend', 'deskripsi' => 'Halaman publik, akun, dan dasbor admin.'], ['nama' => 'Backend', 'deskripsi' => 'Validasi, logika bisnis, dan akses database.'], ['nama' => 'Database', 'deskripsi' => 'Menyimpan pengguna, item, dan transaksi.']],
                'sequence_mermaid' => "sequenceDiagram\n    autonumber\n    actor U as Pengguna\n    participant W as WebApp\n    participant S as Server\n    participant DB as Database\n    U->>W: Buka halaman\n    W->>S: Minta data\n    S->>DB: Ambil data\n    DB-->>S: Hasil\n    S-->>W: Data\n    W-->>U: Tampilkan",
            ],
            'tech_stack' => self::techStack($b, $stack),
            'keputusan_teknologi' => [
                'stack' => $b->stackChoice === 'tulis_sendiri' ? "Pilihan pengguna: {$b->stackCustom}" : $stack['nama'],
                'alasan' => "Dipakai {$stack['ringkas']} karena paling cepat untuk MVP dan sesuai pilihan di langkah Teknologi.",
            ],
            'kedalaman' => self::depth($b, $stack, $flags),
            'design' => self::design($b, $flags),
            'rute' => self::routes($b, $roles, $flags),
            'dummy' => self::dummy($b, $tables, $flags),
        ];
        if (!$b->uiOnly) {
            $prd['database'] = self::database($tables, $stack, $flags, $invariants, $roles);
            // Kontrol keamanan dihitung ulang setelah tabel & tech stack ada, sama seperti pipeline sungguhan.
            $ctx = Pipeline::context($b, ['stack' => $stack, 'bendera' => $flags, 'peran' => $roles], $prd);
            $prd['keamanan'] = self::security($b, $ctx, $tables, $roles);
            $prd['layanan'] = self::services($stack, $flags, $roles);
        }
        $prd['profil'] = ['peran' => $roles];
        $prd['milestone'] = self::milestones($b, $stack, $flags, $prd);
        unset($prd['profil']);
        return $prd;
    }

    /**
     * Tabel tiruan: [nama => [deskripsi, teknis, kolom: [[nama, jenis, boleh_kosong, keterangan, nilai_bawaan?]], tunggal?]].
     * v1.2.1: kolom rentang waktu & kode tamu, stok, sumber transaksi luring, dan tabel pengaturan dari angka masukan.
     */
    private static function tables(array $flags, array $security, array $invariants = [], array $angka = []): array
    {
        $jenisInv = array_column($invariants, 'jenis');
        $priced = $flags['hasPricedEntity'];
        $user = [['id', 'pk', false, ''], ['nama', 'text', false, ''], ['email', 'text', false, 'unik'], ['telepon', 'text', true, ''], ['peran', 'enum', false, 'enum: pelanggan, admin'], ['dibuat_pada', 'time', false, '']];
        if (isset($security['anak'])) {
            $user[] = ['data_anak', 'text', true, 'nama dan kelas anak'];
        }
        if (isset($security['kesehatan'])) {
            $user[] = ['catatan_alergi', 'text', true, 'data kesehatan, sensitif'];
        }
        $item = array_values(array_filter([['id', 'pk', false, ''], ['nama', 'text', false, ''], ['deskripsi', 'text', true, ''],
            $priced ? ['harga_rp', 'int', false, 'rupiah'] : null, $flags['usesFileStorage'] ? ['gambar_url', 'text', true, ''] : null,
            in_array('stok', $jenisInv, true) ? ['stok', 'int', false, 'tidak boleh negatif'] : null,
            ['aktif', 'bool', false, ''], ['dibuat_pada', 'time', false, '']]));
        $tables = ['pengguna' => ['Akun pelanggan dan admin', false, $user], 'item' => ['Item utama yang ditampilkan', false, $item]];
        if ($flags['hasTransactionRecord']) {
            $tables['transaksi'] = ['Transaksi pengguna', false, array_values(array_filter([
                ['id', 'pk', false, ''], ['user_id', 'fk:pengguna', $flags['hasGuestFlow'], ''],
                $flags['hasGuestFlow'] ? ['nama_tamu', 'text', true, 'diisi bila tanpa akun'] : null,
                $flags['hasGuestFlow'] ? ['kontak_tamu', 'text', true, 'nomor WhatsApp tamu'] : null,
                $flags['hasGuestFlow'] ? ['kode', 'text', false, 'unik, kode rahasia 6 karakter untuk cek status'] : null,
                ['item_id', 'fk:item', false, ''], ['nama_item_snapshot', 'text', false, 'salinan nama saat transaksi'],
                $priced ? ['harga_satuan_snapshot', 'int', false, 'rupiah, salinan harga saat transaksi'] : null,
                ['jumlah', 'int', false, ''], $priced ? ['total_rp', 'int', false, 'rupiah'] : null,
                in_array('rentang', $jenisInv, true) ? ['mulai_pada', 'tstz', false, 'awal rentang'] : null,
                in_array('rentang', $jenisInv, true) ? ['selesai_pada', 'tstz', false, 'akhir rentang'] : null,
                $flags['hasOfflineChannel'] ? ['sumber', 'enum', false, 'enum: online, manual'] : null,
                ['status', 'enum', false, 'enum: menunggu, diproses, selesai, dibatalkan'], ['dibuat_pada', 'time', false, ''],
            ]))];
        }
        // P7: angka dari masukan pengguna menjadi kolom pengaturan dengan nilai bawaan; template pesan juga disimpan di sini.
        $settings = [['id', 'pk', false, '']];
        foreach ($angka as $a) {
            $settings[] = [$a['kunci'], 'int', false, "nilai dari masukan: {$a['kalimat']}", (int) $a['nilai']];
        }
        if ($flags['usesWhatsappApi'] || $flags['usesEmail'] || $flags['hasScheduler']) {
            $settings[] = ['template_konfirmasi', 'text', false, 'isi pesan konfirmasi, placeholder {nama} {kode}', 'Halo {nama}, transaksi Anda sudah kami terima. Kode: {kode}.'];
            if ($flags['hasScheduler']) {
                $settings[] = ['template_pengingat', 'text', false, 'isi pesan pengingat, placeholder {nama} {waktu}', 'Pengingat: jadwal Anda {waktu}. Sampai jumpa!'];
            }
        }
        if (count($settings) > 1) {
            $settings[] = ['diperbarui_pada', 'time', false, ''];
            $tables['pengaturan'] = ['Pengaturan aplikasi (satu baris), diubah pengelola', false, $settings, true];
        }
        if ($flags['hasAdminMutation']) {
            $tables['log_audit'] = ['Catatan aksi admin', true, [['id', 'pk', false, ''], ['aktor_id', 'fk:pengguna', false, ''], ['aksi', 'text', false, ''],
                ['entitas', 'text', false, ''], ['entitas_id', 'text', false, ''], ['ringkasan_perubahan', 'json', true, 'nilai sebelum/sesudah ringkas'], ['dibuat_pada', 'time', false, '']]];
        }
        return $tables;
    }

    private static function database(array $tables, array $stack, array $flags, array $invariants, array $roles): array
    {
        $tipe = fn ($c) => trim(match (true) {
            $c[1] === 'pk' => 'uuid, PK',
            str_starts_with($c[1], 'fk:') => 'uuid, FK→' . substr($c[1], 3),
            $c[1] === 'int' => 'integer',
            $c[1] === 'bool' => 'boolean',
            $c[1] === 'time' => 'timestamp',
            $c[1] === 'tstz' => 'timestamptz (UTC)',
            $c[1] === 'json' => 'json',
            default => 'text',
        } . ($c[3] !== '' ? ", {$c[3]}" : '') . ($c[2] ? ', boleh kosong' : '') . (isset($c[4]) ? ', bawaan ' . (is_int($c[4]) ? $c[4] : 'teks templat') : ''), ', ');
        $out = [];
        foreach ($tables as $name => $t) {
            $out[] = ['nama' => $name, 'deskripsi' => $t[0], 'tunggal' => !empty($t[3]), 'teknis' => $t[1], 'kolom' => array_map(fn ($c) => ['nama' => $c[0], 'tipe' => $tipe($c)], $t[2])];
        }
        $relasi = [['dari' => 'pengguna', 'ke' => 'transaksi', 'jenis' => 'satu-ke-banyak', 'label' => 'membuat'], ['dari' => 'item', 'ke' => 'transaksi', 'jenis' => 'satu-ke-banyak', 'label' => 'dipesan']];
        if (!isset($tables['transaksi'])) {
            $relasi = [];
        }
        if (isset($tables['log_audit'])) {
            $relasi[] = ['dari' => 'pengguna', 'ke' => 'log_audit', 'jenis' => 'satu-ke-banyak', 'label' => 'mencatat'];
        }
        [$inv, $custom] = self::invariantMechanisms($invariants, $tables, $stack);
        $settings = [];
        foreach ($tables['pengaturan'][2] ?? [] as $c) {
            if (isset($c[4])) {
                $settings[] = ['kunci' => $c[0], 'nilai_bawaan' => (string) $c[4], 'lokasi' => "pengaturan.{$c[0]}", 'pengubah' => in_array('admin', $roles, true) ? 'admin' : 'pemilik aplikasi'];
            }
        }
        $policies = $stack['rls']
            ? array_map(fn ($t) => ['tabel' => $t, 'aturan' => "Row Level Security aktif: pengguna hanya membaca dan mengubah baris $t miliknya; admin lewat peran admin."], array_values(array_intersect(['pengguna', 'transaksi'], array_keys($tables))))
            : [['tabel' => 'transaksi', 'aturan' => 'Server memeriksa kepemilikan (user_id sama dengan pengguna yang masuk) di setiap permintaan.']];
        return [
            'catatan' => 'Uang disimpan sebagai bilangan bulat rupiah. Semua tabel punya kolom waktu dibuat_pada.',
            'tabel' => $out,
            'relasi' => $relasi,
            'kode_skema' => self::schemaCode($tables, $stack),
            'aturan_pemicu' => [
                'snapshot' => isset($tables['transaksi']) && $flags['hasPricedEntity'] ? 'transaksi.nama_item_snapshot dan transaksi.harga_satuan_snapshot menyalin nilai saat transaksi, jadi perubahan harga tidak mengubah riwayat.' : '',
                'log_audit' => $flags['hasAdminMutation'] ? 'Aksi yang wajib dicatat: tambah/ubah/hapus item, ubah status transaksi, ubah peran pengguna.' : '',
                'tamu' => $flags['hasGuestFlow'] ? 'Transaksi tamu (user_id kosong) dihitung sama dengan transaksi akun untuk rekap jumlah per hari; kontak_tamu dipakai untuk konfirmasi.' : '',
            ],
            'kebijakan_akses' => $policies,
            'invarian' => $inv,
            'migrasi_kustom' => $custom,
            'pengaturan' => $settings,
        ];
    }

    /**
     * P1: tiap invarian dijaga di database sesuai resep stacks.json, lengkap dengan konkurensi dan uji balapan.
     * @return array [invarian, sql_migrasi_kustom]
     */
    private static function invariantMechanisms(array $invariants, array $tables, array $stack): array
    {
        $db = $stack['db'];
        $recipes = Brief::stackConfig()['resep_invarian'][$db] ?? Brief::stackConfig()['resep_invarian']['postgresql'];
        $out = [];
        $sql = [];
        foreach ($invariants as $i => $inv) {
            $id = sprintf('INV-%02d', $i + 1);
            [$mekanisme, $konkurensi] = match ($inv['jenis']) {
                'rentang' => $db === 'd1' || $db === 'sqlite'
                    ? ['Penyimpanan atomik INSERT INTO transaksi ... SELECT ... WHERE NOT EXISTS (SELECT 1 FROM transaksi WHERE item_id = ? AND status <> \'dibatalkan\' AND mulai_pada < ? AND selesai_pada > ?) — rentang mulai_pada & selesai_pada, bukan hanya titik mulai.', 'Satu pernyataan atomik dalam ' . ($db === 'd1' ? 'db.batch()' : 'transaksi BEGIN IMMEDIATE') . '; jumlah baris tersimpan 0 berarti bentrok dan pengguna diminta memilih waktu lain.']
                    : ['Exclusion constraint transaksi_tanpa_tumpang_tindih: EXCLUDE USING gist (item_id WITH =, tstzrange(mulai_pada, selesai_pada, \'[)\') WITH &&) untuk baris berstatus aktif (ekstensi btree_gist).', 'Transaksi database dengan SELECT ... FOR UPDATE pada baris item sebagai lapisan kedua; galat 23P01 diterjemahkan menjadi pesan "waktu ini baru saja diambil".'],
                'stok' => ['CHECK (stok >= 0) pada item.stok dan pengurangan atomik UPDATE item SET stok = stok - ? WHERE id = ? AND stok >= ?.', 'Pengurangan stok dan penyimpanan transaksi dalam satu transaksi database; bila baris terubah 0, transaksi dibatalkan.'],
                'kuota' => ['Batas dibaca dari tabel pengaturan lalu dihitung ulang di dalam transaksi: ' . $recipes['kuota'] . '.', 'Penguncian baris induk selama hitung ulang supaya dua permintaan tidak sama-sama lolos.'],
                default => ['UNIQUE index pada kolom yang dijaga: ' . $recipes['unik'] . '.', 'Galat pelanggaran unik diterjemahkan menjadi pesan ramah pengguna.'],
            };
            $out[] = ['id' => $id, 'jenis' => $inv['jenis'], 'aturan' => $inv['aturan'], 'mekanisme' => $mekanisme, 'konkurensi' => $konkurensi,
                'uji' => "Uji dua permintaan bersamaan untuk $id: hanya satu yang berhasil, yang lain menerima pesan gagal yang jelas."];
            if ($inv['jenis'] === 'rentang' && isset($tables['transaksi'])) {
                $sql[] = $db === 'd1' || $db === 'sqlite'
                    ? "-- $id: simpan atomik tanpa tumpang tindih rentang (mulai_pada, selesai_pada)\nINSERT INTO transaksi (id, item_id, mulai_pada, selesai_pada, status)\nSELECT ?, ?, ?, ?, 'menunggu'\nWHERE NOT EXISTS (\n  SELECT 1 FROM transaksi\n  WHERE item_id = ? AND status <> 'dibatalkan' AND mulai_pada < ? AND selesai_pada > ?\n);"
                    : "-- $id: tidak boleh ada dua transaksi aktif untuk item yang sama pada rentang yang bertabrakan\nCREATE EXTENSION IF NOT EXISTS btree_gist;\nALTER TABLE transaksi\n  ADD CONSTRAINT transaksi_tanpa_tumpang_tindih\n  EXCLUDE USING gist (item_id WITH =, tstzrange(mulai_pada, selesai_pada, '[)') WITH &&)\n  WHERE (status <> 'dibatalkan');";
            }
            if ($inv['jenis'] === 'stok' && isset($tables['item'])) {
                $sql[] = "-- $id: stok tidak boleh negatif\nALTER TABLE item ADD CONSTRAINT item_stok_tidak_negatif CHECK (stok >= 0);";
            }
        }
        return [$out, implode("\n\n", $sql)];
    }

    /** Kode skema sesuai bahasa stack: SQL, Drizzle (TypeScript), atau Prisma. */
    private static function schemaCode(array $tables, array $stack): string
    {
        $lang = $stack['bahasa_skema'];
        $sqlite = in_array($stack['db'], ['d1', 'sqlite'], true);
        $blocks = [];
        foreach ($tables as $name => [, , $cols]) {
            if ($lang === 'ts') {
                $fn = $sqlite ? 'sqliteTable' : 'pgTable';
                $lines = array_map(function ($c) use ($sqlite) {
                    $base = match (true) {
                        $c[1] === 'pk' => $sqlite ? "text('{$c[0]}').primaryKey()" : "uuid('{$c[0]}').primaryKey().defaultRandom()",
                        str_starts_with($c[1], 'fk:') => ($sqlite ? "text('{$c[0]}')" : "uuid('{$c[0]}')") . '.references(() => ' . substr($c[1], 3) . '.id)',
                        $c[1] === 'int' => "integer('{$c[0]}')",
                        $c[1] === 'bool' => $sqlite ? "integer('{$c[0]}', { mode: 'boolean' })" : "boolean('{$c[0]}')",
                        $c[1] === 'time' => $sqlite ? "integer('{$c[0]}', { mode: 'timestamp' })" : "timestamp('{$c[0]}', { withTimezone: true }).defaultNow()",
                        $c[1] === 'tstz' => $sqlite ? "text('{$c[0]}')" : "timestamp('{$c[0]}', { withTimezone: true })",
                        $c[1] === 'json' => $sqlite ? "text('{$c[0]}', { mode: 'json' })" : "jsonb('{$c[0]}')",
                        default => "text('{$c[0]}')",
                    };
                    $default = isset($c[4]) ? '.default(' . (is_int($c[4]) ? $c[4] : json_encode($c[4], JSON_UNESCAPED_UNICODE)) . ')' : '';
                    return "  {$c[0]}: $base" . (!$c[2] && $c[1] !== 'pk' ? '.notNull()' : '') . $default . ',';
                }, $cols);
                $blocks[] = "export const $name = $fn('$name', {\n" . implode("\n", $lines) . "\n});";
            } elseif ($lang === 'prisma') {
                $lines = array_map(function ($c) {
                    $type = match (true) {
                        $c[1] === 'int' => 'Int', $c[1] === 'bool' => 'Boolean', $c[1] === 'time', $c[1] === 'tstz' => 'DateTime', $c[1] === 'json' => 'Json', default => 'String',
                    };
                    $attr = $c[1] === 'pk' ? ' @id @default(uuid())' : ($c[1] === 'time' ? ' @default(now()) @db.Timestamptz' : ($c[1] === 'tstz' ? ' @db.Timestamptz' : ''));
                    $attr .= isset($c[4]) ? ' @default(' . (is_int($c[4]) ? $c[4] : json_encode($c[4], JSON_UNESCAPED_UNICODE)) . ')' : '';
                    return "  {$c[0]} $type" . ($c[2] ? '?' : '') . $attr;
                }, $cols);
                $blocks[] = 'model ' . str_replace('_', '', ucwords($name, '_')) . " {\n" . implode("\n", $lines) . "\n\n  @@map(\"$name\")\n}";
            } else {
                $lines = array_map(function ($c) use ($sqlite) {
                    $type = match (true) {
                        $c[1] === 'pk' => $sqlite ? 'TEXT PRIMARY KEY' : 'UUID PRIMARY KEY DEFAULT gen_random_uuid()',
                        str_starts_with($c[1], 'fk:') => ($sqlite ? 'TEXT' : 'UUID') . ' REFERENCES ' . substr($c[1], 3) . '(id)',
                        $c[1] === 'int' => 'INTEGER', $c[1] === 'bool' => $sqlite ? 'INTEGER' : 'BOOLEAN',
                        $c[1] === 'time' => $sqlite ? 'TEXT DEFAULT CURRENT_TIMESTAMP' : 'TIMESTAMPTZ DEFAULT now()',
                        $c[1] === 'tstz' => $sqlite ? 'TEXT' : 'TIMESTAMPTZ',
                        $c[1] === 'json' => $sqlite ? 'TEXT' : 'JSONB', default => 'TEXT',
                    };
                    $default = isset($c[4]) ? ' DEFAULT ' . (is_int($c[4]) ? $c[4] : "'" . str_replace("'", "''", $c[4]) . "'") : '';
                    return "  {$c[0]} $type" . (!$c[2] && $c[1] !== 'pk' ? ' NOT NULL' : '') . $default;
                }, $cols);
                $blocks[] = "CREATE TABLE $name (\n" . implode(",\n", $lines) . "\n);";
            }
        }
        if ($lang === 'sql' && $stack['rls']) {
            foreach (array_intersect(['pengguna', 'transaksi'], array_keys($tables)) as $t) {
                $owner = $t === 'pengguna' ? 'id' : 'user_id';
                $blocks[] = "ALTER TABLE $t ENABLE ROW LEVEL SECURITY;\nCREATE POLICY {$t}_milik_sendiri ON $t FOR ALL USING (auth.uid() = $owner);";
            }
        }
        $head = match ($lang) {
            'ts' => "import { " . ($sqlite ? 'sqliteTable, text, integer' : 'pgTable, uuid, text, integer, boolean, timestamp, jsonb') . " } from 'drizzle-orm/" . ($sqlite ? 'sqlite-core' : 'pg-core') . "';\n\n",
            'prisma' => "datasource db {\n  provider = \"postgresql\"\n  url      = env(\"DATABASE_URL\")\n}\n\n",
            default => '',
        };
        return $head . implode("\n\n", $blocks);
    }

    private static function techStack(Brief $b, array $stack): array
    {
        if ($b->uiOnly) {
            return [
                ['lapisan' => 'Frontend', 'pilihan' => $stack['lapisan']['Frontend'], 'alasan' => 'Komponen siap pakai dan cepat dibuat.'],
                ['lapisan' => 'Data', 'pilihan' => 'Data tiruan statis dari bab 11 (berkas JSON di repositori)', 'alasan' => 'Prototipe tanpa backend.'],
                ['lapisan' => 'Rahasia & Konfigurasi', 'pilihan' => 'Tidak ada rahasia; konfigurasi publik lewat variabel lingkungan build', 'alasan' => 'Mode hanya-UI.'],
                ['lapisan' => 'Deployment', 'pilihan' => $stack['kedalaman']['deployment'] ?? 'Hosting statis', 'alasan' => 'Hosting statis cukup untuk prototipe tanpa backend.'],
                ['lapisan' => 'Pengujian', 'pilihan' => 'Playwright untuk alur tampilan', 'alasan' => 'Menjaga halaman tetap tampil benar.'],
            ];
        }
        $rows = [];
        foreach ($stack['lapisan'] as $layer => $choice) {
            $rows[] = ['lapisan' => $layer, 'pilihan' => $b->stackChoice === 'tulis_sendiri' && $layer === 'Frontend' ? "{$b->stackCustom} (pilihan pengguna)" : $choice, 'alasan' => "Bagian dari stack {$stack['nama']}."];
        }
        $rows[] = ['lapisan' => 'Rahasia & Konfigurasi', 'pilihan' => 'Variabel lingkungan di server (.env, tidak di-commit; contoh di .env.example)', 'alasan' => 'Kunci tidak pernah sampai ke browser atau repositori.'];
        return $rows;
    }

    /** Bab 7 kedalaman teknis (P5) langsung dari stacks.json; zona waktu dan penjadwal hanya bila benderanya aktif. */
    private static function depth(Brief $b, array $stack, array $flags): array
    {
        $k = $stack['kedalaman'];
        $cron = $flags['hasScheduler'] ? [
            'berkas' => $k['penjadwal']['berkas'],
            'endpoint' => str_replace('{tugas}', 'pengingat', $k['penjadwal']['endpoint']),
            'interval' => $k['penjadwal']['interval'],
            'rahasia' => $k['penjadwal']['rahasia'],
            'idempotensi' => str_replace('{aksi}', 'pengingat', $k['penjadwal']['idempotensi']),
        ] : ['berkas' => '', 'endpoint' => '', 'interval' => '', 'rahasia' => '', 'idempotensi' => ''];
        return [
            'versi' => $k['versi'],
            'auth' => $b->uiOnly ? 'Tidak ada autentikasi (mode hanya-UI).' : $k['auth'],
            'orm_migrasi' => $b->uiOnly ? 'Tidak ada database (mode hanya-UI); data tiruan dari berkas JSON.' : $k['orm_migrasi'],
            'validasi' => $k['validasi'],
            'zona_waktu' => $flags['hasTimeFields'] ? $k['zona_waktu'] : '',
            'penjadwal' => $cron,
            'uji' => $k['uji'],
            'deployment' => $k['deployment'],
        ];
    }

    private static function design(Brief $b, array $flags): array
    {
        $palettes = [
            'bersih_modern' => ['#4F46E5', '#0EA5E9', '#F59E0B', '#FFFFFF', '#F8FAFC', '#0F172A', '#475569', '#E2E8F0', '#16A34A', '#B45309', '#DC2626', 'Plus Jakarta Sans', 'Inter'],
            'minimalis' => ['#111827', '#6B7280', '#2563EB', '#FFFFFF', '#FAFAFA', '#111827', '#4B5563', '#E5E7EB', '#15803D', '#B45309', '#B91C1C', 'DM Serif Display', 'Inter'],
            'korporat' => ['#1D4ED8', '#0F766E', '#F59E0B', '#FFFFFF', '#F1F5F9', '#0F172A', '#475569', '#CBD5E1', '#15803D', '#B45309', '#B91C1C', 'IBM Plex Sans', 'IBM Plex Sans'],
        ];
        $p = $palettes[$b->designStyle] ?? $palettes['bersih_modern'];
        $keys = ['primary', 'secondary', 'accent', 'background', 'surface', 'text', 'text_muted', 'border', 'success', 'warning', 'danger'];
        $dark = array_combine($keys, ['#818CF8', '#38BDF8', '#FBBF24', '#0B1120', '#111827', '#E5E7EB', '#9CA3AF', '#1F2937', '#22C55E', '#F59E0B', '#F87171']);
        $reason = match ($b->designStyle) {
            'bebas' => "Gaya diputuskan: Bersih & Modern dengan aksen indigo, karena " . self::topik($b->ide) . ' dipakai orang awam di HP dan butuh tampilan yang jelas.',
            'tulis_sendiri' => "Mengikuti deskripsi gaya dari pengguna: {$b->designCustom}.",
            default => "Mengikuti gaya {$b->designLabel()} yang dipilih pengguna.",
        };
        return [
            'alasan' => $reason,
            'warna' => ['terang' => array_combine($keys, array_slice($p, 0, 11)), 'gelap' => $flags['hasDarkTheme'] ? $dark : []],
            'tipografi' => ['font_judul' => $p[11], 'font_isi' => $p[12], 'skala' => [12, 14, 16, 20, 24, 32], 'tinggi_baris' => '1.5 untuk isi, 1.25 untuk judul', 'sumber' => 'Google Fonts'],
            'ukuran' => ['ketuk_min' => 44, 'spasi' => [4, 8, 12, 16, 24, 32], 'radius' => '8 px untuk kontrol, 12 px untuk kartu', 'bayangan' => '0 1px 3px rgba(0,0,0,.08) untuk kartu', 'lebar_maks' => '1200 px', 'breakpoint' => ['640px', '1024px']],
            'komponen' => [
                ['nama' => 'Tombol primer', 'gaya' => 'Latar primary, teks putih; hover sedikit lebih gelap; fokus cincin 2 px warna accent; nonaktif opasitas 50%.'],
                ['nama' => 'Tombol sekunder', 'gaya' => 'Garis border, latar surface; hover latar border; fokus cincin 2 px; nonaktif opasitas 50%.'],
                ['nama' => 'Tombol bahaya', 'gaya' => 'Latar danger, teks putih; hover lebih gelap; fokus cincin 2 px; nonaktif opasitas 50%.'],
                ['nama' => 'Kartu', 'gaya' => 'Latar surface, radius 12 px, bayangan halus, padding 16 px.'],
                ['nama' => 'Input form', 'gaya' => 'Tinggi 44 px, border 1 px, label di atas, pesan error di bawah berwarna danger.'],
                ['nama' => 'Badge status', 'gaya' => 'Pil kecil: success untuk selesai, warning untuk menunggu, danger untuk dibatalkan.'],
                ['nama' => 'Tabel/daftar', 'gaya' => 'Baris berjarak 12 px; di HP berubah menjadi daftar kartu.'],
                ['nama' => 'Dialog', 'gaya' => 'Lebar maks 480 px, latar gelap transparan, fokus terkunci di dalam dialog.'],
                ['nama' => 'Toast', 'gaya' => 'Muncul di bawah layar 4 detik, warna sesuai jenis pesan.'],
            ],
            'nuansa' => ['sifat' => ['jelas', 'ramah', 'rapi'], 'hindari' => ['teks abu-abu pucat di atas putih', 'animasi berlebihan']],
            'keadaan' => ['loading' => 'Kerangka (skeleton) seukuran konten.', 'kosong' => 'Ilustrasi kecil + kalimat ajakan + tombol aksi utama.', 'error' => 'Pesan jelas + tombol Coba lagi.'],
            'aksesibilitas' => ['Fokus selalu terlihat (cincin 2 px).', 'Semua ikon punya label untuk pembaca layar.', 'Kontras teks minimal 4.5:1.'],
        ];
    }

    private static function routes(Brief $b, array $roles, array $flags = []): array
    {
        $routes = self::baseRoutes($b, $roles);
        $public = $roles[0];
        $admin = in_array('admin', $roles, true) ? 'admin' : null;
        $guestFr = sprintf('FR-%02d', count($b->features) + 1); // FR cek status tamu ditulis tepat setelah FR fitur
        // P3: cek data milik tamu dengan kode + nomor kontak (bukan satu pengenal).
        if (!empty($flags['hasGuestFlow']) && !$b->uiOnly) {
            $routes['rute'][] = ['path' => '/cek-status', 'halaman' => 'Cek status dengan kode transaksi + nomor kontak', 'peran' => [$public], 'layout' => 'Publik', 'sumber' => [$b->features[0]['nama'], $guestFr]];
            $routes['navigasi'][0]['item'][] = 'Cek status';
            $routes['penjaga_akses'][] = 'Rute /cek-status hanya menampilkan data bila kode transaksi dan nomor kontak cocok, dengan pembatasan laju percobaan; kontak ditampilkan tersamar.';
        }
        // Cakupan pengelola untuk entitas yang dibuat lewat alur publik, dan pencatatan manual kanal luring.
        if ($admin && !empty($flags['hasTransactionRecord'])) {
            $routes['rute'][] = ['path' => '/admin/transaksi', 'halaman' => 'Daftar transaksi (filter & ubah status)', 'peran' => [$admin], 'layout' => 'Admin', 'sumber' => [$b->features[0]['nama']]];
            $routes['rute'][] = ['path' => '/admin/transaksi/[id]', 'halaman' => 'Detail transaksi & riwayat status', 'peran' => [$admin], 'layout' => 'Admin', 'sumber' => [$b->features[0]['nama']]];
            if (!empty($flags['hasOfflineChannel'])) {
                $routes['rute'][] = ['path' => '/admin/transaksi/baru', 'halaman' => 'Pencatatan manual kanal luring (aturan ketersediaan sama)', 'peran' => [$admin], 'layout' => 'Admin', 'sumber' => [$b->features[0]['nama']]];
            }
            foreach ($routes['navigasi'] as &$nav) {
                if ($nav['peran'] === 'admin') {
                    $nav['item'][] = 'Transaksi';
                }
            }
            unset($nav);
        }
        if ($admin && (Triggers::angka($b) || !empty($flags['hasScheduler']))) {
            $routes['rute'][] = ['path' => '/admin/pengaturan', 'halaman' => 'Pengaturan (nilai bawaan & template pesan)', 'peran' => [$admin], 'layout' => 'Admin', 'sumber' => [$b->features[0]['nama']]];
        }
        return $routes;
    }

    private static function baseRoutes(Brief $b, array $roles): array
    {
        $public = $roles[0];
        $member = $roles[1] ?? $roles[0];
        $admin = in_array('admin', $roles, true) ? 'admin' : null;
        $rows = [['path' => '/', 'halaman' => 'Beranda', 'peran' => [$public, $member], 'layout' => 'Publik', 'sumber' => [$b->features[0]['nama']]]];
        $nav = [$public => [], $member => [], 'admin' => []];
        foreach ($b->features as $i => $f) {
            $slug = Documents::slug($f['nama']) ?: 'fitur-' . ($i + 1);
            if ($admin && preg_match('/admin|kelola|dasbor|dashboard|laporan|moderasi|pengaturan/iu', $f['nama'])) {
                [$path, $peran, $layout] = ["/admin/$slug", [$admin], 'Admin'];
                $nav['admin'][] = $f['nama'];
            } elseif (count($roles) > 1 && preg_match('/akun|profil|riwayat|saya/iu', $f['nama'])) {
                [$path, $peran, $layout] = ["/akun/$slug", [$member], 'Akun'];
                $nav[$member][] = $f['nama'];
            } else {
                [$path, $peran, $layout] = ["/$slug", array_values(array_unique([$public, $member])), 'Publik'];
                $nav[$public][] = $f['nama'];
            }
            $rows[] = ['path' => $path, 'halaman' => $f['nama'], 'peran' => $peran, 'layout' => $layout, 'sumber' => [$f['nama'], sprintf('FR-%02d', $i + 1)]];
        }
        if ($admin && !$nav['admin']) {
            $rows[] = ['path' => '/admin', 'halaman' => 'Dasbor admin', 'peran' => [$admin], 'layout' => 'Admin', 'sumber' => [$b->features[0]['nama']]];
            $nav['admin'][] = 'Dasbor';
        }
        $navigation = [['peran' => $public, 'komponen' => 'Header + footer', 'item' => array_merge(['Beranda'], $nav[$public])]];
        if ($member !== $public) {
            $navigation[] = ['peran' => $member, 'komponen' => 'Bottom navigation di HP, header di desktop', 'item' => array_merge(['Beranda'], $nav[$public], $nav[$member])];
        }
        if ($admin) {
            $navigation[] = ['peran' => 'admin', 'komponen' => 'Sidebar', 'item' => $nav['admin']];
        }
        $special = [['jenis' => '404', 'path' => '/404', 'halaman' => 'Halaman tidak ditemukan'], ['jenis' => '403', 'path' => '/403', 'halaman' => 'Tidak punya akses'], ['jenis' => 'error', 'path' => '/error', 'halaman' => 'Terjadi kesalahan']];
        if (count($roles) > 1) {
            $special[] = ['jenis' => 'masuk', 'path' => '/masuk', 'halaman' => 'Masuk akun'];
            $special[] = ['jenis' => 'keluar', 'path' => '/keluar', 'halaman' => 'Keluar lalu kembali ke beranda'];
        }
        return [
            'rute' => $rows,
            'navigasi' => $navigation,
            'rute_khusus' => $special,
            'penjaga_akses' => array_values(array_filter([
                count($roles) > 1 ? "Rute /akun/* wajib masuk sebagai $member." : null,
                $admin ? 'Rute /admin/* wajib masuk dengan peran admin; selain itu diarahkan ke /403.' : null,
                'Rute lain terbuka untuk pengunjung anonim.',
            ])),
        ];
    }

    private static function dummy(Brief $b, array $tables, array $flags): array
    {
        $priced = $flags['hasPricedEntity'];
        $items = [['Paket Hemat', 'Pilihan paling terjangkau', 15000], ['Paket Reguler', 'Pilihan favorit pelanggan', 25000], ['Paket Spesial', 'Porsi dan isi paling lengkap', 40000]];
        $sets = [
            ['DUMMY-PENGGUNA', 'pengguna', ['nama', 'email', 'telepon', 'peran'], [
                ['Rina Wulandari', 'rina@contoh.id', '081234567801', 'pelanggan'], ['Budi Santoso', 'budi@contoh.id', '081234567802', 'pelanggan'], ['Sari Admin', 'sari@contoh.id', '081234567803', 'admin'],
            ]],
            ['DUMMY-ITEM', 'item', array_merge(['nama', 'deskripsi'], $priced ? ['harga_rp'] : [], ['aktif']),
                array_map(fn ($i) => array_merge([$i[0], $i[1]], $priced ? [$i[2]] : [], [true]), $items)],
        ];
        if (isset($tables['transaksi']) || ($b->uiOnly && $flags['hasTransactionRecord'])) {
            $cols = array_merge($flags['hasGuestFlow'] ? ['nama_tamu'] : [], ['nama_item_snapshot'], $priced ? ['harga_satuan_snapshot'] : [], ['jumlah'], $priced ? ['total_rp'] : [], ['status']);
            $rows = [];
            foreach ([[0, 2, 'menunggu', 'Andi'], [1, 1, 'diproses', ''], [2, 3, 'selesai', 'Dewi']] as [$i, $qty, $status, $guest]) {
                $rows[] = array_merge($flags['hasGuestFlow'] ? [$guest] : [], [$items[$i][0]], $priced ? [$items[$i][2]] : [], [$qty], $priced ? [$items[$i][2] * $qty] : [], [$status]);
            }
            $sets[] = ['DUMMY-TRANSAKSI', 'transaksi', $cols, $rows];
        }
        if (isset($tables['pengaturan'])) {
            $cols = array_values(array_map(fn ($c) => $c[0], array_filter($tables['pengaturan'][2], fn ($c) => isset($c[4]))));
            $sets[] = ['DUMMY-PENGATURAN', 'pengaturan', $cols, [array_values(array_map(fn ($c) => $c[4], array_filter($tables['pengaturan'][2], fn ($c) => isset($c[4]))))]];
        }
        $total = $priced ? ' Total Rp30.000.' : '';
        return [
            'dataset' => array_map(fn ($s) => ['id' => $s[0], 'tabel' => $s[1], 'kolom' => $s[2], 'baris' => $s[3]], $sets),
            'template' => [[
                'nama' => 'Konfirmasi via WhatsApp',
                'isi' => 'Halo {nama_pelanggan}, transaksi {nama_item} sebanyak {jumlah} sudah kami terima.' . ($priced ? ' Total {total}.' : ''),
                'contoh' => "Halo Bu Rina, transaksi Paket Hemat sebanyak 2 sudah kami terima.$total",
            ]],
            'copywriting' => [
                'nada' => 'Santai, sopan, dan langsung ke inti.',
                'sapaan' => '"Kak" untuk pengguna umum; nama depan bila sudah masuk.',
                'istilah' => ['Item: barang atau layanan yang dijual', 'Transaksi: pesanan yang dibuat pengguna'],
                'microcopy' => ['judul_utama' => "{$b->judul}: semua kebutuhanmu, lebih cepat tanpa antre chat.", 'cta' => 'Mulai Sekarang', 'kosong' => 'Belum ada data. Yuk mulai dari tombol di bawah.', 'error' => 'Maaf, ada gangguan. Coba lagi sebentar ya.',
                    'penuh' => 'Maaf, pilihan ini baru saja diambil orang lain. Silakan pilih yang lain.', 'sukses' => 'Berhasil disimpan.', 'konfirmasi' => 'Yakin ingin melanjutkan? Tindakan ini tidak bisa dibatalkan.'],
            ],
        ];
    }

    private static function security(Brief $b, array $ctx, array $tables, array $roles): array
    {
        $std = QualityReport::standard();
        $names = array_column($b->features, 'nama');
        $triggers = $ctx['security'];
        $byGroup = [];
        foreach ($ctx['controls']['mvp'] as $code => [$group, $text, $fitur]) {
            $byGroup[$group]['kontrol'][] = $code;
            $byGroup[$group]['teks'][] = $text;
            $byGroup[$group]['fitur'] = array_values(array_unique(array_merge($byGroup[$group]['fitur'] ?? [], $fitur)));
        }
        $sec = [];
        $ids = [];
        foreach ($byGroup as $group => $g) {
            $id = sprintf('SEC-%02d', count($sec) + 1);
            $sec[] = ['id' => $id, 'kelompok' => $group, 'teks' => mb_substr(implode(' ', $g['teks']), 0, 495), 'kontrol' => $g['kontrol'], 'fitur' => $g['fitur'] ?: $names];
            foreach ($g['kontrol'] as $code) {
                $ids[$code] = $id;
            }
        }

        if (!empty($ctx['flags']['hasGuestFlow'])) {
            // P3: bukti kepemilikan untuk data tamu + kontak tersamar.
            $guestText = 'Data milik tamu hanya ditampilkan bila kode transaksi rahasia dan nomor kontak cocok, dengan pembatasan laju percobaan; kontak pelanggan ditampilkan tersamar (contoh 0812-xxxx-1234) di mana pun selain panel pengelola.';
            if (count($sec) < 12) {
                $sec[] = ['id' => sprintf('SEC-%02d', count($sec) + 1), 'kelompok' => 'Otorisasi', 'teks' => $guestText, 'kontrol' => [], 'fitur' => [$names[0]]];
            } else {
                $sec[0]['teks'] = mb_substr($sec[0]['teks'] . ' ' . $guestText, 0, 495);
            }
        }

        $klasifikasi = [['data' => 'Nama, email, dan telepon pengguna', 'sensitivitas' => 'pribadi', 'lokasi' => 'pengguna.email, pengguna.telepon', 'akses' => 'Pemilik akun dan admin', 'retensi' => 'Selama akun aktif (usulan)', 'boleh_masuk_log' => false]];
        if (isset($tables['transaksi'])) {
            $klasifikasi[] = ['data' => 'Riwayat transaksi dan kontak', 'sensitivitas' => 'pribadi', 'lokasi' => 'transaksi.status' . (in_array('kontak_tamu', array_column($tables['transaksi'][2], 0), true) ? ', transaksi.kontak_tamu' : ''), 'akses' => 'Pemilik transaksi dan admin', 'retensi' => '1 tahun (usulan)', 'boleh_masuk_log' => false];
        }
        $sensitive = ['akun' => ['Kata sandi (hash) dan token sesi', 'layanan autentikasi'], 'anak' => ['Data anak (nama dan kelas)', 'pengguna.data_anak'],
            'kesehatan' => ['Data kesehatan/alergi', 'pengguna.catatan_alergi'], 'bayar' => ['Data pembayaran (status dan referensi transaksi)', 'transaksi.status']];
        foreach ($sensitive as $trigger => [$label, $where]) {
            if (isset($triggers[$trigger])) {
                $klasifikasi[] = ['data' => $label, 'sensitivitas' => 'sensitif', 'lokasi' => $where, 'akses' => 'Hanya pemilik data dan peran yang membutuhkannya', 'retensi' => 'Sesingkat mungkin sesuai kebutuhan layanan (usulan)', 'boleh_masuk_log' => false];
            }
        }
        $owasp = [];
        foreach ($std['owasp']['kategori'] as $cat) {
            $refs = array_values(array_unique(array_filter(array_map(fn ($code) => $ids[$code] ?? null, $cat['kontrol']))));
            $owasp[] = ['kode' => $cat['kode'], 'nama' => $cat['nama'], 'sec' => $refs, 'alasan' => $refs ? '' : 'Tidak relevan untuk MVP ini; dibahas di hardening.'];
        }
        $access = [['peran' => 'pengunjung anonim', 'sumber_daya' => 'item yang aktif', 'aksi' => ['baca']]];
        foreach (array_slice($roles, 1) as $role) {
            $access[] = $role === 'admin'
                ? ['peran' => 'admin', 'sumber_daya' => 'item, transaksi, pengguna', 'aksi' => ['baca', 'tulis', 'hapus', 'setujui']]
                : ['peran' => $role, 'sumber_daya' => 'pengguna dan transaksi milik sendiri', 'aksi' => ['baca', 'tulis']];
        }
        return [
            'klasifikasi_data' => $klasifikasi,
            'prinsip_akses' => 'Setiap peran hanya mendapat akses minimum yang dibutuhkan, dan setiap permintaan diperiksa di server.',
            'hak_akses' => $access,
            'persyaratan' => $sec,
            'hardening' => array_slice(array_values(array_map(fn ($c) => $c[1], $ctx['controls']['hardening'])), 0, 8),
            'owasp' => ['edisi' => $std['owasp']['edisi'], 'kategori' => $owasp],
            'risiko_tersisa' => [
                'Kepatuhan terhadap UU Pelindungan Data Pribadi (UU No. 27/2022) perlu ditinjau ahli hukum.',
                'Uji penetrasi belum dilakukan; jadwalkan sebelum rilis.',
            ],
        ];
    }

    private static function services(array $stack, array $flags, array $roles): array
    {
        $rows = [];
        $groups = [['grup' => 'Aplikasi', 'komentar' => 'Pengaturan umum aplikasi', 'variabel' => [['nama' => 'APP_URL', 'nilai' => 'isi_dengan_url_aplikasi', 'komentar' => 'Alamat aplikasi, mis. http://localhost:3000']]]];
        if (count($roles) > 1) {
            $groups[0]['variabel'][] = ['nama' => 'AUTH_SECRET', 'nilai' => 'isi_dengan_rahasia_acak_panjang', 'komentar' => 'Rahasia penanda sesi; buat acak dan jangan di-commit'];
        }
        $db = $stack['id'] === 'full_supabase'
            ? [['nama' => 'SUPABASE_URL', 'nilai' => 'isi_dengan_url_proyek_supabase', 'komentar' => 'URL proyek Supabase'], ['nama' => 'SUPABASE_ANON_KEY', 'nilai' => 'isi_dengan_anon_key', 'komentar' => 'Kunci publik untuk klien'], ['nama' => 'SUPABASE_SERVICE_ROLE_KEY', 'nilai' => 'isi_dengan_service_role_key', 'komentar' => 'Hanya di server']]
            : ($stack['db'] === 'd1' ? [['nama' => 'D1_DATABASE_ID', 'nilai' => 'isi_dengan_id_database_d1', 'komentar' => 'ID database D1 di wrangler.toml']] : [['nama' => 'DATABASE_URL', 'nilai' => 'isi_dengan_url_database', 'komentar' => 'String koneksi database']]);
        $groups[] = ['grup' => 'Database', 'komentar' => 'Koneksi database', 'variabel' => $db];
        foreach (Triggers::services($flags) as $code) {
            $v = $stack['vendor'][$code];
            $rows[] = ['kebutuhan' => $code, 'vendor' => $v['nama'], 'mock' => true, 'alasan' => 'Vendor default stack; selama M1–M2 dipakai adapter tiruan.', 'biaya' => $v['biaya'], 'env' => $v['env'], 'mode_mvp' => 'Adapter tiruan (mock) di balik antarmuka yang sama; vendor asli dipasang di M2'];
            if ($v['env']) {
                $known = array_merge(...array_map(fn ($g) => array_column($g['variabel'], 'nama'), $groups));
                $vars = array_values(array_filter(array_map(fn ($name) => ['nama' => $name, 'nilai' => 'isi_dengan_' . strtolower($name), 'komentar' => "Untuk {$v['nama']}"], $v['env']), fn ($x) => !in_array($x['nama'], $known, true)));
                if ($vars) {
                    $groups[] = ['grup' => $v['nama'], 'komentar' => "Layanan $code", 'variabel' => $vars];
                }
            }
        }
        $rate = $flags['usesRateLimit']
            ? ($flags['serverless']
                ? "Pembatasan laju memakai {$stack['vendor']['rate_limit']['nama']} sebagai penyimpanan terpusat, bukan memori proses, karena setiap permintaan serverless bisa berjalan di instans berbeda."
                : "Pembatasan laju memakai {$stack['vendor']['rate_limit']['nama']}.")
            : '';
        return ['layanan' => $rows, 'rate_limit' => $rate, 'env_example' => $groups];
    }

    /** Bab 13: milestone, Rencana Pengerjaan Terkoordinasi (P4), aturan checkpoint, Master Prompt bergerbang izin (P10). */
    private static function milestones(Brief $b, array $stack, array $flags = [], array $prd = []): array
    {
        $names = array_column($b->features, 'nama');
        $cp = 'CHECKPOINT: berhenti, perbarui PROGRESS.md, ringkas hasil, tunggu persetujuan, dan sarankan memulai percakapan baru bila konteks sudah panjang.';
        $list = [
            ['id' => 'M0', 'nama' => 'Persiapan', 'tujuan' => 'Struktur proyek, dependensi, konfigurasi, CLAUDE.md, PROGRESS.md, dan variabel lingkungan siap.', 'selesai_bila' => ['Perintah jalan berhasil membuka halaman kosong.', 'CLAUDE.md dan PROGRESS.md ada di root proyek.'], 'cakupan' => ['Bab 7', 'Bab 12'], 'checkpoint' => $cp],
            ['id' => 'M1', 'nama' => 'Prototipe UI (data tiruan)', 'tujuan' => 'Semua halaman dan komponen memakai dataset bab 11 sesuai design system dan peta rute.', 'selesai_bila' => ['Semua rute di bab 10 bisa dibuka.', 'Semua data tampil dari dataset DUMMY-* di bab 11.'], 'cakupan' => $names, 'checkpoint' => $cp],
        ];
        if (!$b->uiOnly) {
            array_push($list,
                ['id' => 'M2', 'nama' => 'Backend & Data', 'tujuan' => 'Skema/migrasi, logika server, dan adapter layanan menggantikan data tiruan.', 'selesai_bila' => ['Migrasi dari bab 6 berjalan tanpa galat.', 'Semua halaman membaca data dari database.'], 'cakupan' => $names, 'checkpoint' => $cp],
                ['id' => 'M3', 'nama' => 'Keamanan & Uji', 'tujuan' => 'Aturan SEC Wajib MVP diterapkan dan diuji.', 'selesai_bila' => ['Setiap SEC di bab 8 punya uji yang lulus.', 'Uji alur utama tiap fitur lulus.'], 'cakupan' => $names, 'checkpoint' => $cp],
                ['id' => 'M4', 'nama' => 'Rilis', 'tujuan' => 'Konfigurasi produksi dan daftar periksa rilis selesai.', 'selesai_bila' => ['Aplikasi berjalan di lingkungan produksi dengan HTTPS.', 'Daftar periksa rilis tercentang semua.'], 'cakupan' => ['Bab 12', 'Bab 13'], 'checkpoint' => $cp],
            );
        }
        $rule = [
            'task' => 'Cara kerja Bertahap per Task: checkpoint setelah setiap tugas selesai; tunggu persetujuan sebelum tugas berikutnya.',
            'fase' => 'Cara kerja Bertahap per Fase: checkpoint di akhir setiap milestone; tunggu persetujuan sebelum milestone berikutnya.',
            'sekaligus' => 'Cara kerja Sekaligus Semua: checkpoint di akhir M1 dan di akhir proyek; berhenti dan laporkan jika ada uji yang gagal.',
        ][$b->aiWorkflow];
        $gate = [
            'task' => 'Kerjakan SATU tugas terkoordinasi per giliran. Tugas berikutnya hanya dimulai setelah saya memberi izin.',
            'fase' => 'Kerjakan satu milestone penuh per giliran. Milestone berikutnya hanya dimulai setelah saya memberi izin.',
            'sekaligus' => 'Kamu boleh lanjut antar-tugas tanpa menunggu, tetapi berhenti dan minta izin di akhir M1 dan sebelum M4 Rilis; bila ada uji yang gagal, berhenti dan laporkan.',
        ][$b->aiWorkflow];
        $folder = $b->uiOnly ? "src/\n  pages/\n  components/\n  data/dummy/\ntests/" : "src/\n  app/ (halaman & rute)\n  components/\n  lib/ (adapter layanan, validasi)\n  db/ (skema & migrasi)\ndata/dummy/\ntests/";
        $ids = array_column($list, 'id');
        $master = "Halo! Kamu berperan sebagai senior fullstack developer yang membangun \"{$b->judul}\": " . self::topik($b->ide) . ".\n\n"
            . "Baca dulu berkas berikut: CLAUDE.md, PRD.md, semua berkas di folder features/, dan PROGRESS.md.\n\n"
            . "TECH STACK TERKUNCI (jangan diganti): {$stack['ringkas']}" . ($b->uiOnly ? ' (hanya UI, tanpa backend).' : '.') . "\n\n"
            . "ATURAN KERJA:\n"
            . "1. Sebelum menulis kode apa pun, berikan ringkasan pemahamanmu maksimal 10 poin, sebutkan rencana berkas untuk milestone pertama ({$ids[0]} lalu {$ids[1]}), lalu tanyakan apakah saya siap kamu mulai.\n"
            . "2. $gate\n"
            . "3. Ikuti Rencana Pengerjaan Terkoordinasi di bab 13 (Task M<n>.<k>) dan $rule\n"
            . "4. Patuhi skema database (bab 6), peta rute (bab 10), hak akses (bab 8), aturan bisnis dan invarian (bab 2 dan bab 6), serta design system (bab 9).\n"
            . "5. Jangan mengarang data di luar bab 11 (Data Dummy & Copywriting).\n"
            . "6. Jalankan uji setelah setiap perubahan, perbarui PROGRESS.md, dan berhenti di setiap CHECKPOINT.\n"
            . "7. Tanya bila PRD ambigu. Rahasia hanya di variabel lingkungan; jangan menulis kunci asli ke kode.";
        return [
            'daftar' => $list,
            'rencana' => self::workPlan($b, $stack, $flags, $prd),
            'aturan_checkpoint' => $rule,
            'master_prompt' => $master,
            'claude_md' => [
                'struktur_folder' => $folder,
                'konvensi' => ['Nama berkas dan variabel dalam bahasa Inggris, teks UI dalam Bahasa Indonesia.', 'Satu komponen per berkas.', 'Data tiruan hanya dari data/dummy (bab 11).'],
                'perintah' => $stack['perintah'],
            ],
        ];
    }

    /**
     * Rencana Pengerjaan Terkoordinasi (P4): tugas gemuk "M<n>.<k>" berisi berkas, perilaku, dataset/SEC, FR, tabel,
     * dan Selesai bila. Maksimal 10 per milestone dan 30 total.
     */
    private static function workPlan(Brief $b, array $stack, array $flags, array $prd): array
    {
        $s = $stack['struktur'];
        $page = fn (string $path) => str_replace('{rute}', trim(preg_replace('/\[(\w+)\]/', '[$1]', $path), '/') ?: 'beranda', $s['halaman']);
        $module = fn (string $kind, string $name) => str_replace('{modul}', $name, $s[$kind]);
        $datasets = array_column($prd['dummy']['dataset'] ?? [], 'id');
        $tables = array_column($prd['database']['tabel'] ?? [], 'nama');
        $secIds = array_column($prd['keamanan']['persyaratan'] ?? [], 'id');
        $frByFeature = [];
        foreach ($prd['requirements']['fungsional'] ?? [] as $fr) {
            foreach ($fr['fitur'] as $name) {
                $frByFeature[$name][] = $fr['id'];
            }
        }
        $routesByFeature = [];
        foreach ($prd['rute']['rute'] ?? [] as $r) {
            foreach ($r['sumber'] as $src) {
                $routesByFeature[$src][] = $r['path'];
            }
        }
        $groups = array_chunk($b->features, (int) max(1, ceil(count($b->features) / 5)));
        $plan = [];
        $add = function (string $m, string $judul, array $berkas, string $perilaku, string $selesai, array $extra = []) use (&$plan) {
            $k = count(array_filter($plan, fn ($t) => str_starts_with($t['id'], "$m."))) + 1;
            $plan[] = ['id' => "$m.$k", 'judul' => $judul, 'berkas' => $berkas, 'perilaku' => $perilaku, 'dataset' => $extra['dataset'] ?? [],
                'sec' => $extra['sec'] ?? [], 'fr' => $extra['fr'] ?? [], 'tabel' => $extra['tabel'] ?? [], 'selesai_bila' => $selesai];
        };

        $add('M0', 'Siapkan proyek, dependensi, dan konfigurasi', ['package.json', '.env.example', 'CLAUDE.md', 'PROGRESS.md'],
            'Inisialisasi proyek ' . $stack['ringkas'] . ' dengan versi di bab 7, salin .env.example, isi CLAUDE.md dan PROGRESS.md.', 'Perintah ' . $stack['perintah']['jalan'] . ' membuka halaman kosong tanpa galat.');
        $add('M1', 'Bangun layout, navigasi, dan token design system', [$s['layout'], $s['token']],
            'Layout per peran sesuai bab 10 dan token warna/tipografi bab 9 sebagai CSS variable.', 'Semua layout di bab 10 tampil dengan token bab 9 di lebar 360 px dan 1280 px.', ['dataset' => array_slice($datasets, 0, 1)]);
        foreach ($groups as $g) {
            $paths = array_values(array_unique(array_merge(...array_map(fn ($f) => $routesByFeature[$f['nama']] ?? [], $g))));
            $add('M1', 'Bangun halaman ' . implode(', ', array_column($g, 'nama')) . ' dengan data tiruan', array_map($page, $paths ?: ['/']),
                'Halaman ' . implode(', ', $paths ?: ['/']) . ' lengkap dengan keadaan kosong, memuat, dan gagal.', 'Semua halaman kelompok ini tampil memakai dataset ' . implode(', ', $datasets) . ' tanpa backend.',
                ['dataset' => $datasets, 'fr' => array_merge(...array_map(fn ($f) => $frByFeature[$f['nama']] ?? [], $g))]);
        }
        $add('M1', 'Buat komponen bersama dan keadaan antarmuka', [$module('lib', 'ui')],
            'Komponen tombol, kartu, badge status, dialog, toast, dan keadaan kosong sesuai bab 9.', 'Komponen dipakai ulang di semua halaman M1 dan lolos cek kontras 4.5:1.', ['dataset' => $datasets]);
        if (!$b->uiOnly) {
            $add('M2', 'Buat skema, migrasi, dan benih data', [$s['skema'], $s['seed'], $s['migrasi_kustom']],
                'Tabel ' . implode(', ', $tables) . ' sesuai bab 6, migrasi kustom invarian, dan berkas benih data dari dataset bab 11.', 'Migrasi berjalan bersih dan benih data mengisi semua tabel dari ' . implode(', ', $datasets) . '.', ['tabel' => $tables, 'dataset' => $datasets]);
            if (!empty($prd['database']['invarian'])) {
                $add('M2', 'Terapkan invarian dan transaksi penyimpanan', [$module('lib', 'invarian'), $s['migrasi_kustom']],
                    'Fungsi simpan atomik untuk ' . implode(', ', array_column($prd['database']['invarian'], 'id')) . ' sesuai mekanisme bab 6.', 'Penyimpanan yang melanggar invarian ditolak database dengan pesan ramah.', ['tabel' => array_values(array_intersect(['transaksi', 'item'], $tables))]);
            }
            if (count($prd['profil']['peran'] ?? []) > 1 || count(array_filter($prd['keamanan']['hak_akses'] ?? [], fn ($r) => !Triggers::isAnonymous($r['peran']))) > 0) {
                $add('M2', 'Pasang autentikasi dan penjaga akses per peran', [$module('lib', 'auth'), $module('aksi', 'masuk')],
                    ($stack['kedalaman']['auth'] ?? 'Autentikasi') . '; penjaga rute sesuai bab 10.', 'Rute terlindungi menolak pengguna tanpa peran yang benar.', ['tabel' => array_values(array_intersect(['pengguna'], $tables))]);
            }
            foreach ($groups as $g) {
                $slug = Documents::slug($g[0]['nama']) ?: 'fitur';
                $add('M2', 'Hubungkan ' . implode(', ', array_column($g, 'nama')) . ' ke database', [$module('aksi', $slug)],
                    'Aksi server dengan validasi ' . ($stack['kedalaman']['validasi'] ?? 'input') . ' menggantikan data tiruan.', 'Data kelompok ini dibaca dan disimpan ke database, dataset tiruan tidak dipakai lagi.',
                    ['tabel' => array_values(array_diff($tables, ['log_audit'])), 'fr' => array_merge(...array_map(fn ($f) => $frByFeature[$f['nama']] ?? [], $g))]);
            }
            if (Triggers::services($flags) || !empty($flags['hasScheduler'])) {
                $add('M2', 'Buat adapter layanan dan penjadwal', array_values(array_filter([$module('lib', 'layanan'), $prd['kedalaman']['penjadwal']['berkas'] ?? null])),
                    'Antarmuka adapter dengan implementasi tiruan untuk layanan bab 12' . (!empty($flags['hasScheduler']) ? ', serta penjadwal di ' . $prd['kedalaman']['penjadwal']['endpoint'] : '') . '.', 'Adapter tiruan dipakai di lokal dan dapat diganti vendor asli lewat variabel lingkungan.', ['tabel' => array_values(array_intersect(['pengaturan'], $tables))]);
            }
            $add('M3', 'Terapkan persyaratan keamanan Wajib MVP', [$module('lib', 'keamanan')],
                'Kontrol ' . implode(', ', $secIds) . ' di server.', 'Setiap SEC punya uji otomatis yang lulus.', ['sec' => $secIds]);
            if (!empty($prd['database']['invarian'])) {
                $add('M3', 'Uji konkurensi invarian', ['tests/invarian.test.ts'],
                    'Skenario dua permintaan bersamaan untuk ' . implode(', ', array_column($prd['database']['invarian'], 'id')) . '.', 'Dari dua permintaan bersamaan hanya satu yang berhasil, untuk setiap invarian.', ['sec' => array_slice($secIds, 0, 1)]);
            }
            $add('M3', 'Uji alur utama end-to-end', ['tests/e2e/alur-utama.spec.ts'],
                'Uji alur pengguna di bab 4 dan penjaga akses bab 10.', 'Semua alur utama lulus di lebar 360 px dan 1280 px.', ['sec' => $secIds]);
            if (!empty($flags['hasPublicPages'])) {
                $add('M3', 'Terapkan SEO dan performa halaman publik', [$s['layout']], 'Butir SEO & performa di bab 2.', 'Halaman publik punya meta, Open Graph, sitemap, dan noindex untuk halaman privat.');
            }
            $add('M4', 'Konfigurasi produksi dan penjadwal', array_values(array_filter(['.env.example', $prd['kedalaman']['penjadwal']['berkas'] ?? null])),
                'Variabel produksi, migrasi produksi, dan penjadwal sesuai bab 7.', 'Build produksi lulus dan penjadwal berjalan di lingkungan produksi.');
            $add('M4', 'Jalankan daftar periksa rilis', ['PROGRESS.md'], 'HTTPS, header keamanan, cadangan database, dan uji asap.', 'Semua butir daftar periksa rilis tercentang.');
        }
        return $plan;
    }

    /**
     * Hasil Bikin Task tiruan bertanda milestone (bentuk sama dengan hasil AI).
     * @param array $refs dataset (ID DUMMY-*), sec_fitur (SEC => nama fitur), ui_only
     */
    public static function tasks(array $chunk, array $refs = []): array
    {
        $dataset = in_array('DUMMY-ITEM', $refs['dataset'] ?? [], true) ? 'DUMMY-ITEM' : ($refs['dataset'][0] ?? 'DUMMY-ITEM');
        $plan = $refs['rencana'] ?? [];
        // Tugas terkoordinasi yang menaungi fitur: yang judulnya menyebut fitur, lalu tugas pertama milestone itu.
        $coord = function (string $m, string $feature, string $hint = '') use ($plan): ?string {
            $inMilestone = array_values(array_filter($plan, fn ($r) => str_starts_with($r['id'], "$m.")));
            foreach ($inMilestone as $r) {
                if (str_contains($r['judul'], $feature) || ($hint !== '' && stripos($r['judul'], $hint) !== false)) {
                    return $r['id'];
                }
            }
            return $inMilestone[0]['id'] ?? null;
        };
        return ['fitur' => array_map(function ($f) use ($dataset, $refs, $coord) {
            $n = $f['nama'];
            $tasks = [['judul' => "Buat halaman $n dengan data tiruan $dataset", 'milestone' => 'M1', 'koordinasi' => $coord('M1', $n), 'sub_fitur' => '', 'prioritas' => 'utama']];
            // P8: maksimal 8 task per fitur; sub fitur lebih dari 3 digabung ke task halaman.
            foreach (array_slice($f['subs'], 0, 3) as $s) {
                $tasks[] = ['judul' => "Buat tampilan {$s['nama']}", 'milestone' => 'M1', 'koordinasi' => $coord('M1', $n), 'sub_fitur' => $s['nama'], 'prioritas' => 'utama'];
            }
            $tasks[] = ['judul' => "Tampilkan keadaan kosong, memuat, dan gagal di $n", 'milestone' => 'M1', 'koordinasi' => $coord('M1', $n, 'komponen bersama'), 'sub_fitur' => '', 'prioritas' => 'penting'];
            if (empty($refs['ui_only'])) {
                $secs = array_keys(array_filter($refs['sec_fitur'] ?? [], fn ($names) => in_array($n, $names, true)));
                $tasks[] = ['judul' => "Simpan data $n ke database dan ganti data tiruan", 'milestone' => 'M2', 'koordinasi' => $coord('M2', $n), 'sub_fitur' => '', 'prioritas' => 'utama'];
                $tasks[] = ['judul' => "Terapkan " . (implode(' dan ', array_slice($secs, 0, 2)) ?: 'aturan keamanan') . " pada $n", 'milestone' => 'M3', 'koordinasi' => $coord('M3', $n, 'keamanan'), 'sub_fitur' => '', 'prioritas' => 'utama'];
                $tasks[] = ['judul' => "Tulis uji alur utama $n", 'milestone' => 'M3', 'koordinasi' => $coord('M3', $n, 'alur utama'), 'sub_fitur' => '', 'prioritas' => 'penting'];
            } else {
                $tasks[] = ['judul' => "Rapikan tampilan responsif $n", 'milestone' => 'M1', 'koordinasi' => $coord('M1', $n), 'sub_fitur' => '', 'prioritas' => 'penting'];
            }
            return [
                'nama' => $n,
                'deskripsi' => "Membantu pengguna lewat $n.",
                'tujuan' => "Pengguna bisa memakai $n dengan cepat dan tanpa bingung.",
                'selesai_bila' => ["$n bisa dibuka dari menu utama.", 'Data tampil sesuai sumber datanya.', 'Keadaan kosong, memuat, dan gagal punya pesan yang jelas.'],
                'sub_fitur' => array_map(fn ($s) => [
                    'nama' => $s['nama'],
                    'tujuan' => "Menyediakan {$s['nama']} yang mudah dipakai.",
                    'selesai_bila' => ["{$s['nama']} berfungsi di ponsel dan desktop.", 'Input divalidasi.'],
                ], $f['subs']),
                'tasks' => array_map(fn ($t) => $t + ['fase' => $f['fase']], $tasks),
            ];
        }, $chunk)];
    }

    /** Ambil potongan pendek ide sebagai topik, mis. "website catering". */
    public static function topik(string $ide): string
    {
        $ide = preg_replace('/^(saya\s+)?(ingin|mau|pengen|buat|bikin|membuat)\s+/iu', '', trim($ide));
        $ide = preg_replace('/^(buat|bikin|membuat)\s+/iu', '', $ide);
        $words = array_slice(preg_split('/\s+/u', $ide), 0, 4);
        while (count($words) > 1 && preg_match('/^(untuk|yang|dan|di|dengan|ke|dari|buat|agar)$/iu', end($words))) {
            array_pop($words);
        }
        return mb_strtolower(implode(' ', $words));
    }

    /** Jawaban Workspace Agent di mode demo: merangkum progres dari database, tanpa AI. */
    public static function agentReply(string $question, array $features, ?array $selected): string
    {
        $total = array_sum(array_map(fn ($f) => $f['task']['total'], $features));
        $done = array_sum(array_map(fn ($f) => $f['task']['selesai'], $features));
        $next = null;
        foreach ($features as $f) {
            if ($f['task']['selesai'] < $f['task']['total']) {
                $next = $f;
                break;
            }
        }
        $q = mb_strimwidth($question, 0, 80, '…');
        $text = "Ini jawaban **mode demo**, jadi aku belum benar-benar membaca PRD. Pertanyaanmu: “{$q}”.\n\n"
            . "Ringkasan project sekarang:\n\n"
            . '- **' . count($features) . " fitur** dengan **$done dari $total task** selesai.\n";
        if ($selected) {
            $selesai = count(array_filter($selected['tasks'], fn ($t) => $t['status'] === 'selesai'));
            $text .= "- Fitur yang kamu buka: **{$selected['nama']}**, $selesai/" . count($selected['tasks']) . " task selesai.\n";
        }
        if ($next) {
            $text .= "- Saran berikutnya: lanjutkan **{$next['nama']}** (Fase {$next['fase']}).\n";
        }
        return $text . "\nNyalakan AI sungguhan supaya aku bisa menjawab berdasarkan isi PRD.";
    }
}
