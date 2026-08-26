@php use App\Support\Chart; @endphp
<div>
  @if ($attention['reasons'])
    <div class="band band-{{ $attention['level'] === 'critical' ? 'alert' : '' }}" style="border-radius:var(--radius);margin-bottom:var(--s5)">
      <x-icon name="alert" class="band-icon" />
      <div><span class="band-title">Flagged</span>{{ implode(' · ', $attention['reasons']) }}</div>
    </div>
  @endif

  <div class="row wrap" style="margin-bottom:var(--s5)">
    <span class="badge badge-{{ ['critical' => 'alert', 'concern' => 'caution', 'ok' => 'safe'][$attention['level']] }}">
      {{ ['critical' => 'Needs action', 'concern' => 'Watch', 'ok' => 'On track'][$attention['level']] }}</span>
    <span class="badge">{{ $patient->langName() }}</span>
    <span class="badge">{{ $patient->condition }}</span>
    <span class="hint mono">{{ $patient->email }}</span>
    <span class="grow"></span>
    {{--
      What this patient sees. Only ever offered for the fabricated cohort, so
      it is a demo affordance rather than impersonation of a real person.
    --}}
    @if (config('medbuddy.demo_signin') && str_ends_with((string) $patient->email, config('medbuddy.demo_domain')))
      <form method="POST" action="{{ route('me.demo') }}" target="_blank">
        @csrf
        <input type="hidden" name="patient" value="{{ $patient->id }}">
        <button class="btn btn-secondary btn-sm" type="submit">
          <x-icon name="user" class="i-sm" /> Open patient view
        </button>
      </form>
    @endif
    @can('update', $patient)
      <a class="btn btn-secondary btn-sm" href="{{ route('clinic.patient.edit', $patient) }}" wire:navigate>Edit details</a>
    @endcan
    @can('create', \App\Models\Prescription::class)
      <a class="btn btn-secondary btn-sm" href="{{ route('clinic.prescription.form', $patient) }}" wire:navigate>
        <x-icon name="plus" class="i-sm" /> Add medicine
      </a>
    @endcan
    <button class="btn" type="button" wire:click="sendReminderNow" wire:loading.attr="disabled">
      <span wire:loading.remove wire:target="sendReminderNow"><x-icon name="bell" class="i-sm" /> Send reminder now</span>
      <span wire:loading wire:target="sendReminderNow">Sending…</span>
    </button>
  </div>

  <div class="grid" style="grid-template-columns:1fr 1fr 1fr">
    <div class="chart-card center">
      <div class="chart-head" style="justify-content:center"><span class="chart-title">7-day</span></div>
      {!! Chart::ring($a7, ['tone' => $a7 < 60 ? 'alert' : ($a7 < 80 ? 'caution' : 'safe'), 'label' => '7-day adherence']) !!}
    </div>
    <div class="chart-card center">
      <div class="chart-head" style="justify-content:center"><span class="chart-title">30-day</span></div>
      {!! Chart::ring($a30, ['tone' => $a30 < 60 ? 'alert' : ($a30 < 80 ? 'caution' : 'safe'), 'label' => '30-day adherence']) !!}
    </div>
    <div class="stat" style="display:flex;flex-direction:column;justify-content:center">
      <div class="stat-label">Enrolled</div>
      <div class="stat-note" style="font-size:var(--step-0);color:var(--ink)">{{ $patient->enrolled_at?->format('j M Y') }}</div>
      <div class="stat-label" style="margin-top:var(--s4)">Last reply</div>
      <div class="stat-note" style="font-size:var(--step-0);color:var(--ink)">{{ $patient->last_response_at?->diffForHumans() ?? '—' }}</div>
      <div class="stat-label" style="margin-top:var(--s4)">Reports</div>
      <div class="stat-note" style="font-size:var(--step-0);color:var(--ink)">{{ $patient->reports->count() }}</div>
    </div>
  </div>

  <div class="chart-card" style="margin-top:var(--s5)">
    <div class="chart-head">
      <span class="chart-title">Adherence over time</span>
      <span class="chart-note">60 days · shaded band is the 80% target</span>
    </div>
    {!! Chart::area($series['values'], ['labels' => $series['labels'], 'min' => 0, 'max' => 100,
         'tone' => 'safe', 'band' => ['from' => 80, 'to' => 100], 'title' => $patient->name.' adherence']) !!}
  </div>

  <div class="section-title">Dose history · 14 days</div>
  <div class="label"><div class="label-body">
    <div class="dot-grid">
      @foreach ($history as $day)
        <span class="dot-col">
          @forelse ($day['doses'] as $status)
            <span class="dot dot-{{ $status === 'taken' ? 'taken' : 'missed' }}"
                  title="{{ $day['date']->format('j M') }}: {{ $status }}"></span>
          @empty
            <span class="dot"></span>
          @endforelse
        </span>
      @endforeach
    </div>
    <div class="row-between hint" style="margin-top:var(--s2)">
      <span>{{ $history[0]['date']->format('j M') }}</span><span>today</span>
    </div>
    <div class="row wrap hint" style="margin-top:var(--s4);gap:var(--s4)">
      <span class="row" style="gap:var(--s2)"><span class="dot dot-taken"></span> taken</span>
      <span class="row" style="gap:var(--s2)"><span class="dot dot-missed"></span> missed</span>
      <span>Each column is one day; each dot is one scheduled dose.</span>
    </div>
  </div></div>

  <div class="section-title">Current medicines</div>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Medicine</th><th>Strength</th><th>For</th><th>Schedule</th><th>Explanation</th><th></th></tr></thead>
    <tbody>
      @forelse ($patient->activePrescriptions as $rx)
        <tr>
          <td><strong>{{ $rx->name }}</strong></td>
          <td class="mono">{{ $rx->strength }}</td>
          <td class="hint">{{ $rx->purpose }}</td>
          <td class="mono">{{ implode(' · ', $rx->times) }}</td>
          <td>
            @if ($rx->ai_summary)
              <span class="badge badge-safe">in {{ $patient->langName() }}</span>
            @else
              <span class="hint">none yet</span>
            @endif
          </td>
          <td class="num">
            @can('update', $rx)
              <a class="btn btn-quiet btn-sm" href="{{ route('clinic.prescription.form', [$patient, $rx]) }}" wire:navigate>Edit</a>
            @endcan
          </td>
        </tr>
      @empty
        <tr><td colspan="6"><div class="empty">No medicines recorded. Nothing will be reminded about.</div></td></tr>
      @endforelse
    </tbody>
  </table></div>

  <div class="section-title">Reported outcomes</div>
  <div class="stack">
    @forelse ($patient->reports->sortByDesc('created_at') as $r)
      <article class="label"><div class="label-body">
        <div class="row-between wrap" style="gap:var(--s2)">
          <div class="row wrap" style="gap:var(--s2)">
            <span class="badge badge-{{ $r->severity >= 2 ? 'alert' : ($r->severity === 1 ? 'caution' : 'safe') }}">{{ $r->severityLabel() }}</span>
            <span class="badge badge-info">{{ $r->source === 'link' ? 'via reminder' : 'in app' }}</span>
            @if ($r->audio_path)<span class="badge badge-info">voice note · {{ strtoupper($r->lang) }}</span>@endif
            @if ($r->took_dose === false)<span class="badge badge-alert">dose not taken</span>@endif
            @if ($r->reviewed_at)<span class="badge badge-safe">reviewed</span>@endif
          </div>
          <span class="hint">{{ $r->created_at->format('j M') }} · {{ $r->created_at->diffForHumans() }}</span>
        </div>
        <p style="margin-top:var(--s3)">
          <strong>{{ collect($r->symptoms)->map(fn ($s) => __('respond.'.$s))->join(', ') }}</strong>
          @if ($r->prescription)<span class="hint"> — while taking {{ $r->prescription->name }}</span>@endif
        </p>
        @if ($r->note)<p class="muted" style="margin-top:var(--s1)">&ldquo;{{ $r->note }}&rdquo;</p>@endif
        @if ($r->audio_path)
          <audio controls preload="none" style="width:100%;margin-top:var(--s3)"
                 src="{{ route('clinic.voice', $r) }}"></audio>
        @endif
        @if ($r->needsHumanReview())
          <p class="hint" style="margin-top:var(--s2)">
            No reliable speech-to-text for {{ $patient->langName() }} yet — a nurse listens to this one.
          </p>
        @endif
      </div></article>
    @empty
      <div class="label"><div class="empty">No reports from this patient yet.</div></div>
    @endforelse
  </div>

  <div class="section-title">Consent &amp; contact</div>
  <div class="label"><div class="label-body stack-s">
    @foreach ($patient->consents as $c)
      <div class="row-between wrap">
        <div>
          <strong>{{ ucfirst($c->scope) }}{{ $c->channel ? ' · '.$c->channel : '' }}</strong>
          <div class="hint">Recorded {{ $c->granted_at?->format('j M Y') }}</div>
        </div>
        <span class="badge badge-{{ $c->granted && ! $c->revoked_at ? 'safe' : 'alert' }}">
          {{ $c->granted && ! $c->revoked_at ? 'Granted' : 'Not granted' }}</span>
      </div>
    @endforeach
    @if ($patient->consents->isEmpty())
      <div class="empty">No consent on record. Nothing can be sent to this patient.</div>
    @endif
    @can('manageConsent', $patient)
      <div>
        <a class="btn btn-sm btn-secondary" href="{{ route('clinic.patient.edit', $patient) }}" wire:navigate>
          Change consent
        </a>
      </div>
    @endcan
    <p class="hint">
      Revocable per channel at any time. Every view of this record is written to the audit log.
    </p>
  </div></div>

  <div class="section-title">Caregiver</div>
  <div class="label"><div class="label-body stack">
    @if ($patient->caregiver_email)
      <div class="row-between wrap">
        <div>
          <strong>{{ $patient->caregiver_name ?: 'Caregiver' }}</strong>
          <div class="hint mono">{{ $patient->caregiver_email }}</div>
        </div>
        <button class="btn btn-sm" type="button" wire:click="draftCaregiverSummary" wire:loading.attr="disabled">
          <span wire:loading.remove wire:target="draftCaregiverSummary">Write a summary</span>
          <span wire:loading wire:target="draftCaregiverSummary">Writing…</span>
        </button>
      </div>
      <p class="hint">
        Generated from this patient's own dose history and reports, written in
        {{ $patient->langName() }}, and shown to you before it is sent. Unlike a
        dose reminder it <em>may</em> name medicines — it is a consented disclosure to
        one nominated person, not a lock-screen notification.
      </p>

      @if ($summary)
        <div class="label" style="background:var(--surface-2)"><div class="label-body stack-s">
          <div class="row-between wrap">
            <strong>For {{ $patient->caregiver_name ?: 'the caregiver' }} · {{ $patient->langName() }}</strong>
            <span class="badge badge-{{ $aiLive ? 'safe' : 'info' }}">{{ $aiLive ? 'live model' : 'demo response' }}</span>
          </div>
          <p>{{ $summary['forCaregiver'] }}</p>

          <div class="section-title" style="margin-top:var(--s3)">For the clinician</div>
          <p class="hint">{{ $summary['forClinician'] }}</p>

          @if ($summary['concerns'])
            <ul class="hint" style="margin:0;padding-left:1.1rem">
              @foreach ($summary['concerns'] as $c)<li>{{ $c }}</li>@endforeach
            </ul>
          @endif

          <div class="row" style="gap:var(--s3);margin-top:var(--s3)">
            <button class="btn btn-sm" type="button" wire:click="sendCaregiverSummary">
              Send to {{ $patient->caregiver_email }}
            </button>
            <button class="btn btn-sm btn-secondary" type="button" wire:click="$set('summary', null)">Discard</button>
          </div>
          <p class="hint">
            Nothing is sent until you press send. A model writes the draft; a person signs it off.
          </p>
        </div></div>
      @endif
    @else
      <div class="empty">
        No caregiver on record.
        @can('update', $patient)
          <a href="{{ route('clinic.patient.edit', $patient) }}" wire:navigate>Add one</a>.
        @endcan
      </div>
    @endif
  </div></div>
</div>
