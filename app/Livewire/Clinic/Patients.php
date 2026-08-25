<?php

namespace App\Livewire\Clinic;

use App\Services\AnalyticsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.clinic')]
class Patients extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    #[Url]
    public string $filter = 'all';      // all | attention | silent

    public string $sort = 'adherence7';
    public string $dir = 'asc';

    public function updatedSearch() { $this->resetPage(); }
    public function updatedFilter() { $this->resetPage(); }

    public function sortBy(string $column): void
    {
        if ($this->sort === $column) {
            $this->dir = $this->dir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->dir = 'asc';
        }
        $this->resetPage();
    }

    public function render(AnalyticsService $analytics)
    {
        // The cohort is small and the attention verdict is derived rather than
        // stored, so filtering and sorting happen in the collection. Swap to a
        // query with a materialised adherence column when this stops being fast.
        $rows = $analytics->cohort();

        if ($this->filter === 'attention') {
            $rows = $rows->filter(fn ($p) => $p->attention['level'] !== 'ok');
        } elseif ($this->filter === 'silent') {
            $rows = $rows->filter(fn ($p) => $p->last_response_at && $p->last_response_at->lt(now()->subDays(3)));
        }

        if ($q = trim(mb_strtolower($this->search))) {
            $rows = $rows->filter(fn ($p) => str_contains(mb_strtolower($p->name.' '.$p->condition.' '.$p->langName()), $q));
        }

        $sorted = $rows->sortBy(fn ($p) => $p->{$this->sort} ?? 0, SORT_REGULAR, $this->dir === 'desc');

        return view('livewire.clinic.patients', [
            'total'   => $analytics->cohort()->count(),
            'rows'    => $sorted->values(),
            'onTrack' => $rows->filter(fn ($p) => $p->attention['level'] === 'ok')->count(),
            'urgent'  => $rows->filter(fn ($p) => $p->attention['level'] === 'critical')->count(),
            'silent'  => $rows->filter(fn ($p) => $p->last_response_at && $p->last_response_at->lt(now()->subDays(3)))->count(),
        ])->title('Patients');
    }
}
