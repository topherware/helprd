<?php

/** Panggil Claude (Anthropic Messages API) lewat cURL. Semua panggilan memakai streaming. */
final class AiClient
{
    /** Kode AiException untuk koneksi yang putus di tengah jalan (boleh dicoba ulang). */
    public const NETWORK = 599;
    /** Jawaban AI selesai tetapi bukan JSON yang bisa dibaca (dicoba ulang sekali oleh streaming PRD). */
    public const BAD_JSON = 598;

    /** Jumlah percobaan ulang otomatis bila koneksi ke AI putus (AI_NET_RETRIES, default 2). */
    public static function retries(): int
    {
        return max(0, (int) env('AI_NET_RETRIES', 2));
    }

    /**
     * @return array{0: array, 1: int, 2: int} [data JSON, input_tokens, output_tokens]
     */
    public static function json(string $system, string $prompt, ?int $maxTokens = null): array
    {
        for ($attempt = 0; ; $attempt++) {
            try {
                [$text, $in, $out] = self::stream($system, $prompt, $maxTokens, fn () => null);
                return [self::decode($text), $in, $out];
            } catch (AiException $e) {
                if ($e->getCode() !== self::NETWORK || $attempt >= self::retries()) {
                    throw $e;
                }
                log_error('Koneksi AI putus, dicoba ulang: ' . $e->getMessage());
            }
        }
    }

    /**
     * Streaming teks jawaban Claude.
     * $onText(string $potongan) dipanggil untuk tiap potongan teks; $onTick() dipanggil berkala (±1 detik)
     * selama koneksi terbuka, dan bila mengembalikan false permintaan dibatalkan.
     * Streaming juga menjaga koneksi tetap hidup lewat proxy (mis. Cloudflare memutus setelah ~100 detik diam).
     *
     * @return array{0: string, 1: int, 2: int} [teks lengkap, input_tokens, output_tokens]
     */
    public static function stream(string $system, string $prompt, ?int $maxTokens, callable $onText, ?callable $onTick = null): array
    {
        $key = env('API_KEY');
        if (!$key) {
            throw new AiException('API_KEY belum diisi di .env');
        }
        $url = rtrim(env('API_BASE_URL', 'https://api.anthropic.com'), '/') . '/v1/messages';

        $body = [
            'model' => env('API_MODEL', 'claude-opus-5'),
            'max_tokens' => $maxTokens ?? (int) env('MAX_TOKENS', 16000),
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            // Bila permintaan ditolak filter keamanan, API mengulang otomatis di model cadangan.
            'fallbacks' => 'default',
            'stream' => true,
        ];
        // Effort lebih rendah = waktu "berpikir" lebih singkat, sehingga jawaban panjang lebih cepat selesai.
        if (in_array(env('AI_EFFORT'), ['low', 'medium', 'high', 'xhigh', 'max'], true)) {
            $body['output_config'] = ['effort' => env('AI_EFFORT')];
        }

        $r = ['text' => '', 'in' => 0, 'out' => 0, 'stop' => '', 'stop_details' => null, 'error' => null];
        self::sse($url, [
            'x-api-key: ' . $key,
            'anthropic-version: 2023-06-01',
            'anthropic-beta: server-side-fallback-2026-07-01',
        ], $body, function (array $event) use (&$r, $onText) {
            switch ($event['type'] ?? '') {
                case 'message_start':
                    $r['in'] = (int) ($event['message']['usage']['input_tokens'] ?? 0);
                    break;
                case 'content_block_delta':
                    if (($event['delta']['type'] ?? '') === 'text_delta') {
                        $r['text'] .= $event['delta']['text'];
                        $onText($event['delta']['text']);
                    }
                    break;
                case 'message_delta':
                    $r['stop'] = $event['delta']['stop_reason'] ?? $r['stop'];
                    $r['stop_details'] = $event['delta']['stop_details'] ?? null;
                    $r['in'] = (int) ($event['usage']['input_tokens'] ?? $r['in']);
                    $r['out'] = (int) ($event['usage']['output_tokens'] ?? $r['out']);
                    break;
                case 'error':
                    $r['error'] = $event['error'] ?? $event;
                    break;
            }
        }, $onTick);

        if ($r['error']) {
            throw new AiException('Error dari API saat streaming: ' . json_encode($r['error'], JSON_UNESCAPED_UNICODE));
        }
        if ($r['stop'] === 'refusal') {
            throw new AiException('Claude menolak permintaan: ' . json_encode($r['stop_details'], JSON_UNESCAPED_UNICODE));
        }
        if ($r['stop'] === 'max_tokens') {
            throw new AiException('Jawaban Claude terpotong (max_tokens). Naikkan batas token.');
        }
        if ($r['stop'] === '') {
            throw new AiException('Stream terputus sebelum jawaban selesai (' . mb_strlen($r['text']) . ' karakter diterima).', self::NETWORK);
        }
        return [$r['text'], $r['in'], $r['out']];
    }

    /** Ambil objek JSON dari teks AI (buang ```json fence atau teks di sekitarnya). */
    public static function decode(string $text): array
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        $data = $start !== false && $end > $start ? json_decode(PartialJson::sanitize(substr($text, $start, $end - $start + 1)), true) : null;
        if (!is_array($data)) {
            throw new AiException(sprintf('AI tidak mengembalikan JSON valid (%d karakter, %s): %s … %s',
                mb_strlen($text), json_last_error_msg(), mb_substr($text, 0, 300), mb_substr($text, -300)), self::BAD_JSON);
        }
        return $data;
    }

    /** POST dengan respons Server-Sent Events; $onEvent dipanggil untuk tiap baris "data: {...}". */
    private static function sse(string $url, array $headers, array $body, callable $onEvent, ?callable $onTick): void
    {
        $buffer = '';
        $head = ''; // awal respons mentah, untuk pesan error
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => array_merge(['Content-Type: application/json'], $headers),
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 900,
            // Proxy kadang menahan koneksi tanpa mengirim apa pun. Claude normalnya mengirim data (ping/thinking)
            // tiap beberapa detik, jadi 60 detik tanpa satu byte pun dianggap macet → error jaringan → dicoba ulang.
            CURLOPT_LOW_SPEED_LIMIT => 1,
            CURLOPT_LOW_SPEED_TIME => (int) env('AI_STALL_SECONDS', 60),
            CURLOPT_WRITEFUNCTION => function ($ch, string $chunk) use (&$buffer, &$head, $onEvent) {
                if (strlen($head) < 2000) {
                    $head .= $chunk;
                }
                $buffer .= $chunk;
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $line = rtrim(substr($buffer, 0, $pos), "\r");
                    $buffer = substr($buffer, $pos + 1);
                    if (str_starts_with($line, 'data:')) {
                        $event = json_decode(trim(substr($line, 5)), true);
                        if (is_array($event)) {
                            $onEvent($event);
                        }
                    }
                }
                return strlen($chunk);
            },
        ]);
        if ($onTick) {
            // Dipanggil cURL sekitar sekali per detik; nilai bukan nol membatalkan transfer.
            // (CURLOPT_PROGRESSFUNCTION dipakai karena CURLOPT_XFERINFOFUNCTION baru ada di PHP 8.2.)
            curl_setopt($ch, CURLOPT_NOPROGRESS, false);
            curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, fn () => $onTick() === false ? 1 : 0);
        }
        $ok = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($ok === false) {
            // Dibatalkan oleh $onTick (klien pergi) bukan error jaringan, jadi tidak dicoba ulang.
            throw new AiException("cURL gagal: $error", stripos($error, 'abort') !== false ? 0 : self::NETWORK);
        }
        if ($status >= 400) {
            throw new AiException("HTTP $status dari $url: " . mb_substr($head, 0, 1000), $status);
        }
    }
}
