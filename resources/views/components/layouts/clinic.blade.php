<!DOCTYPE html>
<html lang="en" data-skin="clinic">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0F5F5B">
<title>{{ $title ?? 'MedBuddy Clinic' }} — MedBuddy</title>
<link rel="stylesheet" href="{{ asset('styles/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('styles/tokens.css') }}">
<link rel="stylesheet" href="{{ asset('styles/app.css') }}">
<link rel="stylesheet" href="{{ asset('styles/shell.css') }}">
<link rel="stylesheet" href="{{ asset('styles/chart.css') }}">
<link rel="stylesheet" href="{{ asset('styles/clinic.css') }}">
@livewireStyles
</head>
<body data-app="clinic">

@php
  $nav = [
    ['Operations', [
      ['overview',   'clinic.overview',   'grid',      'Overview',       null],
      ['patients',   'clinic.patients',   'users',     'Patients',       \App\Models\Patient::count()],
      ['adherence',  'clinic.adherence',  'pulse',     'Adherence',      null],
    ]],
    ['Care', [
      ['medicines',  'clinic.medicines',  'pill',      'Medicines',      null],
      ['complaints', 'clinic.complaints', 'chat',      'Complaints',     $openComplaints ?? null],
      ['outcomes',   'clinic.outcomes',   'clipboard', 'Outcomes',       null],
      ['explain',    'clinic.explain',    'search',    'Explain a label', null],
    ]],
    ['Safety', [
      ['signals',    'clinic.signals',    'shield',    'Safety signals', null],
    ]],
    ['Messaging', [
      ['outbox',     'clinic.outbox',     'send',      'Outbox',         \App\Models\Reminder::whereDate('dose_date', today())->count()],
    ]],
  ];
  $current = request()->route()?->getName();
@endphp

<aside class="sidebar" id="sidebar">
  <a class="side-brand" href="{{ route('clinic.overview') }}">
    <span class="brand-mark" aria-hidden="true"></span>
    <span><span class="brand-name">MedBuddy</span><span class="side-brand-sub">Clinic console</span></span>
  </a>

  <nav class="side-nav" aria-label="Sections">
    @foreach ($nav as [$group, $items])
      <div class="nav-group">
        <div class="nav-label">{{ $group }}</div>
        @foreach ($items as [$id, $route, $icon, $label, $count])
          @php $active = $current === $route || ($route === 'clinic.patients' && $current === 'clinic.patient'); @endphp
          <a class="nav-item {{ $active ? 'is-active' : '' }}" href="{{ route($route) }}"
             wire:navigate @if($active) aria-current="page" @endif>
            <x-icon :name="$icon" class="i-sm" />
            <span class="grow">{{ $label }}</span>
            @if ($count)<span class="nav-count {{ $id === 'complaints' ? 'is-hot' : '' }}">{{ $count }}</span>@endif
          </a>
        @endforeach
      </div>
    @endforeach
  </nav>

  <div class="side-foot">
    <a class="nav-item {{ $current === 'clinic.team' ? 'is-active' : '' }}" href="{{ route('clinic.team') }}" wire:navigate>
      <x-icon name="users" class="i-sm" /><span>Team &amp; audit</span>
    </a>
    <a class="nav-item {{ $current === 'clinic.settings' ? 'is-active' : '' }}" href="{{ route('clinic.settings') }}" wire:navigate>
      <x-icon name="lock" class="i-sm" /><span>Settings</span>
    </a>
    {{-- The other half of the product. Staff had no way to find it from here. --}}
    <a class="nav-item" href="{{ route('me.login') }}" target="_blank">
      <x-icon name="heart" class="i-sm" /><span class="grow">Patient app</span>
      <x-icon name="arrowR" class="i-sm" />
    </a>
    <form method="POST" action="{{ route('logout') }}">@csrf
      <button class="nav-item" type="submit" style="width:100%;border:0;background:none;cursor:pointer;text-align:left">
        <x-icon name="arrowL" class="i-sm" /><span>Sign out</span>
      </button>
    </form>
  </div>
</aside>

<div class="appwrap">
  <header class="apptop">
    <button class="iconbtn side-toggle" type="button" id="side-toggle" aria-label="Menu">
      <x-icon name="grid" />
    </button>
    <div class="grow">
      <h1 class="apptop-title">{{ $title ?? 'MedBuddy Clinic' }}</h1>
      @isset($subtitle)<p class="apptop-sub">{{ $subtitle }}</p>@endisset
    </div>
    <div class="row" style="gap:var(--s2)">
      <button class="iconbtn" type="button" data-theme-toggle aria-label="Toggle theme"></button>
      <div class="whoami">
        <div><strong>{{ auth()->user()?->name }}</strong></div>
        <div class="hint">{{ auth()->user()?->organisation?->name }}</div>
      </div>
    </div>
  </header>

  <main class="main">
    {{ $slot }}
  </main>

  <footer class="app-foot">
    <span class="row" style="gap:var(--s2)"><span class="foot-dot"></span>All systems operational</span>
    <span class="sep">·</span><span>{{ auth()->user()?->organisation?->name }} · {{ auth()->user()?->organisation?->city }}</span>
    <span class="sep">·</span>
    <span>Mail: <strong>{{ config('mail.default') }}</strong></span>
    <span class="sep">·</span>
    <span>AI: <strong>{{ config('services.ai.mode') }}</strong> · {{ config('services.ai.provider') }}</span>
    <span class="grow"></span>
    <span>&copy; {{ date('Y') }} MedBuddy</span>
  </footer>
</div>

<div class="toast-stack" id="toasts" role="status" aria-live="polite"></div>

@if (session('toast'))
  <script>window.addEventListener('load', function () {
    window.dispatchEvent(new CustomEvent('toast', { detail: { message: @json(session('toast')), tone: 'safe' } }));
  });</script>
@endif

@livewireScripts
<script>
document.getElementById('side-toggle')?.addEventListener('click', function () {
  document.getElementById('sidebar')?.classList.toggle('is-open');
});

// Theme
(function () {
  var KEY = 'medbuddy.theme';
  var saved = localStorage.getItem(KEY);
  if (saved) document.documentElement.dataset.theme = saved;

  function isDark() {
    return document.documentElement.dataset.theme
      ? document.documentElement.dataset.theme === 'dark'
      : matchMedia('(prefers-color-scheme: dark)').matches;
  }
  function sync() {
    document.querySelectorAll('[data-theme-toggle]').forEach(function (b) {
      b.textContent = isDark() ? '☀' : '☾';
    });
  }
  document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-theme-toggle]')) return;
    document.documentElement.dataset.theme = isDark() ? 'light' : 'dark';
    localStorage.setItem(KEY, document.documentElement.dataset.theme);
    sync();
  });
  sync();
  document.addEventListener('livewire:navigated', sync);
})();

// Toasts. Livewire components dispatch these so a staged action never sits dead.
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
