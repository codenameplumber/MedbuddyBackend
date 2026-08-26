<?php

namespace App\Livewire\Clinic;

use App\Services\AiService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.clinic')]
class Settings extends Component
{
    public function render(AiService $ai)
    {
        return view('livewire.clinic.settings', [
            'aiLive'   => $ai->isLive(),
            'aiModel'  => app(\App\Services\AiService::class)->model(),
            'aiProvider' => app(\App\Services\AiService::class)->providerLabel(),
            'mailer'   => config('mail.default'),
            'channels' => config('medbuddy.channels'),
            'thresholds' => [
                'concern'  => config('medbuddy.adherence_concern'),
                'critical' => config('medbuddy.adherence_critical'),
                'silent'   => config('medbuddy.silent_days'),
                'severity' => config('medbuddy.severity_concern'),
            ],
        ])->title('Settings');
    }
}
