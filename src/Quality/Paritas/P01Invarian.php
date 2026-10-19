<?php

/**
 * P1 Invarian (kritis): tiap invarian bisnis punya mekanisme di database sesuai resep stack (stacks.json resep_invarian),
 * rentang tidak boleh hanya dijaga unique(sumber_daya, waktu_mulai), dan ada tugas uji dua permintaan bersamaan.
 */
final class P01Invarian
{
    public const KONKUREN = '/bersamaan|serentak|konkur|concurren|race|balapan|paralel|simultan/iu';
    public const STRATEGI = '/for\s+update|transaksi|transaction|serializable|advisory|begin\s+immediate|atomik|atomic|not\s+exists/iu';

    public static function berlaku(array $c): bool
    {
        return !$c['flags']['uiOnly'] && $c['invariants'];
    }

    public static function check(array $c): array
    {
        $p = $c['paket'];
        $code = $p->schemaCode();
        $db = $p->section('/database|basis data|skema|fondasi teknis|tech stack|invarian/i') . "\n" . $code;
        $f = fn ($sev, $pesan, $saran = '') => QV::finding('P1', $sev, 6, $pesan, $saran);
        $recipe = Brief::stackConfig()['resep_invarian'][$c['db']] ?? Brief::stackConfig()['resep_invarian']['postgresql'];
        $out = [];
        foreach ($c['invariants'] as $inv) {
            $rule = mb_strimwidth($inv['aturan'], 0, 90, '…');
            $ok = match ($inv['jenis']) {
                'rentang' => self::rangeGuarded($c['db'], $db),
                'kuota' => preg_match(self::STRATEGI, $db) && preg_match('/kuota|kapasitas|batas|count|hitung|jumlah/iu', $db),
                'stok' => (bool) preg_match('/check\s*\(?[^\n]{0,60}(stok|stock|qty|jumlah)\w*[^\n]{0,20}>=\s*0|unsigned|(stok|stock)\w*\s*>=\s*\S+/iu', $db),
                'unik' => (bool) preg_match('/unique|uniqueIndex|@unique|\bunik\b/iu', $code),
                default => true,
            };
            if (!$ok) {
                $point = $inv['jenis'] === 'rentang' ? self::pointOnlyUnique($code) : '';
                $out[] = $f('K', "Invarian \"$rule\" belum dijaga di database" . ($point !== '' ? ": constraint hanya menjaga titik mulai ($point), bukan rentang mulai–selesai." : '.'),
                    'Terapkan resep ' . $c['db'] . ': ' . ($recipe[$inv['jenis']] ?? 'constraint di database + transaksi') . '.');
            }
        }
        if (!preg_match(self::STRATEGI, $db)) {
            $out[] = $f('K', 'Strategi konkurensi (transaksi/penguncian baris/pernyataan atomik) untuk invarian belum ditulis.', 'Tulis transaksi dan penguncian di bab 6 untuk tiap invarian.');
        }
        if (!preg_match(self::KONKUREN, Paritas::taskText($p))) {
            $out[] = $f('K', 'Belum ada tugas uji konkurensi (dua permintaan bersamaan) untuk invarian.', 'Tambahkan tugas uji: dua permintaan bersamaan, hanya satu yang berhasil.');
        }
        return $out;
    }

    /** Rentang terjaga: PostgreSQL wajib exclusion/range; DB lain wajib penguncian/pernyataan atomik + kueri tumpang tindih. */
    private static function rangeGuarded(string $db, string $text): bool
    {
        return match ($db) {
            'postgresql' => (bool) preg_match('/exclude\s+using\s+gist|tstzrange|tsrange|daterange|int4range|btree_gist/i', $text),
            'mongodb' => (bool) preg_match('/slot\s+unik|unique[^\n]{0,40}slot|findoneandupdate|\$setoninsert|atomik|atomic/iu', $text),
            default => preg_match('/for\s+update|begin\s+immediate|where\s+not\s+exists|get_lock|serializable|lock\s+tables/i', $text)
                && preg_match('/tumpang\s*tindih|overlap|bertabrakan|beririsan|&&|mulai\w*\s*<\s*:?\w*selesai|start\w*\s*<\s*:?\w*end/iu', $text),
        };
    }

    /** Index unik yang memuat kolom waktu mulai tetapi tanpa kolom selesai, mis. "unique(sumber_daya_id, mulai)". */
    private static function pointOnlyUnique(string $code): string
    {
        preg_match_all('/(?:uniqueIndex\([^)]*\)\s*\.on\(|unique\s*\(|columns:\s*\[|@@unique\(\s*\[)([^)\]]*)[)\]]/i', $code, $m);
        foreach ($m[1] as $cols) {
            if (preg_match('/mulai|start|jam|waktu|tanggal|begin/i', $cols) && !preg_match('/selesai|end|akhir|until/i', $cols)) {
                return 'unique(' . trim(preg_replace('/\s+|\b(t|table)\./', ' ', $cols)) . ')';
            }
        }
        return '';
    }
}
