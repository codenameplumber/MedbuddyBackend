<!DOCTYPE html>
<html lang="{{ $patient->lang }}" data-skin="patient">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#FCFBF8">
<meta name="robots" content="noindex, nofollow">
<title>MedBuddy</title>
<link rel="stylesheet" href="{{ asset('styles/tokens.css') }}">
<link rel="stylesheet" href="{{ asset('styles/app.css') }}">
<link rel="stylesheet" href="{{ asset('styles/respond.css') }}">
</head>
<body>
<main class="rp">

  <header class="rp-head">
    <div class="rp-brand"><span class="brand-mark" aria-hidden="true"></span><span>MedBuddy</span></div>
    <p class="rp-greet">{{ __('reminder.greeting', ['name' => $patient->firstName()]) }}</p>
  </header>

  <form method="POST" action="{{ url()->signedRoute('respond.store', ['reminder' => $reminder->id]) }}"
        class="rp-body" enctype="multipart/form-data">
    @csrf

    {{-- Step 1 --}}
    <h1 class="rp-q">{{ __('respond.q1', ['slot' => __('respond.slot_'.$reminder->slot)]) }}</h1>
    <div class="rp-choices">
      <label class="rp-choice rp-yes">
        <input type="radio" name="took" value="yes" required class="sr-only" data-took>
        <span class="rp-choice-glyph" aria-hidden="true">&#10003;</span>
        <span>{{ __('respond.yes') }}</span>
      </label>
      <label class="rp-choice rp-no">
        <input type="radio" name="took" value="no" required class="sr-only" data-took>
        <span class="rp-choice-glyph" aria-hidden="true">&times;</span>
        <span>{{ __('respond.no') }}</span>
      </label>
    </div>

    {{-- Steps 2 and 3 are revealed on the client, then posted in ONE request.
         On a bad connection every extra round trip is a chance to fail. --}}
    <div id="more" hidden>
      <h2 class="rp-q" style="margin-top:var(--s7)">{{ __('respond.q2') }}</h2>
      <p class="rp-sub">{{ __('respond.q2sub') }}</p>

      <div class="sym-grid">
        @foreach ($symptoms as $s)
          <label class="sym">
            <input type="checkbox" name="symptoms[]" value="{{ $s }}" class="sr-only" data-sym="{{ $s }}">
            <span class="sym-glyph" aria-hidden="true">{{ ['fine'=>'🙂','dizzy'=>'💫','rash'=>'🔴','stomach'=>'🤢','sleepy'=>'😴','swelling'=>'💧','other'=>'❓'][$s] }}</span>
            <span class="sym-label">{{ __('respond.'.$s) }}</span>
          </label>
        @endforeach
      </div>

      <div class="rp-sev" id="sev" hidden>
        <h3 class="rp-sub" style="font-weight:650;color:var(--ink)">{{ __('respond.sev') }}</h3>
        <div class="sev-row">
          @for ($n = 1; $n <= 3; $n++)
            <label class="sev">
              <input type="radio" name="severity" value="{{ $n }}" class="sr-only" data-sev>
              {{ __('respond.sev'.$n) }}
            </label>
          @endfor
        </div>
      </div>

      <label class="field" style="margin-top:var(--s6)">
        <span class="field-label">{{ __('respond.more') }}</span>
        <textarea class="textarea" name="note" placeholder="{{ __('respond.moresub') }}"></textarea>
      </label>

      {{-- A voice note is the lowest-literacy input there is. It is an
           addition to the tap grid, never a replacement: the grid is what
           produces data a cohort view can count. --}}
      <div class="rec-wrap" data-recorder="form">
        <button class="rec" type="button" data-rec
                data-idle="{{ __('respond.rec') }}" data-rec-str="{{ __('respond.recording') }}"
                data-done="{{ __('respond.recdone') }}" data-nomic="{{ __('respond.recno') }}"
                aria-label="{{ __('respond.rec') }}">
          <x-icon name="mic" />
        </button>
        <span class="hint" data-rec-label>{{ __('respond.rec') }}</span>
        <audio class="rec-play" data-rec-play controls hidden></audio>
        <input type="file" name="audio" accept="audio/*" data-rec-input class="sr-only" tabindex="-1">
      </div>
    </div>

    <button class="btn btn-block btn-lg" type="submit" style="margin-top:var(--s6)">
      {{ __('respond.send') }}
    </button>
  </form>

  <footer class="rp-foot">
    <span>{{ __('reminder.footer') }}</span>
  </footer>
</main>

<script src="{{ asset('js/recorder.js') }}" defer></script>
<script>
// No framework. Progressive disclosure only; the whole form posts once.
(function () {
  var more = document.getElementById('more');
  var sev  = document.getElementById('sev');

  document.querySelectorAll('[data-took]').forEach(function (r) {
    r.addEventListener('change', function () {
      more.hidden = false;
      r.closest('.rp-choices').querySelectorAll('.rp-choice').forEach(function (c) {
        c.classList.toggle('is-on', c.contains(r) && r.checked);
      });
    });
  });

  document.querySelectorAll('[data-sym]').forEach(function (c) {
    c.addEventListener('change', function () {
      // "Feeling fine" is exclusive, mirroring the server-side rule.
      if (c.dataset.sym === 'fine' && c.checked) {
        document.querySelectorAll('[data-sym]').forEach(function (o) {
          if (o !== c) { o.checked = false; o.closest('.sym').setAttribute('aria-pressed', 'false'); }
        });
      } else if (c.checked) {
        var fine = document.querySelector('[data-sym="fine"]');
        if (fine) { fine.checked = false; fine.closest('.sym').setAttribute('aria-pressed', 'false'); }
      }
      c.closest('.sym').setAttribute('aria-pressed', String(c.checked));

      var anyBad = [].some.call(document.querySelectorAll('[data-sym]'), function (x) {
        return x.checked && x.dataset.sym !== 'fine';
      });
      sev.hidden = !anyBad;
    });
  });

  document.querySelectorAll('[data-sev]').forEach(function (r) {
    r.addEventListener('change', function () {
      document.querySelectorAll('.sev').forEach(function (s) {
        s.setAttribute('aria-pressed', String(s.contains(r) && r.checked));
      });
    });
  });
})();
</script>
</body>
</html>
