<!DOCTYPE html>
<html lang="en" data-skin="patient">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in — MedBuddy Clinic</title>
<link rel="stylesheet" href="{{ asset('styles/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('styles/tokens.css') }}">
<link rel="stylesheet" href="{{ asset('styles/app.css') }}">
<link rel="stylesheet" href="{{ asset('styles/site.css') }}">
</head>
<body class="site">
<div class="auth-wrap">

  <aside class="auth-aside">
    <a class="row" style="gap:var(--s3);color:inherit;text-decoration:none" href="{{ route('home') }}">
      <span class="brand-mark" aria-hidden="true"></span>
      <span class="brand-name">MedBuddy</span>
    </a>
    <div>
      <h2>Adherence, complaints and safety signals from the people actually taking the medicine.</h2>
      <p style="margin-top:var(--s4);opacity:.85;max-width:46ch">
        Reminders go out on a schedule, patients answer in one tap in their own
        language, and everything they report lands here.
      </p>
    </div>
    <p class="hint dim">Lagos · Nigeria</p>
  </aside>

  <main class="auth-main">
    <div class="auth-box">
      <div class="row-between">
        <a class="btn btn-quiet" href="{{ route('home') }}">&larr; MedBuddy</a>
        <button class="iconbtn" type="button" data-theme-toggle aria-label="Toggle theme"></button>
      </div>

      <h1 class="page-title" style="margin-top:var(--s4)">Sign in</h1>
      <p class="muted" style="margin-top:var(--s2)">Clinic and pharmacy staff.</p>

      @if ($errors->any())
        <div class="band band-alert" style="border-radius:var(--radius);margin-top:var(--s4)">
          <div>{{ $errors->first() }}</div>
        </div>
      @endif

      <form method="POST" action="{{ route('login') }}" class="stack" style="margin-top:var(--s5)">
        @csrf
        <label class="field">
          <span class="field-label">Work email</span>
          <input class="input" type="email" name="email" value="{{ old('email', 'a.adeyemi@ikejagh.ng') }}" required autofocus>
        </label>
        <label class="field">
          <span class="field-label">Password</span>
          <input class="input" type="password" name="password" value="password" required>
          <span class="field-hint">Seeded demo credentials are pre-filled.</span>
        </label>
        <label class="row" style="gap:var(--s2)">
          <input type="checkbox" name="remember" value="1" checked>
          <span class="hint">Keep me signed in on this device</span>
        </label>
        <button class="btn btn-block btn-lg" type="submit">Continue</button>
      </form>

      <div class="band band-info" style="border-radius:var(--radius);margin-top:var(--s5)">
        <div>
          <span class="band-title">Seeded accounts</span>
          <span class="mono">admin@ikejagh.ng</span> (admin) ·
          <span class="mono">a.adeyemi@ikejagh.ng</span> (physician) ·
          <span class="mono">n.balogun@ikejagh.ng</span> (pharmacist) ·
          <span class="mono">c.eze@ikejagh.ng</span> (nurse) — password <span class="mono">password</span>
        </div>
      </div>
      <p class="hint center" style="margin-top:var(--s5)">
        No account yet?
        <a href="{{ route('register') }}" style="color:var(--accent);font-weight:650">Create one</a>
      </p>
    </div>
  </main>
</div>

<script>
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
    document.querySelectorAll('[data-theme-toggle]').forEach(function (b) { b.textContent = isDark() ? '☀' : '☾'; });
  }
  document.addEventListener('click', function (e) {
    if (!e.target.closest('[data-theme-toggle]')) return;
    document.documentElement.dataset.theme = isDark() ? 'light' : 'dark';
    localStorage.setItem(KEY, document.documentElement.dataset.theme);
    sync();
  });
  sync();
})();
</script>
</body>
</html>
