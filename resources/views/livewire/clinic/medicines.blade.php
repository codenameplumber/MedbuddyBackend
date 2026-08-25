@php use App\Support\Chart; @endphp
<div>
  @php
    $worst = collect($all)->sortBy('adherence')->first();
    $most  = collect($all)->sortByDesc('complaintRate')->first();
  @endphp

  <div class="grid grid-3">
    <div class="stat"><div class="stat-label">Distinct medicines</div>
      <div class="stat-value">{{ count($all) }}</div><div class="stat-note">across the cohort</div></div>
    <div class="stat"><div class="stat-label">Total prescriptions</div>
      <div class="stat-value">{{ collect($all)->sum('patients') }}</div><div class="stat-note">patient-medicine pairs</div></div>
    <div class="stat"><div class="stat-label">Lowest adherence</div>
      <div class="stat-value" style="color:var(--alert)">{{ $worst['adherence'] ?? 0 }}%</div>
      <div class="stat-note">{{ $worst['name'] ?? '—' }}</div></div>
    <div class="stat"><div class="stat-label">Most complaints</div>
      <div class="stat-value" style="color:var(--caution)">{{ $most['complaintRate'] ?? 0 }}%</div>
      <div class="stat-note">of patients on {{ $most['name'] ?? '—' }}</div></div>
  </div>

  <div class="grid" style="grid-template-columns:1.4fr 1fr;margin-top:var(--s5)">
    <div class="chart-card">
      <div class="chart-head"><span class="chart-title">Adherence by medicine</span>
        <span class="chart-note">7-day mean across patients taking it</span></div>
      {!! Chart::bars(collect($all)->map(fn ($d) => [
            'label' => \Illuminate\Support\Str::limit(explode(' ', $d['name'])[0], 11, ''),
            'value' => $d['adherence'],
            'tone'  => $d['adherence'] < 60 ? 'alert' : ($d['adherence'] < 80 ? 'caution' : 'safe'),
          ])->all(), ['max' => 100, 'unit' => '%']) !!}
      <p class="hint" style="margin-top:var(--s3)">
        Bars here mix drug and patient: a medicine only two people take shows their adherence,
        not the drug's. Cross-reference the patient count column before drawing a conclusion.
      </p>
    </div>

    <div class="chart-card">
      <div class="chart-head"><span class="chart-title">Patients per medicine</span></div>
      @php $tones = ['info', 'safe', 'caution', 'alert', 'neutral', 'info']; @endphp
      {!! Chart::donut(collect($all)->take(6)->values()->map(fn ($d, $i) => [
            'label' => explode(' ', $d['name'])[0], 'value' => $d['patients'], 'tone' => $tones[$i % 6],
          ])->all(), ['centre' => ['value' => collect($all)->sum('patients'), 'label' => 'prescriptions']]) !!}
      {!! Chart::legend(collect($all)->take(6)->values()->map(fn ($d, $i) => [
            'label' => explode(' ', $d['name'])[0], 'value' => $d['patients'], 'tone' => $tones[$i % 6],
          ])->all()) !!}
    </div>
  </div>

  <div class="section-title">Formulary</div>
  <div class="dt-bar">
    <span class="input-group dt-search">
      <input class="input" type="search" wire:model.live.debounce.300ms="search"
             placeholder="Search medicine or condition…" aria-label="Filter medicines">
    </span>
    <span class="grow"></span>
    <span class="hint">{{ $drugs->count() }} shown</span>
  </div>

  <div class="table-wrap"><table class="table">
    <thead><tr>
      <th>Medicine</th><th>Prescribed for</th><th>Patients</th><th>Doses/day</th>
      <th>Adherence · 7d</th><th>Reports</th><th>Complaint rate</th><th>Worst severity</th>
    </tr></thead>
    <tbody>
      @forelse ($drugs as $d)
        <tr>
          <td><strong>{{ $d['name'] }}</strong><div class="hint mono">{{ $d['strength'] }}</div></td>
          <td>{{ $d['conditions'] }}</td>
          <td class="num">{{ $d['patients'] }}</td>
          <td class="num mono">{{ number_format($d['dosesPerDay'], 1) }}</td>
          <td>{!! Chart::meter($d['adherence']) !!}</td>
          <td class="num">{{ $d['complaints'] }}</td>
          <td><span class="badge badge-{{ $d['complaintRate'] >= 50 ? 'alert' : ($d['complaintRate'] >= 25 ? 'caution' : 'safe') }}">{{ $d['complaintRate'] }}%</span></td>
          <td><span class="badge badge-{{ $d['maxSeverity'] >= 2 ? 'alert' : ($d['maxSeverity'] === 1 ? 'caution' : 'safe') }}">
            {{ ['None', 'Mild', 'Moderate', 'Severe'][$d['maxSeverity']] }}</span></td>
        </tr>
      @empty
        <tr><td colspan="8"><div class="empty">No medicines match that filter.</div></td></tr>
      @endforelse
    </tbody>
  </table></div>

  <div class="section-title">Interaction checks</div>
  <div class="stack">
    @foreach ($interactions as $x)
      @php $tone = ['Major' => 'alert', 'Moderate' => 'caution', 'Minor' => 'info'][$x['severity']]; @endphp
      <div class="band band-{{ $tone }}" style="border-radius:var(--radius)">
        <x-icon :name="$x['severity'] === 'Major' ? 'alert' : 'info'" class="band-icon" />
        <div>
          <span class="band-title">{{ $x['severity'] }} · {{ $x['a'] }} + {{ $x['b'] }}</span>
          {{ $x['note'] }}
        </div>
      </div>
    @endforeach
    <div class="foot-note">
      <strong style="color:var(--ink)">Where this comes from.</strong>
      Severity is a lookup against a curated dataset scoped to the drugs this cohort actually takes.
      It is deliberately <em>not</em> a model recalling interactions from memory. The coverage is
      narrow and verifiable rather than broad and unverifiable: a pair not in the set returns
      nothing, and says so, instead of inventing a reassuring answer.
    </div>
  </div>
</div>
