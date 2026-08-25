<?php

namespace App\Livewire\Clinic;

use App\Models\Audit;
use App\Services\AdherenceService;
use App\Services\AnalyticsService;
use App\Services\ScheduleService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.clinic')]
class Adherence extends Component
{
    /** Cohort-wide reads are recorded too: 'who saw what' includes a queue. */
    public function mount(): void
    {
        Audit::record('adherence.viewed');
    }

    public function render(AnalyticsService $analytics, AdherenceService $adherence, ScheduleService $schedule)
    {
        $cohort = $analytics->cohort();

        // Direction matters more than level: a patient at 70% and falling
        // needs a different response from one at 70% and climbing.
        $falling = $cohort->map(function ($p) use ($schedule) {
            $week = function (int $from, int $to) use ($p, $schedule) {
                $taken = $total = 0;
                for ($i = $from; $i < $to; $i++) {
                    foreach ($schedule->dosesForDay($p, now()->subDays($i)) as $d) {
                        $total++;
                        if ($d['status'] === 'taken') $taken++;
                    }
                }
                return $total ? (int) round($taken / $total * 100) : 0;
            };
            $p->setAttribute('week1', $week(7, 14));
            $p->setAttribute('week2', $week(0, 7));
            $p->setAttribute('delta', $p->week2 - $p->week1);
            return $p;
        })->filter(fn ($p) => $p->delta < 0)->sortBy('delta')->values();

        return view('livewire.clinic.adherence', [
            'trend'   => $analytics->adherenceTrend(30),
            'heat'    => $analytics->doseTimeHeat(),
            'paired'  => $adherence->morningVsEvening($cohort),
            'drugs'   => $analytics->byDrug(),
            'cohort'  => $cohort,
            'falling' => $falling,
            'stacked' => $cohort->map(fn ($p) => [
                'label' => explode(' ', $p->name)[0],
                'parts' => [
                    ['value' => $p->adherence30, 'tone' => 'safe'],
                    ['value' => max(0, 100 - $p->adherence30), 'tone' => 'alert'],
                ],
            ])->all(),
        ])->title('Adherence analytics');
    }
}
