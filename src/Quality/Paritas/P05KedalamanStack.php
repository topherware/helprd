<?php

/**
 * P5 KedalamanStack (mayor): bab teknis memuat versi mayor, pustaka validasi, auth (sesi + hashing), ORM & migrasi,
 * kebijakan zona waktu (hasTimeFields), konfigurasi penjadwal konkret (hasScheduler); dilarang "mis. A" atau "A/B" sebagai keputusan.
 * Mode hanya-UI: hanya versi dan larangan alternatif.
 */
final class P05KedalamanStack
{
    public const VERSI = '/\b(next\.?js|react|vue|nuxt|svelte(?:kit)?|astro|laravel|tailwind(?:\s*css)?|auth\.js|nextauth|prisma|drizzle(?:\s*orm)?|node(?:\.js)?|php|express|hono|remix|django|rails|typescript|zod|vitest|playwright|supabase-js|vite|wrangler)\s+v?\d+(?:\.\d+|\.x)*\b/iu';
    public const VALIDASI = '/\b(zod|valibot|yup|joi|class-validator|ajv|pydantic|express-validator|superstruct|arktype|vinejs|form request|laravel validation)\b/iu';
    public const ALAT = 'vitest|jest|playwright|cypress|mocha|prisma|drizzle|typeorm|mysql|postgresql|postgres|sqlite|mongodb|vercel|netlify|railway|render|fly\.io|fonnte|wablas|resend|sendgrid|mailgun|npm|pnpm|yarn|bun|clerk|auth\.js|lucia|supabase';

    public static function berlaku(array $c): bool
    {
        return true;
    }

    public static function check(array $c): array
    {
        $p = $c['paket'];
        $tech = $p->section(Paritas::TEKNIS) . "\n" . $p->file('CLAUDE.md');
        $flags = $c['flags'];
        $out = [];
        $f = fn ($pesan, $saran = '') => QV::finding('P5', 'M', 7, $pesan, $saran);
        if (!preg_match(self::VERSI, $tech)) {
            $out[] = $f('Versi mayor paket inti tidak disebut (mis. framework, CSS, auth, ORM).', 'Tulis versi mayor tiap paket inti di bab 7.');
        }
        $alt = [];
        foreach (array_merge(Paket::tableRows($tech), array_map(fn ($b) => [$b], Paket::bullets($tech))) as $row) {
            $cell = implode(' | ', $row);
            if (preg_match('/\(mis\.|\bmis\.\s/iu', $cell, $m) || preg_match('#\b(' . self::ALAT . ')\s*/\s*(' . self::ALAT . ')\b#iu', $cell, $m)) {
                $alt[] = trim($m[0]);
            }
        }
        if ($alt) {
            $out[] = $f('Keputusan teknis masih berupa alternatif: "' . implode('", "', array_slice(array_unique($alt), 0, 4)) . '".', 'Pilih satu alat; jangan menulis "mis." atau "A/B" sebagai keputusan.');
        }
        if ($flags['uiOnly']) {
            return $out;
        }
        $missing = [];
        if (!preg_match(self::VALIDASI, $tech)) {
            $missing[] = 'pustaka validasi input';
        }
        if (!preg_match('/migrasi|migration|migrate/iu', $tech)) {
            $missing[] = 'alat ORM & migrasi';
        }
        $login = array_filter(Triggers::roleNames($c['roles']), fn ($r) => !Triggers::isAnonymous($r));
        if ($login && !(preg_match('/hash|bcrypt|argon|scrypt/iu', $tech) && preg_match('/sesi|session|jwt|cookie/iu', $tech))) {
            $missing[] = 'strategi autentikasi (jenis sesi + hashing)';
        }
        if ($flags['hasTimeFields'] && !(preg_match('/\butc\b/iu', $tech) && preg_match('/zona|timezone|time zone|asia\/|\bwib\b/iu', $tech)
            && preg_match('/date-fns-tz|dayjs|luxon|moment|\bintl\b|temporal|carbon|zoneinfo|pytz/iu', $tech))) {
            $missing[] = 'kebijakan zona waktu (simpan UTC, tampil zona lokal, pustaka)';
        }
        if ($flags['hasScheduler']) {
            $cron = [
                'berkas konfigurasi' => '/vercel\.json|wrangler\.toml|crontab|node-cron|cron\.ya?ml|\.github\/workflows|kernel\.php|routes\/console\.php|pg_cron|cloud scheduler|eventbridge/iu',
                'interval' => '/\*\/\d+|\d+\s+\*\s+\*|@hourly|@daily|setiap\s+\d+\s+(menit|jam)|every\s+\d+/iu',
                'perlindungan rahasia' => '/cron_secret|secret|rahasia|bearer|signature/iu',
                'idempotensi' => '/idempoten|tidak (dikirim|terkirim) (ganda|dua kali)|dobel kirim|tidak dobel|sent_at|dikirim_pada|terkirim_pada|sudah (terkirim|dikirim)|reminder_status|status pengingat/iu',
            ];
            $lack = array_keys(array_filter($cron, fn ($re) => !preg_match($re, $tech)));
            if ($lack) {
                $missing[] = 'penjadwal (' . implode(', ', $lack) . ')';
            }
        }
        if ($missing) {
            $out[] = $f('Bab teknis belum memuat: ' . implode('; ', $missing) . '.', 'Lengkapi Rincian Teknis bab 7 sesuai stacks.json untuk stack terpilih.');
        }
        return $out;
    }
}
