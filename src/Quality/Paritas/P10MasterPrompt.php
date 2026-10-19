<?php

/**
 * P10 MasterPrompt (mayor): baca berkas yang benar, ringkasan pemahaman ≤ 10 poin, rencana berkas milestone pertama,
 * pertanyaan kesiapan, gerbang izin sesuai Cara Kerja AI, larangan mengarang data, kepatuhan skema/rute/hak akses/aturan bisnis/design system.
 */
final class P10MasterPrompt
{
    public const BUTIR = [
        'perintah membaca PRD.md' => '/PRD\.md/u',
        'ringkasan pemahaman maks. 10 poin' => '/ringkas\w*[^\n]{0,80}?(maksimal|maks\.?|paling banyak|≤|tidak lebih dari)\s*10/iu',
        'rencana berkas milestone pertama' => '/rencana\s+(file|berkas)/iu',
        'pertanyaan kesiapan' => '/kesiapan|apakah (saya|kamu|anda) siap|siap (untuk )?(mulai|memulai)|siap kamu mulai/iu',
        'larangan mengarang data' => '/(jangan|dilarang|tidak boleh)\s+(mengarang|membuat data|menambah data)|data dummy[^\n.]{0,40}(dari|di)\s+bab/iu',
        'patuh skema' => '/skema/iu',
        'patuh rute' => '/\brute\b|\broute/iu',
        'patuh hak akses' => '/hak akses/iu',
        'patuh aturan bisnis' => '/aturan bisnis/iu',
        'patuh design system' => '/design system|desain/iu',
    ];
    public const GERBANG = '/(hanya|baru)\s+(dimulai|mulai|lanjut\w*|dikerjakan)\s+setelah[^\n]{0,40}(izin|persetujuan|konfirmasi|setuju)|(tunggu|menunggu)\w*[^\n]{0,40}(izin|persetujuan|konfirmasi)/iu';

    public static function berlaku(array $c): bool
    {
        return true;
    }

    public static function check(array $c): array
    {
        $prompt = $c['paket']->masterPrompt();
        if (trim($prompt) === '') {
            return [QV::finding('P10', 'M', 13, 'Master Prompt belum ada.', 'Tulis Master Prompt bergerbang izin di bab 13.')];
        }
        $missing = array_keys(array_filter(self::BUTIR, fn ($re) => !preg_match($re, $prompt)));
        $workflow = $c['brief']->aiWorkflow;
        if ($workflow !== 'sekaligus' && !preg_match(self::GERBANG, $prompt)) {
            $missing[] = 'gerbang izin ("berikutnya hanya dimulai setelah izin")';
        }
        if ($workflow === 'task' && !preg_match('/(setiap|tiap|per|satu)\s+task/iu', $prompt)) {
            $missing[] = 'gerbang per task (Cara Kerja: Bertahap per Task)';
        }
        return $missing ? [QV::finding('P10', 'M', 13, 'Master Prompt belum memuat: ' . implode(', ', $missing) . '.', 'Lengkapi Master Prompt sesuai Cara Kerja AI (' . $c['brief']->workflowLabel() . ').')] : [];
    }
}
