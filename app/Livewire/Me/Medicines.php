<?php

namespace App\Livewire\Me;

use App\Services\ScheduleService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.patient')]
class Medicines extends Component
{
    use HasPatient;

    public function render(ScheduleService $schedule)
    {
        return view('livewire.me.medicines', [
            'meds' => $this->me()->activePrescriptions()->orderBy('name')->get(),
        ])->title(__('me.meds.title'));
    }
}
