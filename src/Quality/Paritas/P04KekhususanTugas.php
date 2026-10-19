<?php

/**
 * P4 KekhususanTugas (mayor): Rencana Pengerjaan Terkoordinasi (Task M<n>.<k>) dengan berkas/modul dan "Selesai bila";
 * tugas M1 merujuk dataset, M3 merujuk SEC/skenario uji; butir per fitur merujuk Task M<n>.<k> yang menaunginya.
 */
final class P04KekhususanTugas
{
    public const BERKAS = '/`[^`\n]+`|\b[\w\-\[\]\(\)]+(?:\/[\w\-.\[\]\(\)]+)+\.(?:tsx?|jsx?|php|py|sql|json|toml|ya?ml|prisma|md|css|vue|svelte|astro)\b|\b[\w\-]+\.(?:tsx?|jsx?|php|py|sql|prisma|toml)\b/u';
    public const SELESAI = '/selesai bila|kriteria selesai|done when|definition of done/iu';
    public const DATASET = '/DUMMY-|data dummy|data tiruan|dataset|bab 9|bab 11/iu';

    public static function berlaku(array $c): bool
    {
        return true;
    }

    public static function check(array $c): array
    {
        $p = $c['paket'];
        $f = fn ($pesan, $saran = '') => QV::finding('P4', 'M', 13, $pesan, $saran);
        $tasks = $p->coordinatedTasks();
        $out = [];
        if (!$tasks) {
            $out[] = $f('Rencana Pengerjaan Terkoordinasi (Task M<n>.<k>) belum ada; tugas hanya berupa judul.',
                'Tulis tugas gemuk bernomor berisi berkas, perilaku, dataset (M1), SEC/uji (M3), dan "Selesai bila".');
        } else {
            $noFile = array_column(array_filter($tasks, fn ($t) => !preg_match(self::BERKAS, $t['teks'])), 'id');
            if ($noFile) {
                $out[] = $f(count($noFile) . ' tugas tanpa jalur berkas atau nama modul: ' . implode(', ', array_slice($noFile, 0, 8)) . '.', 'Sebutkan berkas/folder yang dibuat atau diubah.');
            }
            $noDone = array_column(array_filter($tasks, fn ($t) => !preg_match(self::SELESAI, $t['teks'])), 'id');
            if ($noDone) {
                $out[] = $f(count($noDone) . ' dari ' . count($tasks) . ' tugas tanpa kriteria "Selesai bila".', 'Tambahkan satu kalimat "Selesai bila" per tugas.');
            }
            $m1 = array_filter($tasks, fn ($t) => $t['milestone'] === 1);
            if ($m1 && !array_filter($m1, fn ($t) => preg_match(self::DATASET, $t['teks']))) {
                $out[] = $f('Tugas milestone 1 tidak merujuk dataset dummy.', 'Sebutkan ID dataset bab 11 yang dipakai tiap halaman.');
            }
            $m3 = array_filter($tasks, fn ($t) => $t['milestone'] === 3);
            if ($m3 && !array_filter($m3, fn ($t) => preg_match('/SEC-\d+|uji|test|skenario/iu', $t['teks']))) {
                $out[] = $f('Tugas milestone 3 tidak merujuk aturan SEC atau skenario uji.');
            }
        }
        if (!$p->single) {
            $ids = array_column($tasks, 'id');
            $items = array_merge([], ...array_values($p->featureChecklist()));
            $unmapped = array_filter($items, fn ($t) => !preg_match('/Task\s+(M?\d+\.\d+)/u', $t, $m) || ($ids && !in_array($m[1], $ids, true)));
            if ($items && $unmapped) {
                $out[] = $f(count($unmapped) . ' dari ' . count($items) . ' butir tugas fitur tidak merujuk Task M<n>.<k> yang sah.', 'Petakan tiap butir ke tugas terkoordinasi di bab 13.');
            }
        }
        return $out;
    }
}
