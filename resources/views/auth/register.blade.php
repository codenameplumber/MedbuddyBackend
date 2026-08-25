<!DOCTYPE html>
<html lang="en" data-skin="patient">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Create an account — MedBuddy</title>
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
      <h2>Consent is recorded per channel, and can be withdrawn at any time.</h2>
      <p style="margin-top:var(--s4);opacity:.85;max-width:46ch">
        Messaging consent and research consent are separate decisions. Someone can accept
        reminders and still keep their symptom reports out of the cohort — and reverse either
        one later without losing the app.
      </p>
      <p style="margin-top:var(--s4);opacity:.85;max-width:46ch">
        Reminders never name your condition or your medicine. They name the time of day.
      </p>
    </div>

    <p class="hint dim">Lagos · Nigeria</p>
  </aside>

  <main class="auth-main">
    <div class="auth-box">

      <div class="row-between">
        <a class="btn btn-quiet" href="{{ route('login') }}"><x-icon name="arrowL" class="i-sm" /> Sign in instead</a>
        <button class="iconbtn" type="button" data-theme-toggle aria-label="Toggle theme"></button>
      </div>

      <div class="steps" style="margin-top:var(--s5)">
        <span class="step is-done"></span><span class="step" id="step2"></span>
      </div>

      <h1 class="page-title">Create an account</h1>
      <p class="muted" style="margin-top:var(--s2)">Who is this account for?</p>

      @if ($errors->any())
        <div class="band band-alert" style="border-radius:var(--radius);margin-top:var(--s4)">
          <x-icon name="alert" class="band-icon" />
          <div>
            <span class="band-title">That didn't go through</span>
            <ul style="margin:0;padding-left:1.1em">
              @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
          </div>
        </div>
      @endif

      <form method="POST" action="{{ route('register') }}" class="stack" style="margin-top:var(--s5)" id="reg-form">
        @csrf

        <div class="opt-grid" id="roles">
          @foreach ([
            ['patient',   'heart',    'I am a patient',       'Managing my own medicines'],
            ['caregiver', 'users',    'I care for someone',   'A parent, relative or patient I look after'],
            ['clinic',    'hospital', 'Pharmacy or hospital', 'Enrolling and tracking patients'],
          ] as [$value, $icon, $title, $desc])
            <label class="opt">
              <span class="opt-icon"><x-icon :name="$icon" class="i-sm" /></span>
              <span>
                <span class="opt-name">{{ $title }}</span>
                <span class="opt-desc">{{ $desc }}</span>
              </span>
              <span class="opt-mark" aria-hidden="true"></span>
              <input type="radio" name="role" value="{{ $value }}" @checked(old('role', 'patient') === $value)>
            </label>
          @endforeach
        </div>

        <label class="field">
          <span class="field-label">Your name</span>
          <span class="input-group">
            <x-icon name="user" class="i-sm" />
            <input class="input" name="name" value="{{ old('name') }}" autocomplete="name" placeholder="e.g. Grace Okafor" required>
          </span>
        </label>

        <label class="field">
          <span class="field-label">Email address</span>
          <span class="input-group">
            <x-icon name="send" class="i-sm" />
            <input class="input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
          </span>
          <span class="field-hint" data-for="patient">
            This is how you sign in. There is no password — each time you want the app, we mail
            you a link that opens it.
          </span>
        </label>

        <label class="field">
          <span class="field-label">Phone number <span class="hint">(optional)</span></span>
          <span class="input-group">
            <x-icon name="phone" class="i-sm" />
            <input class="input" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" placeholder="0803 000 0000">
          </span>
        </label>

        {{-- ── Patient and caregiver only ──────────────────────────── --}}
        <div data-for="patient" class="stack">
          <label class="field">
            <span class="field-label">Preferred language</span>
            <select class="select" name="lang">
              @foreach ($languages as $code => $label)
                <option value="{{ $code }}" @selected(old('lang', 'en') === $code)>{{ $label }}</option>
              @endforeach
            </select>
            <span class="field-hint">
              Explanations, questions and the emergency check all work in your language.
            </span>
          </label>

          <div class="section-title">How we may contact you</div>

          <label class="consent">
            <input type="checkbox" name="messaging_consent" value="1" @checked(old('messaging_consent', true))>
            <span>
              <span class="consent-title">Reminders by message</span>
              <span class="consent-desc">
                A short message at each dose time with a link to confirm. It will never name your
                condition, your medicine, or your dose — only the time of day.
              </span>
            </span>
          </label>

          <label class="consent">
            <input type="checkbox" name="research_consent" value="1" @checked(old('research_consent'))>
            <span>
              <span class="consent-title">Use my reports to improve treatment</span>
              <span class="consent-desc">
                Your symptom reports join anonymous counts that help spot side effects earlier.
                Entirely optional — everything else works exactly the same if you decline.
              </span>
            </span>
          </label>

          <p class="hint">
            Both can be withdrawn at any time in Settings. Withdrawing research consent removes
            you from future counts.
          </p>
        </div>

        {{-- ── Clinic only ─────────────────────────────────────────── --}}
        <div data-for="clinic" class="stack" hidden>
          <div class="section-title">Your organisation</div>

          <label class="field">
            <span class="field-label">Organisation name</span>
            <span class="input-group">
              <x-icon name="hospital" class="i-sm" />
              <input class="input" name="organisation_name" value="{{ old('organisation_name') }}" placeholder="e.g. Ikeja General Hospital">
            </span>
          </label>

          <div class="row wrap" style="gap:var(--s3)">
            <label class="field grow">
              <span class="field-label">City</span>
              <input class="input" name="city" value="{{ old('city') }}" placeholder="Lagos">
            </label>
            <label class="field">
              <span class="field-label">Type</span>
              <select class="select" name="kind">
                <option value="hospital" @selected(old('kind') === 'hospital')>Hospital</option>
                <option value="pharmacy" @selected(old('kind') === 'pharmacy')>Pharmacy</option>
              </select>
            </label>
          </div>

          <label class="field">
            <span class="field-label">Password</span>
            <input class="input" type="password" name="password" autocomplete="new-password">
            <span class="field-hint">At least 8 characters. Staff sign in with a password because every action they take is audited against their name.</span>
          </label>

          <label class="field">
            <span class="field-label">Confirm password</span>
            <input class="input" type="password" name="password_confirmation" autocomplete="new-password">
          </label>
        </div>

        <button class="btn btn-block btn-lg" type="submit" style="margin-top:var(--s2)">
          Create account <x-icon name="arrowR" class="i-sm" />
        </button>
      </form>

      <p class="hint center" style="margin-top:var(--s5)">
        Already registered?
        <a href="{{ route('login') }}" style="color:var(--accent);font-weight:650">Sign in</a>
      </p>

    </div>
  </main>
</div>

<script>
// Which half of the form applies depends on who this is for. Server-side
// validation branches on the same field, so this only hides what would be
// ignored anyway — the form is still correct with JavaScript off.
(function () {
  var form = document.getElementById('reg-form');

  function apply() {
    var role = form.querySelector('input[name="role"]:checked').value;
    var clinic = role === 'clinic';

    form.querySelectorAll('[data-for="patient"]').forEach(function (el) { el.hidden = clinic; });
    form.querySelectorAll('[data-for="clinic"]').forEach(function (el) { el.hidden = !clinic; });
    document.getElementById('step2').classList.toggle('is-done', clinic);

    // A hidden required field blocks submission with no visible reason.
    form.querySelectorAll('[data-for="clinic"] input, [data-for="clinic"] select').forEach(function (el) {
      el.disabled = !clinic;
    });
  }

  form.addEventListener('change', function (e) {
    if (e.target.name === 'role') apply();
  });
  apply();
})();

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
