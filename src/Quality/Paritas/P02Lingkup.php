<?php

/**
 * P2 Lingkup (mayor; kritis untuk peran login tanpa asal): tiap fitur dan peran terlacak ke masukan pengguna,
 * usulan AI tidak masuk milestone. Memakai field `asal` bila ada; PRD tanpa `asal` dinilai dengan heuristik Telusur.
 */
final class P02Lingkup
{
    public static function berlaku(array $c): bool
    {
        return true;
    }

    public static function check(array $c): array
    {
        $p = $c['paket'];
        $b = $c['brief'];
        $input = $b->inputText(false);
        $out = [];
        $structured = $c['features'] && !array_filter($c['features'], fn ($f) => !isset($f['asal']));

        if ($structured) {
            $bad = array_column(array_filter($c['features'], fn ($f) => !in_array($f['asal'], Validator::ASAL, true)), 'nama');
            $usulan = array_column($b->usulan, 'nama');
        } else {
            $usulan = array_map(fn ($s) => trim(preg_replace('/^\*\*([^*]+)\*\*.*$/u', '$1', $s)), Paket::bullets($p->section('/usulan tambahan/i')));
            $names = array_diff(self::featureNames($p), $usulan);
            $bad = array_values(array_filter($names, fn ($n) => !Telusur::traced($n, $input)));
        }
        if ($bad) {
            // Bab null: asal fitur diperbaiki di peta fitur (langkah 4), bukan dengan menulis ulang PRD.
            $out[] = QV::finding('P2', 'M', null, 'Fitur tanpa asal di masukan pengguna: ' . implode(', ', $bad) . '.',
                'Hapus dari lingkup atau pindahkan ke "Usulan Tambahan (belum dijadwalkan)" di bab 2.');
        }

        $milestone = $p->section('/milestone|tahapan|task breakdown/i');
        $leak = array_values(array_filter($usulan, fn ($n) => $n !== '' && mb_stripos($milestone, $n) !== false));
        if ($leak) {
            $out[] = QV::finding('P2', 'M', 13, 'Usulan AI yang belum disetujui masuk milestone: ' . implode(', ', $leak) . '.', 'Keluarkan dari milestone sampai pengguna menyertakannya.');
        }

        $asal = $c['paket']->data['profil']['peran_asal'] ?? [];
        $roles = array_values(array_filter(Triggers::roleNames($c['roles']), fn ($r) => !Triggers::isAnonymous($r)));
        $badRoles = $asal
            ? array_values(array_filter($roles, fn ($r) => !in_array($asal[$r] ?? '', ['ide', 'jawaban', 'catatan', 'keputusan'], true)))
            : array_values(array_filter($roles, fn ($r) => !Telusur::roleTraced($r, $input)));
        if ($badRoles) {
            $out[] = QV::finding('P2', 'K', null, 'Peran tanpa asal di masukan pengguna: ' . implode(', ', $badRoles) . '.', 'Hapus peran (dan akun/tabelnya) atau tanyakan ke pengguna.');
        }

        $pages = [];
        foreach (Telusur::PELENGKAP as $w) {
            if (preg_match('#`/' . $w . '[\w-]*`|^\#{2,4}.*\b' . $w . '#imu', $p->prd()) && !preg_match('/\b' . $w . '/iu', $input)) {
                $pages[] = $w;
            }
        }
        if ($pages) {
            $out[] = QV::finding('P2', 'm', 10, 'Halaman pelengkap tanpa asal: ' . implode(', ', $pages) . '.', 'Catat sebagai usulan tambahan.');
        }
        return $out;
    }

    /** Nama fitur: judul berkas features/*.md, atau judul tingkat 3 di bab fitur (tanpa awalan "Fase n —" / "A."). */
    public static function featureNames(Paket $p): array
    {
        $names = [];
        foreach ($p->featureFiles() as $content) {
            if (preg_match('/^#\s+(.+)$/m', $content, $m)) {
                $names[] = trim($m[1]);
            }
        }
        if (!$names) {
            $section = $p->section('/core features|fitur utama|fitur inti|daftar fitur|alur kerja dan fitur/i');
            foreach (Paket::headings($section, 3) as $h) {
                $names[] = trim(preg_replace('/^(fase\s*\d+\s*[—–-]\s*|[A-Z]\.\s+|\d+(\.\d+)*\.?\s+)/u', '', $h));
            }
        }
        return array_values(array_unique(array_filter($names)));
    }
}
