<?php

/**
 * P8 Batas (kritis): Hardening ≤ 8 butir tanpa duplikat aturan MVP, butir tugas ≤ 8 per fitur dan ≤ 8 × jumlah fitur,
 * tugas terkoordinasi ≤ 30 total dan ≤ 10 per milestone. Menggantikan cek batas V17/V19 v1.2 (yang hanya memperingatkan).
 */
final class P08Batas
{
    public const HARDENING = 8;
    public const PER_FITUR = 8;
    public const RENCANA = 30;
    public const PER_MILESTONE = 10;

    public static function berlaku(array $c): bool
    {
        return true;
    }

    public static function check(array $c): array
    {
        $p = $c['paket'];
        $out = [];
        $hardening = Paket::bullets($p->section('/hardening/i'));
        if (count($hardening) > self::HARDENING) {
            $out[] = QV::finding('P8', 'K', 8, 'Butir Hardening ada ' . count($hardening) . ' (maksimal ' . self::HARDENING . ').', 'Gabungkan atau pangkas; jangan menambah butir baru.');
        }
        preg_match_all('/\*\*SEC-\d+\*\*\s*[—–-]\s*(.+)$/mu', $p->section('/wajib mvp|persyaratan keamanan/i'), $m);
        $dupes = [];
        foreach ($hardening as $h) {
            $hw = QV::words($h);
            foreach ($m[1] as $sec) {
                $sw = QV::words($sec);
                if (count($hw) >= 3 && count(array_intersect($hw, $sw)) / count($hw) >= 0.7) {
                    $dupes[] = mb_strimwidth($h, 0, 60, '…');
                    break;
                }
            }
        }
        if ($dupes) {
            $out[] = QV::finding('P8', 'M', 8, 'Butir Hardening menduplikasi aturan Wajib MVP: "' . implode('"; "', $dupes) . '".', 'Hapus duplikatnya dari Hardening.');
        }

        $checklist = $p->featureChecklist();
        if ($checklist && (!$p->single || $p->data)) {
            $over = array_filter(array_map('count', $checklist), fn ($n) => $n > self::PER_FITUR);
            if ($over) {
                $out[] = QV::finding('P8', 'K', 13, count($over) . ' fitur punya lebih dari ' . self::PER_FITUR . ' butir tugas (mis. "' . array_key_first($over) . '": ' . reset($over) . ').',
                    'Gabungkan butir kecil; maksimal ' . self::PER_FITUR . ' per fitur.') + ['tugas' => true, 'fitur' => array_keys($over)];
            }
            $features = max(count($checklist), count($c['features']), 1);
            $total = array_sum(array_map('count', $checklist));
            if ($total > self::PER_FITUR * $features) {
                $out[] = QV::finding('P8', 'K', 13, "Total butir tugas $total melebihi batas " . (self::PER_FITUR * $features) . ' (' . self::PER_FITUR . ' × jumlah fitur).')
                    + ['tugas' => true, 'fitur' => array_keys($checklist)];
            }
        }

        $plan = $p->coordinatedTasks();
        if (count($plan) > self::RENCANA) {
            $out[] = QV::finding('P8', 'K', 13, 'Tugas terkoordinasi ada ' . count($plan) . ' (maksimal ' . self::RENCANA . ').', 'Gabungkan tugas sejenis.');
        }
        $per = array_filter(array_count_values(array_column($plan, 'milestone')), fn ($n) => $n > self::PER_MILESTONE);
        foreach ($per as $ms => $n) {
            $out[] = QV::finding('P8', 'K', 13, "Milestone $ms punya $n tugas terkoordinasi (maksimal " . self::PER_MILESTONE . ').');
        }
        return $out;
    }
}
