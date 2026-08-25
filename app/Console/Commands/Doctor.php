<?php

namespace App\Console\Commands;

use App\Models\Consent;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Reminder;
use App\Services\AiService;
use App\Services\ScheduleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * System self-check.
 *
 * Answers "is this actually wired up?" rather than "does it compile?". Every
 * line prints a fact read from the running system, not a claim.
 *
 *   php artisan medbuddy:doctor
 */
class Doctor extends Command
{
    protected $signature = 'medbuddy:doctor';
    protected $description = 'Check what is wired, what is dormant, and what will not fire';

    public function handle(ScheduleService $schedule, AiService $ai): int
    {
        $problems = 0;

        // ── Delivery path ────────────────────────────────────────────
        $this->line("\n<fg=cyan>DELIVERY PATH</>");

        $mailer = config('mail.default');
        $this->kv('Mailer', $mailer.($mailer === 'log' ? '  (writes to storage/logs, does not send)' : ''));
        if ($mailer === 'log') {
            $this->warnLine('Email is not actually leaving the machine. Set MAIL_MAILER to smtp.');
            $problems++;
        }

        if ($mailer === 'smtp') {
            $host = (string) config('mail.mailers.smtp.host');
            $user = (string) config('mail.mailers.smtp.username');

            $this->kv('SMTP host', $host.':'.config('mail.mailers.smtp.port')
                .(str_contains($host, 'mailtrap') ? '  (Mailtrap sandbox — real SMTP, one inbox)' : ''));
            $this->kv('SMTP credentials', $user !== '' ? 'set ('.$user.')' : 'MISSING');

            if ($user === '' || config('mail.mailers.smtp.password') === null || config('mail.mailers.smtp.password') === '') {
                $this->warnLine('SMTP is selected but has no credentials. Every send will throw. Paste MAIL_USERNAME and MAIL_PASSWORD.');
                $problems++;
            }

            $this->kv('From address', config('mail.from.address'));
            $this->line('   Verify the path end to end with: <fg=yellow>php artisan medbuddy:mail-test you@example.com</>');
        }

        $queue = config('queue.default');
        $this->kv('Queue connection', $queue);
        $pending = Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0;
        $failed  = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $this->kv('Jobs pending / failed', "{$pending} / {$failed}");
        if ($pending > 0) {
            $this->warnLine("{$pending} job(s) waiting. Nothing sends without: php artisan queue:work");
            $problems++;
        }
        if ($failed > 0) {
            $this->warnLine("{$failed} failed job(s). Inspect with: php artisan queue:failed");
            $problems++;
        }

        // ── Would a reminder fire right now? ─────────────────────────
        $this->line("\n<fg=cyan>SCHEDULER WINDOW MATCH</>");
        $now = now();
        $this->kv('Server time', $now->format('H:i').' ('.config('app.timezone').')');

        $windows = [];
        foreach (Patient::with('activePrescriptions')->get() as $p) {
            foreach ($schedule->windowsFor($p) as $t) {
                $windows[$t] = ($windows[$t] ?? 0) + 1;
            }
        }
        ksort($windows);

        $wouldFire = 0;
        foreach ($windows as $time => $patients) {
            [$h, $m] = array_map('intval', explode(':', $time));
            $due = $now->copy()->setTime($h, $m);
            $diff = (int) $now->diffInMinutes($due, false);
            $fires = $diff <= 0 && $diff >= -20;
            if ($fires) {
                $wouldFire += $patients;
            }
            $this->line(sprintf(
                '    %s  %2d patients  %+5d min  %s',
                $time, $patients, $diff,
                $fires ? '<fg=green>WOULD SEND</>' : '<fg=gray>outside window</>'
            ));
        }
        $this->kv('Reminders that would send now', (string) $wouldFire);
        if ($wouldFire === 0) {
            $this->line('    <fg=gray>Nothing is due this minute. That is normal — the job runs every 15.</>');
        }

        // ── Consent gate ─────────────────────────────────────────────
        $this->line("\n<fg=cyan>CONSENT GATE</>");
        $total = Patient::count();
        $withEmail = Patient::whereNotNull('email')->count();
        $messaging = Patient::all()->filter(fn ($p) => $p->hasConsent('messaging', 'mail'))->count();
        $research  = Patient::all()->filter(fn ($p) => $p->hasConsent('research'))->count();

        $this->kv('Patients', (string) $total);
        $this->kv('With an email address', "{$withEmail} / {$total}");
        $this->kv('Messaging consent (mail)', "{$messaging} / {$total}");
        $this->kv('Research consent', "{$research} / {$total}");
        if ($withEmail < $total) {
            $this->warnLine(($total - $withEmail).' patient(s) can never be reached on the only enabled channel.');
            $problems++;
        }

        // ── Language coverage ────────────────────────────────────────
        //
        // Laravel falls back to English for a missing key, which is the right
        // runtime behaviour and a terrible reporting one: the app looks
        // finished while half of it speaks the wrong language to the person
        // least able to cope with that.
        $this->line("\n<fg=cyan>LANGUAGE COVERAGE</>");

        $flat = function (array $items, string $prefix = '') use (&$flat) {
            $out = [];
            foreach ($items as $k => $v) {
                $key = $prefix ? "$prefix.$k" : $k;
                if (is_array($v)) {
                    $out += $flat($v, $key);
                } else {
                    $out[$key] = $v;
                }
            }

            return $out;
        };

        $worst = 0;
        foreach (['me', 'respond', 'reminder'] as $file) {
            $en = $flat(require lang_path("en/{$file}.php"));
            foreach (array_keys(config('medbuddy.languages')) as $lang) {
                if ($lang === 'en') {
                    continue;
                }
                $path = lang_path("{$lang}/{$file}.php");
                $have = is_file($path) ? $flat(require $path) : [];
                $worst = max($worst, count(array_diff_key($en, $have)));
            }
        }

        $this->kv('Languages', implode(', ', config('medbuddy.languages')));
        $this->kv('Strings falling back to English', $worst.' (worst language)');

        if ($worst > 0) {
            $this->warnLine("{$worst} strings render in English to someone who chose not to read English.");
            $this->line('   List them: <fg=yellow>php artisan medbuddy:i18n --missing=yo</>');
            $problems++;
        }

        // ── Orphan reminders ─────────────────────────────────────────
        $this->line("\n<fg=cyan>DATA INTEGRITY</>");
        $orphans = 0;
        foreach (Reminder::with('patient.activePrescriptions')->get() as $r) {
            $match = $r->patient?->activePrescriptions
                ->first(fn ($rx) => in_array($r->dose_time, $rx->times ?? [], true));
            if (! $match) {
                $orphans++;
            }
        }
        $this->kv('Reminders with no matching prescription', (string) $orphans);
        if ($orphans > 0) {
            $this->warnLine('Those responses cannot be attributed to a medicine; the report saves with a null prescription.');
            $problems++;
        }

        $noRx = Patient::doesntHave('activePrescriptions')->count();
        $this->kv('Patients with no active prescription', (string) $noRx);

        // ── AI ───────────────────────────────────────────────────────
        $this->line("\n<fg=cyan>AI LAYER</>");
        $this->kv('Provider', 'Anthropic (Claude), via anthropic-ai/sdk');
        $this->kv('Model', config('services.anthropic.model'));
        $this->kv('Mode', config('services.anthropic.mode'));
        $this->kv('Key present', filled(config('services.anthropic.key')) ? 'yes' : 'no');

        if (config('services.anthropic.mode') === 'live' && ! filled(config('services.anthropic.key'))) {
            $this->warnLine('Mode is live but there is no ANTHROPIC_API_KEY. Every screen still works — it falls back to mock answers.');
            $problems++;
        }
        $this->kv('Live', $ai->isLive() ? 'yes' : 'no (mock responses)');

        // Which screens actually call it, rather than whether it compiles.
        $callers = [];
        foreach (glob(app_path('Livewire/*/*.php')) as $component) {
            $src = (string) file_get_contents($component);
            if (str_contains($src, 'AiService')) {
                $callers[] = class_basename($component, '.php');
            }
        }
        $this->kv('Reachable from a UI', $callers ? implode(', ', $callers) : 'NO');
        if (! $callers) {
            $this->warnLine('AiService is tested but no screen calls it.');
            $problems++;
        }

        // ── Write paths ──────────────────────────────────────────────
        $this->line("\n<fg=cyan>WRITE PATHS</>");
        $writes = [
            'Patient'      => 'enrol a patient',
            'Prescription' => 'issue a prescription',
            'Consent'      => 'record or revoke consent',
            'User'         => 'add a staff member',
        ];
        $sources = array_merge(
            glob(app_path('Livewire/*/*.php')),
            glob(app_path('Http/Controllers/*.php')),
        );

        foreach ($writes as $model => $what) {
            $where = [];
            foreach ($sources as $file) {
                $src = (string) file_get_contents($file);
                if (str_contains($src, $model.'::create')
                    || str_contains($src, $model.'::updateOrCreate')
                    || str_contains($src, $model.'::firstOrNew')) {
                    $where[] = class_basename($file, '.php');
                }
            }
            $this->line(sprintf(
                '    %-14s %s',
                $model,
                $where ? '<fg=green>'.implode(', ', array_unique($where)).'</>' : "<fg=red>NO UI</> — cannot {$what}"
            ));
            if (! $where) {
                $problems++;
            }
        }

        // ── Patient app ──────────────────────────────────────────────
        $this->line("\n<fg=cyan>PATIENT APP</>");
        $reachablePatients = Patient::whereNotNull('email')->count();
        $this->kv('Can sign in (has an email)', $reachablePatients.' / '.$total);
        $this->kv('Screens', count(array_filter(
            \Illuminate\Support\Facades\Route::getRoutes()->getRoutesByName(),
            fn ($n) => str_starts_with($n, 'me.'),
            ARRAY_FILTER_USE_KEY
        )).' routes under /me');

        $voice = \App\Models\PatientReport::whereNotNull('audio_path')->count();
        $orphanAudio = 0;
        foreach (\App\Models\PatientReport::whereNotNull('audio_path')->get() as $r) {
            if (! \Illuminate\Support\Facades\Storage::disk('voice')->exists($r->audio_path)) {
                $orphanAudio++;
            }
        }
        $this->kv('Voice notes stored', (string) $voice);
        if ($orphanAudio > 0) {
            $this->warnLine("{$orphanAudio} report(s) name an audio file that is not on disk.");
            $problems++;
        }

        // ── Summary ──────────────────────────────────────────────────
        $this->line('');
        if ($problems === 0) {
            $this->info('No blocking gaps found.');
        } else {
            $this->warn("{$problems} gap(s) found. Details above.");
        }

        return self::SUCCESS;
    }

    private function kv(string $k, string $v): void
    {
        $this->line(sprintf('    %-38s <fg=white>%s</>', $k, $v));
    }

    private function warnLine(string $msg): void
    {
        $this->line("    <fg=yellow>!</>  <fg=yellow>{$msg}</>");
    }
}
