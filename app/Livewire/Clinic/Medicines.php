<?php

namespace App\Livewire\Clinic;

use App\Services\AnalyticsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.clinic')]
class Medicines extends Component
{
    public string $search = '';

    /**
     * Interaction severity is a lookup against a curated set scoped to the
     * drugs this cohort actually takes. It is deliberately NOT a model
     * recalling interactions from memory: narrow and verifiable beats broad
     * and unverifiable. A pair not in the set returns nothing and says so.
     */
    public const INTERACTIONS = [
        ['a' => 'Amlodipine', 'b' => 'Hydrochlorothiazide', 'severity' => 'Minor',
         'note' => 'Both lower blood pressure. Combined use is intentional here, but watch for dizziness on standing.'],
        ['a' => 'Metformin', 'b' => 'Amlodipine', 'severity' => 'Minor',
         'note' => 'No clinically significant interaction. Monitor renal function as usual.'],
        ['a' => 'Rifampicin', 'b' => 'Dolutegravir / Lamivudine / Tenofovir', 'severity' => 'Major',
         'note' => 'Rifampicin markedly reduces dolutegravir levels. Dose adjustment is required. Refer to the prescriber.'],
        ['a' => 'Carbamazepine', 'b' => 'Dolutegravir / Lamivudine / Tenofovir', 'severity' => 'Major',
         'note' => 'Carbamazepine induces metabolism and can cause treatment failure. An alternative anticonvulsant is usually preferred.'],
    ];

    public function render(AnalyticsService $analytics)
    {
        $drugs = collect($analytics->byDrug());

        if ($q = trim(mb_strtolower($this->search))) {
            $drugs = $drugs->filter(fn ($d) => str_contains(mb_strtolower($d['name'].' '.$d['conditions']), $q));
        }

        return view('livewire.clinic.medicines', [
            'drugs'        => $drugs->values(),
            'all'          => $analytics->byDrug(),
            'interactions' => self::INTERACTIONS,
        ])->title('Medicines');
    }
}
