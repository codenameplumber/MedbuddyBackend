<?php

namespace App\Livewire\Clinic;

use App\Models\Audit;
use App\Models\Consent;
use App\Models\Patient;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Enrol or edit a patient, including the per-channel consent that every
 * outbound message is gated on.
 */
#[Layout('components.layouts.clinic')]
class PatientForm extends Component
{
    public ?Patient $patient = null;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public ?int $age = null;
    public string $condition = '';
    public string $lang = 'en';
    public string $caregiver_name = '';
    public string $caregiver_email = '';

    // Separate decisions on purpose: someone may accept reminders and still
    // keep their reports out of the cohort.
    public bool $consentMessaging = true;
    public bool $consentResearch = false;

    public function mount(?Patient $patient = null): void
    {
        if ($patient?->exists) {
            $this->authorize('update', $patient);
            $this->patient = $patient;
            // Nullable columns filling typed string properties would throw,
            // so empty is empty in both directions.
            $this->fill(collect($patient->only([
                'name', 'email', 'phone', 'condition', 'lang',
                'caregiver_name', 'caregiver_email',
            ]))->map(fn ($v) => $v ?? '')->all());
            $this->age = $patient->age;
            $this->consentMessaging = $patient->hasConsent('messaging', 'mail');
            $this->consentResearch  = $patient->hasConsent('research');
        } else {
            $this->authorize('create', Patient::class);
        }
    }

    protected function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:120'],
            'email'           => ['nullable', 'email', 'max:180'],
            'phone'           => ['nullable', 'string', 'max:40'],
            'age'             => ['nullable', 'integer', 'between:0,120'],
            'condition'       => ['nullable', 'string', 'max:120'],
            'lang'            => ['required', Rule::in(array_keys(config('medbuddy.languages')))],
            'caregiver_name'  => ['nullable', 'string', 'max:120'],
            'caregiver_email' => ['nullable', 'email', 'max:180'],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        // Email is the only enabled channel, so messaging consent without an
        // address is a promise the system cannot keep.
        if ($this->consentMessaging && blank($this->email)) {
            $this->addError('email', 'An email address is required for reminders, or turn messaging consent off.');
            return null;
        }

        if ($this->patient) {
            $this->patient->update($data);
            Audit::record('patient.updated', $this->patient);
        } else {
            $data['organisation_id'] = auth()->user()->organisation_id;
            $data['enrolled_at'] = now();
            $this->patient = Patient::create($data);
            Audit::record('patient.enrolled', $this->patient);
        }

        $this->syncConsent('messaging', 'mail', $this->consentMessaging);
        $this->syncConsent('research', null, $this->consentResearch);

        session()->flash('toast', 'Patient saved.');

        return $this->redirectRoute('clinic.patient', $this->patient, navigate: true);
    }

    /** Grant, or revoke without deleting: the record of the decision is the point. */
    private function syncConsent(string $scope, ?string $channel, bool $granted): void
    {
        $consent = Consent::firstOrNew([
            'patient_id' => $this->patient->id,
            'scope'      => $scope,
            'channel'    => $channel,
        ]);

        $was = $consent->exists && $consent->granted && ! $consent->revoked_at;

        $consent->fill([
            'granted'    => $granted,
            'granted_at' => $granted ? ($consent->granted_at ?? now()) : $consent->granted_at,
            'revoked_at' => $granted ? null : ($consent->revoked_at ?? now()),
        ])->save();

        if ($was !== $granted) {
            Audit::record($granted ? 'consent.granted' : 'consent.revoked', $this->patient, [
                'scope' => $scope, 'channel' => $channel,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.clinic.patient-form', [
            'languages' => config('medbuddy.languages'),
        ])->title($this->patient ? 'Edit '.$this->patient->name : 'Enrol a patient');
    }
}
