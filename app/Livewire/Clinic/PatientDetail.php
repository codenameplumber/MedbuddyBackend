<?php

namespace App\Livewire\Clinic;

use App\Models\Audit;
use App\Models\Patient;
use App\Models\Reminder;
use App\Notifications\CaregiverSummary;
use App\Notifications\DoseReminder;
use App\Services\AdherenceService;
use App\Services\AiService;
use App\Services\ScheduleService;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.clinic')]
class PatientDetail extends Component
{
    public Patient $patient;

    /** A generated draft, held in the component until a person approves it. */
    public ?array $summary = null;

    public function mount(Patient $patient): void
    {
        $this->patient = $patient->load(['activePrescriptions', 'reports.prescription', 'consents', 'organisation']);

        // Every read of a patient record is written to the audit log. That is
        // a regulatory requirement rather than a feature.
        Audit::record('patient.view', $patient);
    }

    /** Sends a real email immediately, outside the schedule. */
    public function sendReminderNow(ScheduleService $schedule): void
    {
        if (! $this->patient->email) {
            $this->dispatch('toast', message: 'This patient has no email address.', tone: 'caution');
            return;
        }

        if (! $this->patient->hasConsent('messaging', 'mail')) {
            $this->dispatch('toast', message: 'No messaging consent on record for this patient.', tone: 'alert');
            return;
        }

        $time = $schedule->windowsFor($this->patient)[0] ?? '08:00';

        $reminder = Reminder::updateOrCreate(
            ['patient_id' => $this->patient->id, 'dose_date' => today()->toDateString(), 'dose_time' => $time],
            ['slot' => $schedule->slotFor($time), 'channel' => 'mail', 'to' => $this->patient->email],
        );

        Notification::route('mail', $this->patient->email)->notify(new DoseReminder($reminder));
        $reminder->forceFill(['sent_at' => now()])->save();
        Audit::record('reminder.manual_send', $this->patient, ['reminder_id' => $reminder->id]);

        $this->dispatch('toast', message: 'Reminder queued to '.$this->patient->email, tone: 'safe');
    }

    /**
     * Write a caregiver summary — but do not send it.
     *
     * The model drafts; a person signs off. Nothing generated here reaches a
     * caregiver until a staff member reads it and presses send, which is the
     * only defensible way to put model output in front of a family member.
     */
    public function draftCaregiverSummary(AiService $ai, AdherenceService $adherence): void
    {
        $this->authorize('view', $this->patient);

        if (! $this->patient->caregiver_email) {
            $this->dispatch('toast', message: 'No caregiver email on record.', tone: 'caution');
            return;
        }

        // Only facts the database actually holds. No inference, no diagnosis.
        $rate = $adherence->rate($this->patient, 30) ?? 0;
        $missed = collect($adherence->dailySeries($this->patient, 30)['values'])->filter(fn ($v) => $v < 100)->count();

        $facts = collect([
            'Patient first name: '.$this->patient->firstName(),
            'Adherence over the last 30 days: '.$rate.'%',
            'Days with at least one missed dose in that period: '.$missed,
            'Medicines: '.$this->patient->activePrescriptions
                ->map(fn ($rx) => $rx->name.' '.$rx->strength.' at '.implode(' and ', $rx->times ?? []))
                ->join('; '),
            'Symptoms reported through the app: '.($this->patient->reports
                ->flatMap(fn ($r) => $r->symptoms)->unique()->join(', ') ?: 'none'),
        ])->join("\n");

        try {
            $this->summary = $ai->summary($facts, $this->patient->langName());
            Audit::record('ai.summary_drafted', $this->patient, ['live' => $ai->isLive()]);
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), tone: 'alert');
        }
    }

    public function sendCaregiverSummary(AdherenceService $adherence): void
    {
        $this->authorize('view', $this->patient);

        if (! $this->summary || ! $this->patient->caregiver_email) {
            return;
        }

        Notification::route('mail', $this->patient->caregiver_email)
            ->notify(new CaregiverSummary($this->patient, $this->summary, $adherence->rate($this->patient, 30) ?? 0));

        Audit::record('caregiver.summary_sent', $this->patient, ['to' => $this->patient->caregiver_email]);
        $this->summary = null;

        $this->dispatch('toast', message: 'Summary sent to '.$this->patient->caregiver_email, tone: 'safe');
    }

    public function render(AdherenceService $adherence, ScheduleService $schedule, AiService $ai)
    {
        $history = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $history[] = [
                'date'  => $date->copy(),
                'doses' => collect($schedule->dosesForDay($this->patient, $date))->pluck('status')->all(),
            ];
        }

        return view('livewire.clinic.patient-detail', [
            'series'    => $adherence->dailySeries($this->patient, 60),
            'history'   => $history,
            'attention' => $adherence->attention($this->patient),
            'a7'        => $adherence->rate($this->patient, 7) ?? 0,
            'a30'       => $adherence->rate($this->patient, 30) ?? 0,
            'aiLive'    => $ai->isLive(),
        ])->title($this->patient->name);
    }
}
