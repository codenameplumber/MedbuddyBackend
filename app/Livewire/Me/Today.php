<?php

namespace App\Livewire\Me;

use App\Models\DoseEvent;
use App\Services\AdherenceService;
use App\Services\ScheduleService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.patient')]
class Today extends Component
{
    use HasPatient;

    /**
     * Tick a dose off, or untick it.
     *
     * Doses are DERIVED from prescriptions.times, so a taken dose writes a row
     * and an untaken one deletes it. There is no "pending" record to keep in
     * sync, which is why adherence can be a pure function over events.
     */
    public function toggle(int $prescriptionId, string $time): void
    {
        // Scoped to this patient. The id arrives from the browser, so a
        // straight lookup would make ticking off someone else's dose a matter
        // of editing one number in a payload.
        $rx = $this->me()->activePrescriptions()->find($prescriptionId);
        abort_unless($rx, 403);

        $existing = DoseEvent::where('prescription_id', $rx->id)
            ->whereDate('dose_date', today())
            ->where('dose_time', $time)
            ->first();

        if ($existing) {
            $existing->delete();
            return;
        }

        DoseEvent::create([
            'patient_id'      => $this->me()->id,
            'prescription_id' => $rx->id,
            'dose_date'       => today()->toDateString(),
            'dose_time'       => $time,
            'status'          => 'taken',
            // Ticked in the app, not confirmed from a reminder link. Weaker
            // provenance, and the console shows the difference.
            'source'          => 'app',
            'recorded_at'     => now(),
        ]);
    }

    public function render(ScheduleService $schedule, AdherenceService $adherence)
    {
        $me = $this->me()->load('activePrescriptions');

        return view('livewire.me.today', [
            'doses'    => $schedule->dosesForDay($me),
            'progress' => $adherence->todayProgress($me),
            'streak'   => $adherence->streak($me),
            'rate'     => $adherence->rate($me, 7),
        ])->title(__('me.today.title'));
    }
}
