<!DOCTYPE html>
<html lang="en" data-skin="patient">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#FCFBF8">
<meta name="robots" content="noindex, nofollow">
<title>MedBuddy</title>
<link rel="stylesheet" href="{{ asset('styles/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('styles/tokens.css') }}">
<link rel="stylesheet" href="{{ asset('styles/app.css') }}">
<link rel="stylesheet" href="{{ asset('styles/shell.css') }}">
</head>
<body data-app="patient">

<header class="phone-head">
  <a class="iconbtn" href="{{ route('home') }}" aria-label="{{ __('me.common.back') }}">
    <x-icon name="arrowL" />
  </a>
  <div class="grow"><h1 class="phone-title">MedBuddy</h1></div>
</header>

<main class="phone-body">
  @if (session('sent'))
    <div class="band band-safe" style="border-radius:var(--radius)">
      <x-icon name="check" class="band-icon" />
      <div><span class="band-title">{{ __('me.profile.signinSent') }}</span></div>
    </div>
  @else
    <p class="muted" style="font-size:var(--step-1);line-height:1.6">{{ __('me.profile.signinLead') }}</p>

    <form method="POST" action="{{ route('me.login.send') }}" class="stack" style="margin-top:var(--s6)">
      @csrf
      <label class="field">
        <span class="field-label">Email</span>
        <input class="input" type="email" name="email" required autofocus inputmode="email"
               style="font-size:var(--step-1);padding:var(--s4)">
        @error('email')<span class="field-hint" style="color:var(--alert)">{{ $message }}</span>@enderror
      </label>
      <button class="btn btn-block btn-lg" type="submit">{{ __('me.profile.signinCta') }}</button>
    </form>

    <p class="hint center" style="margin-top:var(--s5)">
      Not enrolled yet?
      <a href="{{ route('register') }}" style="color:var(--accent);font-weight:650">Create an account</a>
    </p>

    {{--
      Demo shortcut. The clinic sign-in page lists its seeded accounts because
      it has passwords to list; this one has none, which used to leave the
      patient app with no way in that did not involve reading a mailbox.
    --}}
    @if ($demoPatients->isNotEmpty())
      <div class="section-title" style="margin-top:var(--s7)">Open the demo as a patient</div>
      <p class="hint">
        Skip the mailbox. Every person below is fabricated — one tap signs you in as them,
        in their language.
      </p>

      <div class="stack-s" style="margin-top:var(--s4)">
        @foreach ($demoPatients as $p)
          <form method="POST" action="{{ route('me.demo') }}">
            @csrf
            <input type="hidden" name="patient" value="{{ $p->id }}">
            <button class="label" type="submit"
                    style="display:block;width:100%;text-align:left;border:1px solid var(--rule);
                           background:var(--card);cursor:pointer;padding:0">
              <span class="label-body" style="display:block">
                <span class="row-between wrap" style="gap:var(--s2)">
                  <span>
                    <strong>{{ $p->name }}</strong>
                    <span class="hint" style="display:block">
                      {{ $p->langName() }} · {{ $p->condition }}{{ $p->age ? ' · '.$p->age : '' }}
                    </span>
                  </span>
                  <x-icon name="arrowR" class="i-sm" />
                </span>
              </span>
            </button>
          </form>
        @endforeach
      </div>

      <div class="band band-caution" style="border-radius:var(--radius);margin-top:var(--s5)">
        <x-icon name="alert" class="band-icon" />
        <div>
          <span class="band-title">Demo only</span>
          This panel appears because <span class="mono">MEDBUDDY_DEMO_SIGNIN</span> is on. It
          only ever lists the fabricated cohort, and refuses any record that is not one.
        </div>
      </div>
    @endif
  @endif

  <div class="band band-info" style="border-radius:var(--radius);margin-top:var(--s6)">
    <x-icon name="lock" class="band-icon" />
    <div>
      <span class="band-title">Why there is no password</span>
      The same reasoning as a dose reminder link: this audience churns hard on account
      creation, and a password wall costs more usage than it protects. The link is signed,
      expires in 30 minutes, and works once.
    </div>
  </div>
</main>
</body>
</html>
