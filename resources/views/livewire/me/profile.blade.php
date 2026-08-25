<div class="stack">
  <div class="label"><div class="label-body">
    <div class="med-name" style="font-size:var(--step-2)">{{ $me->name }}</div>
    <div class="hint mono">{{ $me->email }}</div>
    <div class="row wrap" style="gap:var(--s2);margin-top:var(--s3)">
      <span class="badge">{{ $medCount }} {{ __('me.profile.medicines') }}</span>
      <span class="badge">{{ $me->langName() }}</span>
    </div>
  </div></div>

  <div class="label"><div class="label-body">
    <label class="field">
      <span class="field-label">{{ __('me.settings.language') }}</span>
      <select class="select" wire:model.live="lang">
        @foreach ($languages as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach
      </select>
      <span class="field-hint">Everything in this app, and every reminder, changes to this language.</span>
    </label>
  </div></div>

  <div class="label"><div class="label-body">
    <div class="field-label">{{ __('me.settings.textSize') }}</div>
    <div class="row" style="gap:var(--s2);margin-top:var(--s2)">
      @foreach (['normal' => __('me.settings.textNormal'), 'large' => __('me.settings.textLarge'), 'huge' => __('me.settings.textHuge')] as $v => $label)
        <button class="btn btn-sm btn-secondary" type="button"
                onclick="window.dispatchEvent(new CustomEvent('textsize', {detail: '{{ $v }}'}))">{{ $label }}</button>
      @endforeach
    </div>
    <p class="field-hint">Saved on this phone only.</p>
  </div></div>

  <div class="label"><div class="label-body stack-s">
    <div class="section-title" style="margin-top:0">{{ __('me.profile.consent') }}</div>

    <label class="row" style="gap:var(--s3);align-items:flex-start">
      <input type="checkbox" wire:model.live="messaging" style="margin-top:5px;width:24px;height:24px">
      <span>
        <strong>{{ __('me.profile.reminders') }}</strong>
        <span class="hint" style="display:block">{{ __('me.profile.remindersHint') }}</span>
      </span>
    </label>

    <label class="row" style="gap:var(--s3);align-items:flex-start">
      <input type="checkbox" wire:model.live="research" style="margin-top:5px;width:24px;height:24px">
      <span>
        <strong>{{ __('me.profile.research') }}</strong>
        <span class="hint" style="display:block">{{ __('me.profile.researchHint') }}</span>
      </span>
    </label>

    @unless ($messaging)
      <div class="band band-caution" style="border-radius:var(--radius)">
        <x-icon name="alert" class="band-icon" />
        <div>Reminders are off. Nothing will be sent to you until you turn them back on.</div>
      </div>
    @endunless
  </div></div>

  <div class="label"><div class="label-body">
    <div class="section-title" style="margin-top:0">{{ __('me.profile.clinic') }}</div>
    <strong>{{ $me->organisation?->name }}</strong>
    <div class="hint">{{ $me->organisation?->city }}</div>
    <a class="btn btn-secondary btn-block" style="margin-top:var(--s4)" href="tel:{{ $number }}">
      <x-icon name="phone" class="i-sm" /> {{ __('me.settings.emergencyNumber') }}: {{ $number }}
    </a>
  </div></div>

  @if ($me->caregiver_name)
    <div class="label"><div class="label-body">
      <div class="section-title" style="margin-top:0">{{ __('me.profile.caregiver') }}</div>
      <strong>{{ $me->caregiver_name }}</strong>
      <div class="hint mono">{{ $me->caregiver_email }}</div>
      <p class="hint" style="margin-top:var(--s2)">
        Your clinic can send this person a summary of how you are doing. They ask you first.
      </p>
    </div></div>
  @endif

  <a class="btn btn-secondary btn-block" href="{{ route('me.history') }}" wire:navigate>{{ __('me.history.link') }}</a>

  <form method="POST" action="{{ route('me.logout') }}">@csrf
    <button class="btn btn-quiet btn-block" type="submit">{{ __('me.profile.signout') }}</button>
  </form>
</div>
