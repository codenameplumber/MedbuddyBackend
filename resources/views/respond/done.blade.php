<!DOCTYPE html>
<html lang="{{ $patient->lang }}" data-skin="patient">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title>MedBuddy</title>
<link rel="stylesheet" href="{{ asset('styles/tokens.css') }}">
<link rel="stylesheet" href="{{ asset('styles/app.css') }}">
<link rel="stylesheet" href="{{ asset('styles/respond.css') }}">
</head>
<body>
<main class="rp">
  <section class="rp-body rp-done">
    <div class="rp-tick" aria-hidden="true">&#10003;</div>
    <h1 class="rp-q">{{ __('respond.done') }}</h1>
    <p class="rp-sub">{{ $took ? __('respond.donebody') : __('respond.donemissed') }}</p>
  </section>
</main>
</body>
</html>
