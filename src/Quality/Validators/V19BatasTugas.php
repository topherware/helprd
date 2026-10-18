<?php

/** V19 BatasTugas (minor): ≤ 14 task per fitur, ≤ 12 × jumlah fitur total, tanpa duplikat, Master Prompt merujuk berkas yang benar. */
final class V19BatasTugas
{
    public const BERKAS = ['CLAUDE.md', 'PRD.md', 'features/', 'PROGRESS.md'];

    public static function check(array $c): array
    {
        $f = fn ($pesan, $saran = '') => QV::finding('V19', 'm', 13, $pesan, $saran);
        $out = [];
        $counts = array_count_values(array_column($c['tasks'], 'fitur'));
        foreach ($counts as $name => $n) {
            if ($n > 14) {
                $out[] = $f("Fitur \"$name\" punya $n task (maksimal 14).", 'Gabungkan task sepele.');
            }
        }
        $limit = 12 * max(1, count($c['features']));
        if (count($c['tasks']) > $limit) {
            $out[] = $f('Total task ' . count($c['tasks']) . " melebihi batas $limit (12 × jumlah fitur).");
        }
        $titles = array_map(fn ($t) => QV::lower(preg_replace('/^\[M\d\]\s*/', '', $t['title'])), $c['tasks']);
        $dupes = array_keys(array_filter(array_count_values($titles), fn ($n) => $n > 1));
        if ($dupes) {
            $out[] = $f(count($dupes) . ' judul task ganda, mis. "' . $dupes[0] . '".');
        }
        $prompt = $c['prd']['milestone']['master_prompt'] ?? '';
        $missing = array_filter(self::BERKAS, fn ($b) => !str_contains($prompt, $b));
        if ($prompt === '' || $missing) {
            $out[] = $f($prompt === '' ? 'Master Prompt belum ada.' : 'Master Prompt belum merujuk: ' . implode(', ', $missing) . '.');
        }
        return $out;
    }
}
