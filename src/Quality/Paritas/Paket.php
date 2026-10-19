<?php

/**
 * Paket dokumen yang dinilai validator paritas (v1.2.1): PRD.md, features/*.md, TASKS.md, CLAUDE.md, .env.example, db/schema.*.
 * Satu implementasi P1–P10 dipakai untuk PRD acuan (markdown) dan PRD PlanKu (dirakit dari JSON, ditambah data terstruktur).
 */
final class Paket
{
    /**
     * @param array<string, string> $files jalur => isi
     * @param bool $single format satu dokumen (aturan khusus berkas per fitur tidak berlaku)
     * @param array|null $data data terstruktur PlanKu: prd, profil, features (dengan asal), usulan, tasks
     */
    public function __construct(public array $files, public bool $single = false, public ?array $data = null)
    {
    }

    /** Folder PRD acuan (PRD.md + berkas pendamping) atau satu berkas markdown. */
    public static function fromPath(string $path): self
    {
        if (is_file($path)) {
            return new self(['PRD.md' => (string) file_get_contents($path)], true);
        }
        $root = rtrim(str_replace('\\', '/', $path), '/');
        $files = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            $rel = substr(str_replace('\\', '/', $file->getPathname()), strlen($root) + 1);
            if ($file->getSize() <= 2_000_000) {
                $files[$rel] = (string) file_get_contents($file->getPathname());
            }
        }
        ksort($files);
        return new self($files, !self::hasFeatureFiles($files));
    }

    /**
     * Paket dari PRD PlanKu: berkas dirakit dengan renderer yang sama dengan ZIP.
     * @param array $tasks [fitur, title, milestone, koordinasi, status]
     */
    public static function fromPrd(Brief $b, array $prd, array $tasks = []): self
    {
        $files = ['PRD.md' => Documents::renderPrd($b->features, $prd)];
        $byFeature = [];
        foreach ($tasks as $t) {
            $byFeature[$t['fitur']][] = $t;
        }
        $line = fn ($t) => '- [' . (($t['status'] ?? '') === 'selesai' ? 'x' : ' ') . '] ' . (!empty($t['milestone']) ? "[{$t['milestone']}] " : '')
            . $t['title'] . (!empty($t['koordinasi']) ? " → Task {$t['koordinasi']}" : '');
        $tasksMd = "# TASKS — {$b->judul}\n";
        foreach ($b->features as $i => $f) {
            $own = $byFeature[$f['nama']] ?? [];
            $tasksMd .= "\n### {$f['nama']}\n" . implode("\n", array_map($line, $own)) . "\n";
            $files[Documents::featureFilename($i, $f)] = "# {$f['nama']}\n\n## Task\n\n" . implode("\n", array_map($line, $own)) . "\n";
        }
        $files['TASKS.md'] = $tasksMd;
        if (!empty($prd['milestone']['claude_md'])) {
            $files['CLAUDE.md'] = Documents::claudeMd($b->judul, $prd);
        }
        if (empty($prd['profil']['bendera']['uiOnly'])) {
            if (!empty($prd['layanan']['env_example'])) {
                $files['.env.example'] = Documents::envExample($prd['layanan']);
            }
            if (($prd['database']['kode_skema'] ?? '') !== '') {
                $files[Documents::schemaPath($prd['profil']['stack']['bahasa_skema'] ?? 'sql')] = $prd['database']['kode_skema'];
            }
        }
        return new self($files, false, [
            'prd' => $prd,
            'profil' => $prd['profil'] ?? [],
            'features' => $b->features,
            'usulan' => $b->usulan,
            'tasks' => $tasks,
        ]);
    }

    private static function hasFeatureFiles(array $files): bool
    {
        foreach (array_keys($files) as $path) {
            if (str_starts_with($path, 'features/') && str_ends_with($path, '.md')) {
                return true;
            }
        }
        return false;
    }

    /* ---------- Pembaca ---------- */

    public function prd(): string
    {
        return $this->files['PRD.md'] ?? '';
    }

    public function file(string $path): string
    {
        return $this->files[$path] ?? '';
    }

    /** Berkas fitur: jalur => isi. */
    public function featureFiles(): array
    {
        return array_filter($this->files, fn ($p) => str_starts_with($p, 'features/') && str_ends_with($p, '.md'), ARRAY_FILTER_USE_KEY);
    }

    /** Seluruh teks paket (untuk pencarian kata kunci yang boleh muncul di mana saja). */
    public function all(): string
    {
        return implode("\n\n", $this->files);
    }

    /**
     * Isi bagian PRD yang judulnya cocok dengan pola: dari judul sampai judul berikutnya dengan tingkat sama atau lebih tinggi.
     * Judul di dalam blok kode diabaikan.
     */
    public function section(string $pattern, ?string $text = null): string
    {
        $lines = explode("\n", $text ?? $this->prd());
        $out = [];
        $level = 0;
        $fence = false;
        foreach ($lines as $line) {
            if (preg_match('/^\s*(```|~~~)/', $line)) {
                $fence = !$fence;
            }
            if (!$fence && preg_match('/^(#{1,6})\s+(.*)$/', $line, $m)) {
                $l = strlen($m[1]);
                if ($level && $l <= $level) {
                    $level = 0;
                }
                if (!$level && preg_match($pattern, $m[2])) {
                    $level = $l;
                }
            }
            if ($level) {
                $out[] = $line;
            }
        }
        return implode("\n", $out);
    }

    /** Judul-judul tingkat tertentu di dalam teks (di luar blok kode). */
    public static function headings(string $text, int $level): array
    {
        $out = [];
        $fence = false;
        foreach (explode("\n", $text) as $line) {
            if (preg_match('/^\s*(```|~~~)/', $line)) {
                $fence = !$fence;
            }
            if (!$fence && preg_match('/^#{' . $level . '}\s+(.*)$/', $line, $m)) {
                $out[] = trim($m[1]);
            }
        }
        return $out;
    }

    /** Blok kode berpagar: [bahasa, isi]. */
    public static function codeBlocks(string $text): array
    {
        preg_match_all('/^\s*```([\w+-]*)[^\n]*\n(.*?)^\s*```/ms', $text, $m, PREG_SET_ORDER);
        return array_map(fn ($x) => [strtolower($x[1]), $x[2]], $m);
    }

    /** Kode skema & migrasi: blok kode yang mendefinisikan tabel/constraint, ditambah db/schema.* dan berkas migrasi. */
    public function schemaCode(): string
    {
        $parts = [];
        foreach (self::codeBlocks($this->prd()) as [, $code]) {
            if (preg_match('/create\s+table|alter\s+table|pgTable|sqliteTable|mysqlTable|\bmodel\s+\w+\s*\{|exclude\s+using|create\s+(unique\s+)?index|Schema\s*\(/i', $code)) {
                $parts[] = $code;
            }
        }
        foreach ($this->files as $path => $content) {
            if (preg_match('#^(db/schema\.|migrations?/|drizzle/|prisma/)#', $path)) {
                $parts[] = $content;
            }
        }
        return implode("\n\n", array_unique($parts));
    }

    /** Isi .env.example (berkas, atau blok kode env/bash di PRD yang berisi VAR=nilai). */
    public function env(): string
    {
        if (isset($this->files['.env.example'])) {
            return $this->files['.env.example'];
        }
        foreach (self::codeBlocks($this->prd()) as [$lang, $code]) {
            if (in_array($lang, ['env', 'bash', 'dotenv', 'ini', 'sh', ''], true) && preg_match('/^[A-Z][A-Z0-9_]+=/m', $code)) {
                return $code;
            }
        }
        return '';
    }

    /** Butir daftar ("- x" / "1. x") tingkat atas dari sebuah teks, tanpa butir centang. */
    public static function bullets(string $text): array
    {
        preg_match_all('/^(?:-|\*|\d+\.)\s+(?!\[[ x]\])(.+)$/m', $text, $m);
        return array_map('trim', $m[1]);
    }

    /**
     * Tugas terkoordinasi bernomor ("Task M1.2" / "Task 1.2") beserta sub-butirnya.
     * @return array<int, array{id: string, milestone: int, teks: string}>
     */
    public function coordinatedTasks(): array
    {
        if ($this->data && isset($this->data['prd']['milestone']['rencana'])) {
            return array_map(function ($t) {
                $teks = $t['judul'] . "\nBerkas: " . implode(', ', array_map(fn ($f) => "`$f`", $t['berkas'])) . "\nPerilaku: {$t['perilaku']}\nData: " . implode(', ', $t['dataset'])
                    . "\nKeamanan: " . implode(', ', $t['sec']) . "\nSelesai bila: {$t['selesai_bila']}";
                return ['id' => $t['id'], 'milestone' => (int) substr($t['id'], 1), 'teks' => $teks];
            }, $this->data['prd']['milestone']['rencana']);
        }
        $out = [];
        $lines = explode("\n", $this->prd());
        foreach ($lines as $i => $line) {
            if (!preg_match('/^\s*-\s+\[[ x]\]\s+\*\*Task\s+(M?)(\d+)\.(\d+)\b/u', $line, $m)) {
                continue;
            }
            $text = $line;
            for ($j = $i + 1; isset($lines[$j]) && preg_match('/^\s{2,}\S/', $lines[$j]); $j++) {
                $text .= "\n" . $lines[$j];
            }
            $out[] = ['id' => $m[1] . $m[2] . '.' . $m[3], 'milestone' => (int) $m[2], 'teks' => $text];
        }
        return $out;
    }

    /**
     * Butir centang per fitur (Bikin Task): fitur => daftar teks butir. Data terstruktur dipakai lebih dulu.
     * Format satu dokumen tidak punya butir per fitur (dikembalikan kosong).
     */
    public function featureChecklist(): array
    {
        if ($this->data) {
            $out = [];
            foreach ($this->data['tasks'] as $t) {
                $out[$t['fitur']][] = $t['title'] . (!empty($t['koordinasi']) ? " → Task {$t['koordinasi']}" : '');
            }
            return $out;
        }
        $out = [];
        foreach ($this->featureFiles() as $path => $content) {
            $name = preg_match('/^#\s+(.+)$/m', $content, $m) ? trim($m[1]) : basename($path, '.md');
            preg_match_all('/^\s*-\s+\[[ x]\]\s+(?!\*\*Task\b)(.+)$/m', $content, $items);
            $out[$name] = array_map('trim', $items[1]);
        }
        if (!$out && isset($this->files['TASKS.md'])) {
            foreach (preg_split('/^###\s+/m', $this->files['TASKS.md']) as $i => $chunk) {
                if ($i === 0) {
                    continue;
                }
                $name = trim(strtok($chunk, "\n"));
                preg_match_all('/^\s*-\s+\[[ x]\]\s+(.+)$/m', $chunk, $items);
                $out[$name] = array_map('trim', $items[1]);
            }
        }
        return $out;
    }

    /** Blok Master Prompt: blok kode pertama setelah judul "Master … Prompt". */
    public function masterPrompt(): string
    {
        $section = $this->section('/master.*prompt|starter prompt/i');
        $blocks = self::codeBlocks($section);
        return $blocks[0][1] ?? trim(preg_replace('/^#.*$/m', '', $section));
    }

    /** Baris tabel markdown: daftar sel per baris (tanpa baris pemisah). */
    public static function tableRows(string $text): array
    {
        $rows = [];
        foreach (explode("\n", $text) as $line) {
            if (preg_match('/^\s*\|(.+)\|\s*$/', $line, $m) && !preg_match('/^\s*\|[\s:|-]+\|\s*$/', $line)) {
                $rows[] = array_map(fn ($c) => trim(str_replace('\|', '|', $c)), preg_split('/(?<!\\\\)\|/', $m[1]));
            }
        }
        return $rows;
    }
}
