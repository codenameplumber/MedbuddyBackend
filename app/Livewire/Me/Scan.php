<?php

namespace App\Livewire\Me;

use App\Models\Audit;
use App\Services\AiService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.patient')]
class Scan extends Component
{
    use HasPatient, WithFileUploads;

    public string $mode = 'explain';        // explain | identify
    public string $text = '';
    public $photo;

    public ?array $result = null;
    public ?string $error = null;

    public function updatedMode(): void
    {
        $this->reset(['result', 'error']);
    }

    public function run(AiService $ai): void
    {
        $this->reset(['result', 'error']);

        if (blank($this->text) && ! $this->photo) {
            $this->error = __('me.explain.needInput');
            return;
        }

        $this->validate([
            'text'  => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:8192'],
        ]);

        $me = $this->me();

        $dataUrl = $this->photo
            ? 'data:'.$this->photo->getMimeType().';base64,'.base64_encode(file_get_contents($this->photo->getRealPath()))
            : null;

        try {
            $this->result = $this->mode === 'identify'
                ? $ai->identify($this->text, $me->langName(), $dataUrl)
                : $ai->explain($this->text, $me->langName(), $dataUrl);

            Audit::record('patient.ai_'.$this->mode, $me, ['live' => $ai->isLive()]);
        } catch (\Throwable $e) {
            $this->error = __('me.common.error');
            report($e);
        }
    }

    /**
     * What "read aloud" reads.
     *
     * Not the whole screen: the point is the parts a patient has to act on —
     * what it is, how to take it, what to watch for. Headings and layout are
     * for the eye.
     */
    private function spokenSummary(): string
    {
        if (! $this->result) {
            return '';
        }

        if ($this->mode === 'identify') {
            return trim($this->result['visualFeatures'].' '
                .AiService::displayRule($this->result['confidence'])['note']);
        }

        $parts = array_filter([
            $this->result['medicineName'] ?? null,
            $this->result['purpose'] ?? null,
            $this->result['dosing'] ?? null,
            ...($this->result['warnings'] ?? []),
        ]);

        return trim(implode(' ', $parts));
    }

    public function render(AiService $ai)
    {
        $me = $this->me();

        return view('livewire.me.scan', [
            'live'        => $ai->isLive(),
            'lang'        => $me->lang,
            'spoken'      => $this->spokenSummary(),
            'displayRule' => $this->result && $this->mode === 'identify'
                ? AiService::displayRule($this->result['confidence'])
                : null,
        ])->title(__('me.explain.title'));
    }
}
