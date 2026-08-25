<div>
  <div class="grid grid-3">
    <div class="stat"><div class="stat-label">Open complaints</div>
      <div class="stat-value">{{ $all->count() }}</div><div class="stat-note">symptom reports</div></div>
    <div class="stat"><div class="stat-label">Moderate or severe</div>
      <div class="stat-value" style="color:var(--{{ $severe ? 'alert' : 'safe' }})">{{ $severe }}</div>
      <div class="stat-note">prioritised in the queue</div></div>
    <div class="stat"><div class="stat-label">With a voice note</div>
      <div class="stat-value">{{ $audio }}</div><div class="stat-note">patient recorded audio</div></div>
    <div class="stat"><div class="stat-label">Awaiting human review</div>
      <div class="stat-value" style="color:var(--{{ $review ? 'caution' : 'safe' }})">{{ $review }}</div>
      <div class="stat-note">no reliable transcription for the language</div></div>
  </div>

  <div class="section-title">Triage queue</div>
  <div class="row wrap" style="margin-bottom:var(--s4)">
    @foreach (['all' => 'All', 'severe' => 'Moderate or severe', 'audio' => 'Has voice note',
               'review' => 'Awaiting review', 'missed' => 'Dose not taken'] as $key => $label)
      <button class="btn btn-sm {{ $filter === $key ? '' : 'btn-secondary' }}" type="button"
              wire:click="$set('filter', '{{ $key }}')">{{ $label }}</button>
    @endforeach
  </div>

  <div class="stack">
    @forelse ($rows as $r)
      <article class="label" wire:key="report-{{ $r->id }}">
        <div class="label-body">
          <div class="row-between wrap" style="gap:var(--s2)">
            <div class="row wrap" style="gap:var(--s2)">
              <a href="{{ route('clinic.patient', $r->patient) }}" wire:navigate><strong>{{ $r->patient?->name }}</strong></a>
              <span class="hint">{{ $r->patient?->condition }}</span>
              <span class="badge badge-{{ $r->severity >= 2 ? 'alert' : ($r->severity === 1 ? 'caution' : 'safe') }}">{{ $r->severityLabel() }}</span>
              @if ($r->took_dose === false)<span class="badge badge-alert">dose not taken</span>@endif
              @if ($r->triage_category)<span class="badge badge-alert">triage: {{ $r->triage_category }}</span>@endif
              @if ($r->reviewed_at)<span class="badge badge-safe">reviewed</span>@endif
            </div>
            <span class="hint">{{ $r->created_at->format('j M') }} · {{ $r->created_at->diffForHumans() }}</span>
          </div>

          <p style="margin-top:var(--s3);font-size:var(--step-1)">
            <strong>{{ collect($r->symptoms)->map(fn ($s) => __('respond.'.$s))->join(', ') }}</strong>
            @if ($r->prescription)<span class="hint"> — while taking {{ $r->prescription->name }}</span>@endif
          </p>
          @if ($r->note)<p class="muted" style="margin-top:var(--s2)">&ldquo;{{ $r->note }}&rdquo;</p>@endif

          @if ($r->audio_path)
            <div class="band {{ $r->lang === 'en' ? 'band-info' : '' }}" style="border-radius:var(--radius);margin-top:var(--s3)">
              <x-icon name="mic" class="band-icon" />
              <div>
                <span class="band-title">Voice note · {{ strtoupper($r->lang) }}</span>
                @if ($r->lang === 'en')
                  Auto-transcribed and searchable.
                @else
                  No reliable speech-to-text for this language yet — a nurse listens to this one.
                  That is the honest state of ASR coverage for Yoruba, Hausa and Igbo.
                @endif
              </div>
            </div>
          @endif

          <div class="row wrap" style="margin-top:var(--s4)">
            @unless ($r->reviewed_at)
              <button class="btn btn-sm" type="button" wire:click="markReviewed({{ $r->id }})">Mark reviewed</button>
            @endunless
            <a class="btn btn-secondary btn-sm" href="mailto:{{ $r->patient?->email }}">Email patient</a>
            <span class="grow"></span>
            <a class="btn btn-quiet btn-sm" href="{{ route('clinic.signals') }}" wire:navigate>See cohort signal →</a>
          </div>
        </div>
      </article>
    @empty
      <div class="label"><div class="empty">Nothing in this queue.</div></div>
    @endforelse
  </div>
</div>
