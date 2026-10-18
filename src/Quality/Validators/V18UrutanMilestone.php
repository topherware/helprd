<?php

/**
 * V18 UrutanMilestone (bab 13 + task): task berawalan M1/M2/M3 dan berurutan per fitur; M1 tanpa istilah backend;
 * tiap milestone punya Selesai bila dan CHECKPOINT; aturan checkpoint sesuai Cara Kerja AI; CLAUDE.md & PROGRESS.md lengkap.
 */
final class V18UrutanMilestone
{
    public const M1_TERLARANG = '/\btabel (database|db)\b|\btabel\s+`?[a-z]+_[a-z_]+`?|\bdatabase\b|\bbasis data\b|\bapi\b|\bendpoint\b|\brls\b|row level|\bmigrasi\b|\bskema (database|db)\b|\bvendor\b|\bwebhook\b|\bquery\b|\bsql\b/iu';
    public const CHECKPOINT = [
        'task' => '/setiap (tugas|task)/iu',
        'fase' => '/(setiap|tiap) milestone/iu',
        'sekaligus' => '/M1.*akhir proyek|akhir proyek.*M1/isu',
    ];

    public static function check(array $c): array
    {
        $m = $c['prd']['milestone'] ?? [];
        $f = fn ($pesan, $saran = '') => QV::finding('V18', 'M', 13, $pesan, $saran);
        if (empty($m['daftar'])) {
            return [QV::finding('V18', 'K', 13, 'Bab 13 Milestone & Master Prompt belum ada, sehingga tidak ada CHECKPOINT.', 'Tulis milestone M0–M4 dengan Selesai bila dan CHECKPOINT.')];
        }
        $out = [];
        $uiOnly = $c['flags']['uiOnly'];
        $expected = $uiOnly ? ['M0', 'M1'] : ['M0', 'M1', 'M2', 'M3', 'M4'];
        $have = array_column($m['daftar'], 'id');
        if ($missing = array_diff($expected, $have)) {
            $out[] = $f('Milestone belum lengkap: ' . implode(', ', $missing) . '.');
        }
        if ($uiOnly && ($extra = array_diff($have, $expected))) {
            $out[] = $f('Mode hanya-UI hanya berisi M0 dan M1, tetapi ada ' . implode(', ', $extra) . '.');
        }
        foreach ($m['daftar'] as $x) {
            if (!preg_match('/checkpoint/iu', $x['checkpoint'])) {
                $out[] = QV::finding('V18', 'K', 13, "Milestone {$x['id']} tidak punya CHECKPOINT.", 'Tulis: berhenti, perbarui PROGRESS.md, ringkas, tunggu persetujuan.');
            }
            if (!$x['selesai_bila']) {
                $out[] = $f("Milestone {$x['id']} belum punya kriteria Selesai bila.");
            }
        }
        $rule = self::CHECKPOINT[$c['brief']->aiWorkflow] ?? self::CHECKPOINT['fase'];
        $ruleText = $m['aturan_checkpoint'] ?? '';
        if (!preg_match($rule, $ruleText) || ($c['brief']->aiWorkflow === 'sekaligus' && !preg_match('/gagal/iu', $ruleText))) {
            $out[] = $f('Aturan checkpoint tidak sesuai Cara Kerja AI "' . $c['brief']->workflowLabel() . '".');
        }
        $cm = $m['claude_md'] ?? [];
        if (($cm['struktur_folder'] ?? '') === '' || ($cm['perintah']['jalan'] ?? '') === '' || ($cm['perintah']['uji'] ?? '') === '') {
            $out[] = $f('Isi CLAUDE.md belum lengkap (struktur folder, perintah jalan dan uji).');
        }

        if (!$c['tasks']) {
            $out[] = $f('Belum ada task bertanda milestone.', 'Jalankan "Bikin Task" dulu.');
            return $out;
        }
        $rank = ['M1' => 1, 'M2' => 2, 'M3' => 3];
        $byFeature = [];
        foreach ($c['tasks'] as $t) {
            $byFeature[$t['fitur']][] = $t;
        }
        $badM1 = [];
        foreach ($byFeature as $name => $tasks) {
            $noMilestone = count(array_filter($tasks, fn ($t) => !isset($rank[$t['milestone'] ?? ''])));
            if ($noMilestone) {
                $out[] = $f("$noMilestone task fitur \"$name\" belum berawalan [M1]/[M2]/[M3].");
                continue;
            }
            $ranks = array_map(fn ($t) => $rank[$t['milestone']], $tasks);
            $sorted = $ranks;
            sort($sorted);
            if ($ranks !== $sorted) {
                $out[] = $f("Task fitur \"$name\" tidak berurutan M1 → M2 → M3.");
            }
            if ($uiOnly && max($ranks) > 1) {
                $out[] = $f("Mode hanya-UI: task fitur \"$name\" harus [M1] semua.");
            }
            foreach ($tasks as $t) {
                if ($t['milestone'] === 'M1' && preg_match(self::M1_TERLARANG, $t['title'])) {
                    $badM1[] = $t['title'];
                }
            }
        }
        if ($badM1) {
            $out[] = $f(count($badM1) . ' task [M1] menyebut tabel/API/RLS/migrasi/vendor, mis. "' . $badM1[0] . '".', 'Pindahkan ke [M2].');
        }
        return $out;
    }
}
