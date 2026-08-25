<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Translation coverage, counted rather than claimed.
 *
 *   php artisan medbuddy:i18n
 *   php artisan medbuddy:i18n --missing=yo
 *
 * Laravel falls back to English for a missing key, which is the right runtime
 * behaviour and a terrible reporting one: the app looks finished while half of
 * it silently speaks the wrong language to the person least able to cope with
 * that. This counts the gap so it cannot hide, and prints the exact keys a
 * native-speaker reviewer has to fill in.
 *
 * A string hardcoded in a Blade view is worse than a missing key — it cannot
 * even be found by this. If you are adding patient-facing copy, it goes in
 * lang/en first.
 */
class Translations extends Command
{
    protected $signature = 'medbuddy:i18n {--missing= : Print every untranslated key for this language}';
    protected $description = 'Report how much of the patient app exists in each language';

    private const FILES = ['me', 'respond', 'reminder'];

    public function handle(): int
    {
        $langs = array_keys(config('medbuddy.languages'));
        $others = array_values(array_diff($langs, ['en']));

        if ($only = $this->option('missing')) {
            return $this->listMissing($only);
        }

        $totalEn = 0;
        $gaps = array_fill_keys($others, 0);

        foreach (self::FILES as $file) {
            $en = $this->flatten($this->load('en', $file));
            $totalEn += count($en);

            $row = ['<fg=cyan>'.$file.'.php</>', count($en)];

            foreach ($others as $lang) {
                $have = $this->flatten($this->load($lang, $file));
                $missing = count(array_diff_key($en, $have));
                $gaps[$lang] += $missing;

                $pct = count($en) ? (int) round((count($en) - $missing) / count($en) * 100) : 100;
                $row[] = $missing === 0
                    ? "<fg=green>{$pct}%</>"
                    : ($pct >= 60 ? "<fg=yellow>{$pct}%</> ({$missing} missing)" : "<fg=red>{$pct}%</> ({$missing} missing)");
            }

            $rows[] = $row;
        }

        $this->newLine();
        $this->table(
            array_merge(['File', 'Keys (en)'], array_map(fn ($l) => config('medbuddy.languages')[$l], $others)),
            $rows,
        );

        $worst = max($gaps);

        if ($worst === 0) {
            $this->info('Every string exists in every language.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->line("  <fg=yellow>!</> {$worst} of {$totalEn} strings fall back to English in the worst-covered language.");
        $this->line('    A patient sees this today. It is the honest failure mode, but it is still English');
        $this->line('    in front of someone who chose not to read English.');
        $this->newLine();
        $this->line('    List them:  <fg=yellow>php artisan medbuddy:i18n --missing=yo</>');

        return self::SUCCESS;
    }

    private function listMissing(string $lang): int
    {
        if (! array_key_exists($lang, config('medbuddy.languages'))) {
            $this->error("Unknown language \"{$lang}\". Known: ".implode(', ', array_keys(config('medbuddy.languages'))));

            return self::FAILURE;
        }

        $any = false;

        foreach (self::FILES as $file) {
            $en = $this->flatten($this->load('en', $file));
            $missing = array_diff_key($en, $this->flatten($this->load($lang, $file)));

            if (! $missing) {
                continue;
            }

            $any = true;
            $this->newLine();
            $this->line("<fg=cyan>lang/{$lang}/{$file}.php</> — ".count($missing).' to add');

            foreach ($missing as $key => $english) {
                $this->line(sprintf("  %-28s %s", $key, \Illuminate\Support\Str::limit((string) $english, 68)));
            }
        }

        if (! $any) {
            $this->info("lang/{$lang} is complete.");
        }

        return self::SUCCESS;
    }

    private function load(string $lang, string $file): array
    {
        $path = lang_path("{$lang}/{$file}.php");

        return is_file($path) ? (array) require $path : [];
    }

    /** @return array<string, string> dotted key => English value */
    private function flatten(array $items, string $prefix = ''): array
    {
        $out = [];

        foreach ($items as $key => $value) {
            $dotted = $prefix ? "{$prefix}.{$key}" : (string) $key;

            if (is_array($value)) {
                $out += $this->flatten($value, $dotted);
            } else {
                $out[$dotted] = $value;
            }
        }

        return $out;
    }
}
