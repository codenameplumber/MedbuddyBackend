<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\Prescription;
use App\Services\AdherenceService;
use App\Services\AnalyticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Renders the competition submission to PDF.
 *
 *   php artisan medbuddy:submission
 *
 * The figures in the document are read from the live database rather than
 * typed in, so a reseed cannot leave the submission quoting numbers a judge
 * will not see on screen. That is the whole reason this is a command and not
 * a file someone edits by hand.
 */
class BuildSubmission extends Command
{
    protected $signature = 'medbuddy:submission
                            {--out= : Where to write the PDF (default: MedBuddy-Submission.pdf)}
                            {--html : Only write the HTML, do not render}';

    protected $description = 'Build the submission PDF from the live cohort figures';

    /** Where Chrome lives on each platform. First hit wins. */
    private const CHROME = [
        'C:\Program Files\Google\Chrome\Application\chrome.exe',
        'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
        'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
        'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
        '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        '/usr/bin/google-chrome',
        '/usr/bin/chromium',
    ];

    public function handle(AnalyticsService $analytics, AdherenceService $adherence): int
    {
        $source = storage_path('app/submission/index.html');

        if (! File::exists($source)) {
            $this->error("Missing template: {$source}");

            return self::FAILURE;
        }

        $this->line('  Reading the cohort…');
        $figures = $this->figures($analytics, $adherence);

        foreach ($figures as $token => $value) {
            $this->line(sprintf('    %-22s %s', $token, $value));
        }

        // The template carries {{TOKENS}} the live numbers replace.
        $html = File::get($source);
        foreach ($figures as $token => $value) {
            $html = str_replace('{{'.$token.'}}', (string) $value, $html);
        }

        $out = $this->option('out') ?: base_path('MedBuddy-Submission.pdf');

        if ($this->option('html')) {
            File::put($staged = storage_path('app/submission/rendered.html'), $html);
            $this->info("HTML written to {$staged}");

            return self::SUCCESS;
        }

        /*
         | Rendered from a file:// URL so this needs no running web server.
         | The fonts are referenced relative to the template, which is why it
         | lives under storage/app/submission rather than being generated to a
         | temp directory.
         */
        File::put($staged = storage_path('app/submission/rendered.html'), $html);

        $chrome = collect(self::CHROME)->first(fn ($p) => File::exists($p));

        if (! $chrome) {
            $this->warn('  No Chrome or Edge found, so the PDF was not rendered.');
            $this->line("  The HTML is at {$staged} — open it and print to PDF.");

            return self::SUCCESS;
        }

        $this->line('  Rendering with '.basename($chrome).'…');

        $process = new Process([
            $chrome,
            '--headless', '--disable-gpu', '--no-sandbox',
            '--run-all-compositor-stages-before-draw',
            '--virtual-time-budget=15000',
            '--allow-file-access-from-files',
            '--no-pdf-header-footer',
            '--print-to-pdf='.$out,
            'file:///'.str_replace('\\', '/', $staged),
        ]);

        $process->setTimeout(120);
        $process->run();

        if (! File::exists($out)) {
            $this->error('  Chrome produced no file. Output:');
            $this->line('  '.trim($process->getErrorOutput() ?: $process->getOutput()));

            return self::FAILURE;
        }

        File::delete($staged);

        $pdf = File::get($out);
        preg_match_all('/\/Type\s*\/Page[^s]/', $pdf, $pages);

        $this->newLine();
        $this->info(sprintf(
            '  %s — %d pages, %s',
            basename($out),
            count($pages[0]),
            number_format(strlen($pdf) / 1024, 0).' KB',
        ));

        if (count($pages[0]) !== 9) {
            $this->warn('  Expected 9 pages. Content is overflowing a sheet — check the template.');
        }

        return self::SUCCESS;
    }

    /** Every number the document quotes, straight from the database. */
    private function figures(AnalyticsService $analytics, AdherenceService $adherence): array
    {
        $cohort = $analytics->cohort();
        $stats = $analytics->cohortStats($cohort);
        $paired = $adherence->morningVsEvening($cohort, 30);
        $funnel = collect($analytics->deliveryFunnel())->keyBy('label');

        return [
            'PATIENTS'    => $stats['enrolled'],
            'ADHERENCE7'  => $stats['adherence7'],
            'ATTENTION'   => $stats['needsAttention'],
            'RESPOND'     => $stats['respondRate'],
            'PRESCRIPTIONS' => Prescription::where('active', true)->count(),
            'REPORTS'     => \App\Models\PatientReport::count(),
            'REMINDERS'   => $funnel['Sent']['value'] ?? 0,
            'DOSE_EVENTS' => number_format(\App\Models\DoseEvent::count()),
            'MORNING'     => $paired['morning'] ?? 0,
            'EVENING'     => $paired['evening'] ?? 0,
            'PAIRED_GAP'  => $paired['gap'] ?? 0,
            'PAIRED_N'    => $paired['n'] ?? 0,
            'LANGUAGES'   => count(config('medbuddy.languages')),
            'STAFF'       => \App\Models\User::count(),
            'SIGNALS'     => count($analytics->signals()),
            'VOICE_NOTES' => \App\Models\PatientReport::whereNotNull('audio_path')->count(),
            'SILENT'      => Patient::whereNull('last_response_at')
                                ->orWhere('last_response_at', '<', now()->subDays(3))->count(),
        ];
    }
}
