<x-layouts.patient :title="__('me.sos.title')">
  <div class="band band-alert" style="border-radius:var(--radius)">
    <x-icon name="alert" class="band-icon" />
    <div>
      <span class="band-title" style="font-size:var(--step-1)">{{ __('me.sos.title') }}</span>
      {{ __('me.sos.lead') }}
    </div>
  </div>

  <a class="btn btn-block btn-lg" style="margin-top:var(--s5);font-size:var(--step-2);padding:var(--s6)"
     href="tel:{{ $number }}">
    <x-icon name="phone" /> {{ __('me.sos.call') }} {{ $number }}
  </a>

  @if ($patient->caregiver_name && $patient->phone)
    <a class="btn btn-secondary btn-block btn-lg" style="margin-top:var(--s3)" href="tel:{{ $patient->phone }}">
      {{ __('me.sos.call') }} {{ $patient->caregiver_name }}
    </a>
  @endif

  <div class="label" style="margin-top:var(--s5)"><div class="label-body">
    <div class="section-title" style="margin-top:0">{{ __('me.meds.title') }}</div>
    <p class="hint">Show this to whoever is helping you.</p>
    <div class="stack-s" style="margin-top:var(--s3)">
      @forelse ($patient->activePrescriptions as $rx)
        <div class="row-between wrap">
          <strong>{{ $rx->name }}</strong>
          <span class="mono hint">{{ $rx->strength }} · {{ implode(' · ', $rx->times ?? []) }}</span>
        </div>
      @empty
        <div class="empty">{{ __('me.meds.none') }}</div>
      @endforelse
    </div>
    @if ($patient->condition)
      <p class="hint" style="margin-top:var(--s4)">Being treated for: <strong>{{ $patient->condition }}</strong></p>
    @endif
  </div></div>

  <a class="btn btn-quiet btn-block" style="margin-top:var(--s5)" href="{{ route('me.today') }}" wire:navigate>
    {{ __('me.sos.back') }}
  </a>
</x-layouts.patient>
