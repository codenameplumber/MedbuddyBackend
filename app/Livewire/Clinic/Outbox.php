<?php

namespace App\Livewire\Clinic;

use App\Jobs\DispatchDueReminders;
use App\Models\Audit;
use App\Models\Reminder;
use App\Services\AnalyticsService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.clinic')]
class Outbox extends Component
{
    /** Runs the scheduler job now rather than waiting for the next tick. */
    public function runNow(): void
    {
        DispatchDueReminders::dispatchSync();
        Audit::record('reminders.manual_dispatch');

        $this->dispatch('toast', message: 'Dispatcher ran. Any dose window open in the last 20 minutes was sent.', tone: 'safe');
    }

    public function render(AnalyticsService $analytics)
    {
        return view('livewire.clinic.outbox', [
            'funnel' => $analytics->deliveryFunnel(),
            'recent' => Reminder::with('patient')->latest('sent_at')->whereNotNull('sent_at')->limit(25)->get(),
            'queued' => Reminder::with('patient')->whereNull('sent_at')->limit(25)->get(),
            'today'  => Reminder::whereDate('dose_date', today())->count(),
        ])->title('Message outbox');
    }
}
