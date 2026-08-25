<?php

namespace App\Livewire\Me;

use App\Models\PatientReport;
use App\Services\TriageService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * "Tell us how you feel", in the app rather than through a reminder link.
 *
 * Tap before type: the symptom grid yields clean structured data with zero
 * literacy and zero speech-to-text, so the cohort view works regardless of
 * which languages ASR happens to support.
 */
#[Layout('components.layouts.patient')]
class Report extends Component
{
    use HasPatient, WithFileUploads;

    /** @var string[] */
    public array $symptoms = [];
    public int $severity = 1;
    public string $note = '';

    /** A recorded blob, handed over by public/js/recorder.js. */
    public $audio;

    public bool $sent = false;
    public ?string $crisis = null;

    public function updatedSymptoms(): void
    {
        // "Feeling fine" is exclusive. Without this the cohort fills with
        // "fine + rash", which means nothing.
        if (in_array('fine', $this->symptoms, true) && count($this->symptoms) > 1) {
            $this->symptoms = ['fine'];
        }
    }

    public function isComplaint(): bool
    {
        return $this->symptoms && ! in_array('fine', $this->symptoms, true);
    }

    public function submit(TriageService $triage): void
    {
        $this->validate([
            'symptoms'   => ['required', 'array', 'min:1'],
            'symptoms.*' => ['string', 'in:'.implode(',', config('medbuddy.symptoms'))],
            'severity'   => ['integer', 'between:0,3'],
            'note'       => ['nullable', 'string', 'max:2000'],
            'audio'      => ['nullable', 'file', 'max:10240'],
        ]);

        $me = $this->me();

        // Private disk. Playback in the console is authenticated and audited.
        $audioPath = $this->audio ? $this->audio->store('p'.$me->id, 'voice') : null;

        // The same deterministic gate the reminder page uses. A free-text note
        // describing a crisis must not quietly become a routine symptom report.
        $verdict = $triage->check($this->note, $me->lang);

        PatientReport::create([
            'patient_id'      => $me->id,
            'symptoms'        => array_values($this->symptoms),
            'severity'        => $this->isComplaint() ? $this->severity : 0,
            'note'            => $this->note ?: null,
            'audio_path'      => $audioPath,
            'lang'            => $me->lang,
            'source'          => 'app',
            'triage_category' => $verdict['crisis'] ? $verdict['category'] : null,
        ]);

        $me->forceFill(['last_response_at' => now()])->save();

        $this->crisis = $verdict['crisis'] ? $verdict['category'] : null;
        $this->sent = true;
    }

    public function render(TriageService $triage)
    {
        return view('livewire.me.report', [
            'options'  => config('medbuddy.symptoms'),
            'guidance' => $this->crisis ? $triage->guidance($this->crisis) : null,
            'number'   => $this->me()->organisation?->emergency_number ?? '112',
        ])->title(__('me.report.title'));
    }
}
