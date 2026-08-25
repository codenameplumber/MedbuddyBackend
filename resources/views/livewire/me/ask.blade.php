<div>
  @if ($crisis)
    {{-- Reached with no network call. TriageService decided this before
         anything left the device, which is the entire point of the gate. --}}
    <div class="band band-alert" style="border-radius:var(--radius)">
      <x-icon name="alert" class="band-icon" />
      <div>
        <span class="band-title" style="font-size:var(--step-1)">{{ __('me.crisis.title') }}</span>
        {{ __('me.crisis.lead') }}
      </div>
    </div>

    @if ($guidance)
      <div class="label" style="margin-top:var(--s4)"><div class="label-body">
        <p style="font-size:var(--step-1)">{{ $guidance }}</p>
      </div></div>
    @endif

    <a class="btn btn-block btn-lg" style="margin-top:var(--s5)" href="tel:{{ $number }}">
      <x-icon name="phone" class="i-sm" /> {{ __('me.sos.call') }} {{ $number }}
    </a>
    <a class="btn btn-secondary btn-block" style="margin-top:var(--s3)" href="{{ route('me.emergency') }}" wire:navigate>
      {{ __('me.sos.title') }}
    </a>
    <button class="btn btn-quiet btn-block" style="margin-top:var(--s3)" type="button" wire:click="clearCrisis">
      {{ __('me.sos.back') }}
    </button>

  @else
    <p class="muted">{{ __('me.ask.lead') }}</p>

    <div class="stack" style="margin-top:var(--s5)">
      @forelse ($thread as $i => $m)
        <div wire:key="t-{{ $i }}" class="label {{ $m['role'] === 'me' ? '' : '' }}"
             style="{{ $m['role'] === 'me' ? 'margin-left:15%;background:var(--surface-2)' : 'margin-right:8%' }}">
          <div class="label-body">
            <p style="{{ $m['role'] === 'ai' ? 'font-size:var(--step-1);line-height:1.65' : '' }}">{{ $m['text'] }}</p>
            @if ($m['note'])
              <div class="band band-caution" style="border-radius:var(--radius);margin-top:var(--s3)">
                <x-icon name="alert" class="band-icon" />
                <div>{{ $m['note'] }}</div>
              </div>
            @endif
          </div>
        </div>
      @empty
        <div class="label"><div class="empty">{{ __('me.ask.empty') }}</div></div>
      @endforelse

      <div wire:loading wire:target="send" class="label"><div class="label-body">
        <p class="muted">{{ __('me.explain.working') }}</p>
      </div></div>
    </div>

    <form wire:submit="send" class="stack" style="margin-top:var(--s5)">
      <label class="field">
        <span class="sr-only">{{ __('me.ask.placeholder') }}</span>
        <textarea class="textarea" rows="3" wire:model="question"
                  placeholder="{{ __('me.ask.placeholder') }}"></textarea>
      </label>
      <button class="btn btn-block btn-lg" type="submit" wire:loading.attr="disabled" wire:target="send">
        {{ __('me.ask.send') }}
      </button>
      @if ($thread)
        <button class="btn btn-quiet btn-sm" type="button" wire:click="clearThread">{{ __('me.common.close') }}</button>
      @endif
    </form>

    <p class="hint" style="margin-top:var(--s5)">
      {{ __('me.common.aiNote') }}
      @unless ($live) <span class="badge badge-info">{{ __('me.settings.aiMock') }}</span> @endunless
    </p>
  @endif
</div>
