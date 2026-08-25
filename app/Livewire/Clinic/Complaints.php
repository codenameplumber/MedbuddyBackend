<?php

namespace App\Livewire\Clinic;

use App\Models\Audit;
use App\Models\PatientReport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.clinic')]
class Complaints extends Component
{
    #[Url]
    public string $filter = 'all';   // all | severe | audio | review | missed

    /** Cohort-wide reads are recorded too: "who saw what" includes a queue. */
    public function mount(): void
    {
        Audit::record('complaints.viewed');
    }

    public function markReviewed(int $id): void
    {
        $report = PatientReport::findOrFail($id);
        $report->forceFill(['reviewed_at' => now(), 'reviewed_by' => auth()->id()])->save();
        Audit::record('report.reviewed', $report);

        $this->dispatch('toast', message: 'Marked reviewed.', tone: 'safe');
    }

    public function render()
    {
        $all = PatientReport::with(['patient', 'prescription'])
            ->latest()
            ->get()
            ->filter->isComplaint();

        $rows = match ($this->filter) {
            'severe' => $all->where('severity', '>=', 2),
            'audio'  => $all->filter(fn ($r) => (bool) $r->audio_path),
            'review' => $all->filter(fn ($r) => $r->needsHumanReview()),
            'missed' => $all->where('took_dose', false),
            default  => $all,
        };

        return view('livewire.clinic.complaints', [
            'rows'     => $rows->sortByDesc(fn ($r) => [$r->severity, $r->created_at->timestamp])->values(),
            'all'      => $all,
            'severe'   => $all->where('severity', '>=', 2)->count(),
            'audio'    => $all->filter(fn ($r) => (bool) $r->audio_path)->count(),
            'review'   => $all->filter(fn ($r) => $r->needsHumanReview())->count(),
        ])->title('Complaints');
    }
}
