@props(['text', 'lang' => 'en'])

@php
  $langName = config('medbuddy.languages')[$lang] ?? 'English';
@endphp

{{--
  Read aloud.

  The reason this matters more here than on most products: the audience
  includes people who cannot comfortably read the screen at all. An explanation
  they cannot read is the same problem as the label they could not read.

  The button ships VISIBLE and js/speak.js hides it only if this browser has no
  speech support at all. The first version had that backwards — hidden until
  JavaScript proved a voice existed — and since browsers load their voice list
  asynchronously, the common outcome was no button ever appearing. Failing
  toward "the control is there" is the right way round for a feature whose
  whole job is to help someone who cannot read the alternative.

  The text sits in a hidden span rather than a <template>: Livewire morphs this
  DOM on every re-render and template content is the kind of thing that does
  not survive it.
--}}

<div class="speak" data-speak
     data-lang="{{ $lang }}"
     data-label-play="{{ __('me.common.speak') }}"
     data-label-stop="{{ __('me.common.stop') }}"
     data-label-approx="{{ __('me.common.speakApprox', ['lang' => $langName]) }}">
  <button class="btn btn-quiet btn-sm" type="button" data-speak-btn>
    <x-icon name="mic" class="i-sm" />
    <span data-speak-label>{{ __('me.common.speak') }}</span>
  </button>
  <span class="hint" data-speak-note hidden></span>
  <span class="sr-only" data-speak-text>{{ $text }}</span>
</div>
