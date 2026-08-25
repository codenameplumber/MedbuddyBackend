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

{{--
  This page is cached by the service worker before anyone signs in, so it
  cannot know which language the reader wants. It says the same short thing
  in all four rather than defaulting to English at the one moment the app is
  least able to help — and the emergency number, which is the only thing here
  that has to work at zero bars, needs no translation at all.
--}}

<header class="phone-head">
  <span class="brand-mark" aria-hidden="true"></span>
  <div class="grow"><h1 class="phone-title">MedBuddy</h1></div>
</header>

<main class="phone-body">

  <div class="band band-caution" style="border-radius:var(--radius)">
    <x-icon name="alert" class="band-icon" />
    <div><span class="band-title">No connection</span></div>
  </div>

  <div class="stack" style="margin-top:var(--s6)">
    @foreach ([
      ['English', 'You are offline. Your doses are safe — they are stored with your clinic. Open this again when you have a connection.'],
      ['Yorùbá',  'Kò sí ìsopọ̀ ìtàkùn. Àwọn òògùn rẹ wà láìséwu ní ilé ìwòsàn rẹ. Ṣí i padà nígbà tí o bá ní ìsopọ̀.'],
      ['Hausa',   'Babu haɗin yanar gizo. Bayanan magungunanka suna nan lafiya a asibiti. Sake buɗe wannan idan ka sami haɗi.'],
      ['Igbo',    'Ị nweghị njikọ ịntanetị. Ọgwụ gị dị nchekwa nʼụlọọgwụ gị. Meghee ya ọzọ mgbe ị nwere njikọ.'],
    ] as [$name, $text])
      <article class="label" lang="{{ ['English'=>'en','Yorùbá'=>'yo','Hausa'=>'ha','Igbo'=>'ig'][$name] }}">
        <div class="label-body">
          <div class="hint" style="text-transform:uppercase;letter-spacing:var(--tracking-caps)">{{ $name }}</div>
          <p style="margin-top:var(--s2)">{{ $text }}</p>
        </div>
      </article>
    @endforeach
  </div>

  <div class="band band-alert" style="border-radius:var(--radius);margin-top:var(--s6)">
    <x-icon name="phone" class="band-icon" />
    <div>
      <span class="band-title">Emergency · Gaggawa · Pàjáwìrì · Ihe Mberede</span>
      <a href="tel:112" class="btn btn-block btn-lg" style="margin-top:var(--s3)">
        Call 112
      </a>
    </div>
  </div>

  <p class="hint center" style="margin-top:var(--s6)">
    <a href="{{ route('me.today') }}">Try again</a>
  </p>
</main>

<script>
// Reload the moment the device is back, so nobody sits looking at this page
// after the connection has already returned.
window.addEventListener('online', function () { location.replace(@json(route('me.today'))); });
</script>
</body>
</html>
