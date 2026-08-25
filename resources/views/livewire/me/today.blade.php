@php use App\Support\Chart; @endphp
<div>
  @if ($progress['total'] > 0)
    <div class="label"><div class="label-body">
      <div class="row" style="gap:var(--s5)">
        {!! Chart::ring(
          $progress['total'] ? (int) round($progress['taken'] / $progress['total'] * 100) : 0,
          ['size' => 104, 'thickness' => 11,
           'tone' => $progress['remaining'] === 0 ? 'safe' : 'caution',
           'label' => __('me.today.title')]
        ) !!}
        <div class="grow">
          <div class="streak-n">{{ $streak }}</div>
          <div class="muted">{{ $streak > 0 ? __('me.today.streak') : __('me.today.streakNone') }}</div>
          @if ($rate !== null)
            <div class="hint" style="margin-top:var(--s2)">
              {{ $rate }}% {{ __('me.history.week') }}
            </div>
          @endif
          <a class="btn btn-quiet btn-sm" style="padding-left:0" href="{{ route('me.history') }}" wire:navigate>
            {{ __('me.history.link') }} →
          </a>
        </div>
      </div>
    </div></div>

    @if ($progress['remaining'] === 0)
      <div class="band band-safe" style="border-radius:var(--radius);margin-top:var(--s4)">
        <x-icon name="check" class="band-icon" />
        <div><span class="band-title">{{ __('me.today.allDone') }}</span></div>
      </div>
    @else
      <p class="hint" style="margin-top:var(--s4)">
        {{ $progress['remaining'] }} {{ __('me.today.remaining') }}
      </p>
    @endif
  @endif

  <div style="margin-top:var(--s5)">
    @if (! $doses)
      <div class="label"><div class="empty">
        <p style="font-size:var(--step-1)">{{ __('me.today.none') }}</p>
        <a class="btn" style="margin-top:var(--s5)" href="{{ route('me.medicine.add') }}" wire:navigate>
          {{ __('me.today.addFirst') }}
        </a>
      </div></div>
    @else
      <div class="stack">
        @foreach ($doses as $d)
          @php $done = $d['status'] === 'taken'; @endphp
          <article class="label {{ $done ? 'dose-taken' : '' }}" wire:key="d-{{ $d['prescription']->id }}-{{ $d['time'] }}">
            <div class="dose">
              <a class="grow" href="{{ route('me.medicine', $d['prescription']) }}" wire:navigate
                 style="text-decoration:none;color:inherit">
                <div class="dose-time">{{ \App\Support\Clock::time($d['time']) }}</div>
                <div class="med-name">{{ $d['prescription']->name }}</div>
                @if ($d['prescription']->strength)<div class="dosage">{{ $d['prescription']->strength }}</div>@endif
                @if ($d['prescription']->purpose)<div class="med-generic">{{ $d['prescription']->purpose }}</div>@endif
              </a>
              <button class="check" type="button"
                      wire:click="toggle({{ $d['prescription']->id }}, '{{ $d['time'] }}')"
                      aria-pressed="{{ $done ? 'true' : 'false' }}"
                      aria-label="{{ $done ? __('me.today.taken') : __('me.today.markTaken') }}: {{ $d['prescription']->name }}">✓</button>
            </div>
          </article>
        @endforeach
      </div>

      <a class="btn btn-secondary btn-block" style="margin-top:var(--s5)" href="{{ route('me.report') }}" wire:navigate>
        <x-icon name="chat" class="i-sm" /> {{ __('me.report.title') }}
      </a>
    @endif
  </div>
</div>
