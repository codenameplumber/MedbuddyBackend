<?php

namespace App\Livewire\Clinic;

use App\Models\Audit;
use App\Services\AnalyticsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.clinic')]
class Outcomes extends Component
{
    /** Cohort-wide reads are recorded too: 'who saw what' includes a queue. */
    public function mount(): void
    {
        Audit::record('outcomes.viewed');
    }

    public function render(AnalyticsService $analytics)
    {
        return view('livewire.clinic.outcomes', [
            'o'     => $analytics->outcomes(),
            'trend' => $analytics->adherenceTrend(30),
        ])->title('Outcomes');
    }
}
