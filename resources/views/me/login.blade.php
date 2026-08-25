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
