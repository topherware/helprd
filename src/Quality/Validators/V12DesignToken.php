<?php

/** V12 DesignToken (bab 9): token warna valid, kontras, font bernama, ukuran ketuk, gaya komponen, gaya Bebas terkunci. */
final class V12DesignToken
{
    public const TOKENS = ['primary', 'secondary', 'accent', 'background', 'surface', 'text', 'text_muted', 'border', 'success', 'warning', 'danger'];

    public static function check(array $c): array
    {
        $f = fn ($pesan, $saran = '') => QV::finding('V12', 'M', 9, $pesan, $saran);
        $d = $c['prd']['design'] ?? [];
        if (empty($d['warna']['terang'])) {
            return [$f('Bab 9 Design System belum ada: token warna kosong.', 'Tulis 11 token warna (HEX/HSL), tipografi, ukuran, gaya komponen, nuansa, keadaan, dan aksesibilitas.')];
        }
        $out = [];
        $themes = ['terang' => true, 'gelap' => $c['flags']['hasDarkTheme']];
        foreach ($themes as $theme => $required) {
            $set = $d['warna'][$theme] ?? [];
            if (!$required) {
                continue;
            }
            if (!$set) {
                $out[] = $f('Tema gelap diminta tetapi token warna tema gelap kosong.', 'Isi set token yang sama untuk tema gelap.');
                continue;
            }
            $missing = array_diff(self::TOKENS, array_keys($set));
            $invalid = array_keys(array_filter($set, fn ($v) => QV::color((string) $v) === null));
            if ($missing) {
                $out[] = $f("Token warna tema $theme belum lengkap: " . implode(', ', $missing) . '.');
            }
            if ($invalid) {
                $out[] = $f("Nilai warna tema $theme tidak valid (bukan HEX/HSL): " . implode(', ', array_map(fn ($k) => "$k = {$set[$k]}", $invalid)) . '.', 'Pakai format #RRGGBB atau hsl(h s% l%).');
            }
            $text = QV::color((string) ($set['text'] ?? ''));
            $bg = QV::color((string) ($set['background'] ?? ''));
            if ($text && $bg && ($ratio = QV::contrast($text, $bg)) < 4.5) {
                $out[] = $f(sprintf('Kontras text terhadap background tema %s hanya %.2f:1 (minimal 4.5:1).', $theme, $ratio), 'Gelapkan teks atau terangkan latar.');
            }
        }

        $t = $d['tipografi'] ?? [];
        $generic = '/^(sans-serif|serif|monospace|system-ui|default|bebas|terserah|font bebas|-)?$/iu';
        if (preg_match($generic, trim($t['font_judul'] ?? '')) || preg_match($generic, trim($t['font_isi'] ?? ''))) {
            $out[] = $f('Pasangan font judul dan isi belum berupa nama font nyata.', 'Sebut nama font, mis. "Plus Jakarta Sans" + "Inter".');
        }
        if (count($t['skala'] ?? []) < 3 || ($t['tinggi_baris'] ?? '') === '' || ($t['sumber'] ?? '') === '') {
            $out[] = $f('Tipografi belum lengkap: butuh skala ukuran (≥ 3 nilai), tinggi baris, dan sumber font.');
        }
        if ((int) ($d['ukuran']['ketuk_min'] ?? 0) < 44) {
            $out[] = $f('Ukuran area ketuk minimal kurang dari 44×44 px.', 'Tetapkan ketuk_min 44 atau lebih.');
        }

        $komponen = mb_strtolower(implode(' | ', array_map(fn ($k) => $k['nama'] . ' ' . $k['gaya'], $d['komponen'] ?? [])));
        $need = ['tombol primer' => '/primer|primary/u', 'tombol sekunder' => '/sekunder|secondary/u', 'tombol bahaya' => '/bahaya|danger|destruktif/u',
            'kartu' => '/kartu|card/u', 'input form' => '/input|form/u', 'badge status' => '/badge|lencana/u', 'tabel/daftar' => '/tabel|daftar|list|table/u',
            'dialog' => '/dialog|modal/u', 'toast' => '/toast|notifikasi singkat/u'];
        $missing = array_keys(array_filter($need, fn ($re) => !preg_match($re, $komponen)));
        if ($missing) {
            $out[] = $f('Gaya komponen belum ada untuk: ' . implode(', ', $missing) . '.');
        }
        $buttons = mb_strtolower(implode(' ', array_map(fn ($k) => $k['gaya'], array_filter($d['komponen'] ?? [], fn ($k) => preg_match('/tombol|button/iu', $k['nama'])))));
        if (!preg_match('/hover/u', $buttons) || !preg_match('/fokus|focus/u', $buttons) || !preg_match('/nonaktif|disabled/u', $buttons)) {
            $out[] = $f('Gaya tombol belum menyebut status hover, fokus, dan nonaktif.');
        }

        $n = $d['nuansa'] ?? [];
        if (count($n['sifat'] ?? []) < 3 || count($n['sifat'] ?? []) > 5 || count($n['hindari'] ?? []) < 2) {
            $out[] = $f('Nuansa visual harus berisi 3–5 kata sifat dan 2 hal yang dihindari.');
        }
        $s = $d['keadaan'] ?? [];
        if (($s['loading'] ?? '') === '' || ($s['kosong'] ?? '') === '' || ($s['error'] ?? '') === '') {
            $out[] = $f('Pola keadaan antarmuka (loading, kosong, error) belum lengkap.');
        }
        if (empty($d['aksesibilitas'])) {
            $out[] = $f('Aksesibilitas dasar (fokus terlihat, label pembaca layar, kontras ≥ 4.5:1) belum ditulis.');
        }

        $all = mb_strtolower(json_encode($d, JSON_UNESCAPED_UNICODE));
        if ($c['brief']->designStyle === 'bebas' && (($d['alasan'] ?? '') === '' || preg_match('/terserah|sesuai selera|bebas saja/u', $all))) {
            $out[] = $f('Gaya "Bebas" belum dikunci menjadi gaya konkret beserta alasannya.', 'Tulis gaya yang diputuskan dan alasan satu kalimat di design.alasan.');
        }
        return $out;
    }
}
