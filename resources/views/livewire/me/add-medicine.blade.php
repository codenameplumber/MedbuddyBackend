<div>
  <form wire:submit="save" class="stack">

    <label class="field">
      <span class="field-label">{{ __('me.meds.name') }}</span>
      <input class="input" wire:model="name" placeholder="{{ __('me.meds.namePlaceholder') }}"
             autocomplete="off" required>
      @error('name')<span class="field-error">{{ $message }}</span>@enderror
    </label>

    <label class="field">
      <span class="field-label">{{ __('me.meds.strength') }}</span>
      <input class="input" wire:model="strength" placeholder="{{ __('me.meds.strengthPlaceholder') }}"
             autocomplete="off">
      @error('strength')<span class="field-error">{{ $message }}</span>@enderror
    </label>

    <label class="field">
      <span class="field-label">{{ __('me.meds.form') }}</span>
      <select class="select" wire:model="form">
        @foreach ($forms as $option)
          <option value="{{ $option }}">{{ $option }}</option>
        @endforeach
      </select>
    </label>

    <label class="field">
      <span class="field-label">{{ __('me.meds.purpose') }}</span>
      <input class="input" wire:model="purpose" placeholder="{{ __('me.meds.purposePlaceholder') }}"
             autocomplete="off">
      @error('purpose')<span class="field-error">{{ $message }}</span>@enderror
    </label>

    <fieldset class="field" style="border:0;padding:0">
      <legend class="field-label">{{ __('me.meds.times') }}</legend>

      <div class="stack-s">
        @foreach ($times as $i => $time)
          <div class="row" style="gap:var(--s2)" wire:key="t-{{ $i }}">
            <input class="input grow" type="time" wire:model="times.{{ $i }}" required>
            @if (count($times) > 1)
              <button class="iconbtn" type="button" wire:click="removeTime({{ $i }})"
                      aria-label="{{ __('me.common.close') }}">
                <x-icon name="x" />
              </button>
            @endif
          </div>
        @endforeach
      </div>

      @error('times.*')<span class="field-error">{{ $message }}</span>@enderror

      @if (count($times) < 6)
        <button class="btn btn-secondary btn-block" type="button" wire:click="addTime"
                style="margin-top:var(--s3)">
          + {{ __('me.meds.addTime') }}
        </button>
      @endif
    </fieldset>

    <div class="band band-info" style="border-radius:var(--radius)">
      <x-icon name="info" class="band-icon" />
      <div>
        <span class="band-title">{{ __('me.meds.ownTitle') }}</span>
        {{ __('me.meds.ownBody') }}
      </div>
    </div>

    <button class="btn btn-block btn-lg" type="submit">
      <span wire:loading.remove wire:target="save">{{ __('me.meds.save') }}</span>
      <span wire:loading wire:target="save">…</span>
    </button>

    <a class="btn btn-quiet btn-block" href="{{ route('me.medicines') }}" wire:navigate>
      {{ __('me.common.cancel') }}
    </a>
  </form>
</div>
