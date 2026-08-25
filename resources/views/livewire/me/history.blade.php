@php use App\Support\Chart; @endphp
<div>
  <div class="label"><div class="label-body">
    <div class="row" style="gap:var(--s5)">
      <div class="grow">
        <div class="streak-n">{{ $streak }}</div>
        <div class="muted">{{ $streak > 0 ? __('me.today.streak') : __('me.today.streakNone') }}</div>
      </div>
      <div style="text-align:right">
        @if ($rate7 !== null)<div><strong>{{ $rate7 }}%</strong> <span class="hint">{{ __('me.history.week') }}</span></div>@endif
        @if ($rate30 !== null)<div><strong>{{ $rate30 }}%</strong> <span class="hint">{{ __('me.history.month') }}</span></div>@endif
      </div>
    </div>
  </div></div>

  <div class="chart-card" style="margin-top:var(--s4)">
    {!! Chart::area($series['values'], ['labels' => $series['labels'], 'min' => 0, 'max' => 100,
         'tone' => 'safe', 'band' => ['from' => 80, 'to' => 100], 'title' => __('me.history.title')]) !!}
  </div>

  <div class="label" style="margin-top:var(--s4)"><div class="label-body">
    <div class="dot-grid">
      @foreach ($days as $day)
        <span class="dot-col">
          @forelse ($day['doses'] as $status)
            <span class="dot dot-{{ $status === 'taken' ? 'taken' : 'missed' }}"
                  title="{{ $day['date']->format('j M') }}"></span>
          @empty
            <span class="dot"></span>
          @endforelse
        </span>
      @endforeach
    </div>
    <div class="row wrap hint" style="margin-top:var(--s4);gap:var(--s4)">
      <span class="row" style="gap:var(--s2)"><span class="dot dot-taken"></span> {{ __('me.history.legendTaken') }}</span>
      <span class="row" style="gap:var(--s2)"><span class="dot dot-missed"></span> {{ __('me.history.legendMissed') }}</span>
    </div>
    <p class="hint" style="margin-top:var(--s2)">{{ __('me.history.each') }}</p>
  </div></div>

  <div class="section-title">{{ __('me.history.reports') }}</div>
  <div class="stack">
    @forelse ($reports as $r)
      <div class="label" wire:key="r-{{ $r->id }}"><div class="label-body">
        <div class="row-between wrap" style="gap:var(--s2)">
          <strong>{{ collect($r->symptoms)->map(fn ($s) => __('respond.'.$s))->join(', ') }}</strong>
          <span class="hint">{{ $r->created_at->diffForHumans() }}</span>
        </div>
        @if ($r->note)<p class="muted" style="margin-top:var(--s2)">&ldquo;{{ $r->note }}&rdquo;</p>@endif
        @if ($r->reviewed_at)<span class="badge badge-safe" style="margin-top:var(--s2)">A nurse has read this</span>@endif
      </div></div>
    @empty
      <div class="label"><div class="empty">{{ __('me.history.none') }}</div></div>
    @endforelse
  </div>
</div>
