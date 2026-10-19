<?php

/**
 * V19 BatasTugas (minor): tanpa judul task ganda, Master Prompt merujuk berkas yang benar.
 * Batas jumlah task (≤ 8 per fitur, ≤ 8 × jumlah fitur) ditegakkan P08Batas (kritis) sejak v1.2.1.
 */
final class V19BatasTugas
{
    public const BERKAS = ['CLAUDE.md', 'PRD.md', 'features/', 'PROGRESS.md'];

    public static function check(array $c): array
    {
        $f = fn ($pesan, $saran = '') => QV::finding('V19', 'm', 13, $pesan, $saran);
        $out = [];
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
