<?php

namespace App\Livewire\Me;

use App\Models\Audit;
use App\Models\Prescription;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * A patient adding a medicine to their own list.
 *
 * The clinic owns anything it prescribed — this writes a row with no
 * prescribed_by, which is what marks it as the patient's own and is why the
 * medicines list can say plainly which is which. Staff see the same
 * distinction on the patient record, so nobody mistakes something bought over
 * a counter for something a doctor wrote.
 */
#[Layout('components.layouts.patient')]
class AddMedicine extends Component
{
    use HasPatient;

    public string $name = '';
    public string $strength = '';
    public string $form = 'tablet';
    public string $purpose = '';

    /** @var array<int, string> */
    public array $times = ['08:00'];

    public const FORMS = ['tablet', 'capsule', 'syrup', 'drops', 'injection', 'inhaler'];

    public function addTime(): void
    {
        if (count($this->times) < 6) {
            $this->times[] = '20:00';
        }
    }

    public function removeTime(int $index): void
    {
        // Never leave a medicine with no schedule: it would generate no
        // reminders and never appear on Today, which reads as the app
        // silently losing it.
        if (count($this->times) > 1) {
            unset($this->times[$index]);
            $this->times = array_values($this->times);
        }
    }

    protected function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:120'],
            'strength' => ['nullable', 'string', 'max:60'],
            'form'     => ['required', 'in:'.implode(',', self::FORMS)],
            'purpose'  => ['nullable', 'string', 'max:120'],
            'times'    => ['required', 'array', 'min:1', 'max:6'],
            'times.*'  => ['required', 'date_format:H:i'],
        ];
    }

    public function save()
    {
        $data = $this->validate();
        $me = $this->me();

        $times = collect($data['times'])->unique()->sort()->values()->all();

        $rx = Prescription::create([
            'patient_id'    => $me->id,
            'prescribed_by' => null,          // added by the patient, not prescribed
            'name'      => $data['name'],
            'strength'  => $data['strength'] ?: null,
            'form'      => $data['form'],
            'purpose'   => $data['purpose'] ?: null,
            'times'     => $times,
            'starts_on' => today()->toDateString(),
            'active'    => true,
        ]);

        Audit::record('prescription.self_added', $rx, ['patient_id' => $me->id]);

        session()->flash('toast', __('me.meds.save'));

        return $this->redirect(route('me.medicines'), navigate: true);
    }

    public function render()
    {
        $this->me();   // sets the locale, and 403s if the session has no patient

        return view('livewire.me.add-medicine', ['forms' => self::FORMS])
            ->title(__('me.meds.add'));
    }
}
