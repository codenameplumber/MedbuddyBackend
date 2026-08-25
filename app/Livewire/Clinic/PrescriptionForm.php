<?php

namespace App\Livewire\Clinic;

use App\Models\Audit;
use App\Models\Patient;
use App\Models\Prescription;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.clinic')]
class PrescriptionForm extends Component
{
    public Patient $patient;
    public ?Prescription $prescription = null;

    public string $name = '';
    public string $generic = '';
    public string $strength = '';
    public string $form = 'tablet';
    public string $purpose = '';
    /** @var string[] */
    public array $times = ['08:00'];
    public ?string $ends_on = null;

    public function mount(Patient $patient, ?Prescription $prescription = null): void
    {
        $this->patient = $patient;

        if ($prescription?->exists) {
            $this->authorize('update', $prescription);
            $this->prescription = $prescription;
            $this->fill(collect($prescription->only(['name', 'generic', 'strength', 'form', 'purpose']))->map(fn ($v) => $v ?? '')->all());
            $this->times = $prescription->times ?: ['08:00'];
            $this->ends_on = $prescription->ends_on?->toDateString();
        } else {
            $this->authorize('create', Prescription::class);
        }
    }

    public function addTime(): void
    {
        $this->times[] = '20:00';
    }

    public function removeTime(int $i): void
    {
        unset($this->times[$i]);
        $this->times = array_values($this->times);

        if (empty($this->times)) {
            $this->times = ['08:00'];
        }
    }

    protected function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:150'],
            'generic'  => ['nullable', 'string', 'max:150'],
            'strength' => ['nullable', 'string', 'max:60'],
            'form'     => ['required', 'string', 'max:40'],
            'purpose'  => ['nullable', 'string', 'max:150'],
            'times'    => ['required', 'array', 'min:1'],
            'times.*'  => ['required', 'date_format:H:i'],
            'ends_on'  => ['nullable', 'date', 'after:today'],
        ];
    }

    public function save()
    {
        $data = $this->validate();

        // De-duplicate and sort: the dose windows drive reminder grouping, and
        // two identical times would mean two messages in the same window.
        $data['times'] = array_values(array_unique($data['times']));
        sort($data['times']);

        if ($this->prescription) {
            $this->prescription->update($data);
            Audit::record('prescription.updated', $this->prescription);
        } else {
            $this->prescription = Prescription::create($data + [
                'patient_id'    => $this->patient->id,
                'prescribed_by' => auth()->id(),
                'starts_on'     => today(),
                'active'        => true,
            ]);
            Audit::record('prescription.issued', $this->prescription, ['patient_id' => $this->patient->id]);
        }

        session()->flash('toast', 'Prescription saved.');

        return $this->redirectRoute('clinic.patient', $this->patient, navigate: true);
    }

    public function render()
    {
        return view('livewire.clinic.prescription-form', [
            'forms' => ['tablet', 'capsule', 'syrup', 'drops', 'injection', 'inhaler', 'patch'],
        ])->title($this->prescription ? 'Edit prescription' : 'New prescription');
    }
}
