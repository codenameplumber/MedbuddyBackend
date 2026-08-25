<?php

namespace App\Notifications;

use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a named caregiver, so it MAY name medicines — unlike a dose
 * reminder, which goes to a lock screen and must not.
 *
 * The distinction is deliberate: this is an explicit, consented disclosure to
 * one nominated person, not a notification that anyone nearby might read.
 */
class CaregiverSummary extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Patient $patient,
        public array $summary,
        public int $adherence,
    ) {
        $this->locale = $patient->lang;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('How '.$this->patient->firstName().' is doing with their medicines')
            ->view('mail.caregiver-summary', [
                'patient'   => $this->patient,
                'summary'   => $this->summary,
                'adherence' => $this->adherence,
            ]);
    }
}
