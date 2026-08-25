<?php

namespace App\Livewire\Clinic;

use App\Models\Audit;
use App\Services\AnalyticsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.clinic')]
class Signals extends Component
{
    /** Cohort-wide reads are recorded too: 'who saw what' includes a queue. */
    public function mount(): void
    {
        Audit::record('signals.viewed');
    }

    public function render(AnalyticsService $analytics)
    {
        $rows = $analytics->signals();

        return view('livewire.clinic.signals', [
            'rows'   => $rows,
            'severe' => collect($rows)->where('maxSeverity', '>=', 2)->count(),
        ])->title('Safety signals');
    }
}
