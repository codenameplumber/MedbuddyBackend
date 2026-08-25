@php use App\Support\Chart; @endphp
<div>
  <div class="grid grid-3">
    @foreach ([
      ['Enrolled', $total, 'across all conditions', null],
      ['On track', $onTrack, '80% adherence or better', 'safe'],
      ['Needs action', $urgent, 'below 60% or silent', $urgent ? 'alert' : null],
      ['Not responding', $silent, 'no reply for 3+ days', $silent ? 'caution' : null],
    ] as [$l, $v, $n, $tone])
      <div class="stat">
        <div class="stat-label">{{ $l }}</div>
        <div class="stat-value" @if($tone) style="color:var(--{{ $tone }})" @endif>{{ $v }}</div>
        <div class="stat-note">{{ $n }}</div>
      </div>
    @endforeach
  </div>

  <div class="dt" style="margin-top:var(--s5)">
    <div class="dt-bar">
      <span class="input-group dt-search">
        <input class="input" type="search" wire:model.live.debounce.300ms="search"
               placeholder="Search name, condition, language…" aria-label="Filter patients">
      </span>
      <div class="row" role="group" aria-label="Filter">
        @foreach (['all' => 'All', 'attention' => 'Needs attention', 'silent' => 'Not responding'] as $key => $label)
          <button class="btn btn-sm {{ $filter === $key ? '' : 'btn-secondary' }}" type="button"
                  wire:click="$set('filter', '{{ $key }}')">{{ $label }}</button>
        @endforeach
      </div>
      <span class="grow"></span>
      <span class="hint dt-count">{{ $rows->count() }} shown</span>
      @can('create', \App\Models\Patient::class)
        <a class="btn btn-sm" href="{{ route('clinic.patient.create') }}" wire:navigate>
          <x-icon name="plus" class="i-sm" /> Enrol a patient
        </a>
      @endcan
    </div>

    <div class="table-wrap">
      <table class="table">
        <thead><tr>
          @foreach ([
            'name' => 'Patient', 'condition' => 'Condition', 'adherence7' => 'Adherence · 7d',
            'adherence30' => '30d', 'lang' => 'Language',
          ] as $col => $label)
            <th class="is-sortable {{ $sort === $col ? 'is-sorted' : '' }}" wire:click="sortBy('{{ $col }}')">
              <span class="th-inner">{{ $label }}
                <span class="th-arrow">{{ $sort === $col ? ($dir === 'asc' ? '▲' : '▼') : '' }}</span></span>
            </th>
          @endforeach
          <th>Last reply</th><th>Status</th>
        </tr></thead>
        <tbody>
          @forelse ($rows as $p)
            <tr class="is-{{ $p->attention['level'] }}" style="cursor:pointer"
                onclick="window.location='{{ route('clinic.patient', $p) }}'">
              <td><strong>{{ $p->name }}</strong><div class="hint">{{ $p->age }} years</div></td>
              <td>{{ $p->condition }}</td>
              <td>{!! Chart::meter($p->adherence7) !!}</td>
              <td class="num hint">{{ $p->adherence30 }}%</td>
              <td class="hint">{{ $p->langName() }}</td>
              <td class="hint">{{ $p->last_response_at?->diffForHumans() ?? '—' }}</td>
              <td><span class="badge badge-{{ ['critical' => 'alert', 'concern' => 'caution', 'ok' => 'safe'][$p->attention['level']] }}">
                {{ ['critical' => 'Needs action', 'concern' => 'Watch', 'ok' => 'On track'][$p->attention['level']] }}</span></td>
            </tr>
          @empty
            <tr><td colspan="7"><div class="empty">No patients match that filter.</div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
