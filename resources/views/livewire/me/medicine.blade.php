@php use App\Support\Chart; @endphp
<div>
  <div class="label"><div class="label-body">
    <div class="med-name" style="font-size:var(--step-2)">{{ $rx->name }}</div>
    @if ($rx->strength)<div class="dosage">{{ $rx->strength }}</div>@endif
    @if ($rx->purpose)<p class="muted" style="margin-top:var(--s2)">{{ $rx->purpose }}</p>@endif

    <div class="row wrap" style="gap:var(--s2);margin-top:var(--s4)">
      @foreach ($rx->times ?? [] as $t)<span class="badge">{{ $t }}</span>@endforeach
    </div>
  </div></div>

  <div class="label" style="margin-top:var(--s4)"><div class="label-body">
    <div class="row" style="gap:var(--s5)">
      {!! Chart::ring($adherence, ['size' => 92, 'thickness' => 10,
           'tone' => $adherence < 60 ? 'alert' : ($adherence < 80 ? 'caution' : 'safe'),
           'label' => 'Doses taken']) !!}
      <div class="grow">
        <strong>{{ $adherence }}%</strong>
        <div class="hint">of this medicine's doses over 30 days</div>
      </div>
    </div>
  </div></div>

  @if ($summary)
    <div class="stack" style="margin-top:var(--s4)">
      @if ($summary['purpose'])
        <div class="label"><div class="label-body">
          <div class="section-title" style="margin-top:0">{{ __('me.explain.purpose') }}</div>
          <p>{{ $summary['purpose'] }}</p>
        </div></div>
      @endif

      @if ($summary['dosing'])
        <div class="label"><div class="label-body">
          <div class="section-title" style="margin-top:0">{{ __('me.explain.dosing') }}</div>
          <p>{{ $summary['dosing'] }}</p>
        </div></div>
      @endif

      @if ($summary['sideEffects'])
        <div class="label"><div class="label-body">
          <div class="section-title" style="margin-top:0">{{ __('me.explain.sideEffects') }}</div>
          <ul style="margin:var(--s2) 0 0;padding-left:1.1rem">
            @foreach ($summary['sideEffects'] as $s)<li>{{ $s }}</li>@endforeach
          </ul>
        </div></div>
      @endif

      @if ($summary['warnings'])
        <div class="label"><div class="label-body">
          <div class="section-title" style="margin-top:0">{{ __('me.explain.warnings') }}</div>
          <ul style="margin:var(--s2) 0 0;padding-left:1.1rem">
            @foreach ($summary['warnings'] as $w)<li>{{ $w }}</li>@endforeach
          </ul>
        </div></div>
      @endif

      @if ($summary['storage'])
        <div class="label"><div class="label-body">
          <div class="section-title" style="margin-top:0">{{ __('me.explain.storage') }}</div>
          <p>{{ $summary['storage'] }}</p>
        </div></div>
      @endif
    </div>

    <p class="hint" style="margin-top:var(--s4)">{{ __('me.common.aiNote') }}</p>
  @else
    <div class="label" style="margin-top:var(--s4)"><div class="empty">
      <p>No explanation saved for this medicine yet.</p>
      <a class="btn btn-secondary" style="margin-top:var(--s4)" href="{{ route('me.ask') }}" wire:navigate>
        {{ __('me.ask.title') }}
      </a>
    </div></div>
  @endif

  <a class="btn btn-secondary btn-block" style="margin-top:var(--s5)" href="{{ route('me.report') }}" wire:navigate>
    {{ __('respond.q2') }}
  </a>
</div>
