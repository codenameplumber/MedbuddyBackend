<?php

namespace App\Livewire\Me;

use App\Models\Audit;
use App\Services\AiService;
use App\Services\TriageService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Session;
use Livewire\Component;

/**
 * Medication Q&A, with the deterministic emergency gate in front of it.
 *
 * The gate is the whole point: TriageService runs before any network call,
 * synchronously, offline, in all four languages. A crisis is never subject to
 * a model deciding whether it is serious, and never waits on a request that
 * might time out on 2G.
 */
#[Layout('components.layouts.patient')]
class Ask extends Component
{
    use HasPatient;

    public string $question = '';

    /** @var array<int, array{role: string, text: string, note: ?string}> */
    #[Session(key: 'ask-thread')]
    public array $thread = [];

    public ?string $crisis = null;

    public function send(AiService $ai, TriageService $triage): void
    {
        $q = trim($this->question);
        if ($q === '') {
            return;
        }

        $me = $this->me();
        $this->thread[] = ['role' => 'me', 'text' => $q, 'note' => null];
        $this->question = '';

        $meds = $me->activePrescriptions()->pluck('name')->all();
        $answer = $ai->ask($q, $me->lang, $me->langName(), $meds);

        if ($answer['crisis']) {
            // Nothing is sent anywhere. The screen changes immediately.
            $this->crisis = $answer['category'];
            Audit::record('patient.triage_tripped', $me, ['category' => $answer['category']]);
            return;
        }

        $r = $answer['result'];

        $this->thread[] = [
            'role' => 'ai',
            'text' => $r['answer'],
            'note' => $r['seeClinician'] ? __('me.common.notAdvice') : null,
        ];
    }

    public function clearCrisis(): void
    {
        $this->crisis = null;
    }

    public function clearThread(): void
    {
        $this->thread = [];
    }

    public function render(AiService $ai, TriageService $triage)
    {
        $me = $this->me();

        return view('livewire.me.ask', [
            'live'     => $ai->isLive(),
            'guidance' => $this->crisis ? $triage->guidance($this->crisis) : null,
            'number'   => $me->organisation?->emergency_number ?? '112',
        ])->title(__('me.ask.title'));
    }
}
