{{--
  Reached when a free-text note trips the deterministic triage gate.
  No AI on this page at all: it renders instantly with no network, which is
  the entire reason the gate is deterministic.
--}}
<!DOCTYPE html>
<html lang="{{ $patient->lang }}" data-skin="patient">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#B91C1C">
<meta name="robots" content="noindex, nofollow">
<title>Get help now — MedBuddy</title>
<link rel="stylesheet" href="{{ asset('styles/tokens.css') }}">
<link rel="stylesheet" href="{{ asset('styles/app.css') }}">
<link rel="stylesheet" href="{{ asset('styles/respond.css') }}">
</head>
<body>
<main class="rp">
  <section class="rp-body">
    <div class="rp-tick rp-tick-warn" aria-hidden="true">!</div>
    <h1 class="rp-q">This may be an emergency</h1>
    <p class="rp-sub">
      What you described can be serious. Do not wait for an answer from this app.
      Get help now.
    </p>

    @if ($guidance)
      <div class="band band-alert" style="border-radius:var(--radius);margin-top:var(--s5)">
        <div><span class="band-title">Do this now</span>{{ $guidance }}</div>
      </div>
    @endif

    <a class="btn btn-block btn-lg"
       style="margin-top:var(--s6);background:var(--alert);border-color:var(--alert);min-height:88px;font-size:var(--step-2)"
       href="tel:{{ $number }}">Call {{ $number }}</a>

    <p class="hint center" style="margin-top:var(--s5)">
      Your answer was still saved and your clinic can see it.
    </p>
  </section>
</main>
</body>
</html>
