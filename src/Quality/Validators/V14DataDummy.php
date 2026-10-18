<?php

/** V14 DataDummy (bab 11): dataset per entitas, cocok dengan skema, template terisi, task "data tiruan" merujuk ID, format uang. */
final class V14DataDummy
{
    public static function check(array $c): array
    {
        $d = $c['prd']['dummy'] ?? [];
        $f = fn ($pesan, $saran = '') => QV::finding('V14', 'M', 11, $pesan, $saran);
        if (empty($d['dataset'])) {
            return [$f('Bab 11 Data Dummy & Copywriting belum ada dataset baku.', 'Tulis dataset DUMMY-<ENTITAS> untuk setiap entitas yang tampil di UI.')];
        }
        $out = [];
        $ids = array_column($d['dataset'], 'id');
        $badIds = array_filter($ids, fn ($id) => !preg_match('/^DUMMY-[A-Z0-9_-]+$/', $id));
        if ($badIds) {
            $out[] = $f('ID dataset harus berbentuk DUMMY-<ENTITAS>: ' . implode(', ', $badIds) . '.');
        }

        $tables = [];
        foreach ($c['prd']['database']['tabel'] ?? [] as $t) {
            $tables[QV::lower($t['nama'])] = $t;
        }
        $useSchema = $c['flags']['hasDatabase'] && $tables;
        $byTable = [];
        foreach ($d['dataset'] as $set) {
            $table = QV::lower($set['tabel']);
            $byTable[$table] = $set;
            $single = $useSchema && !empty($tables[$table]['tunggal']);
            if (count($set['baris']) < ($single ? 1 : 3)) {
                $out[] = $f("{$set['id']} hanya punya " . count($set['baris']) . ' baris (minimal ' . ($single ? 1 : 3) . ').');
            }
            foreach ($set['baris'] as $i => $row) {
                if (count($row) !== count($set['kolom'])) {
                    $out[] = $f("{$set['id']} baris " . ($i + 1) . ' jumlah nilainya tidak sama dengan jumlah kolom.');
                    break;
                }
            }
            if ($useSchema) {
                if (!isset($tables[$table])) {
                    $out[] = $f("{$set['id']} merujuk tabel `{$set['tabel']}` yang tidak ada di skema bab 6.");
                    continue;
                }
                $cols = array_map([QV::class, 'lower'], array_column($tables[$table]['kolom'], 'nama'));
                $unknown = array_diff(array_map([QV::class, 'lower'], $set['kolom']), $cols);
                if ($unknown) {
                    $out[] = $f("{$set['id']} memakai kolom yang tidak ada di tabel `{$set['tabel']}`: " . implode(', ', $unknown) . '.', 'Samakan nama kolom dengan skema bab 6.');
                }
            }
        }
        if ($useSchema) {
            foreach ($tables as $name => $t) {
                if (empty($t['teknis']) && $name !== 'log_audit' && !isset($byTable[$name])) {
                    $out[] = $f("Entitas `{$t['nama']}` belum punya dataset tiruan.");
                }
            }
        }

        foreach ($d['template'] ?? [] as $t) {
            if (preg_match('/\{[a-z_]+\}/i', $t['contoh'])) {
                $out[] = $f("Contoh template \"{$t['nama']}\" masih berisi placeholder.", 'Isi semua placeholder di contoh.');
            }
        }

        // Task M1 yang memakai data tiruan wajib menyebut ID dataset yang ada.
        $noRef = [];
        foreach ($c['tasks'] as $t) {
            if (($t['milestone'] ?? 'M1') === 'M1' && preg_match('/data tiruan|dummy|data contoh|mock data/iu', $t['title'])) {
                preg_match_all('/DUMMY-[A-Z0-9_-]*[A-Z0-9]/', $t['title'], $m);
                if (!$m[0] || array_diff($m[0], $ids)) {
                    $noRef[] = $t['title'];
                }
            }
        }
        if ($noRef) {
            $out[] = $f(count($noRef) . ' task [M1] "data tiruan" tidak merujuk ID dataset yang ada, mis. "' . $noRef[0] . '".', 'Sebut ID dataset bab 11, mis. "pakai DUMMY-MENU".');
        }

        $texts = [];
        foreach ($d['dataset'] as $set) {
            foreach ($set['baris'] as $row) {
                $texts = array_merge($texts, array_filter($row, 'is_string'));
            }
        }
        $texts = array_merge($texts, array_column($d['template'] ?? [], 'contoh'), array_values($d['copywriting']['microcopy'] ?? []));
        preg_match_all('/Rp\s?[\d.,]+/u', implode(' ', $texts), $m);
        $badMoney = array_unique(array_filter(array_map(fn ($x) => rtrim($x, '.,'), $m[0]), fn ($x) => !preg_match('/^Rp\d{1,3}(\.\d{3})*(,\d{1,2})?$/', $x)));
        if ($badMoney) {
            $out[] = $f('Format uang tidak konsisten: ' . implode(', ', array_slice($badMoney, 0, 4)) . '.', 'Pakai format "Rp15.000".');
        }
        return $out;
    }
}
