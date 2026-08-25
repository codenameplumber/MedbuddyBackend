<?php

namespace App\Notifications;

use App\Models\Reminder;
use App\Services\ReminderComposer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * The dose reminder.
 *
 * Adding SMS later is one line in via(): ['mail', 'vonage']. That is the whole
 * reason email-only today is a simplification rather than technical debt.
 */
class DoseReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Reminder $reminder)
    {
        // Sets the locale for everything inside the view. Without it the
        // subject came out in Yoruba over an English button, because the
        // composer passes a locale explicitly and the Blade __() calls did not.
        $this->locale = $reminder->patient?->lang ?? 'en';
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $patient  = $this->reminder->patient;
        $composer = app(ReminderComposer::class);
        $composed = $composer->compose($patient, $this->reminder->slot);

        // Scoped to ONE dose and expiring with its window. No login: a password
        // wall between a reminder and a dose check-off costs more responses
        // than it protects, and the responses are the dataset.
        $url = URL::temporarySignedRoute(
            'respond.show',
            now()->addHours((int) config('medbuddy.link_ttl_hours', 6)),
            ['reminder' => $this->reminder->id],
        );

        $this->reminder->forceFill(['body' => $composed['body']])->save();

        return (new MailMessage)
            ->subject($composed['subject'])
            ->view('mail.dose-reminder', [
                'patient'  => $patient,
                'reminder' => $this->reminder,
                'body'     => $composed['body'],
                'url'      => $url,
            ]);
    }
}
