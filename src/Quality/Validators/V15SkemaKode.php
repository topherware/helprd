<?php

/**
 * V15 SkemaKode (bab 6, kritis): blok kode skema ada; tabel kode = narasi = ER; uang bukan float;
 * snapshot, log_audit, dan transaksi tamu bila benderanya aktif; kebijakan akses bila stack mendukung RLS.
 */
final class V15SkemaKode
{
    public const TRANSAKSI = '/pesan|order|reservasi|pinjam|sewa|tagihan|transaksi|invoice|checkout/iu';
    public const UANG = '/harga|total|biaya|tarif|subtotal|ongkir|nominal|diskon|price|amount|_rp$|saldo|bayar/iu';

    public static function check(array $c): array
    {
        $k = fn ($pesan, $saran = '') => QV::finding('V15', 'K', 6, $pesan, $saran);
        $db = $c['prd']['database'] ?? [];
        $flags = $c['flags'];
        $code = (string) ($db['kode_skema'] ?? '');
        if ($code === '') {
            return [$k('Blok kode skema database belum ada.', 'Tulis skema siap pakai (' . Documents::schemaPath($c['stack']['bahasa_skema']) . ') yang sama dengan daftar tabel.')];
        }
        $out = [];
        $blocks = QV::schemaBlocks($code);
        $lang = QV::schemaLanguage($code) ?: $c['stack']['bahasa_skema'];
        $narr = [];
        foreach ($db['tabel'] ?? [] as $t) {
            $narr[QV::lower($t['nama'])] = $t;
        }
        $onlyCode = array_diff(array_keys($blocks), array_keys($narr));
        $onlyNarr = array_diff(array_keys($narr), array_keys($blocks));
        if ($onlyCode || $onlyNarr) {
            $out[] = $k('Himpunan tabel di kode skema berbeda dengan narasi:'
                . ($onlyCode ? ' hanya di kode: ' . implode(', ', $onlyCode) . '.' : '') . ($onlyNarr ? ' hanya di narasi: ' . implode(', ', $onlyNarr) . '.' : ''),
                'Samakan nama tabel di kode, deskripsi, dan diagram ER.');
        }
        $er = array_unique(array_map([QV::class, 'lower'], array_merge(array_column($db['relasi'] ?? [], 'dari'), array_column($db['relasi'] ?? [], 'ke'))));
        $erOnly = array_diff($er, array_keys($narr));
        if ($erOnly) {
            $out[] = $k('Diagram ER memuat tabel yang tidak ada di narasi: ' . implode(', ', $erOnly) . '.');
        }

        // Uang bukan float, baik di narasi maupun di kode.
        $floats = [];
        foreach ($narr as $name => $t) {
            foreach ($t['kolom'] as $col) {
                if (preg_match(self::UANG, $col['nama']) && preg_match('/float|double|real\b/i', $col['tipe'])) {
                    $floats[] = "$name.{$col['nama']}";
                }
            }
            if (isset($blocks[$name]) && preg_match_all('/^\s*[`"]?(\w+)[`"]?\s*[:\s]\s*(.*)$/m', $blocks[$name], $m, PREG_SET_ORDER)) {
                foreach ($m as [, $colName, $rest]) {
                    if (preg_match(self::UANG, $colName) && preg_match('/\b(float|double|real|doublePrecision|Float)\b/', $rest)) {
                        $floats[] = "$name.$colName";
                    }
                }
            }
        }
        if ($floats) {
            $out[] = $k('Kolom uang memakai tipe float/double/real: ' . implode(', ', array_unique($floats)) . '.', 'Simpan sebagai bilangan bulat rupiah atau numeric.');
        }

        $columns = fn ($t) => array_map([QV::class, 'lower'], array_column($t['kolom'], 'nama'));
        $txTables = array_filter($narr, fn ($t, $name) => preg_match(self::TRANSAKSI, $name), ARRAY_FILTER_USE_BOTH);
        $rules = $db['aturan_pemicu'] ?? [];

        if ($flags['hasPricedEntity'] && $flags['hasTransactionRecord']) {
            $snap = false;
            foreach ($txTables ?: $narr as $t) {
                $snap = $snap || (bool) preg_grep('/snapshot/', $columns($t));
            }
            if (!$snap || ($rules['snapshot'] ?? '') === '') {
                $out[] = $k('Snapshot nilai transaksi belum ada: tabel transaksi harus menyimpan salinan harga/nama saat transaksi.', 'Tambah kolom mis. harga_satuan_snapshot dan nama_item_snapshot, lalu jelaskan di aturan data.');
            }
        }
        if ($flags['hasAdminMutation']) {
            $log = $narr['log_audit'] ?? null;
            $cols = $log ? implode(' ', $columns($log)) : '';
            if (!$log || !preg_match('/aktor|pelaku|admin_id|user_id|oleh/', $cols) || !preg_match('/aksi/', $cols) || !preg_match('/entitas/', $cols) || !preg_match('/dibuat|waktu|created/', $cols)) {
                $out[] = $k('Tabel log_audit (siapa, aksi, entitas, id entitas, ringkasan sebelum/sesudah, waktu) belum ada padahal admin mengubah data.');
            }
            if (($rules['log_audit'] ?? '') === '') {
                $out[] = $k('Daftar aksi admin yang wajib dicatat di log audit belum ditulis.');
            }
        }
        if ($flags['hasGuestFlow']) {
            $ok = false;
            foreach ($txTables as $name => $t) {
                $user = null;
                foreach ($t['kolom'] as $col) {
                    if (preg_match('/^(user|pengguna|pelanggan|akun)_?id$/i', $col['nama'])) {
                        $user = $col;
                    }
                }
                $nullable = $user && preg_match('/boleh kosong|nullable|opsional|\bnull\b/iu', $user['tipe']) && !preg_match('/not null|wajib/iu', $user['tipe']);
                $codeNullable = $user && isset($blocks[$name]) ? QV::columnRequired($blocks[$name], $user['nama'], $lang) === false : $nullable;
                $guestCols = count(preg_grep('/tamu|guest/', $columns($t))) >= 2;
                $ok = $ok || ($nullable && $codeNullable && $guestCols);
            }
            if (!$ok || ($rules['tamu'] ?? '') === '') {
                $out[] = $k('Transaksi tamu belum didukung: user_id harus boleh kosong (di narasi dan kode) dan ada kolom nama_tamu + kontak_tamu, plus penjelasan cara menghitung tamu.');
            }
        }
        if ($c['stack']['rls'] && SecurityTriggers::personalTables($c['prd'])) {
            if (empty($db['kebijakan_akses']) || !preg_match('/row level security|create policy/i', $code)) {
                $out[] = $k('Stack mendukung Row Level Security tetapi kebijakan akses baris untuk tabel data pribadi/transaksi belum ditulis.');
            }
        }
        return $out;
    }
}
