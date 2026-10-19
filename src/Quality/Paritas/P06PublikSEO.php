<?php

/** P6 PublikSEO (minor): halaman publik punya judul/meta, tag sosial, sitemap/robots, noindex privat, optimasi gambar, cache; maks. 8 butir. */
final class P06PublikSEO
{
    public const BUTIR = [
        'judul & meta dinamis' => '/meta description|<title>|generatemetadata|judul (halaman )?dinamis|title dinamis|meta tag/iu',
        'tag sosial (Open Graph)' => '/open graph|\bog:|\bog\b|twitter card/iu',
        'sitemap & robots' => '/sitemap|robots/iu',
        'noindex halaman privat' => '/noindex/iu',
        'optimasi gambar' => '/webp|avif|optimasi gambar|gambar dioptimalkan|next\/image|<image>|lazy/iu',
        'cache/revalidasi data publik' => '/cache|caching|revalidat|\bisr\b|\bcdn\b/iu',
    ];

    public static function berlaku(array $c): bool
    {
        return $c['flags']['hasPublicPages'];
    }

    public static function check(array $c): array
    {
        $text = $c['paket']->prd();
        $missing = array_keys(array_filter(self::BUTIR, fn ($re) => !preg_match($re, $text)));
        $out = [];
        if ($missing) {
            $out[] = QV::finding('P6', 'm', 2, 'Syarat SEO/performa halaman publik belum ada: ' . implode(', ', $missing) . '.', 'Tambahkan NFR SEO & performa (maks. 8 butir).');
        }
        $seo = $c['paket']->data['prd']['requirements']['seo'] ?? [];
        if (count($seo) > 8) {
            $out[] = QV::finding('P6', 'm', 2, 'Butir SEO & performa ada ' . count($seo) . ' (maksimal 8).');
        }
        return $out;
    }
}
