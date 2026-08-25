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
        $this->line('  Mailer      <fg=yellow>'.config('mail.default').'</> via '
            .config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port'));
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
            $reminder = Reminder::create([
                'patient_id' => $patient->id,
                'dose_date'  => today()->toDateString(),
                'dose_time'  => $patient->activePrescriptions->first()->times[0] ?? '08:00',
                'slot'       => 'morning',
                'channel'    => 'mail',
                'to'         => $to,
                'sent_at'    => now(),
            ]);
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
            $this->line('  Most common causes, in order:');
            $this->line('   1. MAIL_USERNAME / MAIL_PASSWORD are empty, or from a different Mailtrap inbox.');
            $this->line('   2. MAIL_PORT is blocked outbound. Mailtrap also accepts 587 and 25.');
            $this->line('   3. Config is cached from before you edited .env — run: php artisan config:clear');

            return self::FAILURE;
        }

        DB::rollBack();

        $this->newLine();
        $this->info(config('mail.default') === 'log'
            ? 'Mailer is "log": nothing left the machine. Look in storage/logs/laravel.log.'
            : 'Both messages were handed to the SMTP server. Open the inbox to read them.');

        return self::SUCCESS;
    }

    private function send(string $label, callable $fn): void
    {
        $fn();
        $this->line("  <fg=green>sent</>  {$label}");
    }
}
