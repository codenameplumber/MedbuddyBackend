@php
  $current = request()->route()?->getName();

  // The tab bar is five destinations, fixed. Everything else is reached from
  // inside one of them, so the chrome never has to grow.
  $tabs = [
    ['me.today',     'today',     'check',  __('me.nav.today')],
    ['me.medicines', 'medicines', 'pill',   __('me.nav.meds')],
    ['me.scan',      'scan',      'search', __('me.nav.explain')],
    ['me.ask',       'ask',       'chat',   __('me.nav.ask')],
    ['me.profile',   'me',        'user',   __('me.nav.settings')],
  ];

  $group = match ($current) {
    'me.medicines', 'me.medicine' => 'medicines',
    'me.profile', 'me.history', 'me.report' => 'me',
    'me.scan' => 'scan',
    'me.ask'  => 'ask',
    default   => 'today',
  };

  $back = match ($current) {
    'me.medicine' => route('me.medicines'),
    'me.history', 'me.report' => route('me.today'),
    default => null,
  };
@endphp
<!DOCTYPE html>
<html lang="{{ $me?->lang ?? 'en' }}" data-skin="patient">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#FCFBF8">
<meta name="robots" content="noindex, nofollow">
<title>{{ $title ?? 'MedBuddy' }} — MedBuddy</title>
<link rel="stylesheet" href="{{ asset('styles/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('styles/tokens.css') }}">
<link rel="stylesheet" href="{{ asset('styles/app.css') }}">
<link rel="stylesheet" href="{{ asset('styles/shell.css') }}">
<link rel="stylesheet" href="{{ asset('styles/chart.css') }}">
<link rel="stylesheet" href="{{ asset('styles/respond.css') }}">
@livewireStyles
</head>
<body data-app="patient">

<header class="phone-head">
  @if ($back)
    <a class="iconbtn" href="{{ $back }}" wire:navigate aria-label="{{ __('me.common.back') }}">
      <x-icon name="arrowL" />
    </a>
  @else
    <span class="brand-mark" aria-hidden="true"></span>
  @endif
  <div class="grow">
    <h1 class="phone-title">{{ $title ?? 'MedBuddy' }}</h1>
    @isset($subtitle)<p class="phone-sub">{{ $subtitle }}</p>@endisset
  </div>
  <a class="sos" href="{{ route('me.emergency') }}" aria-label="{{ __('me.sos.title') }}">SOS</a>
</header>

<main class="phone-body">
  {{ $slot }}
</main>

<nav class="tabbar" aria-label="Main">
  @foreach ($tabs as [$route, $id, $icon, $label])
    <a class="tab" href="{{ route($route) }}" wire:navigate
       @if($group === $id) aria-current="page" @endif>
      <span class="tab-icon"><x-icon :name="$icon" /></span>
      <span>{{ $label }}</span>
    </a>
  @endforeach
</nav>

<div class="toast-stack" id="toasts" role="status" aria-live="polite"></div>

@livewireScripts
<script src="{{ asset('js/recorder.js') }}" defer></script>
<script>
// Text size. Someone holding a phone at arm's length in bright sun is the
// actual reading condition, so this is a first-class control, not a setting.
(function () {
  var KEY = 'medbuddy.textsize';
  function apply(v) { document.documentElement.dataset.textsize = v || 'normal'; }
  apply(localStorage.getItem(KEY));
  window.addEventListener('textsize', function (e) {
    localStorage.setItem(KEY, e.detail); apply(e.detail);
  });
  document.addEventListener('livewire:navigated', function () { apply(localStorage.getItem(KEY)); });
})();

window.addEventListener('toast', function (e) {
  var host = document.getElementById('toasts');
  if (!host) return;
  var el = document.createElement('div');
  el.className = 'toast toast-' + (e.detail.tone || 'info');
  el.textContent = e.detail.message;
  host.appendChild(el);
  requestAnimationFrame(function () { el.classList.add('is-in'); });
  setTimeout(function () { el.classList.remove('is-in'); setTimeout(function () { el.remove(); }, 400); }, 3600);
});
</script>
</body>
</html>
