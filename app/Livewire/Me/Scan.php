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

    public function render(AiService $ai)
    {
        return view('livewire.me.scan', [
            'live'        => $ai->isLive(),
            'displayRule' => $this->result && $this->mode === 'identify'
                ? AiService::displayRule($this->result['confidence'])
                : null,
        ])->title(__('me.explain.title'));
    }
}
