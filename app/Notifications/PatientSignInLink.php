<?php

namespace App\Notifications;

use App\Models\Patient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class PatientSignInLink extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Patient $patient)
    {
        $this->locale = $patient->lang;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'me.auth',
            now()->addMinutes(30),
            ['patient' => $this->patient->id],
        );

        // Same privacy rule as a dose reminder: nothing here names a condition
        // or a medicine.
        return (new MailMessage)
            ->subject('Open MedBuddy')
            ->view('mail.signin-link', ['patient' => $this->patient, 'url' => $url]);
    }
}
