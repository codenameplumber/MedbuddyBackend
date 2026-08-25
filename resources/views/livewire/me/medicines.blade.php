<div>
  @forelse ($meds as $rx)
    <a class="label" href="{{ route('me.medicine', $rx) }}" wire:navigate wire:key="m-{{ $rx->id }}"
       style="display:block;text-decoration:none;color:inherit;margin-bottom:var(--s4)">
      <div class="label-body">
        <div class="row-between wrap" style="gap:var(--s2)">
          <div class="grow">
            <div class="med-name">{{ $rx->name }}</div>
            @if ($rx->strength)<div class="dosage">{{ $rx->strength }}</div>@endif
            @if ($rx->purpose)<div class="med-generic">{{ $rx->purpose }}</div>@endif
          </div>
          <x-icon name="arrowR" class="i-sm" />
        </div>
        <div class="row wrap" style="gap:var(--s2);margin-top:var(--s3)">
          @foreach ($rx->times ?? [] as $t)<span class="badge">{{ $t }}</span>@endforeach
          <span class="hint">{{ count($rx->times ?? []) }} {{ __('me.meds.doseCount') }}</span>
          @if ($rx->ai_summary)<span class="badge badge-safe">{{ __('me.explain.title') }}</span>@endif
          @unless ($rx->prescribed_by)<span class="badge">added by you</span>@endunless
        </div>
      </div>
    </a>
  @empty
    <div class="label"><div class="empty">
      <p style="font-size:var(--step-1)">{{ __('me.meds.none') }}</p>
      <p class="hint" style="margin-top:var(--s3)">
        Your clinic keeps this list. You can add anything else you take yourself.
      </p>
    </div></div>
  @endforelse

  <a class="btn btn-block btn-lg" href="{{ route('me.medicine.add') }}" wire:navigate
     style="margin-top:var(--s5)">+ {{ __('me.meds.add') }}</a>

  <div class="band band-info" style="border-radius:var(--radius);margin-top:var(--s5)">
    <x-icon name="info" class="band-icon" />
    <div>
      <span class="band-title">Not sure what a tablet is?</span>
      <a href="{{ route('me.scan') }}" wire:navigate>{{ __('me.explain.lead') }}</a>
    </div>
  </div>
</div>
