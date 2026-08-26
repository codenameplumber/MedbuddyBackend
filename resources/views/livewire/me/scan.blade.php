<div>
  <div class="row" role="group" aria-label="Task">
    <button class="btn btn-sm {{ $mode === 'explain' ? '' : 'btn-secondary' }}" type="button"
            wire:click="$set('mode', 'explain')">{{ __('me.explain.title') }}</button>
    <button class="btn btn-sm {{ $mode === 'identify' ? '' : 'btn-secondary' }}" type="button"
            wire:click="$set('mode', 'identify')">{{ __('me.nav.explain') }}</button>
  </div>

  <p class="muted" style="margin-top:var(--s4)">{{ __('me.explain.lead') }}</p>

  <form wire:submit="run" class="stack" style="margin-top:var(--s5)">
    <label class="field">
      <span class="field-label">{{ __('me.explain.textLabel') }}</span>
      <textarea class="textarea" rows="4" wire:model="text"
                placeholder="{{ __('me.explain.textPlaceholder') }}"></textarea>
    </label>

    {{--
      Take OR choose.

      This was one bare file input carrying capture="environment", which on a
      phone forces the camera and removes the gallery entirely — so a patient
      who had already photographed their label, or who had it sent by a
      relative, had no way to use it. Two inputs, two obvious buttons: one
      capture, one not.
    --}}
    <fieldset class="photo-pick">
      <legend class="field-label">{{ __('me.explain.photo') }}</legend>

      @if (! $photo)
        {{-- An empty slot in the shape of the thing that goes in it. --}}
        <div class="photo-slot" wire:loading.class="is-busy" wire:target="photo">
          <div class="photo-slot-frame" aria-hidden="true">
            <span class="photo-slot-icon"><x-icon name="camera" class="i-lg" /></span>
          </div>

          <p class="photo-slot-lead">{{ __('me.explain.photoLead') }}</p>

          <div class="photo-actions">
            {{-- capture= asks for the camera; without it the OS offers the library. --}}
            <label class="btn photo-btn">
              <x-icon name="camera" class="i-sm" />
              <span>{{ __('me.common.takePhoto') }}</span>
              <input type="file" accept="image/*" capture="environment"
                     wire:model="photo" class="sr-only-input">
            </label>

            <label class="btn btn-secondary photo-btn">
              <x-icon name="grid" class="i-sm" />
              <span>{{ __('me.common.choosePhoto') }}</span>
              <input type="file" accept="image/*" wire:model="photo" class="sr-only-input">
            </label>
          </div>

          <p class="photo-busy" wire:loading wire:target="photo">
            <span class="photo-spinner" aria-hidden="true"></span>
            {{ __('me.explain.working') }}
          </p>
        </div>
      @else
        <figure class="photo-preview">
          <div class="photo-preview-img">
            <img src="{{ $photo->temporaryUrl() }}" alt="{{ __('me.explain.photo') }}">
          </div>
          <figcaption>
            <span class="photo-ok"><x-icon name="check" class="i-sm" /> {{ __('me.explain.photoReady') }}</span>
            <span class="grow"></span>
            <label class="btn btn-secondary btn-sm photo-btn">
              <x-icon name="camera" class="i-sm" />
              <span>{{ __('me.common.retakePhoto') }}</span>
              <input type="file" accept="image/*" wire:model="photo" class="sr-only-input">
            </label>
            <button class="btn btn-quiet btn-sm" type="button" wire:click="$set('photo', null)">
              <x-icon name="x" class="i-sm" /> {{ __('me.common.removePhoto') }}
            </button>
          </figcaption>
        </figure>
      @endif

      @error('photo')<span class="field-error">{{ $message }}</span>@enderror
    </fieldset>

    <button class="btn btn-block btn-lg" type="submit" wire:loading.attr="disabled" wire:target="run,photo">
      <span wire:loading.remove wire:target="run">{{ __('me.explain.run') }}</span>
      <span wire:loading wire:target="run">{{ __('me.explain.working') }}</span>
    </button>
  </form>

  @if ($error)
    <div class="band band-alert" style="border-radius:var(--radius);margin-top:var(--s4)">
      <x-icon name="alert" class="band-icon" /><div>{{ $error }}</div>
    </div>
  @endif

  @if ($result && $mode === 'explain')
    @php $shaky = in_array($result['readingConfidence'], ['low', 'cannot_read'], true) || $result['askPharmacist']; @endphp

    <div class="stack" style="margin-top:var(--s5)">
      @if ($shaky)
        <div class="band band-caution" style="border-radius:var(--radius)">
          <x-icon name="alert" class="band-icon" />
          <div>
            <span class="band-title">{{ $result['medicineName'] ?: '—' }}</span>
            {{ $result['purpose'] }}
          </div>
        </div>
      @else
        <div class="label"><div class="label-body">
          <div class="med-name" style="font-size:var(--step-2)">{{ $result['medicineName'] }}</div>
        </div></div>

        <div class="label"><div class="label-body">
          <div class="section-title" style="margin-top:0">{{ __('me.explain.purpose') }}</div>
          <p style="font-size:var(--step-1);line-height:1.65">{{ $result['purpose'] }}</p>
        </div></div>

        <div class="label"><div class="label-body">
          <div class="section-title" style="margin-top:0">{{ __('me.explain.dosing') }}</div>
          <p style="font-size:var(--step-1);line-height:1.65">{{ $result['dosing'] }}</p>
        </div></div>
      @endif

      @foreach ([['sideEffects', 'me.explain.sideEffects'], ['warnings', 'me.explain.warnings']] as [$key, $label])
        @if ($result[$key])
          <div class="label"><div class="label-body">
            <div class="section-title" style="margin-top:0">{{ __($label) }}</div>
            <ul style="margin:var(--s2) 0 0;padding-left:1.1rem;font-size:var(--step-1);line-height:1.65">
              @foreach ($result[$key] as $x)<li style="margin-bottom:var(--s2)">{{ $x }}</li>@endforeach
            </ul>
          </div></div>
        @endif
      @endforeach

      @if ($result['storage'])
        <div class="label"><div class="label-body">
          <div class="section-title" style="margin-top:0">{{ __('me.explain.storage') }}</div>
          <p>{{ $result['storage'] }}</p>
        </div></div>
      @endif
    </div>

    <div class="row" style="margin-top:var(--s4)">
      <x-speak :text="$spoken" :lang="$lang" />
    </div>

    <p class="hint" style="margin-top:var(--s3)">{{ __('me.common.aiNote') }}</p>

  @elseif ($result)
    <div class="stack" style="margin-top:var(--s5)">
      <div class="label"><div class="label-body">
        <p style="font-size:var(--step-1);line-height:1.65">{{ $result['visualFeatures'] }}</p>
      </div></div>

      <div class="band band-caution" style="border-radius:var(--radius)">
        <x-icon name="alert" class="band-icon" />
        <div><span class="band-title">{{ __('me.common.notAdvice') }}</span>{{ $displayRule['note'] }}</div>
      </div>

      @if ($displayRule['showSingleName'] && $result['candidates'])
        <div class="label"><div class="label-body">
          <div class="med-name" style="font-size:var(--step-2)">{{ $result['candidates'][0]['name'] }}</div>
          <p class="hint">{{ $result['candidates'][0]['reasoning'] }}</p>
        </div></div>
      @elseif ($displayRule['showAllCandidates'])
        @foreach ($result['candidates'] as $c)
          <div class="label"><div class="label-body">
            <strong>{{ $c['name'] }}</strong>
            <p class="hint">{{ $c['reasoning'] }}</p>
          </div></div>
        @endforeach
      @endif
    </div>
  @endif

  @if ($result)
    @unless ($live)
      <div class="band band-info" style="border-radius:var(--radius);margin-top:var(--s5)">
        <x-icon name="info" class="band-icon" />
        <div>
          <span class="band-title">{{ __('me.common.demoAnswer') }}</span>
          {{ __('me.common.demoAnswerNote') }}
        </div>
      </div>
    @endunless
  @endif
</div>
