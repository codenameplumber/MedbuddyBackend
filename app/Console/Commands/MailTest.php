<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\Reminder;
use App\Notifications\DoseReminder;
use App\Notifications\PatientSignInLink;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Proves the delivery path with a real send.
 *
 *   php artisan medbuddy:mail-test you@example.com
 *
 * `medbuddy:doctor` can only report what is configured. This actually opens
 * the SMTP connection and hands the message over, which is the only way to
 * find out that a password is wrong, a port is blocked, or a from-address is
 * rejected — all of which look perfectly healthy in config.
 *
 * It sends the two messages the product depends on, not a "hello world":
 * a dose reminder (the one the response rate rides on) and a patient sign-in
 * link, both rendered from real records so the Blade views, the signed links
 * and the translations are exercised too.
 */
class MailTest extends Command
{
    protected $signature = 'medbuddy:mail-test
                            {to : The mailbox to send to}
                            {--patient= : Use this patient id rather than the first one}
                            {--lang= : Force a language (en, yo, ha, ig) to check the diacritics survive}';

    protected $description = 'Send a real reminder and sign-in link, to prove the mail path works';

    public function handle(): int
    {
        $to = $this->argument('to');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error("\"{$to}\" is not an email address.");

            return self::FAILURE;
        }

        $patient = $this->option('patient')
            ? Patient::with('activePrescriptions')->find($this->option('patient'))
            : Patient::with('activePrescriptions')->whereHas('activePrescriptions')->first();

        if (! $patient) {
            $this->error('No patient with an active prescription to build a message from. Run: php artisan db:seed');

            return self::FAILURE;
        }

        if ($lang = $this->option('lang')) {
            $patient->lang = $lang;
        }

        $this->newLine();
        $mailer = (string) config('mail.default');

        $this->line('  Mailer      <fg=yellow>'.$mailer.'</>'.match ($mailer) {
            'smtp'   => ' via '.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'),
            'resend' => ' — Resend HTTP API'.(filled(config('services.resend.key')) ? '' : '  <fg=red>(no RESEND_API_KEY)</>'),
            'log'    => ' — writes to storage/logs, sends nothing',
            default  => '',
        });
        $this->line('  From        '.config('mail.from.address'));
        $this->line('  To          <fg=yellow>'.$to.'</>');
        $this->line('  As patient  '.$patient->name.' ('.$patient->langName().')');
        $this->newLine();

        /*
         | A dose reminder signs its response link against a real Reminder id
         | and writes the sent body back to that row, so the row has to exist.
         | It is created inside a transaction that is always rolled back: by
         | the time we roll back the message is already with the SMTP server,
         | and a test message left behind would be counted by the outbox funnel
         | as a reminder a patient never received.
         */
        DB::beginTransaction();

        try {
            $time = $patient->activePrescriptions->first()->times[0] ?? '08:00';

            // One reminder per patient per dose window is a unique index, and
            // this patient very often already has today's. Take the row over
            // rather than colliding with it — the transaction puts the
            // original back, so the outbox is untouched either way. Creating
            // blind here made the command fail with a constraint violation
            // that read like an SMTP problem.
            $reminder = Reminder::updateOrCreate(
                [
                    'patient_id' => $patient->id,
                    'dose_date'  => today()->toDateString(),
                    'dose_time'  => $time,
                ],
                [
                    'slot'    => 'morning',
                    'channel' => 'mail',
                    'to'      => $to,
                    'sent_at' => now(),
                ],
            );
            $reminder->setRelation('patient', $patient);

            // sendNow, not notify: these notifications are queued in production,
            // and a test that only proves the job was enqueued proves nothing
            // about SMTP.
            $this->send('dose reminder', fn () => Notification::route('mail', $to)
                ->notifyNow(new DoseReminder($reminder)));

            $this->send('sign-in link', fn () => Notification::route('mail', $to)
                ->notifyNow(new PatientSignInLink($patient)));
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->line('  <fg=red>failed</>');
            $this->error('  '.$e->getMessage());
            $this->newLine();
            $this->advise($e, (string) config('mail.default'));

            return self::FAILURE;
        }

        DB::rollBack();

        $this->newLine();
        $this->info(match ((string) config('mail.default')) {
            'log'    => 'Mailer is "log": nothing left the machine. Look in storage/logs/laravel.log.',
            'resend' => 'Resend accepted both messages. Check the inbox, and https://resend.com/emails for delivery.',
            default  => 'Both messages were handed to the mail server. Open the inbox to read them.',
        });

        return self::SUCCESS;
    }

    /**
     * Say the thing that is actually wrong.
     *
     * The first version printed the Mailtrap SMTP checklist under every
     * failure, including a Resend API error and a database constraint
     * violation — which is the most misleading output it could produce, at the
     * moment someone is least able to tell.
     */
    private function advise(\Throwable $e, string $mailer): void
    {
        $message = $e->getMessage();

        // Resend answers over HTTP, so its failures never look like SMTP.
        if ($mailer === 'resend' || str_contains($message, 'Resend')) {
            if (str_contains($message, 'not verified') || str_contains($message, 'domains')) {
                $this->line('  The API key is fine — Resend accepted it. The from-domain is not.');
                $this->line('  Either:');
                $this->line('   1. Verify the domain at <fg=yellow>https://resend.com/domains</> and point');
                $this->line('      MAIL_FROM_ADDRESS at an address on it.');
                $this->line('   2. Or, to test right now with no domain at all, use the Resend sandbox sender:');
                $this->line('        <fg=yellow>MAIL_FROM_ADDRESS=onboarding@resend.dev</>');
                $this->line('      It only delivers to the address that owns the Resend account.');

                return;
            }

            if (str_contains($message, 'API key') || str_contains($message, 'Unauthorized') || str_contains($message, '401')) {
                $this->line('  Resend rejected the key. Check RESEND_API_KEY, then: php artisan config:clear');

                return;
            }

            $this->line('  Resend rejected the request. The message above is theirs, verbatim.');

            return;
        }

        if ($mailer === 'smtp') {
            $this->line('  Most common causes, in order:');
            $this->line('   1. MAIL_USERNAME / MAIL_PASSWORD are empty, or from a different inbox.');
            $this->line('   2. MAIL_PORT is blocked outbound. Mailtrap also accepts 587 and 25.');
            $this->line('   3. Config is cached from before you edited .env — run: php artisan config:clear');

            return;
        }

        $this->line('  That is not a mail transport error — the message never reached the mailer.');
        $this->line('  '.get_class($e));
    }

    private function send(string $label, callable $fn): void
    {
        $fn();
        $this->line("  <fg=green>sent</>  {$label}");
    }
}
