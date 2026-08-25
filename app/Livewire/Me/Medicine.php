<?php

namespace App\Livewire\Me;

use App\Models\Prescription;
use App\Services\AdherenceService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.patient')]
class Medicine extends Component
{
    use HasPatient;

    public Prescription $rx;

    public function mount(Prescription $prescription): void
    {
        // Route-model binding hands over any id. Scope it to this patient or
        // the URL becomes a way to read someone else's prescription.
        abort_unless($prescription->patient_id === $this->me()->id, 403);

        $this->rx = $prescription;
    }

    public function render(AdherenceService $adherence)
    {
        $taken = $this->rx->doseEvents()->taken()
            ->whereDate('dose_date', '>=', today()->subDays(29))->count();

        $scheduled = max(1, $this->rx->dosesPerDay() * 30);

        return view('livewire.me.medicine', [
            // The explanation the clinic already generated and attached. Shown
            // from the database, so it costs nothing and works offline.
            'summary'   => $this->rx->ai_summary,
            'adherence' => min(100, (int) round($taken / $scheduled * 100)),
        ])->title($this->rx->name);
    }
}
