<?php

/** Bantuan bersama validator v1.2: pembuat temuan, warna & kontras WCAG, dan pembaca kode skema. */
final class QV
{
    public const PENALTI = ['K' => 25, 'M' => 10, 'm' => 3];

    /** Temuan terstruktur: id validator, keparahan (K kritis, M mayor, m minor), bab PRD, pesan, saran perbaikan. */
    public static function finding(string $id, string $sev, ?int $bab, string $pesan, string $saran = ''): array
    {
        return ['id' => $id, 'sev' => $sev, 'bab' => $bab, 'pesan' => $pesan, 'saran' => $saran];
    }

    /* ---------- Warna ---------- */

    /** "#RGB", "#RRGGBB", "hsl(220 90% 56%)", "hsl(220, 90%, 56%)", atau "220 90% 56%" → [r, g, b] 0-255; null bila tidak valid. */
    public static function color(string $value): ?array
    {
        $v = trim($value);
        if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $v, $m)) {
            $hex = strlen($m[1]) === 3 ? preg_replace('/(.)/', '$1$1', $m[1]) : $m[1];
            return array_map('hexdec', str_split($hex, 2));
        }
        if (preg_match('/^(?:hsla?\(\s*)?(\d+(?:\.\d+)?)(?:deg)?\s*,?\s*(\d+(?:\.\d+)?)%\s*,?\s*(\d+(?:\.\d+)?)%\s*(?:[,\/]\s*[\d.]+%?\s*)?\)?$/i', $v, $m)
            && (str_starts_with(strtolower($v), 'hsl') === str_ends_with($v, ')'))) {
            [$h, $s, $l] = [fmod((float) $m[1], 360) / 360, min(100, (float) $m[2]) / 100, min(100, (float) $m[3]) / 100];
            if ($s == 0) {
                return array_fill(0, 3, (int) round($l * 255));
            }
            $q = $l < .5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $hue = function ($t) use ($p, $q) {
                $t += $t < 0 ? 1 : ($t > 1 ? -1 : 0);
                return match (true) { $t < 1 / 6 => $p + ($q - $p) * 6 * $t, $t < 1 / 2 => $q, $t < 2 / 3 => $p + ($q - $p) * (2 / 3 - $t) * 6, default => $p };
            };
            return [(int) round($hue($h + 1 / 3) * 255), (int) round($hue($h) * 255), (int) round($hue($h - 1 / 3) * 255)];
        }
        return null;
    }

    /** Rasio kontras WCAG 2.x antara dua warna [r, g, b]. */
    public static function contrast(array $a, array $b): float
    {
        $lum = function (array $rgb) {
            $c = array_map(function ($v) {
                $v /= 255;
                return $v <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4;
            }, $rgb);
            return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
        };
        [$l1, $l2] = [$lum($a), $lum($b)];
        return (max($l1, $l2) + 0.05) / (min($l1, $l2) + 0.05);
    }

    /* ---------- Kode skema ---------- */

    /** Tabel di kode skema: nama tabel (huruf kecil) => isi definisinya. Mendukung SQL, Drizzle (TS), dan Prisma. */
    public static function schemaBlocks(string $code): array
    {
        $out = [];
        if (preg_match_all('/create\s+table\s+(?:if\s+not\s+exists\s+)?[`"\[]?(?:\w+\.)?[`"\]]?(\w+)[`"\]]?\s*\(/i', $code, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[1] as $i => [$name]) {
                $out[strtolower($name)] = self::balanced($code, $m[0][$i][1] + strlen($m[0][$i][0]) - 1, '(', ')');
            }
        }
        if (preg_match_all('/(?:pgTable|sqliteTable|mysqlTable)\(\s*[\'"](\w+)[\'"]\s*,\s*\{/', $code, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[1] as $i => [$name]) {
                $out[strtolower($name)] = self::balanced($code, $m[0][$i][1] + strlen($m[0][$i][0]) - 1, '{', '}');
            }
        }
        if (preg_match_all('/\bmodel\s+(\w+)\s*\{/', $code, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[1] as $i => [$name]) {
                $body = self::balanced($code, $m[0][$i][1] + strlen($m[0][$i][0]) - 1, '{', '}');
                $table = preg_match('/@@map\(\s*"(\w+)"\s*\)/', $body, $map) ? $map[1] : $name;
                $out[strtolower($table)] = $body;
            }
        }
        return $out;
    }

    /** Bahasa skema yang terdeteksi dari kode: sql, ts, prisma, atau ''. */
    public static function schemaLanguage(string $code): string
    {
        return match (true) {
            (bool) preg_match('/(?:pgTable|sqliteTable|mysqlTable)\(/', $code) => 'ts',
            (bool) preg_match('/\bmodel\s+\w+\s*\{/', $code) => 'prisma',
            (bool) preg_match('/create\s+table/i', $code) => 'sql',
            default => '',
        };
    }

    /** Apakah kolom wajib diisi (NOT NULL) menurut definisi tabel di kode. null bila kolom tidak ditemukan. */
    public static function columnRequired(string $block, string $column, string $lang): ?bool
    {
        $col = preg_quote($column, '/');
        if (!preg_match("/^\s*[`\"]?$col\b[`\"]?\s*(.*)$/m", $block, $m)) {
            return null;
        }
        return match ($lang) {
            'ts' => str_contains($m[1], '.notNull()'),
            'prisma' => !preg_match('/^\w+\?/', $m[1]),
            default => (bool) preg_match('/\bNOT\s+NULL\b|\bPRIMARY\s+KEY\b/i', $m[1]),
        };
    }

    private static function balanced(string $text, int $open, string $l, string $r): string
    {
        $depth = 0;
        for ($i = $open, $n = strlen($text); $i < $n; $i++) {
            $depth += $text[$i] === $l ? 1 : ($text[$i] === $r ? -1 : 0);
            if ($depth === 0) {
                return substr($text, $open + 1, $i - $open - 1);
            }
        }
        return substr($text, $open + 1);
    }

    /* ---------- Lain-lain ---------- */

    /** FR/NFR/OQ dalam bentuk seragam (format v1.1 berupa string tanpa ID). */
    public static function reqs(array $list): array
    {
        return array_map(fn ($x) => is_array($x) ? $x + ['id' => '', 'teks' => '', 'fitur' => []] : ['id' => '', 'teks' => (string) $x, 'fitur' => []], $list);
    }

    public static function lower(string $s): string
    {
        return mb_strtolower(trim($s));
    }

    /** Kata bermakna (≥ 4 huruf, bukan kata sambung) dari sebuah teks. */
    public static function words(string $text): array
    {
        $stop = ['yang', 'untuk', 'dengan', 'pada', 'dari', 'atau', 'dalam', 'agar', 'setiap', 'harus', 'bisa', 'akan', 'juga', 'saat', 'ingin', 'maksimal', 'minimal'];
        preg_match_all('/[\p{L}\p{N}]{4,}/u', mb_strtolower($text), $m);
        return array_values(array_unique(array_diff($m[0], $stop)));
    }

    /** Semua string di dalam nilai bertingkat (array JSON), supaya pemeriksaan per kalimat tidak tercampur antar-field. */
    public static function texts($value): array
    {
        if (is_string($value)) {
            return [$value];
        }
        return is_array($value) ? array_merge([], ...array_map([self::class, 'texts'], array_values($value))) : [];
    }

    /** Kalimat-kalimat dari teks panjang. */
    public static function sentences(string $text): array
    {
        return array_filter(array_map('trim', preg_split('/(?<=[.!?])\s+|\n+/u', $text)));
    }

    public static function negated(string $sentence): bool
    {
        return (bool) preg_match('/\b(tidak|tanpa|bukan|jangan|dilarang|never|not|no)\b/iu', $sentence);
    }
}
