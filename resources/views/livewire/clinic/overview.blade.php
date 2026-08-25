@php use App\Support\Chart; @endphp
<div>
  <div class="band band-info" style="border-radius:var(--radius);margin-bottom:var(--s5)">
    <x-icon name="send" class="band-icon" />
    <div>
      <span class="band-title">See the loop close</span>
      Open the <a href="{{ route('clinic.outbox') }}" wire:navigate>message outbox</a>, run the dispatcher,
      then open the reminder link from the mail log. The response lands right here.
    </div>
  </div>

  <div class="grid grid-3">
    @foreach ([
      ['Patients enrolled', $stats['enrolled'], 'active in this programme', null],
      ['Adherence · 7 days', $stats['adherence7'].'%', 'cohort mean', null],
      ['Reports today', $stats['reportsToday'], 'patient-reported outcomes', null],
      ['Needs attention', $stats['needsAttention'], 'below threshold or silent', $stats['needsAttention'] ? 'alert' : 'safe'],
      ['Open complaints', $stats['complaintsOpen'], 'awaiting review', $stats['complaintsOpen'] ? 'caution' : 'safe'],
      ['Reminder response', $stats['respondRate'].'%', 'replied within the window', null],
    ] as [$label, $value, $note, $tone])
      <div class="stat">
        <div class="stat-label">{{ $label }}</div>
        <div class="stat-value" @if($tone) style="color:var(--{{ $tone }})" @endif>{{ $value }}</div>
        <div class="stat-note">{{ $note }}</div>
      </div>
    @endforeach
  </div>

  <div class="grid" style="grid-template-columns:1.6fr 1fr;margin-top:var(--s5)">
    <div class="chart-card">
      <div class="chart-head">
        <span class="chart-title">Cohort adherence</span>
        <span class="chart-note">last 30 days · shaded band is the 80% target</span>
      </div>
      {!! Chart::area($trend['values'], ['labels' => $trend['labels'], 'min' => 40, 'max' => 100,
           'tone' => 'safe', 'band' => ['from' => 80, 'to' => 100], 'title' => 'Cohort adherence']) !!}
    </div>

    <div class="chart-card">
      <div class="chart-head"><span class="chart-title">Who is on track</span></div>
      {!! Chart::donut($bands, ['centre' => ['value' => $stats['enrolled'], 'label' => 'patients']]) !!}
      {!! Chart::legend(collect($bands)->map(fn ($b) => ['label' => $b['label'], 'tone' => $b['tone'], 'value' => $b['value']])->all()) !!}
    </div>
  </div>

  <div class="chart-card" style="margin-top:var(--s5)">
    <div class="chart-head">
      <span class="chart-title">Adherence by dose window</span>
      <span class="chart-note">30 days · darker is better</span>
    </div>
    {!! Chart::heatStrip(collect($heat)->map(fn ($h) => ['label' => $h['label'].' · '.$h['pct'].'%', 'value' => $h['value']])->all(), ['tone' => 'safe']) !!}

    @if ($paired)
      <div class="band band-info" style="border-radius:var(--radius);margin-top:var(--s4)">
        <x-icon name="info" class="band-icon" />
        <div>
          <span class="band-title">Read these bars carefully</span>
          They compare <em>windows</em>, not like with like — each bar is a different set of people,
          so most of what you see is who is in each bucket. The comparison that means something is
          <strong>within</strong> a patient: among the {{ $paired['n'] }} people who take both a
          morning and an evening dose, morning runs <strong>{{ $paired['morning'] }}%</strong> and
          evening <strong>{{ $paired['evening'] }}%</strong> — a {{ $paired['gap'] }}-point gap with
          each patient as their own control.
        </div>
      </div>
    @endif
  </div>

  <div class="section-title">Needs attention</div>
  @if ($attention->isEmpty())
    <div class="label"><div class="empty">Everyone is on track today.</div></div>
  @else
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Patient</th><th>Condition</th><th>Adherence · 7d</th><th>Last reply</th><th>Status</th><th>Why</th></tr></thead>
      <tbody>
      @foreach ($attention as $p)
        <tr class="is-{{ $p->attention['level'] }}" style="cursor:pointer"
            onclick="window.location='{{ route('clinic.patient', $p) }}'">
          <td><strong>{{ $p->name }}</strong><div class="hint">{{ $p->age }} years · {{ strtoupper($p->lang) }}</div></td>
          <td>{{ $p->condition }}</td>
          <td>{!! Chart::meter($p->adherence7) !!}</td>
          <td class="hint">{{ $p->last_response_at?->diffForHumans() ?? '—' }}</td>
          <td><span class="badge badge-{{ ['critical' => 'alert', 'concern' => 'caution', 'ok' => 'safe'][$p->attention['level']] }}">
            {{ ['critical' => 'Needs action', 'concern' => 'Watch', 'ok' => 'On track'][$p->attention['level']] }}</span></td>
          <td class="hint">{{ implode(' · ', $p->attention['reasons']) }}</td>
        </tr>
      @endforeach
      </tbody>
    </table></div>
  @endif

  <div class="section-title">Recent patient reports</div>
  <div class="stack">
    @forelse ($reports as $r)
      <article class="label">
        <div class="label-body">
          <div class="row-between wrap" style="gap:var(--s2)">
            <div class="row wrap" style="gap:var(--s2)">
              <a href="{{ route('clinic.patient', $r->patient) }}" wire:navigate><strong>{{ $r->patient?->name }}</strong></a>
              <span class="badge badge-{{ $r->severity >= 2 ? 'alert' : ($r->severity === 1 ? 'caution' : 'safe') }}">{{ $r->severityLabel() }}</span>
              @if ($r->source === 'link')<span class="badge badge-info">via reminder</span>@endif
              @if ($r->audio_path)<span class="badge">voice note · {{ strtoupper($r->lang) }}</span>@endif
              @if ($r->took_dose === false)<span class="badge badge-alert">dose not taken</span>@endif
            </div>
            <span class="hint">{{ $r->created_at->diffForHumans() }}</span>
          </div>
          <p style="margin-top:var(--s2)">
            {{ collect($r->symptoms)->map(fn ($s) => __('respond.'.$s))->join(', ') }}
            @if ($r->prescription)<span class="hint"> — while taking {{ $r->prescription->name }}</span>@endif
          </p>
          @if ($r->note)<p class="muted" style="margin-top:var(--s1)">&ldquo;{{ $r->note }}&rdquo;</p>@endif
        </div>
      </article>
    @empty
      <div class="label"><div class="empty">No reports yet.</div></div>
    @endforelse
  </div>
</div>
