<?php

namespace App\Livewire\Clinic;

use App\Models\PatientReport;
use App\Services\AdherenceService;
use App\Services\AnalyticsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.clinic')]
class Overview extends Component
{
    public function render(AnalyticsService $analytics, AdherenceService $adherence)
    {
        $cohort = $analytics->cohort();

        return view('livewire.clinic.overview', [
            'stats'     => $analytics->cohortStats($cohort),
            'trend'     => $analytics->adherenceTrend(30),
            'bands'     => $analytics->adherenceBands($cohort),
            'heat'      => $analytics->doseTimeHeat(),
            'paired'    => $adherence->morningVsEvening($cohort),
            'attention' => $cohort
                ->filter(fn ($p) => $p->attention['level'] !== 'ok')
                ->sortBy([
                    fn ($p) => ['critical' => 0, 'concern' => 1, 'ok' => 2][$p->attention['level']],
                    fn ($p) => $p->adherence7,
                ])->values(),
            'reports'   => PatientReport::with(['patient', 'prescription'])->latest()->limit(6)->get(),
        ])->title('Overview');
    }
}
