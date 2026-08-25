<?php

namespace App\Livewire\Me;

use App\Services\AdherenceService;
use App\Services\ScheduleService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.patient')]
class History extends Component
{
    use HasPatient;

    public function render(AdherenceService $adherence, ScheduleService $schedule)
    {
        $me = $this->me()->load('prescriptions');

        $days = [];
        for ($i = 27; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $days[] = [
                'date'  => $date->copy(),
                'doses' => collect($schedule->dosesForDay($me, $date))->pluck('status')->all(),
            ];
        }

        return view('livewire.me.history', [
            'series' => $adherence->dailySeries($me, 30),
            'days'   => $days,
            'streak' => $adherence->streak($me),
            'rate7'  => $adherence->rate($me, 7),
            'rate30' => $adherence->rate($me, 30),
            'reports' => $me->reports()->latest()->limit(10)->get(),
        ])->title(__('me.history.title'));
    }
}
