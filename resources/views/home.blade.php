@php use App\Support\Chart; @endphp
<!DOCTYPE html>
<html lang="en" data-skin="patient">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MedBuddy — Medication adherence in the patient's own language</title>
<meta name="description" content="MedBuddy explains prescriptions in English, Yorùbá, Hausa and Igbo, keeps patients on their doses offline, and gives clinics a feedback loop from the people actually taking the medicine.">
<link rel="stylesheet" href="{{ asset('styles/fonts.css') }}">
<link rel="stylesheet" href="{{ asset('styles/tokens.css') }}">
<link rel="stylesheet" href="{{ asset('styles/app.css') }}">
<link rel="stylesheet" href="{{ asset('styles/chart.css') }}">
<link rel="stylesheet" href="{{ asset('styles/site.css') }}">
</head>
<body class="site">

<header class="site-head">
  <div class="container">
    <a class="brand" href="{{ route('home') }}">
      <span class="brand-mark" aria-hidden="true"></span>
      <span class="brand-name">MedBuddy</span>
    </a>
    <nav class="site-nav">
      <a href="#patient">Patient app</a>
      <a href="#clinic">Clinic console</a>
      <a href="#loop">The loop</a>
      <a href="#build">How it's built</a>
    </nav>
    <span class="grow"></span>
    <button class="iconbtn" type="button" data-theme-toggle aria-label="Toggle theme"></button>
    <a class="btn btn-secondary" href="{{ route('login') }}">Sign in</a>
    <a class="btn" href="{{ route('me.login') }}">Open the app</a>
  </div>
</header>

{{-- ── Hero ──────────────────────────────────────────────────────── --}}
<section class="hero">
  <div class="container">
    <div class="hero-grid">
      <div>
        <span class="eyebrow"><x-icon name="globe" class="i-sm" />English · Yorùbá · Hausa · Igbo</span>
        <h1 class="display" style="margin-top:var(--s4)">A prescription you can't read is a prescription you can't follow.</h1>
        <p class="lede measure" style="margin-top:var(--s5)">
          Two apps and one loop between them. Patients get their label explained and their
          doses tracked. Clinics get adherence, complaints and safety signals from
          the people actually taking the medicine.
        </p>
        <div class="row wrap" style="margin-top:var(--s6)">
          <a class="btn btn-lg" href="{{ route('me.login') }}">Open the patient app</a>
          <a class="btn btn-secondary btn-lg" href="{{ route('login') }}">Open the clinic console</a>
        </div>
        <p class="hint" style="margin-top:var(--s3)">
          Sign-in for patients is a magic link — no password. Staff credentials are on the sign-in page.
        </p>
      </div>

      {{-- Real phone UI, not a mockup image --}}
      <div class="phone-frame">
        <div class="phone-screen">
          <div class="pf-head">
            <span class="pf-mark"></span>
            <div class="grow"><strong>Today</strong><div class="pf-sub">{{ now()->format('l, j F') }}</div></div>
            <span class="pf-sos">SOS</span>
          </div>
          <div class="pf-body">
            <div class="pf-card pf-row">
              <div>{!! Chart::ring(67, ['size' => 74, 'thickness' => 8, 'tone' => 'caution', 'label' => "Today's doses"]) !!}</div>
              <div class="grow"><div class="pf-streak">12</div><div class="pf-sub">day streak</div></div>
            </div>
            <div class="pf-card pf-taken pf-row">
              <div class="grow"><div class="pf-time">8:00 AM</div><strong style="text-decoration:line-through">Amlodipine</strong><div class="pf-sub">5 mg · blood pressure</div></div>
              <span class="pf-check is-on">✓</span>
            </div>
            <div class="pf-card pf-row">
              <div class="grow"><div class="pf-time">8:00 PM</div><strong>Metformin</strong><div class="pf-sub">500 mg · blood sugar</div></div>
              <span class="pf-check"></span>
            </div>
            <div class="pf-band">Mu tábìlẹ́tì kan lójoojúmọ́</div>
          </div>
          <div class="pf-tabs"><span class="is-on">Today</span><span>Medicines</span><span>Scan</span><span>Ask</span><span>Me</span></div>
        </div>
      </div>
    </div>

    <div class="figures">
      <div><div class="n">{{ $figures['pages'] }}</div><div class="hint" style="margin-top:var(--s1)">clinic console pages</div></div>
      <div><div class="n">4</div><div class="hint" style="margin-top:var(--s1)">languages, including the crisis detector</div></div>
      <div><div class="n">{{ $figures['patients'] }}</div><div class="hint" style="margin-top:var(--s1)">patients in the seeded cohort</div></div>
      <div><div class="n">{{ $stats['respondRate'] }}%</div><div class="hint" style="margin-top:var(--s1)">answer a reminder within its dose window</div></div>
    </div>
  </div>
</section>

{{-- ── Clinic ────────────────────────────────────────────────────── --}}
<section class="section section-alt" id="clinic">
  <div class="container">
    <div class="sec-head">
      <div><span class="eyebrow">The clinic console</span>
        <p class="hint" style="margin-top:var(--s2)">{{ $figures['pages'] }} pages, built for a desk</p></div>
      <div>
        <h2>Dense, sortable, and dark at 3am</h2>
        <p class="lede measure" style="margin-top:var(--s3)">
          Staff scan a cohort, not one card at a time. Sidebar navigation, real data tables
          with sort and pagination, and charts that answer a question rather than decorate a page.
        </p>
      </div>
    </div>

    {{-- The charts below are the console's own chart code reading the real
         database, not screenshots. What you see here is what staff see. --}}
    <div class="browser-frame">
      <div class="bf-bar"><span></span><span></span><span></span><em>{{ parse_url(config('app.url'), PHP_URL_HOST) }}/clinic</em></div>
      <div class="bf-body" data-skin="clinic">
        <div class="bf-side">
          <div class="bf-brand"><span class="bf-mark"></span><strong>MedBuddy</strong></div>
          <div class="bf-navlabel">Operations</div>
          <div class="bf-nav is-on">Overview</div>
          <div class="bf-nav">Patients <em>{{ $stats['enrolled'] }}</em></div>
          <div class="bf-nav">Adherence</div>
          <div class="bf-navlabel">Care</div>
          <div class="bf-nav">Medicines</div>
          <div class="bf-nav">Complaints <em class="is-hot">{{ $stats['complaintsOpen'] }}</em></div>
          <div class="bf-nav">Outcomes</div>
          <div class="bf-navlabel">Safety</div>
          <div class="bf-nav">Signals <em class="is-hot">{{ $figures['signals'] }}</em></div>
        </div>
        <div class="bf-main">
          <div class="bf-stats">
            <div class="bf-stat"><span>Patients</span><strong>{{ $stats['enrolled'] }}</strong></div>
            <div class="bf-stat"><span>Adherence 7d</span><strong>{{ $stats['adherence7'] }}%</strong></div>
            <div class="bf-stat"><span>Reports today</span><strong>{{ $stats['reportsToday'] }}</strong></div>
            <div class="bf-stat"><span>Needs action</span><strong style="color:var(--alert)">{{ $stats['needsAttention'] }}</strong></div>
          </div>
          <div class="bf-charts">
            <div class="bf-panel">
              <div class="bf-ptitle">Cohort adherence · 30 days</div>
              {!! Chart::area($trend['values'], ['labels' => [], 'min' => 40, 'max' => 100, 'tone' => 'safe', 'band' => ['from' => 80, 'to' => 100], 'height' => 120]) !!}
            </div>
            <div class="bf-panel">
              <div class="bf-ptitle">Who is on track</div>
              {!! Chart::donut($bands, ['size' => 108, 'thickness' => 15, 'centre' => ['value' => $stats['enrolled'], 'label' => 'patients']]) !!}
              {!! Chart::legend(collect($bands)->map(fn ($b) => ['label' => explode(' ', $b['label'])[0], 'tone' => $b['tone']])->all()) !!}
            </div>
          </div>
          <div class="bf-panel" style="margin-top:10px">
            <div class="bf-ptitle">Adherence by dose window</div>
            {!! Chart::heatStrip(collect($heat)->map(fn ($h) => ['label' => $h['label'].' · '.$h['pct'].'%', 'value' => $h['value']])->all(), ['tone' => 'safe']) !!}
          </div>
        </div>
      </div>
    </div>

    <div class="cards" style="margin-top:var(--s6)">
      <a class="card" href="{{ route('clinic.adherence') }}">
        <div class="card-icon is-info"><x-icon name="pulse" /></div>
        <h3>Adherence <x-icon name="arrowR" class="i-sm" /></h3>
        <p class="muted">Dose-window heat map, per-patient stacked bars, and who is trending down rather than just who is low.</p>
      </a>
      <a class="card" href="{{ route('clinic.medicines') }}">
        <div class="card-icon"><x-icon name="pill" /></div>
        <h3>Medicines <x-icon name="arrowR" class="i-sm" /></h3>
        <p class="muted">Every drug in the cohort: patients on it, adherence, complaint rate, and deterministic interaction checks.</p>
      </a>
      <a class="card" href="{{ route('clinic.complaints') }}">
        <div class="card-icon is-safe"><x-icon name="chat" /></div>
        <h3>Complaints <x-icon name="arrowR" class="i-sm" /></h3>
        <p class="muted">The triage queue. Severity-sorted, filterable, with voice notes flagged for human review where ASR can't cope.</p>
      </a>
    </div>
  </div>
</section>

{{-- ── Patient ───────────────────────────────────────────────────── --}}
<section class="section" id="patient">
  <div class="container">
    <div class="sec-head">
      <div><span class="eyebrow">The patient app</span>
        <p class="hint" style="margin-top:var(--s2)">Eight screens, built for a phone held at arm's length</p></div>
      <div>
        <h2>Their language, their text size, one tap per dose</h2>
        <p class="lede measure" style="margin-top:var(--s3)">
          No password — a magic link in the reminder mail is the credential, because this
          audience churns hard on account creation. Text size is a first-class control,
          not a setting three menus deep.
        </p>
      </div>
    </div>

    <div class="cards">
      <a class="card" href="{{ route('me.login') }}">
        <div class="card-icon is-safe"><x-icon name="check" /></div>
        <h3>Today <x-icon name="arrowR" class="i-sm" /></h3>
        <p class="muted">Every dose due, a streak worth keeping, and one tap to confirm.</p>
      </a>
      <a class="card" href="{{ route('me.login') }}">
        <div class="card-icon"><x-icon name="camera" /></div>
        <h3>Explain a label <x-icon name="arrowR" class="i-sm" /></h3>
        <p class="muted">Photograph a pharmacy note or a doctor's handwriting and get it in plain words.</p>
      </a>
      <a class="card" href="{{ route('me.login') }}">
        <div class="card-icon is-info"><x-icon name="chat" /></div>
        <h3>Ask a question <x-icon name="arrowR" class="i-sm" /></h3>
        <p class="muted">Medication questions answered in their own language — behind an emergency gate that runs first.</p>
      </a>
    </div>
  </div>
</section>

{{-- ── Loop ──────────────────────────────────────────────────────── --}}
<section class="section section-alt" id="loop">
  <div class="container">
    <div class="sec-head">
      <div><span class="eyebrow">The loop</span>
        <p class="hint" style="margin-top:var(--s2)">Where the data comes from</p></div>
      <div>
        <h2>A message, one tap, and it lands on the dashboard</h2>
      </div>
    </div>

    <div class="cards">
      <div class="card">
        <div class="card-icon"><x-icon name="send" /></div>
        <h3>1 · The message says almost nothing</h3>
        <p class="muted">
          &ldquo;Did you take your <strong>morning tablet</strong>?&rdquo; Never the drug, never the condition.
          A lock-screen preview naming an antiretroviral can out someone's diagnosis.
        </p>
        <span class="badge badge-safe">enforced by a test</span>
      </div>
      <div class="card">
        <div class="card-icon is-info"><x-icon name="lock" /></div>
        <h3>2 · The link needs no login</h3>
        <p class="muted">
          Scoped to one dose, expiring with the window. A password wall here costs more
          responses than it protects — and the responses are the entire dataset.
        </p>
      </div>
      <div class="card">
        <div class="card-icon is-safe"><x-icon name="mic" /></div>
        <h3>3 · Tap first, speak second</h3>
        <p class="muted">
          An icon grid gives clean structured data with zero literacy required. The voice note
          is the richness on top, reviewed by a nurse where transcription can't cope.
        </p>
      </div>
    </div>

    <p class="center" style="margin-top:var(--s6)">
      <a class="btn btn-lg" href="{{ route('clinic.outbox') }}">See the outbox and follow a link →</a>
    </p>
  </div>
</section>

{{-- ── Build ─────────────────────────────────────────────────────── --}}
<section class="section" id="build">
  <div class="container">
    <div class="sec-head">
      <div><span class="eyebrow">How it's built</span>
        <p class="hint" style="margin-top:var(--s2)">Where AI is load-bearing, and where it's banned</p></div>
      <div>
        <h2>The model explains. It never decides whether something is an emergency.</h2>
      </div>
    </div>

    <div class="split-table">
      <div class="split-col split-ai">
        <h3>Claude does the work</h3>
        <ul>
          <li><x-icon name="camera" class="i-sm" /><span>Explain a label, pharmacy note, or a doctor's handwriting</span></li>
          <li><x-icon name="pill" class="i-sm" /><span>Identify a pill — with a confidence rating the UI enforces</span></li>
          <li><x-icon name="chat" class="i-sm" /><span>Answer medication questions in four languages</span></li>
          <li><x-icon name="clipboard" class="i-sm" /><span>Draft a caregiver summary, sent only after a person approves it</span></li>
        </ul>
      </div>
      <div class="split-col split-det">
        <h3>Deterministic code does the work</h3>
        <ul>
          <li><x-icon name="alert" class="i-sm" /><span>Emergency detection — <em>before</em> any model call, in all four languages</span></li>
          <li><x-icon name="shield" class="i-sm" /><span>Interaction severity — curated dataset, never model recall</span></li>
          <li><x-icon name="bell" class="i-sm" /><span>Doses, reminders, streaks, adherence maths</span></li>
          <li><x-icon name="phone" class="i-sm" /><span>SOS contacts and emergency dialling</span></li>
        </ul>
      </div>
    </div>

    <div class="hero-grid" style="margin-top:var(--s6);align-items:start">
      <p class="pull">
        Twenty characters needed by Yorùbá, Igbo and Hausa were missing from the brand
        fonts. We found that with a script, not on stage.
      </p>
      <div class="foot-note">
        <strong style="color:var(--ink)">The dot-below vowels sit in the Vietnamese Unicode block.</strong>
        The standard <span class="mono">latin-ext</span> subset skips straight over it, and IBM Plex
        has no glyphs for the Hausa hooked letters at all. So the branded faces are used only on
        English surfaces, and the patient app runs a verified system stack instead. Correctness
        beat branding.
        <div style="margin-top:var(--s3)">
          <a class="btn btn-secondary btn-sm" href="{{ route('deck.technical') }}#fonts">Read the audit</a>
          <a class="btn btn-secondary btn-sm" href="/tools/diacritics.html">Run the diacritic check</a>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="site-foot">
  <div class="container row wrap" style="gap:var(--s4)">
    <span class="row" style="gap:var(--s2)">
      <span class="brand-mark" style="width:22px;height:22px" aria-hidden="true"></span><strong>MedBuddy</strong>
    </span>
    <span class="grow"></span>
    <a href="{{ route('me.login') }}">Patient app</a>
    <a href="{{ route('login') }}">Clinic console</a>
    <a href="{{ route('register') }}">Create an account</a>
    <a href="{{ route('deck.pitch') }}">Pitch deck</a>
    <a href="{{ route('deck.technical') }}">Technical deck</a>
    <a href="/tools/diacritics.html">Diacritic check</a>
    <span>AI: <strong>{{ config('services.ai.mode') }}</strong> · {{ config('services.ai.provider') }}</span>
    <span>&copy; {{ date('Y') }}</span>
  </div>
</footer>

<script>
// Theme. Same contract as the two app shells: an explicit choice wins, and is
// remembered; with no choice the OS decides.
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
})();
</script>

<style>
/* ── Device frames. Real markup, not images. ─────────────────────── */
.phone-frame {
  border: 10px solid #1b1a17; border-radius: 34px; background: #1b1a17;
  box-shadow: var(--e3); overflow: hidden; max-width: 320px; margin-inline: auto;
}
.phone-screen { background: var(--paper); border-radius: 24px; overflow: hidden; font-size: 13px; }
.pf-head { display: flex; align-items: center; gap: 8px; padding: 14px 12px 10px; border-bottom: 1px solid var(--rule); }
.pf-mark { width: 26px; height: 26px; border-radius: 7px; background: var(--caution); flex: none; }
.pf-sub { color: var(--ink-3); font-size: 11px; }
.pf-sos { background: var(--alert); color: #fff; font-weight: 800; font-size: 10px; padding: 6px 10px; border-radius: 999px; }
.pf-body { padding: 12px; display: flex; flex-direction: column; gap: 8px; }
.pf-card { background: var(--card); border: 1px solid var(--rule); border-radius: 9px; padding: 10px; }
.pf-row { display: flex; align-items: center; gap: 10px; }
.pf-taken { background: var(--safe-bg); border-color: var(--safe-line); }
.pf-time { font-family: var(--font-mono); font-size: 11px; color: var(--ink-2); font-weight: 700; }
.pf-streak { font-family: var(--font-mono); font-size: 26px; font-weight: 700; line-height: 1; }
.pf-check { width: 32px; height: 32px; border-radius: 50%; border: 2.5px solid var(--rule-2); flex: none; display: grid; place-items: center; color: transparent; }
.pf-check.is-on { background: var(--safe); border-color: var(--safe); color: #fff; font-size: 16px; }
.pf-band { border-left: 4px solid var(--caution); background: var(--caution-bg); padding: 8px 10px; border-radius: 5px; font-size: 12px; }
.pf-tabs { display: flex; border-top: 1px solid var(--rule); background: var(--card); }
.pf-tabs span { flex: 1; text-align: center; padding: 10px 2px; font-size: 9px; font-weight: 700; color: var(--ink-3); }
.pf-tabs .is-on { color: var(--ink); box-shadow: inset 0 2px 0 var(--caution); }

.browser-frame { border: 1px solid var(--rule-2); border-radius: 12px; overflow: hidden; box-shadow: var(--e3); background: var(--card); }
.bf-bar { display: flex; align-items: center; gap: 6px; padding: 9px 12px; background: var(--paper-2); border-bottom: 1px solid var(--rule); }
.bf-bar span { width: 10px; height: 10px; border-radius: 50%; background: var(--rule-2); }
.bf-bar em { margin-left: 10px; font-style: normal; font-size: 11px; color: var(--ink-3); font-family: var(--font-mono); }
.bf-body { display: grid; grid-template-columns: 168px 1fr; background: var(--paper); font-size: 12px; }
.bf-side { background: var(--card); border-right: 1px solid var(--rule); padding: 10px 8px; }
.bf-brand { display: flex; align-items: center; gap: 7px; padding: 4px 6px 10px; border-bottom: 1px solid var(--rule); margin-bottom: 8px; }
.bf-mark { width: 20px; height: 20px; border-radius: 6px; background: var(--accent); }
.bf-navlabel { font-size: 9px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--ink-3); padding: 8px 6px 3px; }
.bf-nav { display: flex; align-items: center; padding: 6px; border-radius: 5px; color: var(--ink-2); font-weight: 600; }
.bf-nav em { margin-left: auto; font-style: normal; font-size: 9px; font-family: var(--font-mono); background: var(--neutral-bg); padding: 1px 5px; border-radius: 999px; }
.bf-nav em.is-hot { background: var(--alert-bg); color: var(--alert); }
.bf-nav.is-on { background: var(--accent); color: var(--accent-ink); }
.bf-main { padding: 12px; min-width: 0; }
.bf-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
.bf-stat { background: var(--card); border: 1px solid var(--rule); border-radius: 8px; padding: 9px; }
.bf-stat span { display: block; font-size: 9px; text-transform: uppercase; letter-spacing: .08em; color: var(--ink-3); font-weight: 700; }
.bf-stat strong { display: block; font-family: var(--font-mono); font-size: 20px; letter-spacing: -.02em; margin-top: 2px; }
.bf-charts { display: grid; grid-template-columns: 1.6fr 1fr; gap: 10px; margin-top: 10px; }
.bf-panel { background: var(--card); border: 1px solid var(--rule); border-radius: 8px; padding: 10px; min-width: 0; }
.bf-ptitle { font-size: 10px; font-weight: 700; color: var(--ink-2); margin-bottom: 8px; }
.bf-panel .ch-legend { margin-top: 8px; gap: 4px 10px; }
.bf-panel .ch-key { font-size: 9px; }
.bf-panel .heat-label { font-size: 8px; }
.bf-panel .heat-box { height: 30px; }

@media (max-width: 760px) {
  .bf-body { grid-template-columns: 1fr; }
  .bf-side { display: none; }
  .bf-charts { grid-template-columns: 1fr; }
  .bf-stats { grid-template-columns: repeat(2, 1fr); }
}
</style>
</body>
</html>
