<?php

/**
 * Baca JSON yang masih setengah jadi (sedang di-stream): tutup string, array, dan objek yang masih terbuka.
 * Bila ujungnya berupa kunci atau nilai yang belum lengkap, potong mundur ke batas aman terakhir.
 */
final class PartialJson
{
    /**
     * Perbaiki kesalahan JSON yang sering ditulis model, tanpa mengubah JSON yang sudah valid:
     * - baris baru/tab mentah di dalam string (mis. di diagram mermaid) → "\n" / "\t";
     * - tanda kutip di dalam string yang lupa di-escape (kutip yang tidak diikuti , } ] : dianggap isi string);
     * - escape tidak sah (mis. "\_") → backslash digandakan;
     * - koma sebelum } atau ].
     */
    public static function sanitize(string $json): string
    {
        $out = '';
        $inString = false;
        $escape = false;
        $len = strlen($json);
        for ($i = 0; $i < $len; $i++) {
            $c = $json[$i];
            if ($inString) {
                if ($escape) {
                    $escape = false;
                    if (!str_contains('"\\/bfnrtu', $c)) {
                        $out .= '\\'; // escape tidak sah (mis. \_) → backslash digandakan
                    }
                } elseif ($c === '\\') {
                    $escape = true;
                } elseif ($c === '"') {
                    $j = $i + 1;
                    while ($j < $len && strpos(" \t\r\n", $json[$j]) !== false) {
                        $j++;
                    }
                    if ($j >= $len || strpos(',}]:', $json[$j]) !== false) {
                        $inString = false;
                    } else {
                        $c = '\\"'; // kutip di tengah isi string
                    }
                } elseif ($c === "\n") {
                    $c = '\n';
                } elseif ($c === "\r") {
                    $c = '';
                } elseif ($c === "\t") {
                    $c = '\t';
                }
            } elseif ($c === '"') {
                $inString = true;
            } elseif ($c === '}' || $c === ']') {
                $out = preg_replace('/,\s*$/', '', $out);
            }
            $out .= $c;
        }
        return $out;
    }

    public static function parse(string $text): ?array
    {
        $start = strpos($text, '{');
        if ($start === false) {
            return null;
        }
        $s = self::sanitize(substr($text, $start));
        $stack = '';     // urutan pembuka yang masih terbuka, mis. "{[{"
        $inString = false;
        $escape = false;
        $cuts = [];      // [posisi potong, stack saat itu] — sebelum koma atau sesudah pembuka
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $c = $s[$i];
            if ($inString) {
                if ($escape) {
                    $escape = false;
                } elseif ($c === '\\') {
                    $escape = true;
                } elseif ($c === '"') {
                    $inString = false;
                }
                continue;
            }
            if ($c === '"') {
                $inString = true;
            } elseif ($c === '{' || $c === '[') {
                $stack .= $c;
                $cuts[] = [$i + 1, $stack];
            } elseif ($c === '}' || $c === ']') {
                $stack = substr($stack, 0, -1);
                if ($stack === '') {
                    return json_decode(substr($s, 0, $i + 1), true) ?: null; // objek sudah lengkap
                }
            } elseif ($c === ',') {
                $cuts[] = [$i, $stack];
            }
        }

        // 1) Coba utuh: tutup string yang terbuka (buang escape setengah jadi) lalu semua pembuka.
        $body = $s;
        if ($inString) {
            $body = preg_replace('/\\\\(u[0-9a-fA-F]{0,3})?$/', '', $body) . '"';
        }
        if (($data = self::close($body, $stack)) !== null) {
            return $data;
        }
        // 2) Potong mundur ke batas aman (maksimal beberapa kali supaya tetap cepat).
        foreach (array_slice(array_reverse($cuts), 0, 8) as [$pos, $openers]) {
            if (($data = self::close(substr($s, 0, $pos), $openers)) !== null) {
                return $data;
            }
        }
        return null;
    }

    private static function close(string $body, string $openers): ?array
    {
        $closers = strtr(strrev($openers), ['{' => '}', '[' => ']']);
        $data = json_decode(rtrim($body, " \t\r\n,:") . $closers, true);
        return is_array($data) ? $data : null;
    }
}
