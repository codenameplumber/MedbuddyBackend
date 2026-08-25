@php use App\Support\Chart; @endphp
<div>
  {{-- The honest framing goes ABOVE the encouraging numbers, not in a footnote under them. --}}
  <div class="band band-alert" style="border-radius:var(--radius);margin-bottom:var(--s5)">
    <x-icon name="alert" class="band-icon" />
    <div>
      <span class="band-title">Read this before the numbers</span>
      The "before enrolment" baseline is an <strong>assumption, not a measurement</strong> — nobody was
      tracking these patients before they joined. Everyone here also self-selected into a programme,
      which is exactly the population most likely to improve anyway. Treat the lift below as
      <em>a hypothesis worth testing properly</em>, never as a result. A real answer needs a control arm.
    </div>
  </div>

  <div class="grid grid-3">
    <div class="stat"><div class="stat-label">Assumed baseline</div>
      <div class="stat-value">{{ $o['baseline'] }}%</div><div class="stat-note">typical untracked adherence</div></div>
    <div class="stat"><div class="stat-label">Cohort now</div>
      <div class="stat-value" style="color:var(--safe)">{{ $o['cohortNow'] }}%</div><div class="stat-note">30-day mean</div></div>
    <div class="stat"><div class="stat-label">Apparent lift</div>
      <div class="stat-value" style="color:var(--caution)">{{ $o['lift'] >= 0 ? '+' : '' }}{{ $o['lift'] }} pts</div>
      <div class="stat-note">unverified — see the caveat</div></div>
    <div class="stat"><div class="stat-label">Channel</div>
      <div class="stat-value" style="font-size:var(--step-2)">Email</div>
      <div class="stat-note">the only one enabled today</div></div>
  </div>

  <div class="grid" style="grid-template-columns:1fr 1fr;margin-top:var(--s5)">
    <div class="chart-card">
      <div class="chart-head"><span class="chart-title">Where the cohort sits now</span>
        <span class="chart-note">30 days · target band shaded</span></div>
      {!! Chart::area($trend['values'], ['labels' => $trend['labels'], 'min' => 40, 'max' => 100,
           'tone' => 'safe', 'band' => ['from' => 80, 'to' => 100]]) !!}
    </div>
    <div class="chart-card center">
      <div class="chart-head" style="justify-content:center"><span class="chart-title">Against the 80% target</span></div>
      {!! Chart::ring($o['cohortNow'], ['size' => 168, 'thickness' => 18,
           'tone' => $o['cohortNow'] >= 80 ? 'safe' : 'caution', 'label' => 'Cohort adherence']) !!}
      <p class="hint" style="margin-top:var(--s4)">
        80% is the conventional threshold above which adherence is considered clinically adequate for
        most chronic therapies. It is a rule of thumb, not a law.
      </p>
    </div>
  </div>

  <div class="section-title">By condition</div>
  <div class="chart-card">
    {!! Chart::bars(collect($o['conditions'])->map(fn ($c) => [
          'label' => explode(' ', $c['condition'])[0],
          'value' => $c['adherence'],
          'tone'  => $c['adherence'] < 60 ? 'alert' : ($c['adherence'] < 80 ? 'caution' : 'safe'),
        ])->all(), ['max' => 100, 'unit' => '%', 'title' => 'Adherence by condition']) !!}

    <div class="table-wrap" style="margin-top:var(--s5)"><table class="table">
      <thead><tr><th>Condition</th><th>Patients</th><th>Adherence · 30d</th><th>Read</th></tr></thead>
      <tbody>
        @foreach ($o['conditions'] as $c)
          <tr>
            <td><strong>{{ $c['condition'] }}</strong></td>
            <td class="num">{{ $c['patients'] }}</td>
            <td>{!! Chart::meter($c['adherence']) !!}</td>
            <td class="hint">
              @if ($c['adherence'] < 60) Course at risk — regimen complexity and side effects both plausible.
              @elseif ($c['adherence'] < 80) Below target. Worth a scheduling review.
              @else Holding above target. @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table></div>
    <p class="hint" style="margin-top:var(--s3)">
      Denominators here are single digits. One patient moving changes a whole row.
    </p>
  </div>

  <div class="section-title">By the patient's own language</div>
  <div class="chart-card">
    {!! Chart::bars(collect($o['languages'])->map(fn ($l) => [
          'label' => $l['name'],
          'value' => $l['adherence'],
          'tone'  => $l['adherence'] < 60 ? 'alert' : ($l['adherence'] < 80 ? 'caution' : 'safe'),
        ])->all(), ['max' => 100, 'unit' => '%', 'title' => 'Adherence by language']) !!}
    {!! Chart::legend(collect($o['languages'])->map(fn ($l) => [
          'label' => $l['name'].' · '.$l['patients'].' patients', 'tone' => 'neutral',
          'value' => $l['reports'].' reports',
        ])->all()) !!}
    <p class="hint" style="margin-top:var(--s3)">
      The question this is really asking: does answering in your own language change whether you
      answer at all? Too few patients here to tell — but it is the measurement the whole product is
      built to make.
    </p>
  </div>
</div>
