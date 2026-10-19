<?php

/**
 * P9 KunciPilihan (kritis): stack pilihan pengguna (stacks.json `kunci`) dipakai apa adanya; satu ORM, satu auth, satu database
 * di seluruh bab teknis, CLAUDE.md, .env.example, dan skema. Gaya desain pilihan dipakai di design system (PRD PlanKu).
 */
final class P09KunciPilihan
{
    public const KELUARGA = [
        'ORM' => [
            'Drizzle' => '/drizzle/i', 'Prisma' => '/prisma/i', 'Eloquent' => '/eloquent/i', 'TypeORM' => '/typeorm/i', 'Sequelize' => '/sequelize/i',
            'Mongoose' => '/mongoose/i', 'Kysely' => '/kysely/i', 'SQLAlchemy' => '/sqlalchemy/i', 'Knex' => '/\bknex/i',
        ],
        'autentikasi' => [
            'Auth.js' => '/auth\.js|nextauth|next-auth/i', 'Clerk' => '/\bclerk\b/i', 'Supabase Auth' => '/supabase auth/i', 'Lucia' => '/\blucia\b/i',
            'Better Auth' => '/better[- ]auth/i', 'Firebase Auth' => '/firebase auth/i', 'Auth0' => '/auth0/i', 'Laravel Breeze/Sanctum' => '/sanctum|breeze|fortify|jetstream/i',
            'Passport' => '/passport/i',
        ],
        'database' => [
            'PostgreSQL' => '/postgres|\bneon\b|supabase/i', 'MySQL' => '/mysql|mariadb|planetscale/i', 'SQLite' => '/sqlite|turso|libsql|\bD1\b/i', 'MongoDB' => '/mongo/i',
        ],
    ];

    public static function berlaku(array $c): bool
    {
        return !$c['flags']['uiOnly'];
    }

    public static function check(array $c): array
    {
        $p = $c['paket'];
        $scope = implode("\n", array_filter(QV::sentences($p->section(Paritas::TEKNIS) . "\n" . $p->file('CLAUDE.md') . "\n" . $p->env()), fn ($s) => !QV::negated($s)));
        $out = [];
        foreach (self::KELUARGA as $group => $families) {
            $found = array_keys(array_filter($families, fn ($re) => preg_match($re, $scope)));
            if (count($found) > 1) {
                $out[] = QV::finding('P9', 'K', 7, "Lebih dari satu $group disebut: " . implode(', ', $found) . '.', 'Pakai satu pilihan saja di seluruh dokumen.');
            }
        }
        $locked = Brief::stack($c['brief']->stackChoice);
        if ($locked && !empty($locked['kunci'])) {
            foreach ($locked['kunci'] as $key => $aliases) {
                $hit = array_filter($aliases, fn ($a) => mb_stripos($scope, $a) !== false);
                if (!$hit) {
                    $out[] = QV::finding('P9', 'K', 7, "Bab teknis tidak memakai $key pilihan pengguna (" . implode(' / ', $aliases) . ", stack {$locked['nama']}).",
                        'Pakai stack pilihan pengguna apa adanya.');
                }
            }
        }
        $b = $c['brief'];
        if ($p->data && !in_array($b->designStyle, ['bebas', 'tulis_sendiri'], true)
            && mb_stripos($p->section('/design system|desain/i'), $b->designLabel()) === false) {
            $out[] = QV::finding('P9', 'K', 9, "Design system tidak memakai gaya pilihan pengguna ({$b->designLabel()}).", 'Pakai gaya desain pilihan di bab 9.');
        }
        return $out;
    }
}
