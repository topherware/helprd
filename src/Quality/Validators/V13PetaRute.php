<?php

/** V13 PetaRute (bab 10): setiap fitur punya rute, path unik & valid, peran terdefinisi, rute 404/403, navigasi per peran. */
final class V13PetaRute
{
    public static function check(array $c): array
    {
        $r = $c['prd']['rute'] ?? [];
        if (empty($r['rute'])) {
            return [QV::finding('V13', 'K', 10, 'Bab 10 Peta Rute & Layout per Peran belum ada.', 'Tulis tabel rute (path, halaman, peran, layout, fitur sumber) untuk setiap fitur.')];
        }
        $f = fn ($pesan, $saran = '') => QV::finding('V13', 'M', 10, $pesan, $saran);
        $out = [];

        // Fitur dianggap punya rute bila namanya, atau ID FR miliknya, ada di kolom sumber.
        $frOwner = [];
        foreach (QV::reqs($c['prd']['requirements']['fungsional'] ?? []) as $fr) {
            $frOwner[strtoupper($fr['id'])] = array_map([QV::class, 'lower'], $fr['fitur']);
        }
        $covered = [];
        foreach ($r['rute'] as $row) {
            foreach ($row['sumber'] as $src) {
                $covered[QV::lower($src)] = true;
                foreach ($frOwner[strtoupper(trim($src))] ?? [] as $name) {
                    $covered[$name] = true;
                }
            }
        }
        foreach ($c['features'] as $feature) {
            if (!isset($covered[QV::lower($feature['nama'])])) {
                $out[] = QV::finding('V13', 'K', 10, "Fitur \"{$feature['nama']}\" belum punya rute.", 'Tambahkan baris rute dengan fitur sumber ini.');
            }
        }

        $paths = array_column($r['rute'], 'path');
        $bad = array_filter($paths, fn ($p) => !preg_match('#^/[a-z0-9\-/_:\[\].]*$#', $p));
        if ($bad) {
            $out[] = $f('Path tidak valid (harus huruf kecil dan diawali "/"): ' . implode(', ', $bad) . '.');
        }
        $dupes = array_keys(array_filter(array_count_values($paths), fn ($n) => $n > 1));
        if ($dupes) {
            $out[] = $f('Path ganda: ' . implode(', ', $dupes) . '.');
        }

        $roles = array_map([QV::class, 'lower'], $c['roles']);
        $used = array_values(array_unique(array_merge(...array_map(fn ($row) => array_map([QV::class, 'lower'], $row['peran']), $r['rute']))));
        $unknown = array_diff($used, $roles);
        if ($unknown) {
            $out[] = $f('Peran di tabel rute tidak didefinisikan: ' . implode(', ', $unknown) . '.', 'Pakai nama peran yang sama dengan bab 2, 6, dan 8: ' . implode(', ', $roles) . '.');
        }
        $kinds = array_map([QV::class, 'lower'], array_column($r['rute_khusus'] ?? [], 'jenis'));
        $need = ['404', '403', 'error'];
        if (count(array_filter($roles, fn ($x) => !Triggers::isAnonymous($x))) > 0) {
            array_push($need, 'masuk', 'keluar');
        }
        $missing = array_diff($need, $kinds);
        if ($missing) {
            $out[] = $f('Rute khusus belum ada: ' . implode(', ', $missing) . '.');
        }
        $navRoles = array_map([QV::class, 'lower'], array_column($r['navigasi'] ?? [], 'peran'));
        $noNav = array_diff($used, $navRoles);
        if ($noNav) {
            $out[] = $f('Pola navigasi belum ditulis untuk peran: ' . implode(', ', $noNav) . '.');
        }
        return $out;
    }
}
