<?php

namespace App\Livewire\Clinic;

use App\Models\Audit;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\AiService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Staff-facing label reader. Paste the text on a pack, or photograph it, and
 * get a plain-language explanation in the patient's own language which can be
 * attached to their prescription.
 */
#[Layout('components.layouts.clinic')]
class Explain extends Component
{
    use WithFileUploads;

    public string $mode = 'explain';        // explain | identify
    public string $text = '';
    public $photo;
    public ?int $patientId = null;
    public ?int $prescriptionId = null;

    public ?array $result = null;
    public ?string $error = null;
    public bool $busy = false;

    public function updatedMode(): void
    {
        $this->reset(['result', 'error']);
    }

    public function run(AiService $ai): void
    {
        $this->reset(['result', 'error']);

        if (blank($this->text) && ! $this->photo) {
            $this->error = 'Add some text or a photo first.';
            return;
        }

        $this->validate([
            'text'  => ['nullable', 'string', 'max:4000'],
            'photo' => ['nullable', 'image', 'max:8192'],
        ]);

        $patient = $this->patientId ? Patient::find($this->patientId) : null;
        $language = $patient?->langName() ?? 'English';

        // Images go to the model as a data URL. Downscaling happens client-side
        // before upload to bound the image-token cost.
        $dataUrl = null;
        if ($this->photo) {
            $dataUrl = 'data:'.$this->photo->getMimeType().';base64,'
                .base64_encode(file_get_contents($this->photo->getRealPath()));
        }

        try {
            $this->result = $this->mode === 'identify'
                ? $ai->identify($this->text, $language, $dataUrl)
                : $ai->explain($this->text, $language, $dataUrl);

            Audit::record('ai.'.$this->mode, $patient, [
                'live' => $ai->isLive(),
                'confidence' => $this->result['readingConfidence'] ?? $this->result['confidence'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    /** Cache the explanation on the prescription so patients see it in their app. */
    public function attachToPrescription(): void
    {
        if (! $this->prescriptionId || ! $this->result || $this->mode !== 'explain') {
            return;
        }

        $rx = Prescription::findOrFail($this->prescriptionId);
        $this->authorize('update', $rx);

        // Never attach something the model told us it could not read.
        if (($this->result['readingConfidence'] ?? '') === 'low'
            || ($this->result['readingConfidence'] ?? '') === 'cannot_read'
            || ($this->result['askPharmacist'] ?? false)) {
            $this->dispatch('toast', message: 'That reading was not confident enough to attach.', tone: 'caution');
            return;
        }

        $rx->update(['ai_summary' => $this->result]);
        Audit::record('prescription.summary_attached', $rx);
        $this->dispatch('toast', message: 'Explanation saved to '.$rx->name.'.', tone: 'safe');
    }

    public function render(AiService $ai)
    {
        $patients = Patient::where('organisation_id', auth()->user()->organisation_id)
            ->orderBy('name')->get(['id', 'name', 'lang']);

        return view('livewire.clinic.explain', [
            'patients'      => $patients,
            'prescriptions' => $this->patientId
                ? Prescription::where('patient_id', $this->patientId)->where('active', true)->get()
                : collect(),
            'live'          => $ai->isLive(),
            'displayRule'   => $this->result && $this->mode === 'identify'
                ? AiService::displayRule($this->result['confidence'])
                : null,
        ])->title('Explain a medicine');
    }
}
