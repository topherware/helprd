<?php
/** Bantuan bersama untuk tes CLI (tanpa database dan tanpa API): fixture dibangun dari mode tiruan. */
require __DIR__ . '/../src/helpers.php';

$failures = 0;
function check(string $name, bool $ok, string $detail = ''): void
{
    global $failures;
    echo ($ok ? 'LULUS ' : 'GAGAL ') . $name . ($ok || $detail === '' ? '' : "\n       $detail") . "\n";
    $failures += $ok ? 0 : 1;
}

function finish(): void
{
    global $failures;
    echo $failures ? "\n$failures pengujian gagal.\n" : "\nSemua pengujian lulus.\n";
    exit($failures ? 1 : 0);
}

/** Tiga kasus positif benchmark (katering, jasa kebersihan, peminjaman alat lab), dipakai bersama tools/benchmark.php. */
function cases(): array
{
    static $cases = null;
    return $cases ??= json_decode(file_get_contents(__DIR__ . '/../benchmark/kasus.json'), true);
}

function brief(string $case = 'katering', array $override = []): Brief
{
    return Brief::fromArray(array_merge(cases()[$case], $override));
}

/** [brief, prd, tasks] mode tiruan. */
function fixture(string $case = 'katering', array $override = []): array
{
    $b = brief($case, $override);
    $prd = Pipeline::mockPrd($b);
    return [$b, $prd, Pipeline::mockTasks($b, $prd)];
}

/** ID validator yang punya temuan setelah fixture diubah oleh $mutate(&$prd, &$tasks). */
function failing(?callable $mutate = null, string $case = 'katering', array $override = []): array
{
    [$b, $prd, $tasks] = fixture($case, $override);
    if ($mutate) {
        $mutate($prd, $tasks);
    }
    return array_values(array_unique(array_column(QualityReport::evaluate($b, $prd, $tasks)['temuan'], 'id')));
}
