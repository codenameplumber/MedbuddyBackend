<div>
  @if ($sent)
    @if ($crisis)
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
    @else
      <div class="rp-done" style="padding:var(--s7) 0;text-align:center">
        <div class="rp-tick" aria-hidden="true">&#10003;</div>
        <h2 class="rp-q">{{ __('me.report.sent') }}</h2>
        <p class="rp-sub">{{ __('me.report.sentBody') }}</p>
      </div>
    @endif

    <a class="btn btn-secondary btn-block" style="margin-top:var(--s5)" href="{{ route('me.today') }}" wire:navigate>
      {{ __('me.nav.today') }}
    </a>

  @else
    <form wire:submit="submit">
      <h2 class="rp-q">{{ __('respond.q2') }}</h2>
      <p class="rp-sub">{{ __('respond.q2sub') }}</p>

      <div class="sym-grid">
        @foreach ($options as $s)
          <label class="sym" aria-pressed="{{ in_array($s, $symptoms, true) ? 'true' : 'false' }}" wire:key="s-{{ $s }}">
            <input type="checkbox" class="sr-only" value="{{ $s }}" wire:model.live="symptoms">
            <span class="sym-glyph" aria-hidden="true">{{ ['fine'=>'🙂','dizzy'=>'💫','rash'=>'🔴','stomach'=>'🤢','sleepy'=>'😴','swelling'=>'💧','other'=>'❓'][$s] }}</span>
            <span class="sym-label">{{ __('respond.'.$s) }}</span>
          </label>
        @endforeach
      </div>
      @error('symptoms')<p class="hint" style="color:var(--alert);margin-top:var(--s3)">{{ $message }}</p>@enderror

      @if ($this->isComplaint())
        <div class="rp-sev">
          <h3 class="rp-sub" style="font-weight:650;color:var(--ink)">{{ __('respond.sev') }}</h3>
          <div class="sev-row">
            @for ($n = 1; $n <= 3; $n++)
              <label class="sev" aria-pressed="{{ $severity === $n ? 'true' : 'false' }}" wire:key="sev-{{ $n }}">
                <input type="radio" class="sr-only" value="{{ $n }}" wire:model.live="severity">
                {{ __('respond.sev'.$n) }}
              </label>
            @endfor
          </div>
        </div>
      @endif

      <label class="field" style="margin-top:var(--s6)">
        <span class="field-label">{{ __('respond.more') }}</span>
        <textarea class="textarea" rows="3" wire:model="note" placeholder="{{ __('respond.moresub') }}"></textarea>
      </label>

      {{-- Someone who cannot read the symptom labels can still hold a button
           and talk. The tap grid stays: it is what a cohort view can count. --}}
      <div class="rec-wrap" data-recorder="livewire" data-rec-prop="audio" wire:ignore>
        <button class="rec" type="button" data-rec
                data-idle="{{ __('respond.rec') }}" data-rec-str="{{ __('respond.recording') }}"
                data-done="{{ __('respond.recdone') }}" data-nomic="{{ __('respond.recno') }}"
                aria-label="{{ __('respond.rec') }}">
          <x-icon name="mic" />
        </button>
        <span class="hint" data-rec-label>{{ __('respond.rec') }}</span>
        <audio class="rec-play" data-rec-play controls hidden></audio>
      </div>

      @if ($audio)
        <p class="hint" style="margin-top:var(--s2)">
          <x-icon name="check" class="i-sm" /> {{ __('respond.recdone') }}
        </p>
      @endif
      @error('audio')<p class="hint" style="color:var(--alert)">{{ $message }}</p>@enderror

      <button class="btn btn-block btn-lg" type="submit" style="margin-top:var(--s6)"
              wire:loading.attr="disabled" wire:target="audio,submit">{{ __('respond.send') }}</button>
    </form>
  @endif
</div>
