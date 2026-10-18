<?php

/**
 * Batasi jumlah panggilan AI per IP per jam, berdasarkan tabel api_logs.
 * Chat agent punya kuota sendiri (AGENT_RATE_LIMIT_PER_HOUR) supaya tidak menghabiskan kuota dokumen (K9).
 */
final class RateLimiter
{
    public const AGENT = 'agent';

    public static function check(string $ip, string $endpoint = ''): void
    {
        $agent = $endpoint === self::AGENT;
        $limit = $agent ? (int) env('AGENT_RATE_LIMIT_PER_HOUR', 40) : (int) env('RATE_LIMIT_PER_HOUR', 30);
        $count = (int) Database::run(
            'SELECT COUNT(*) FROM api_logs WHERE ip_address = ? AND created_at > NOW() - INTERVAL 1 HOUR AND (endpoint = ?) = ?',
            [$ip, self::AGENT, (int) $agent]
        )->fetchColumn();
        if ($count >= $limit) {
            throw new UserError($agent
                ? "Agent sudah menjawab $limit pertanyaan dalam satu jam terakhir. Istirahat sebentar, lalu coba lagi nanti ya."
                : 'Batas pemakaian AI tercapai. Coba lagi sekitar satu jam lagi.', 429);
        }
    }

    public static function log(?int $projectId, string $ip, string $endpoint, int $inputTokens, int $outputTokens): void
    {
        Database::run(
            'INSERT INTO api_logs (project_id, ip_address, endpoint, input_tokens, output_tokens) VALUES (?, ?, ?, ?, ?)',
            [$projectId, $ip, $endpoint, $inputTokens, $outputTokens]
        );
    }
}
