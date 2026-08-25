<?php

namespace App\Livewire\Me;

use App\Models\Audit;
use App\Models\Consent;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The screen that makes consent real.
 *
 * A consent record the patient cannot reach is a claim, not a control. Turning
 * reminders off here writes a revocation the dispatcher checks on every run,
 * and SchedulingTest proves the mail actually stops.
 */
#[Layout('components.layouts.patient')]
class Profile extends Component
{
    use HasPatient;

    public string $lang = 'en';
    public bool $messaging = true;
    public bool $research = false;

    public function mount(): void
    {
        $me = $this->me();
        $this->lang = $me->lang;
        $this->messaging = $me->hasConsent('messaging', 'mail');
        $this->research  = $me->hasConsent('research');
    }

    public function updatedLang(): void
    {
        $this->validate(['lang' => ['required', Rule::in(array_keys(config('medbuddy.languages')))]]);

        $me = $this->me();
        $me->update(['lang' => $this->lang]);
        app()->setLocale($this->lang);

        Audit::record('patient.language_changed', $me, ['lang' => $this->lang]);
    }

    public function updatedMessaging(): void
    {
        $this->setConsent('messaging', 'mail', $this->messaging);
    }

    public function updatedResearch(): void
    {
        $this->setConsent('research', null, $this->research);
    }

    private function setConsent(string $scope, ?string $channel, bool $granted): void
    {
        $me = $this->me();

        $consent = Consent::firstOrNew([
            'patient_id' => $me->id, 'scope' => $scope, 'channel' => $channel,
        ]);

        // Revoked, not deleted: the record of the decision is the point.
        $consent->fill([
            'granted'    => $granted,
            'granted_at' => $granted ? now() : $consent->granted_at,
            'revoked_at' => $granted ? null : now(),
        ])->save();

        Audit::record($granted ? 'consent.granted' : 'consent.revoked', $me, [
            'scope' => $scope, 'channel' => $channel, 'by' => 'patient',
        ]);
    }

    public function render()
    {
        $me = $this->me();

        return view('livewire.me.profile', [
            'me'        => $me,
            'languages' => config('medbuddy.languages'),
            'number'    => $me->organisation?->emergency_number ?? '112',
            'medCount'  => $me->activePrescriptions()->count(),
        ])->title(__('me.settings.title'));
    }
}
