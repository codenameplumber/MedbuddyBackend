<?php

namespace App\Jobs;

use App\Models\Patient;
use App\Models\Reminder;
use App\Notifications\DoseReminder;
use App\Services\ScheduleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * The piece the browser could never do: fire while the patient phone is asleep.
 *
 * Runs on the scheduler every 15 minutes, finds dose windows that have just
 * opened, and queues exactly one message per patient per window.
 */
class DispatchDueReminders implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $windowMinutes = 20) {}

    public function handle(ScheduleService $schedule): void
    {
        $now = now();
        $today = $now->toDateString();
        $sent = 0;

        $patients = Patient::with('activePrescriptions')
            ->whereNotNull('email')
            ->get();

        foreach ($patients as $patient) {
            // Consent is checked per channel, every time, and is revocable.
            if (! $patient->hasConsent('messaging', 'mail')) {
                continue;
            }

            foreach ($schedule->windowsFor($patient, $now) as $time) {
                [$h, $m] = array_map('intval', explode(':', $time));
                $due = $now->copy()->setTime($h, $m);

                // Only windows that opened within the last N minutes, so a
                // scheduler that runs late does not spam yesterday's doses.
                $diff = $now->diffInMinutes($due, false);
                if ($diff > 0 || $diff < -$this->windowMinutes) {
                    continue;
                }

                // Look the row up with whereDate rather than an equality match on
                // a cast attribute: a DATE column and a datetime-serialised
                // Carbon compare differently across drivers, and getting it
                // wrong means the dispatcher throws on its second run instead
                // of quietly skipping. The unique index is the backstop.
                $existing = Reminder::where('patient_id', $patient->id)
                    ->whereDate('dose_date', $today)
                    ->where('dose_time', $time)
                    ->first();

                if ($existing && $existing->sent_at) {
                    continue;
                }

                $reminder = $existing ?: new Reminder([
                    'patient_id' => $patient->id,
                    'dose_date'  => $today,
                    'dose_time'  => $time,
                ]);

                $reminder->fill([
                    'slot'    => $schedule->slotFor($time),
                    'channel' => 'mail',
                    'to'      => $patient->email,
                ])->save();

                try {
                    Notification::route('mail', $patient->email)->notify(new DoseReminder($reminder));
                    $reminder->forceFill(['sent_at' => now(), 'error' => null])->save();
                    $sent++;
                } catch (\Throwable $e) {
                    $reminder->forceFill(['error' => mb_substr($e->getMessage(), 0, 250)])->save();
                    Log::error('[reminders] send failed', [
                        'patient' => $patient->id, 'time' => $time, 'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::info("[reminders] dispatched {$sent}");
    }
}
