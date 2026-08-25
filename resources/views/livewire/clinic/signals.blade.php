@php use App\Support\Chart; @endphp
<div>
  <div class="band band-alert" style="border-radius:var(--radius);margin-bottom:var(--s5)">
    <x-icon name="alert" class="band-icon" />
    <div>
      <span class="band-title">These are signals, not findings</span>
      This cohort is small, self-selected and unblinded, and a patient reporting a symptom while
      taking a drug is not the drug causing it. Confounding by the underlying condition is the
      obvious rival explanation for most rows below. Treat every line as
      <strong>a question worth investigating</strong>, never as evidence of causation, and route
      anything serious into the formal pharmacovigilance channel.
    </div>
  </div>

  <div class="grid grid-3">
    <div class="stat"><div class="stat-label">Active signals</div>
      <div class="stat-value">{{ count($rows) }}</div><div class="stat-note">drug × symptom pairs</div></div>
    <div class="stat"><div class="stat-label">With a severe report</div>
      <div class="stat-value" style="color:var(--{{ $severe ? 'alert' : 'safe' }})">{{ $severe }}</div>
      <div class="stat-note">worth escalating first</div></div>
    <div class="stat"><div class="stat-label">Patients affected</div>
      <div class="stat-value">{{ collect($rows)->sum('affected') }}</div><div class="stat-note">across all signals</div></div>
    <div class="stat"><div class="stat-label">Highest rate</div>
      <div class="stat-value" style="color:var(--caution)">{{ $rows[0]['rate'] ?? 0 }}%</div>
      <div class="stat-note">{{ isset($rows[0]) ? __('respond.'.$rows[0]['symptom']).' on '.explode(' ', $rows[0]['drug'])[0] : '—' }}</div></div>
  </div>

  @if ($rows)
    <div class="chart-card" style="margin-top:var(--s5)">
      <div class="chart-head"><span class="chart-title">Reporting rate by signal</span>
        <span class="chart-note">patients reporting ÷ patients on that medicine</span></div>
      {!! Chart::bars(collect($rows)->map(fn ($r) => [
            'label' => \Illuminate\Support\Str::limit(explode(' ', $r['drug'])[0], 8, '').'/'.\Illuminate\Support\Str::limit($r['symptom'], 5, ''),
            'value' => $r['rate'],
            'tone'  => $r['rate'] >= 50 ? 'alert' : ($r['rate'] >= 25 ? 'caution' : 'safe'),
          ])->all(), ['max' => 100, 'unit' => '%']) !!}
      <p class="hint" style="margin-top:var(--s3)">
        Denominators this small make a single report swing the percentage wildly. The count columns
        in the table below matter more than these bars.
      </p>
    </div>
  @endif

  <div class="section-title">Signal table</div>
  <div class="table-wrap"><table class="table">
    <thead><tr>
      <th>Medicine</th><th>Reported symptom</th><th>Patients affected</th>
      <th>On this medicine</th><th>Rate</th><th>Reports</th><th>Highest severity</th>
    </tr></thead>
    <tbody>
      @forelse ($rows as $r)
        <tr>
          <td><strong>{{ $r['drug'] }}</strong></td>
          <td>{{ __('respond.'.$r['symptom']) }}</td>
          <td class="num">{{ $r['affected'] }}</td>
          <td class="num hint">{{ $r['exposed'] }}</td>
          <td><span class="badge badge-{{ $r['rate'] >= 50 ? 'alert' : ($r['rate'] >= 25 ? 'caution' : 'safe') }}">{{ $r['rate'] }}%</span></td>
          <td class="num">{{ $r['reports'] }}</td>
          <td><span class="badge badge-{{ $r['maxSeverity'] >= 2 ? 'alert' : ($r['maxSeverity'] === 1 ? 'caution' : 'safe') }}">
            {{ ['None', 'Mild', 'Moderate', 'Severe'][$r['maxSeverity']] }}</span></td>
        </tr>
      @empty
        <tr><td colspan="7"><div class="empty">No symptom reports yet.</div></td></tr>
      @endforelse
    </tbody>
  </table></div>

  <div class="section-title">Why this matters</div>
  <div class="label"><div class="label-body">
    <p>
      Adverse drug reactions are under-reported almost everywhere, and the reports that do get filed
      come overwhelmingly from clinicians rather than patients. The patient-experienced side — the
      rash that never reached a clinic, the dizziness someone quietly decided to live with, the dose
      they stopped taking without telling anyone — is largely invisible.
    </p>
    <p style="margin-top:var(--s3)">
      A reminder that a patient answers in their own language, with one tap, captures exactly that
      missing layer. Adherence data explains <em>whether</em> people took the medicine; this explains
      <em>why they stopped</em>.
    </p>
    <p style="margin-top:var(--s3)" class="hint">
      Anything meeting the reporting threshold belongs in the national pharmacovigilance system as
      well as here. This console is a detector, not a substitute.
    </p>
  </div></div>
</div>
