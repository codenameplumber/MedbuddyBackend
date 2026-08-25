@php use App\Support\Chart; @endphp
<div>
  <div class="grid grid-3">
    <div class="stat">
      <div class="stat-label">Cohort mean · 30d</div>
      <div class="stat-value">{{ (int) round(array_sum($trend['values']) / max(1, count($trend['values']))) }}%</div>
      <div class="stat-note">all patients, all doses</div>
    </div>
    <div class="stat">
      <div class="stat-label">Morning dose</div>
      <div class="stat-value" style="color:var(--safe)">{{ $paired['morning'] ?? '—' }}%</div>
      <div class="stat-note">{{ $paired ? $paired['n'].' patients who take both' : 'no paired patients' }}</div>
    </div>
    <div class="stat">
      <div class="stat-label">Evening dose</div>
      <div class="stat-value" style="color:var(--caution)">{{ $paired['evening'] ?? '—' }}%</div>
      <div class="stat-note">same patients, same 30 days</div>
    </div>
    <div class="stat">
      <div class="stat-label">Within-patient gap</div>
      <div class="stat-value" style="color:var(--alert)">{{ $paired ? $paired['gap'].' pts' : '—' }}</div>
      <div class="stat-note">each patient is their own control</div>
    </div>
  </div>

  <div class="chart-card" style="margin-top:var(--s5)">
    <div class="chart-head">
      <span class="chart-title">Cohort adherence, 30 days</span>
      <span class="chart-note">shaded band is the 80% clinical target</span>
    </div>
    {!! Chart::area($trend['values'], ['labels' => $trend['labels'], 'min' => 40, 'max' => 100,
         'tone' => 'safe', 'band' => ['from' => 80, 'to' => 100], 'height' => 220]) !!}
  </div>

  <div class="chart-card" style="margin-top:var(--s5)">
    <div class="chart-head">
      <span class="chart-title">Adherence by dose window</span>
      <span class="chart-note">30 days · darker is better</span>
    </div>
    {!! Chart::heatStrip(collect($heat)->map(fn ($h) => ['label' => $h['label'].' · '.$h['pct'].'%', 'value' => $h['value']])->all(), ['tone' => 'safe']) !!}

    <div class="foot-note" style="margin-top:var(--s5)">
      <strong style="color:var(--ink)">These bars do not compare like with like.</strong>
      Each bar is a different set of people, so most of what you are seeing is <em>who</em> is in each
      bucket, not what time of day does. Comparing them directly would be the same confounding error
      the outcomes page warns about.
      @if ($paired)
        <div style="margin-top:var(--s3)">
          <strong style="color:var(--ink)">The valid comparison is within a patient.</strong>
          Among the {{ $paired['n'] }} people who take both a morning and an evening dose, morning runs
          <strong>{{ $paired['morning'] }}%</strong> and evening <strong>{{ $paired['evening'] }}%</strong>
          — a {{ $paired['gap'] }}-point gap with each patient acting as their own control. That points at
          routine rather than at the medicine, and the intervention is reminder timing. With
          n={{ $paired['n'] }} it is still a hypothesis, not a result.
        </div>
      @endif
    </div>
  </div>

  <div class="grid" style="grid-template-columns:1fr 1fr;margin-top:var(--s5)">
    <div class="chart-card">
      <div class="chart-head"><span class="chart-title">Adherence per patient</span>
        <span class="chart-note">30 days · taken vs missed</span></div>
      {!! Chart::stackedBars($stacked, ['title' => 'Doses taken and missed']) !!}
      {!! Chart::legend([['label' => 'Taken', 'tone' => 'safe'], ['label' => 'Missed', 'tone' => 'alert']]) !!}
    </div>

    <div class="chart-card">
      <div class="chart-head"><span class="chart-title">Adherence by medicine</span></div>
      {!! Chart::bars(collect($drugs)->map(fn ($d) => [
            'label' => \Illuminate\Support\Str::limit(explode(' ', $d['name'])[0], 9, ''),
            'value' => $d['adherence'],
            'tone'  => $d['adherence'] < 60 ? 'alert' : ($d['adherence'] < 80 ? 'caution' : 'safe'),
          ])->all(), ['max' => 100, 'unit' => '%']) !!}
      <p class="hint" style="margin-top:var(--s3)">
        Resist reading a dose-frequency story into this. With one or two patients per drug, these
        bars are showing you individuals, not pharmacology.
      </p>
    </div>
  </div>

  <div class="section-title">Patients losing ground</div>
  @if ($falling->isEmpty())
    <div class="label"><div class="empty">Nobody is trending downwards this fortnight.</div></div>
  @else
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Patient</th><th>Condition</th><th>Week 1</th><th>Week 2</th><th>Change</th></tr></thead>
      <tbody>
        @foreach ($falling as $p)
          <tr class="is-{{ $p->attention['level'] }}" style="cursor:pointer"
              onclick="window.location='{{ route('clinic.patient', $p) }}'">
            <td><strong>{{ $p->name }}</strong></td>
            <td>{{ $p->condition }}</td>
            <td class="num hint">{{ $p->week1 }}%</td>
            <td>{!! Chart::meter($p->week2) !!}</td>
            <td><span class="badge badge-{{ $p->delta <= -15 ? 'alert' : 'caution' }}">{{ $p->delta }} pts</span></td>
          </tr>
        @endforeach
      </tbody>
    </table></div>
    <p class="hint" style="margin-top:var(--s3)">
      A patient at 70% and falling needs a different response from one at 70% and climbing.
      Level alone hides that.
    </p>
  @endif
</div>
