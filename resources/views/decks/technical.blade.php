<!DOCTYPE html>
<!-- ═══════════════════════════════════════════════════════════════════
     MedBuddy — pitch deck.

     16:9 slides at 297×167mm. On screen they stack for scrolling review;
     printed, each slide is exactly one page with no margins — which is how
     the PDF is produced.

     The charts here are the real chart library reading the real seed data,
     not screenshots. If the product changes, the deck changes with it.
     ═══════════════════════════════════════════════════════════════════ -->
<html lang="en" data-skin="patient">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MedBuddy — pitch deck</title>
<link rel="stylesheet" href="/styles/fonts.css">
<link rel="stylesheet" href="/styles/tokens.css">
<link rel="stylesheet" href="/styles/app.css">
<link rel="stylesheet" href="/styles/chart.css">
<link rel="stylesheet" href="/styles/deck.css">
</head>
<body class="deck">

<div class="deck-bar">
  <strong>MedBuddy — pitch deck</strong>
  <span style="opacity:.6" id="count"></span>
  <span style="flex:1"></span>
  <a href="/">Site</a>
  <a href="/me">Patient app</a>
  <a href="/clinic">Clinic console</a>
  <button type="button" onclick="window.print()">Print / Save as PDF</button>
</div>

<!-- ═════════════ 1 · COVER ═════════════ -->
<section class="slide slide-cover">
  <div class="s-eyebrow">AI Build Fest 2026</div>
  <h1 class="s-title">A prescription you can&rsquo;t read<br>is a prescription you can&rsquo;t follow.</h1>
  <p class="s-lede">
    MedBuddy explains medicines in the patient&rsquo;s own language, keeps them on their doses
    offline, and gives the clinic a feedback loop from the people actually taking the medicine.
  </p>
  <div class="cover-meta">
    <div><strong>3</strong>surfaces &mdash; patient app, reminder link, clinic console</div>
    <div><strong>4</strong>languages &mdash; English, Yor&ugrave;b&aacute;, Hausa, Igbo</div>
    <div><strong>0</strong>dependencies &mdash; no framework, no CDN, no build step</div>
    <div><strong>0</strong>bars needed for doses, alarms and SOS</div>
  </div>
</section>

<!-- ═════════════ 2 · THE PROBLEM ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">The problem</div>
  <h2 class="s-title">Roughly half of all medicine for long-term illness is never taken as directed.</h2>
  <div class="s-body">
    <div class="s-cols s-3">
      <div class="s-card s-card-alert">
        <div class="s-kicker">Failure one &middot; comprehension</div>
        <h3>The label is in a language you do not read</h3>
        <p>Prescriptions are written in English, in clinical shorthand, often by hand.
        &ldquo;1 tab PO BD PRN&rdquo; is not an instruction &mdash; it is a cipher. Nigeria alone
        has more than 500 living languages.</p>
      </div>
      <div class="s-card s-card-alert">
        <div class="s-kicker">Failure two &middot; routine</div>
        <h3>Nobody forgets the morning dose</h3>
        <p>They forget the evening one. Adherence is not mainly a motivation problem, it is a
        routine problem &mdash; and routines break in a specific, measurable place that nobody
        is currently measuring.</p>
      </div>
      <div class="s-card s-card-alert">
        <div class="s-kicker">Failure three &middot; side effects</div>
        <h3>The rash that never reached a clinic</h3>
        <p>People stop a drug because of something they never told anyone. The clinic records
        a non-adherent patient. It never learns the reason, so it cannot fix it.</p>
      </div>
    </div>
    <div class="s-band s-band-alert">
      <div>
        <b>And the clinic is blind to all three.</b>
        A doctor finds out months later, at the next appointment, from a patient trying to
        remember. The one group that knows exactly what went wrong &mdash; the patients &mdash;
        has no cheap way to say so.
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 3 · THE INSIGHT ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">The insight</div>
  <h2 class="s-title">A reply is only cheap if you can read the question.</h2>
  <div class="s-body">
    <div class="s-cols s-2a">
      <div style="display:flex;flex-direction:column;gap:5mm;min-height:0">
        <div class="s-card">
          <h3>Every adherence product asks the patient to do the work</h3>
          <p>Install an app. Create an account. Remember a password. Read English. Type a
          sentence. Each step drops a slice of the population &mdash; and it is never a random
          slice. It is the least literate, least connected and least confident users, which
          is exactly the cohort with the worst outcomes.</p>
        </div>
        <div class="s-card s-card-safe">
          <h3>So we removed all five</h3>
          <p>The reminder arrives on WhatsApp or SMS. The link opens with no login and no
          install. The question is in the patient&rsquo;s own language. The answer is a tap on
          a picture. The voice note is a bonus on top, never the load-bearing input.</p>
        </div>
      </div>
      <div class="s-card s-card-accent">
        <h3>What that buys</h3>
        <ul class="s-list">
          <li>Structured adherence data from people who cannot read the label</li>
          <li>Symptom reports at the moment they happen, not recalled months later</li>
          <li>Adverse-event surveillance from the patient side &mdash; a layer that is almost
          entirely missing from every national system today</li>
          <li>A measurable answer to the question the whole product is built to ask:
          <span class="hl">does being asked in your own language change whether you answer at all?</span></li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 4 · WHAT IT IS ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">What MedBuddy is</div>
  <h2 class="s-title">Three surfaces, and one loop between them.</h2>
  <div class="s-body">
    <div class="s-cols s-3">
      <div class="s-card s-card-accent">
        <div class="s-kicker">Surface one &middot; the phone</div>
        <h3>Patient app</h3>
        <p>Ten screens. Doses and streaks, a camera that explains a label, questions answered
        in four languages, and an SOS that works at zero bars.</p>
        <p class="s-kicker" style="margin-top:auto">Installable PWA &middot; offline-first</p>
      </div>
      <div class="s-card s-card-info">
        <div class="s-kicker">Surface two &middot; the message</div>
        <h3>Reminder link</h3>
        <p>A page that opens from an SMS in well under a second, with no login, no install and
        no framework behind it. Four taps and it is done.</p>
        <p class="s-kicker" style="margin-top:auto">Zero literacy required</p>
      </div>
      <div class="s-card s-card-safe">
        <div class="s-kicker">Surface three &middot; the desk</div>
        <h3>Clinic console</h3>
        <p>Ten pages. Cohort adherence, a complaints queue, drug-level safety signals, and an
        outbox showing exactly what every patient will receive.</p>
        <p class="s-kicker" style="margin-top:auto">Dense, sortable, dark at 3am</p>
      </div>
    </div>
    <div class="s-band s-band-info">
      <div>
        <b>The loop is the product.</b>
        The patient app alone is a pill reminder &mdash; there are hundreds. The console alone
        is a dashboard with nothing in it. What is worth building is the path between them:
        a message that costs nothing to answer, feeding a view the clinic has never had.
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 5 · SECTION DIVIDER ═════════════ -->
<section class="slide slide-dark">
  <div class="s-eyebrow">Part one</div>
  <h2 class="s-title">The loop</h2>
  <p class="s-lede">A message that names nothing, a link that needs no login, one tap, and it
  lands on the dashboard. Five steps &mdash; all of them live in this build.</p>
</section>

<!-- ═════════════ 6 · THE LOOP END TO END ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">The loop, end to end</div>
  <h2 class="s-title s-title-sm">Dose window &rarr; message &rarr; tap &rarr; cohort view &rarr; clinical action</h2>
  <div class="s-body">
    <div class="s-flow">
      <div class="s-flow-step">
        <div class="s-flow-n">01</div>
        <h4>The scheduler wakes</h4>
        <p>One message per patient per dose <em>window</em>, never one per medicine. Four
        separate messages at 08:00 teaches people to ignore all four.</p>
      </div>
      <div class="s-flow-step">
        <div class="s-flow-n">02</div>
        <h4>The message says almost nothing</h4>
        <p>&ldquo;Did you take your morning tablet?&rdquo; No drug, no condition, no dose. A
        lock-screen preview is a disclosure surface.</p>
      </div>
      <div class="s-flow-step">
        <div class="s-flow-n">03</div>
        <h4>The link opens instantly</h4>
        <p>No login. Scoped to one dose, expiring with the window, and rendered entirely in
        the patient&rsquo;s own language.</p>
      </div>
      <div class="s-flow-step">
        <div class="s-flow-n">04</div>
        <h4>Tap first, speak second</h4>
        <p>Yes or no, then a grid of large symptom glyphs, then severity. A voice note only
        if there is something more to say.</p>
      </div>
      <div class="s-flow-step">
        <div class="s-flow-n">05</div>
        <h4>It lands on the desk</h4>
        <p>The report appears in the overview feed, the patient record, the complaints queue
        and the safety-signal table within seconds.</p>
      </div>
    </div>
    <div class="s-cols s-2" style="flex:0 0 auto">
      <div class="s-note">
        <strong>You can walk this live.</strong> Open the clinic outbox, pick any patient,
        click &ldquo;Open this link&rdquo;, answer it the way a patient would, then return to
        the overview. The report is there, tagged as having arrived during this session.
      </div>
      <div class="s-note">
        <strong>What is standing in for a server.</strong> The join between link and console is
        <span class="mono">localStorage</span>, which only works because both surfaces happen
        to be the same browser. That is exactly why the real thing needs a backend &mdash; see
        the honesty ledger later in this deck.
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 7 · THE MESSAGE ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Step two &middot; privacy by construction</div>
  <h2 class="s-title s-title-sm">The message names the schedule. Never the diagnosis.</h2>
  <div class="s-body">
    <div class="s-cols s-2">
      <div style="display:flex;flex-direction:column;gap:4mm;min-height:0">
        <div class="s-code">Hello Amina, did you take your <span class="hi">evening tablet</span>?
Tap to confirm and tell us how you feel.

https://medbuddy.app/r/<span class="dim">cDR8MjE6MDA</span></div>
        <div class="s-band s-band-safe">
          <div>
            <b>What is deliberately absent</b>
            <span class="mono">Dolutegravir</span> &nbsp;<span class="mono">HIV</span>
            &nbsp;<span class="mono">1 tablet</span> &nbsp;<span class="mono">21:00</span>
            &nbsp;<span class="mono">clinic name</span>
          </div>
        </div>
      </div>
      <div class="s-card s-card-alert">
        <h3>Why this is not over-caution</h3>
        <p>A WhatsApp preview naming an antiretroviral, on a shared or unlocked phone, discloses
        someone&rsquo;s HIV status to whoever is holding it. The same is true of a TB regimen,
        an antipsychotic, or a contraceptive.</p>
        <p>In a household where that disclosure is dangerous, the rational response is to leave
        the programme &mdash; so the patients who most need adherence support are the first to go.</p>
        <ul class="s-list s-list-alert" style="margin-top:auto">
          <li>Specifics live behind the token, after the check</li>
          <li>Conditions are stored, and never enter a message body</li>
          <li>An automated test fails the build if a drug name ever appears in an outbound body</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 8 · THE LINK ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Step three &middot; the no-login decision</div>
  <h2 class="s-title s-title-sm">A password wall here costs more responses than it protects.</h2>
  <div class="s-body">
    <div class="s-cols s-2a">
      <div class="s-card">
        <h3>The trade, stated plainly</h3>
        <p>The responses <em>are</em> the dataset. Every friction step between a reminder and a
        check-off removes respondents, and never randomly &mdash; the least literate and least
        connected go first, which is the precise cohort the product exists to reach.</p>
        <p>So the token is the security model, scoped as tightly as the job allows.</p>
        <div class="s-cols s-2" style="gap:4mm;flex:0 0 auto;margin-top:2mm">
          <div>
            <div class="s-kicker">The token carries</div>
            <ul class="s-list s-list-info">
              <li>One patient</li>
              <li>One dose window</li>
              <li>Nothing else</li>
            </ul>
          </div>
          <div>
            <div class="s-kicker">The token cannot</div>
            <ul class="s-list s-list-alert">
              <li>Read the medical record</li>
              <li>Change a prescription</li>
              <li>Outlive its dose window</li>
            </ul>
          </div>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:4mm;min-height:0">
        <div class="s-band s-band-alert">
          <div>
            <b>Demo build versus production &mdash; the honest version</b>
            The token in this build is reversible base64, so the entire loop runs with no
            server at all. It proves the UX, not the security model.
          </div>
        </div>
        <div class="s-card s-card-safe">
          <h3>What production requires</h3>
          <ul class="s-list s-list-safe">
            <li>HMAC-signed server-side, key never leaving the server</li>
            <li>An audience claim naming exactly one dose</li>
            <li>Hard expiry at the end of the dose window</li>
            <li>Single-use &mdash; a replay is rejected</li>
            <li>Rate limiting per token and per address</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 9 · THE RESPONSE PAGE ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Step four &middot; the response page</div>
  <h2 class="s-title s-title-sm">Tap first. Speak second. Never type.</h2>
  <div class="s-body">
    <div class="s-cols s-4">
      <div class="s-card s-card-accent">
        <div class="s-flow-n">01 &middot; CONFIRM</div>
        <h3>Did you take it?</h3>
        <p>Two buttons, full width, unmissable. A tick and a cross &mdash; readable without a
        single word of text.</p>
      </div>
      <div class="s-card s-card-accent">
        <div class="s-flow-n">02 &middot; SYMPTOMS</div>
        <h3>How do you feel?</h3>
        <p>Seven large glyphs. Multi-select, except &ldquo;feeling fine&rdquo;, which is
        mutually exclusive &mdash; otherwise the cohort fills with &ldquo;fine + rash&rdquo;,
        which means nothing.</p>
      </div>
      <div class="s-card s-card-accent">
        <div class="s-flow-n">03 &middot; SEVERITY</div>
        <h3>How bad is it?</h3>
        <p>Three levels, and only shown when something was actually reported. A severity
        question after &ldquo;I feel fine&rdquo; is noise.</p>
      </div>
      <div class="s-card s-card-accent">
        <div class="s-flow-n">04 &middot; VOICE</div>
        <h3>Say more, if you want</h3>
        <p>Hold to record. Skipped entirely when nothing is wrong. A nurse listens where
        transcription cannot cope.</p>
      </div>
    </div>
    <div class="s-cols s-2" style="flex:0 0 auto">
      <div class="s-band s-band-info">
        <div>
          <b>Why the glyph grid is the load-bearing input</b>
          It yields clean structured data with zero literacy and zero speech recognition &mdash;
          so the cohort view works regardless of how thin ASR coverage is for a given language.
        </div>
      </div>
      <div class="s-note">
        <strong>This page imports nothing from the app.</strong> Not even the shared i18n module.
        It has to render instantly on a bad connection from a message, so it duplicates fifteen
        strings rather than carry a dependency on the patient store. That is a deliberate,
        documented trade.
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 10 · THE JOIN ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Step five &middot; the join</div>
  <h2 class="s-title s-title-sm">One tap, and it is on four screens at once.</h2>
  <div class="s-body">
    <div class="s-cols s-2b">
      <div class="s-code">saveResponse({
  patientId: <span class="ok">'p2'</span>,
  time:      <span class="ok">'20:00'</span>,
  took:      <span class="no">false</span>,
  symptoms:  [<span class="ok">'rash'</span>],
  severity:  <span class="hi">2</span>,
  audio:     <span class="ok">true</span>,
  lang:      <span class="ok">'ha'</span>,
})
<span class="dim">     |
     v  reshaped by clinic/data.js</span>
Overview feed        <span class="ok">&#10003;</span>
Patient record       <span class="ok">&#10003;</span>
Complaints queue     <span class="ok">&#10003;</span>
Safety signal table  <span class="ok">&#10003;</span></div>
      <div style="display:flex;flex-direction:column;gap:4mm;min-height:0">
        <div class="s-card">
          <h3>The report is attributed, not just recorded</h3>
          <p>The dose time is matched against the medicines scheduled at that time, so a symptom
          arrives already attached to a drug &mdash; the best attribution available without
          asking a patient to name a molecule.</p>
        </div>
        <div class="s-card s-card-info">
          <h3>Provenance is part of the record</h3>
          <p>A report arriving through a reminder link is marked <span class="mono">source:
          'link'</span>. It is a materially stronger signal than the same symptom recalled in a
          consultation three weeks later, and the console never flattens the two together.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 11 · SECTION DIVIDER ═════════════ -->
<section class="slide slide-dark">
  <div class="s-eyebrow">Part two</div>
  <h2 class="s-title">The patient app</h2>
  <p class="s-lede">Ten screens, built to be read at arm&rsquo;s length by someone who may be
  anxious, elderly, and reading a language the label was not written in.</p>
</section>

<!-- ═════════════ 12 · PATIENT APP MAP ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">The patient app &middot; ten screens</div>
  <h2 class="s-title s-title-sm">Five tabs, and five screens behind them.</h2>
  <div class="s-body">
    <table class="s-table s-table-dense">
      <thead><tr><th style="width:20%">Screen</th><th style="width:28%">What it does</th><th>The design decision behind it</th></tr></thead>
      <tbody>
        <tr><td><strong>Today</strong><div class="s-kicker">tab 1</div></td><td>Doses due, a progress ring, and the streak count</td><td>The default screen answers one question &mdash; what do I take now &mdash; before anything else competes for attention</td></tr>
        <tr><td><strong>Medicines</strong><div class="s-kicker">tab 2</div></td><td>Every medicine, its purpose in plain words, and its schedule</td><td>Sorted by what it is <em>for</em>, not by name. Patients think in conditions, not molecules</td></tr>
        <tr><td><strong>Scan</strong><div class="s-kicker">tab 3</div></td><td>Photograph a label or a pill and have it explained</td><td>The single highest-value AI surface, and the one with the strictest confidence rules</td></tr>
        <tr><td><strong>Ask</strong><div class="s-kicker">tab 4</div></td><td>Free-text questions about your own medicines</td><td>Every message passes the deterministic emergency gate before the model sees it</td></tr>
        <tr><td><strong>Me</strong><div class="s-kicker">tab 5</div></td><td>Language, text size, voice, emergency contacts, AI mode</td><td>Text scale multiplies the whole type scale at once, so nothing breaks at the largest setting</td></tr>
        <tr><td><strong>Add medicine</strong></td><td>Name, strength, form, purpose, times of day</td><td>Times are chips, not a time picker. Picking 08:00 on a phone dial is a known drop-off point</td></tr>
        <tr><td><strong>Medicine detail</strong></td><td>One medicine, its AI explanation, and its history</td><td>Any AI-written text stays labelled as AI-written, permanently</td></tr>
        <tr><td><strong>History</strong></td><td>Streaks, weekly rate, and the dose grid over time</td><td>Progress shown without shaming. A missed dose is grey, not red</td></tr>
        <tr><td><strong>How I feel</strong></td><td>The in-app twin of the reminder response page</td><td>Same symptom vocabulary, so app and link reports are directly comparable</td></tr>
        <tr><td><strong>Emergency</strong></td><td>Guidance, the emergency number, and saved contacts</td><td>No AI on this page at all. It renders instantly, offline, with no network</td></tr>
      </tbody>
    </table>
  </div>
</section>

<!-- ═════════════ 13 · TODAY ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Patient app &middot; Today</div>
  <h2 class="s-title s-title-sm">One decision per screen, at arm&rsquo;s length.</h2>
  <div class="s-body">
    <div class="s-cols s-2b">
      <div style="display:flex;flex-direction:column;gap:4mm;min-height:0">
        <div class="s-card s-card-accent">
          <h3>The 56-pixel floor</h3>
          <p>Every tap target is at least 56px &mdash; a floor, not a target. The audience includes
          elderly users with reduced dexterity and cracked screens. The clinic console gets a denser
          40px, because it is driven with a mouse.</p>
        </div>
        <div class="s-card s-card-safe">
          <h3>The streak is honest</h3>
          <p>A day counts only if <em>every</em> scheduled dose was taken, and a day with nothing
          scheduled cannot extend a streak. The streak also does not visibly break at 00:01 &mdash;
          today counts as in progress until it is complete.</p>
        </div>
        <div class="s-card s-card-info">
          <h3>Undo is free</h3>
          <p>Tapping a taken dose again un-takes it, with no confirmation dialogue &mdash; a
          mis-tap costs one more tap, and a modal in front of a 72-year-old is a bigger obstacle
          than the mistake.</p>
        </div>
      </div>
      <div class="s-chart s-chart-lg" id="c-ring">
        <div class="s-chart-t">Today&rsquo;s doses &mdash; the ring on the home screen</div>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 14 · SCAN ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Patient app &middot; Scan</div>
  <h2 class="s-title s-title-sm">Point the camera at the thing you cannot read.</h2>
  <div class="s-body">
    <div class="s-cols s-2">
      <div class="s-card s-card-accent">
        <div class="s-kicker">Mode one</div>
        <h3>Read a label</h3>
        <p>A printed label, a pharmacy note, or a doctor&rsquo;s handwriting. Returns purpose,
        dosing, side effects, warnings and storage &mdash; in the patient&rsquo;s language, in
        short sentences, with no jargon and no markdown.</p>
        <ul class="s-list">
          <li>Handwriting explicitly lowers the confidence rating</li>
          <li>Any ambiguity sets <span class="mono">askPharmacist</span> and the UI leads with it</li>
          <li>A confirmed reading can be saved straight into the medicine list</li>
        </ul>
      </div>
      <div class="s-card s-card-info">
        <div class="s-kicker">Mode two</div>
        <h3>Identify a pill</h3>
        <p>A loose tablet with no packaging. Returns only what is visible &mdash; colour, shape,
        scoring, imprint &mdash; plus up to three candidates, each with its reasoning.</p>
        <ul class="s-list s-list-info">
          <li>&ldquo;I cannot tell&rdquo; is a correct answer, not a failure</li>
          <li>High confidence is reserved for clear packaging text naming the medicine</li>
          <li>Photos are downscaled on-device before upload, bounding the token cost</li>
        </ul>
      </div>
    </div>
    <div class="s-band s-band-alert">
      <div>
        <b>Identifying a pill from a photo is genuinely unreliable.</b>
        Many pills share a colour and a shape, imprints are often too small to resolve, and
        generics vary by country. The prompt says so, the schema forces a rating, and the
        interface enforces what each rating may display &mdash; the subject of the next slide.
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 15 · THE CONFIDENCE CONTRACT ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">The confidence contract</div>
  <h2 class="s-title s-title-sm">The model supplies a confidence. The interface decides what that confidence is allowed to show.</h2>
  <div class="s-body">
    <table class="s-table s-table-dense">
      <thead>
        <tr>
          <th style="width:16%">Model returns</th>
          <th style="width:17%">Single name shown?</th>
          <th style="width:17%">All candidates shown?</th>
          <th>What the patient actually reads</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><span class="badge badge-safe">high</span></td>
          <td><strong style="color:var(--safe)">Yes</strong></td>
          <td>No</td>
          <td>The name, the reasoning, and &ldquo;still worth confirming with your pharmacist.&rdquo;</td>
        </tr>
        <tr>
          <td><span class="badge badge-caution">medium</span></td>
          <td><strong style="color:var(--alert)">No</strong></td>
          <td>Yes</td>
          <td>&ldquo;I am not certain. These are possibilities only &mdash; do not take anything based on this.&rdquo;</td>
        </tr>
        <tr>
          <td><span class="badge badge-caution">low</span></td>
          <td><strong style="color:var(--alert)">No</strong></td>
          <td>Yes</td>
          <td>Identical treatment to medium. There is no rendering in which a guess reads as an answer.</td>
        </tr>
        <tr>
          <td><span class="badge badge-alert">cannot_identify</span></td>
          <td><strong style="color:var(--alert)">No</strong></td>
          <td>No</td>
          <td>&ldquo;I could not tell what this is. Please show it to your pharmacist.&rdquo;</td>
        </tr>
      </tbody>
    </table>
    <div class="s-cols s-2" style="flex:0 0 auto">
      <div class="s-band s-band-safe">
        <div>
          <b>Structurally guaranteed, not politely requested</b>
          The confidence field is <span class="mono">required</span> in the JSON schema passed
          to the model, so it cannot be omitted. A prompt that asks nicely can be ignored; a
          schema cannot.
        </div>
      </div>
      <div class="s-note">
        <strong>The same rule governs label reading.</strong> A low or unreadable result hides
        the medicine name entirely and leads with a red band, rather than printing a plausible
        name above a caveat nobody reads.
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 16 · ASK ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Patient app &middot; Ask</div>
  <h2 class="s-title s-title-sm">Questions in four languages &mdash; behind a gate the model never sees past.</h2>
  <div class="s-body">
    <div class="s-cols s-2a">
      <div class="s-card">
        <h3>What it will answer</h3>
        <ul class="s-list s-list-safe">
          <li>&ldquo;Can I take this with food?&rdquo;</li>
          <li>&ldquo;What is this one for?&rdquo;</li>
          <li>&ldquo;Why do I feel dizzy in the morning?&rdquo; &mdash; with a pointer to the pharmacist</li>
        </ul>
        <h3 style="margin-top:3mm">What it refuses, by contract</h3>
        <ul class="s-list s-list-alert">
          <li>&ldquo;Should I stop taking this?&rdquo;</li>
          <li>&ldquo;Can I double the dose?&rdquo;</li>
          <li>Anything that amounts to a diagnosis</li>
        </ul>
        <p style="margin-top:auto">The schema carries a <span class="mono">refused</span> boolean
        and a <span class="mono">seeClinician</span> boolean, so a refusal is a structured state
        the interface can style &mdash; not a sentence the app has to parse out of prose.</p>
      </div>
      <div style="display:flex;flex-direction:column;gap:4mm;min-height:0">
        <div class="s-band s-band-alert">
          <div>
            <b>The gate runs first, and it is not a model</b>
            Every message is checked against an on-device emergency term list before any network
            call exists. If it trips, the app routes to the emergency screen and never contacts
            the model at all.
          </div>
        </div>
        <div class="s-card s-card-info">
          <h3>The patient&rsquo;s medicine list is in the prompt</h3>
          <p>Names, strengths and purposes are passed as context, so &ldquo;the white one&rdquo;
          resolves to something real instead of a general answer about white tablets.</p>
        </div>
        <div class="s-card">
          <h3>Read aloud, but only honestly</h3>
          <p>English gets real speech synthesis. For the other three the button is hidden rather
          than reading the text in an English voice.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 17 · THE TRIAGE GATE ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Safety &middot; the emergency gate</div>
  <h2 class="s-title s-title-sm">The model is never asked whether something is an emergency.</h2>
  <div class="s-body">
    <div class="s-cols s-2b">
      <div class="s-code">check(<span class="ok">"my chest is tight"</span>, <span class="ok">"yo"</span>)
<span class="dim">// synchronous. on-device. zero network.</span>
  &rarr; { crisis: <span class="no">true</span>, category: <span class="hi">'cardiac'</span> }

<span class="dim">// ai/chat.js — the gate</span>
<span class="hi">if</span> (triage.crisis) <span class="hi">return</span> { crisis: <span class="no">true</span> }
<span class="dim">// ...the model call is below this line
//    and is never reached.</span></div>
      <div style="display:flex;flex-direction:column;gap:4mm;min-height:0">
        <div class="s-card s-card-alert">
          <h3>Eight categories, four languages</h3>
          <p>Cardiac, breathing, stroke, bleeding, allergic reaction, overdose, self-harm and
          collapse &mdash; each with its own guidance text on the emergency screen.</p>
        </div>
        <div class="s-card">
          <h3>Three deliberate design choices</h3>
          <ul class="s-list s-list-alert">
            <li><strong>Every language is scanned, always</strong> &mdash; people code-switch,
            and a Yor&ugrave;b&aacute; speaker may well type &ldquo;chest pain&rdquo;</li>
            <li><strong>Diacritics are stripped first,</strong> so
            <span class="multi">&ldquo;&#7885;fun&rdquo;</span> and &ldquo;ofun&rdquo; both match</li>
            <li><strong>It is tuned to over-trigger</strong> &mdash; a false alarm costs one
            screen, a miss costs far more</li>
          </ul>
        </div>
        <div class="s-band s-band-alert">
          <div>
            <b>Stated limitation</b>
            The non-English term lists are a first pass and need a clinician and a native speaker
            to expand them. They are plain strings, not regular expressions, precisely so a
            non-programmer can edit them.
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 18 · EMERGENCY SCREEN ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Patient app &middot; Emergency</div>
  <h2 class="s-title s-title-sm">The one screen that must work when everything else has failed.</h2>
  <div class="s-body">
    <div class="s-cols s-3">
      <div class="s-card s-card-alert">
        <h3>No AI on this page</h3>
        <p>Not a single model call. It renders instantly with no network, which is the entire
        reason the triage gate is deterministic in the first place.</p>
      </div>
      <div class="s-card s-card-alert">
        <h3>An 88px call button</h3>
        <p>Larger than anything else in the product, coloured alert-red, wired to a real
        <span class="mono">tel:</span> link. Below it, saved emergency contacts, each one tap.</p>
      </div>
      <div class="s-card s-card-alert">
        <h3>Category-specific guidance</h3>
        <p>When triage routes here it carries its category, so a stroke match shows &ldquo;note
        the time symptoms started&rdquo; rather than generic advice.</p>
      </div>
    </div>
    <div class="s-cols s-2" style="flex:0 0 auto">
      <div class="s-note">
        <strong>The SOS button is on every patient screen.</strong> Top right, permanently, in
        the shell rather than in each page &mdash; so there is no screen from which help is more
        than one tap away. It is hidden only on the emergency page itself, where it would point
        at the page you are already on.
      </div>
      <div class="s-band s-band-safe">
        <div>
          <b>Everything on this path is cached</b>
          The service worker holds the emergency page, the triage module and the contacts. At
          zero bars, on a plane, in a basement: it opens.
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 19 · LANGUAGE ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Four languages</div>
  <h2 class="s-title s-title-sm">English, Yor&ugrave;b&aacute;, Hausa, Igbo &mdash; including the crisis detector.</h2>
  <div class="s-body">
    <div class="s-cols s-4">
      <div class="s-card">
        <div class="s-kicker">English</div>
        <h3 class="multi">Did you take your morning tablet?</h3>
        <p>Full brand typography. Real speech synthesis. The only surface where both are true.</p>
      </div>
      <div class="s-card">
        <div class="s-kicker">Yor&ugrave;b&aacute;</div>
        <h3 class="multi">&#7778;&eacute; o mu t&aacute;b&igrave;l&#7865;&#769;t&igrave; &agrave;&aacute;r&#7885;&#768; r&#7865;?</h3>
        <p>Tone marks and dot-below vowels are load-bearing. Rendered from a verified system stack.</p>
      </div>
      <div class="s-card">
        <div class="s-kicker">Hausa</div>
        <h3 class="multi">Ka sha kwayar safe naka?</h3>
        <p>Hooked letters &#595; &#599; &#409; &#436; appear throughout the symptom and severity strings.</p>
      </div>
      <div class="s-card">
        <div class="s-kicker">Igbo</div>
        <h3 class="multi">&#7882; &#7745;&#7909;&#7885;la &#7885;gw&#7909; &#7909;t&#7909;t&#7909; g&#7883;?</h3>
        <p>Dotted vowels &#7883; &#7885; &#7909; and &#7745; &mdash; four characters the brand fonts do not carry at all.</p>
      </div>
    </div>
    <div class="s-cols s-2" style="flex:0 0 auto">
      <div class="s-band s-band-alert">
        <div>
          <b>Marked as draft, in the source, on purpose</b>
          Every non-English string carries a native-speaker-review warning. Health interfaces are
          exactly where a clumsy translation does real harm. The keys and structure are stable, so
          a reviewer edits values and nothing else.
        </div>
      </div>
      <div class="s-note">
        <strong>Language is a property of the patient, not the device.</strong> The reminder link
        renders in the patient&rsquo;s own language with no setting to find and no menu to open
        &mdash; the clinic already knows which language they speak.
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 20 · OFFLINE ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Offline-first</div>
  <h2 class="s-title s-title-sm">Zero bars is the normal case, not the edge case.</h2>
  <div class="s-body">
    <div class="s-cols s-2">
      <div class="s-card s-card-safe">
        <h3>Works with no connection at all</h3>
        <ul class="s-list s-list-safe">
          <li>Every dose, reminder and streak &mdash; all computed on-device</li>
          <li>The full medicine list and every saved explanation</li>
          <li>The emergency screen, the SOS contacts and the triage gate</li>
          <li>The whole clinic console, running on seeded data</li>
        </ul>
      </div>
      <div class="s-card s-card-info">
        <h3>Needs a connection</h3>
        <ul class="s-list s-list-info">
          <li>Explaining a new label or identifying a pill</li>
          <li>Answering a question that is not already in the history</li>
          <li>Generating a caregiver summary</li>
        </ul>
        <p style="margin-top:auto">Each failure says <em>&ldquo;you are offline &mdash; your
        medicines and reminders still work&rdquo;</em>, rather than showing an error and leaving
        the user unsure whether the app is broken.</p>
      </div>
    </div>
    <div class="s-cols s-3" style="flex:0 0 auto">
      <div class="s-note"><strong>Cache-first for the shell.</strong> 60+ files precached: every
      page, every stylesheet, every module, and the fonts &mdash; because a font swapping in
      mid-demo looks broken.</div>
      <div class="s-note"><strong>Files are cached individually.</strong> One 404 cannot leave the
      app with no cache at all, which is what a single bulk add would do.</div>
      <div class="s-note"><strong>API traffic is never cached.</strong> Cross-origin requests are
      not touched by the worker, and a failed navigation falls back to the app&rsquo;s home screen.</div>
    </div>
  </div>
</section>

<!-- ═════════════ 21 · SECTION DIVIDER ═════════════ -->
<section class="slide slide-dark">
  <div class="s-eyebrow">Part three</div>
  <h2 class="s-title">The clinic console</h2>
  <p class="s-lede">Ten pages, built for a desk. Staff scan a cohort, not one card at a time
  &mdash; so the rhythm tightens, the tables sort, and every chart answers a question rather
  than decorating a page.</p>
</section>

<!-- ═════════════ 22 · CONSOLE MAP ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">The clinic console &middot; ten pages</div>
  <h2 class="s-title s-title-sm">Operations, Care, Safety, Messaging.</h2>
  <div class="s-body">
    <table class="s-table s-table-dense">
      <thead><tr><th style="width:9%">Group</th><th style="width:16%">Page</th><th style="width:32%">What it answers</th><th>What is on it</th></tr></thead>
      <tbody>
        <tr><td class="s-kicker">Operations</td><td><strong>Overview</strong></td><td>What needs my attention this morning?</td><td>Four cohort stats, the 30-day trend, the adherence donut, the dose-window heat strip, and a live report feed</td></tr>
        <tr><td></td><td><strong>Patients</strong></td><td>Who is enrolled, and who is slipping?</td><td>Sortable, filterable, paginated table with a derived attention verdict per row</td></tr>
        <tr><td></td><td><strong>Patient record</strong></td><td>What is going on with this one person?</td><td>Medicines, a 14-day dose grid, every report they have sent, consent state and channel</td></tr>
        <tr><td></td><td><strong>Adherence</strong></td><td>Where are doses being lost, and when?</td><td>Dose-window heat map, per-patient stacked bars, and the paired morning-versus-evening comparison</td></tr>
        <tr><td class="s-kicker">Care</td><td><strong>Medicines</strong></td><td>How is the cohort doing on each drug?</td><td>Formulary table, adherence and complaint rate per drug, and deterministic interaction checks</td></tr>
        <tr><td></td><td><strong>Complaints</strong></td><td>What are patients telling us?</td><td>Severity-sorted triage queue, filterable, with voice notes flagged for human review</td></tr>
        <tr><td></td><td><strong>Outcomes</strong></td><td>Is any of this actually working?</td><td>Adherence by condition, channel and language &mdash; behind a caveat that leads rather than follows</td></tr>
        <tr><td class="s-kicker">Safety</td><td><strong>Safety signals</strong></td><td>Is a drug hurting people?</td><td>Drug &times; symptom pairs, reporting rate against exposure, worst severity, escalation route</td></tr>
        <tr><td class="s-kicker">Messaging</td><td><strong>Outbox</strong></td><td>What exactly are we about to send?</td><td>Every scheduled reminder, its full body, its live link, and the delivery funnel</td></tr>
        <tr><td></td><td><strong>Settings</strong></td><td>What counts as &ldquo;needs attention&rdquo;?</td><td>Thresholds in one place, channel status, team roles, the audit-log note, demo controls</td></tr>
      </tbody>
    </table>
  </div>
</section>

<!-- ═════════════ 23 · OVERVIEW ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Console &middot; Overview</div>
  <h2 class="s-title s-title-sm">The morning screen: four numbers, three charts, one feed.</h2>
  <div class="s-body">
    <div class="s-cols s-2a" style="flex:1;min-height:0">
      <div class="s-chart s-chart-lg" id="c-area">
        <div class="s-chart-t">Cohort adherence &middot; 30 days, target band shaded</div>
      </div>
      <div class="s-chart" id="c-donut">
        <div class="s-chart-t">Who is on track</div>
      </div>
    </div>
    <div class="s-cols s-3" style="flex:0 0 auto">
      <div class="s-note"><strong>Charts drawn from real data.</strong> These are the product&rsquo;s
      own chart library reading the product&rsquo;s own seed cohort &mdash; not screenshots pasted
      into a deck.</div>
      <div class="s-note"><strong>&ldquo;Needs attention&rdquo; is derived once, centrally.</strong>
      Low adherence, days of silence, or a recent severe report. One function, so no two pages can
      disagree about who is at risk.</div>
      <div class="s-note"><strong>Live reports are marked.</strong> Anything submitted through a
      link during this session is tagged, so a demo makes the loop visible rather than asking the
      audience to take it on trust.</div>
    </div>
  </div>
</section>

<!-- ═════════════ 24 · ADHERENCE ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Console &middot; Adherence</div>
  <h2 class="s-title s-title-sm">The evening dose is where the programme leaks.</h2>
  <div class="s-body">
    <div class="s-chart s-chart-lg" id="c-heat" style="flex:0 0 auto">
      <div class="s-chart-t">Adherence by dose window &middot; 30 days across the whole cohort</div>
    </div>
    <div class="s-cols s-2" style="flex:1;min-height:0">
      <div class="s-card s-card-alert">
        <h3>The naive version of this chart is wrong &mdash; and we say so on the page</h3>
        <p>Comparing the 07:00 bucket with the 21:00 bucket compares <em>people</em>, not times
        of day. The early bucket happens to contain a poorly-adherent TB patient; the late one
        contains an exceptionally adherent ARV patient. The gap is real, but that chart is not
        evidence of it.</p>
      </div>
      <div class="s-card s-card-safe">
        <h3>So the console computes a paired comparison instead</h3>
        <p>Restrict to patients who take a dose in <em>both</em> windows. Each patient becomes
        their own control, confounding by person disappears, and the morning-minus-evening gap
        finally means something.</p>
        <p>This is the difference between a dashboard that looks analytical and one that is.
        It is four extra lines of code and one honest caveat.</p>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 25 · MEDICINES ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Console &middot; Medicines</div>
  <h2 class="s-title s-title-sm">Interaction severity comes from a dataset, never from model recall.</h2>
  <div class="s-body">
    <div class="s-cols s-2a">
      <div style="display:flex;flex-direction:column;gap:3mm;min-height:0">
        <div class="s-band s-band-alert">
          <div>
            <b>Major &middot; Rifampicin + Dolutegravir/Lamivudine/Tenofovir</b>
            Rifampicin markedly reduces dolutegravir levels. Dose adjustment is required &mdash;
            refer to the prescriber.
          </div>
        </div>
        <div class="s-band s-band-alert">
          <div>
            <b>Major &middot; Carbamazepine + Dolutegravir/Lamivudine/Tenofovir</b>
            Carbamazepine induces metabolism and can cause treatment failure. An alternative
            anticonvulsant is usually preferred.
          </div>
        </div>
        <div class="s-band s-band-info">
          <div>
            <b>Minor &middot; Amlodipine + Hydrochlorothiazide</b>
            Both lower blood pressure. Combined use is intentional here, but watch for dizziness
            on standing.
          </div>
        </div>
      </div>
      <div class="s-card s-card-safe">
        <h3>Narrow and honest beats broad and unverifiable</h3>
        <p>The set is curated and scoped to the drugs this cohort actually takes &mdash;
        antimalarials, antihypertensives, antidiabetics, ARVs and TB regimens.</p>
        <p>A pair that is not in the set returns nothing, <em>and says so</em>, instead of
        inventing a reassuring answer. A language model asked to recall interactions from memory
        will always produce something; that is precisely the problem.</p>
        <div class="s-kicker" style="margin-top:auto">Also on this page</div>
        <ul class="s-list">
          <li>Adherence and complaint rate per medicine</li>
          <li>Patients per drug, and doses per day</li>
          <li>A caveat that a drug two people take shows <em>their</em> adherence, not the drug&rsquo;s</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 26 · COMPLAINTS ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Console &middot; Complaints</div>
  <h2 class="s-title s-title-sm">A triage queue, not an inbox.</h2>
  <div class="s-body">
    <div class="s-cols s-3">
      <div class="s-card s-card-alert">
        <h3>Sorted by severity, not by time</h3>
        <p>An inbox sorted newest-first buries the moderate rash from two days ago under six
        &ldquo;feeling fine&rdquo; replies from this morning. Severity leads; recency breaks ties.</p>
      </div>
      <div class="s-card s-card-info">
        <h3>Voice notes are flagged, not transcribed</h3>
        <p>Speech recognition for Yor&ugrave;b&aacute;, Hausa and Igbo is thin. Rather than
        pretending otherwise, those reports are marked for a nurse to listen to.</p>
      </div>
      <div class="s-card s-card-safe">
        <h3>Every row joins back to a person</h3>
        <p>Patient, drug, dose window, language, severity, provenance &mdash; one click to the
        full record. A complaint with no context is not actionable.</p>
      </div>
    </div>
    <div class="s-band s-band-info">
      <div>
        <b>Why this queue is the highest-value screen in the console</b>
        Adherence data tells you <em>whether</em> someone took the medicine. This tells you
        <em>why they stopped</em> &mdash; and that is the only one of the two a clinic can
        actually act on.
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 27 · SAFETY SIGNALS ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Console &middot; Safety signals</div>
  <h2 class="s-title s-title-sm">Adverse-event surveillance from the people actually taking the drugs.</h2>
  <div class="s-body">
    <div class="s-band s-band-alert" style="flex:0 0 auto">
      <div>
        <b>These are signals, not findings &mdash; and the page says so above the table, not beneath it</b>
        The cohort is small, self-selected and unblinded. A patient reporting a symptom while taking
        a drug is not the drug causing it, and confounding by the underlying condition is the obvious
        rival explanation for most rows. Every line is a question worth investigating, never evidence
        of causation.
      </div>
    </div>
    <div class="s-cols s-2" style="flex:1;min-height:0">
      <div class="s-card">
        <h3>The gap this fills</h3>
        <p>Adverse drug reactions are under-reported almost everywhere, and the reports that are
        filed come overwhelmingly from clinicians. The patient-experienced side &mdash; the rash
        that never reached a clinic, the dizziness someone quietly decided to live with, the dose
        they stopped without telling anyone &mdash; is largely invisible.</p>
        <p>A reminder answered in your own language with one tap captures exactly that missing layer.</p>
      </div>
      <div class="s-card s-card-info">
        <h3>How the table is built</h3>
        <ul class="s-list s-list-info">
          <li>Grouped by drug &times; reported symptom</li>
          <li>Rate is patients reporting divided by patients exposed &mdash; not raw counts</li>
          <li>Worst severity is carried alongside, so one severe report is never averaged away</li>
          <li>The page warns that with denominators this small, the counts matter more than the bars</li>
        </ul>
        <p style="margin-top:auto"><strong>This console is a detector, not a substitute.</strong>
        Anything meeting the reporting threshold belongs in the national pharmacovigilance system
        as well.</p>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 28 · OUTCOMES ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Console &middot; Outcomes</div>
  <h2 class="s-title s-title-sm">Is any of this working? The honest answer is: not proven yet.</h2>
  <div class="s-body">
    <div class="s-band s-band-alert" style="flex:0 0 auto">
      <div>
        <b>Read this before the numbers</b>
        The &ldquo;before enrolment&rdquo; baseline is an assumption, not a measurement &mdash;
        nobody was tracking these patients before they joined. Everyone here also self-selected
        into a programme, which is exactly the population most likely to improve anyway. The lift
        is a hypothesis worth testing properly. A real answer needs a control arm.
      </div>
    </div>
    <div class="s-cols s-2" style="flex:1;min-height:0">
      <div class="s-card">
        <h3>What the page shows anyway</h3>
        <ul class="s-list">
          <li>Adherence by condition &mdash; with a note that denominators are single digits</li>
          <li>Adherence by reminder channel &mdash; with a note that people <em>chose</em> their
          channel, so this may say more about who picks WhatsApp than about WhatsApp</li>
          <li>Adherence by the patient&rsquo;s own language &mdash; too few patients to conclude
          anything, but it is the measurement the whole product exists to make</li>
        </ul>
      </div>
      <div class="s-card s-card-accent">
        <h3>Why put the caveat above the good news?</h3>
        <p>Because a dashboard that leads with an encouraging number and footnotes the
        qualification is teaching its readers to skip the qualification.</p>
        <p>Clinical staff are the last audience that should be trained to do that. The design cost
        is one band at the top of a page. The credibility gain is the whole console.</p>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 29 · OUTBOX ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Console &middot; Outbox</div>
  <h2 class="s-title s-title-sm">Every message, in full, before it goes anywhere.</h2>
  <div class="s-body">
    <div class="s-cols s-2">
      <div class="s-card">
        <h3>What the page gives staff</h3>
        <ul class="s-list">
          <li>Every reminder scheduled today, grouped by dose window</li>
          <li>The exact body text, and the exact link, per patient</li>
          <li>Channel, address and delivery status per row</li>
          <li>A delivery funnel: sent, delivered, opened, responded</li>
        </ul>
        <p style="margin-top:auto"><strong>Response rate is the only number that matters here.</strong>
        Delivered-but-ignored is a message that trained someone to ignore the next one.</p>
      </div>
      <div style="display:flex;flex-direction:column;gap:4mm;min-height:0">
        <div class="s-card s-card-info">
          <h3>Grouped per window, not per medicine</h3>
          <p>A patient on four morning medicines gets one message, not four. This is the difference
          between a reminder and a nuisance, and it is enforced in the data layer rather than left
          to a scheduler&rsquo;s discretion.</p>
        </div>
        <div class="s-band s-band-alert">
          <div>
            <b>Nothing is actually sent in this build</b>
            Outbound SMS, WhatsApp and email need a server holding the provider credentials and a
            scheduler that runs while the patient&rsquo;s phone is asleep. The links, however, are
            live: open one, answer it, and the report appears on the overview.
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 30 · SECTION DIVIDER ═════════════ -->
<section class="slide slide-dark">
  <div class="s-eyebrow">Part four</div>
  <h2 class="s-title">How it is built</h2>
  <p class="s-lede">Where AI is load-bearing, where it is banned outright, and why a health
  product with no dependencies is a safety decision rather than a stunt.</p>
</section>

<!-- ═════════════ 31 · ARCHITECTURE ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Architecture</div>
  <h2 class="s-title s-title-sm">Five layers, and one rule between each pair.</h2>
  <div class="s-body">
    <table class="s-table s-table-dense">
      <thead><tr><th style="width:16%">Layer</th><th style="width:26%">Modules</th><th>The rule it enforces</th></tr></thead>
      <tbody>
        <tr><td><strong>Pages</strong></td><td class="mono">30 HTML files</td><td>Each page supplies only its content region. Navigation, header, tabs and footer come from the shell, so ten pages never means ten copies of the nav</td></tr>
        <tr><td><strong>Shell &amp; UI</strong></td><td class="mono">shell, ui, icons, table, charts, dom</td><td>Screens are functions returning HTML strings. Every interpolation is escaped by default; a trusted fragment has to opt out explicitly</td></tr>
        <tr><td><strong>Core</strong></td><td class="mono">schedule, adherence, triage, tts, i18n</td><td>Pure functions over state. No DOM, no network, no writes &mdash; which is what makes them testable and trustworthy on stage</td></tr>
        <tr><td><strong>AI boundary</strong></td><td class="mono">client, prompts, schemas, mock, explain, chat</td><td>One file talks to a model. It does not import the store and it does not touch the DOM. Every function is input &rarr; validated object</td></tr>
        <tr><td><strong>Data</strong></td><td class="mono">store, models, clinic/data, responses</td><td>One writer, one persist, one subscribe. The clinic layer has a single MODE switch: <span class="mono">'seed'</span> today, <span class="mono">'api'</span> tomorrow, and nothing above it changes</td></tr>
      </tbody>
    </table>
    <div class="s-cols s-3" style="flex:0 0 auto">
      <div class="s-note"><strong>Zero dependencies.</strong> No framework, no CDN, no build step,
      no lockfile. Open a file and it runs.</div>
      <div class="s-note"><strong>Why that is a safety decision.</strong> Offline-first is a
      requirement, venue wifi is not worth betting a demo on, and every transitive dependency in a
      health app is a supply-chain question somebody has to answer.</div>
      <div class="s-note"><strong>~8,600 lines</strong> across 30 pages, 26 modules and 9
      stylesheets &mdash; all of it readable in an afternoon.</div>
    </div>
  </div>
</section>

<!-- ═════════════ 32 · AI VS DETERMINISTIC ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">The central design decision</div>
  <h2 class="s-title s-title-sm">The model explains. It never decides whether something is an emergency.</h2>
  <div class="s-body">
    <div class="s-cols s-2">
      <div class="s-card s-card-info">
        <h3>AI does the work</h3>
        <ul class="s-list s-list-info">
          <li><strong>Explain a label</strong>, a pharmacy note, or a doctor&rsquo;s handwriting</li>
          <li><strong>Identify a pill</strong> &mdash; with a confidence rating the UI enforces</li>
          <li><strong>Answer medication questions</strong> in four languages</li>
          <li><strong>Write a caregiver summary</strong>, plus a compact clinician version</li>
        </ul>
        <p style="margin-top:auto">Common thread: every one of these is <em>explanation</em>. The
        consequence of being wrong is confusion, and the interface is built to catch it.</p>
      </div>
      <div class="s-card s-card-alert">
        <h3>Deterministic code does the work</h3>
        <ul class="s-list s-list-alert">
          <li><strong>Emergency detection</strong> &mdash; before any model call, in all four languages</li>
          <li><strong>Interaction severity</strong> &mdash; curated dataset, never model recall</li>
          <li><strong>Doses, reminders, streaks, adherence maths</strong></li>
          <li><strong>SOS contacts and emergency dialling</strong></li>
        </ul>
        <p style="margin-top:auto">Common thread: every one of these is a <em>decision</em>. The
        consequence of being wrong is harm, and no confidence rating makes that acceptable.</p>
      </div>
    </div>
    <div class="s-quote">If being wrong could hurt someone, a model does not get to decide it.</div>
  </div>
</section>

<!-- ═════════════ 33 · THE AI BOUNDARY ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">The AI boundary</div>
  <h2 class="s-title s-title-sm">Everything a model returns is untrusted until it clears the schema.</h2>
  <div class="s-body">
    <div class="s-cols s-2b">
      <div class="s-code">output_config: {
  format: { type: <span class="ok">'json_schema'</span>, schema },
  effort: <span class="ok">'medium'</span>,
}

<span class="dim">// then, before the UI sees anything:</span>
<span class="hi">if</span> (payload.stop_reason === <span class="ok">'refusal'</span>) throw
<span class="hi">if</span> (!textBlock)                     throw
<span class="hi">if</span> (!JSON.parse(...))               throw
validate(parsed, schema)            <span class="dim">// required
                                    // fields + enums</span></div>
      <div style="display:flex;flex-direction:column;gap:4mm;min-height:0">
        <div class="s-card s-card-safe">
          <h3>Five failure modes, each handled by name</h3>
          <p><span class="mono">no_key</span> &middot; <span class="mono">auth</span> &middot;
          <span class="mono">rate_limit</span> &middot; <span class="mono">network</span> &middot;
          <span class="mono">refusal</span> &middot; <span class="mono">bad_shape</span> &mdash;
          each with a message written for a patient, not a developer.</p>
        </div>
        <div class="s-card s-card-info">
          <h3>A refusal is an HTTP 200</h3>
          <p>A safety classifier declining returns a successful response with no usable content.
          If you read <span class="mono">content[0]</span> without checking
          <span class="mono">stop_reason</span> first, you crash on the one path that most needs
          to degrade gracefully.</p>
        </div>
        <div class="s-card">
          <h3>Validation is deliberately not a library</h3>
          <p>It checks required fields, enum membership and array types &mdash; enough to
          guarantee every field the UI reads exists and holds a real value, with no dependency
          to audit.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 34 · PROMPTS AND SCHEMAS ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Prompts and schemas</div>
  <h2 class="s-title s-title-sm">The safety rules live in one file, so a clinician can review them without reading JavaScript.</h2>
  <div class="s-body">
    <div class="s-cols s-2a">
      <div class="s-card s-card-alert">
        <h3>The shared safety preamble &mdash; on every single call</h3>
        <ul class="s-list s-list-alert">
          <li>Never diagnose. Never suggest starting, stopping or changing a dose</li>
          <li>Never contradict what a doctor or pharmacist has said</li>
          <li>If unsure, say so plainly and point to the pharmacist &mdash; an honest &ldquo;I am
          not sure&rdquo; beats a confident guess</li>
          <li>Short sentences, everyday words, no jargon; any medical term explained immediately</li>
          <li>No markdown, no bullets, no headings &mdash; the app does the formatting</li>
        </ul>
      </div>
      <div style="display:flex;flex-direction:column;gap:3mm;min-height:0">
        <div class="s-kicker">Four schemas, four contracts</div>
        <table class="s-table s-table-dense">
          <tbody>
            <tr><td><span class="mono">EXPLAIN</span></td><td>forces <span class="mono">readingConfidence</span> and <span class="mono">askPharmacist</span></td></tr>
            <tr><td><span class="mono">IDENTIFY</span></td><td>forces <span class="mono">confidence</span>; candidates capped at 3, each with reasoning</td></tr>
            <tr><td><span class="mono">CHAT</span></td><td>forces <span class="mono">refused</span> and <span class="mono">seeClinician</span> as booleans</td></tr>
            <tr><td><span class="mono">SUMMARY</span></td><td>splits caregiver prose from a compact clinician version</td></tr>
          </tbody>
        </table>
        <div class="s-note" style="margin-top:auto">
          <strong>Language is a parameter, not a translation step.</strong> The system prompt says
          &ldquo;write every field in Yor&ugrave;b&aacute;&rdquo;. There is no English intermediate
          to lose meaning through, and no second round trip.
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 35 · MOCK MODE ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Demo insurance</div>
  <h2 class="s-title s-title-sm">Mock mode is the default, and it is not a shortcut.</h2>
  <div class="s-body">
    <div class="s-cols s-3">
      <div class="s-card s-card-safe">
        <h3>The demo cannot fail on network</h3>
        <p>No venue wifi to drop, no rate limit to hit, no key to have expired, no cost per run.
        The product opens and works with no account and no key at all.</p>
      </div>
      <div class="s-card s-card-info">
        <h3>Edge states become reproducible</h3>
        <p>Type &ldquo;blurry&rdquo; and get a low-confidence reading. Type &ldquo;unknown&rdquo;
        and get <span class="mono">cannot_identify</span>. Type &ldquo;should I stop&rdquo; and get
        a refusal. The hard UI states are exercised deliberately rather than hoped for.</p>
      </div>
      <div class="s-card s-card-accent">
        <h3>And the whole app stays developable offline</h3>
        <p>Mock responses carry a deliberate 700&ndash;1200ms delay, so the loading states are
        real and honest rather than instant in development and janky in production.</p>
      </div>
    </div>
    <div class="s-cols s-2" style="flex:0 0 auto">
      <div class="s-band s-band-alert">
        <div>
          <b>The API key problem, stated rather than hidden</b>
          With no backend, a live key must sit in the browser. So it is never bundled &mdash; the
          user pastes their own in Settings and it stays in that device&rsquo;s localStorage.
          Anthropic names the browser-access header &ldquo;dangerous&rdquo; for good reason.
        </div>
      </div>
      <div class="s-band s-band-safe">
        <div>
          <b>And the fix is already wired</b>
          The client has a third mode, <span class="mono">'proxy'</span>, pointing at
          <span class="mono">/api/claude</span>. Moving to a server-held key is two lines in one
          file, not a rewrite.
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 36 · DATA MODEL ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">The data model</div>
  <h2 class="s-title s-title-sm">Schedules are the source of truth. Dose events are only the deviations.</h2>
  <div class="s-body">
    <div class="s-cols s-2b">
      <div class="s-code"><span class="dim">// stored</span>
med.schedule = { times: [<span class="ok">'08:00'</span>, <span class="ok">'20:00'</span>],
                 days:  [0,1,2,3,4,5,6] }

<span class="dim">// stored only when it deviates</span>
doseEvents[<span class="ok">'m1|2026-08-18|20:00'</span>] =
  { status: <span class="ok">'taken'</span>, at: 1755500000000 }

<span class="dim">// everything else is derived</span>
dosesForDay(state, day)   <span class="ok">&rarr;</span> pure
currentStreak(state)      <span class="ok">&rarr;</span> pure
adherenceRate(state, 7)   <span class="ok">&rarr;</span> pure</div>
      <div style="display:flex;flex-direction:column;gap:4mm;min-height:0">
        <div class="s-card s-card-safe">
          <h3>Why it is worth being strict about</h3>
          <p>Storing every dose would create a second source of truth that has to be kept in sync
          with the schedule. Deriving them means state stays tiny, adherence is a pure function,
          and editing a schedule cannot leave orphaned history behind.</p>
        </div>
        <div class="s-card s-card-info">
          <h3>Local time, deliberately</h3>
          <p>Day keys are computed in local time, not UTC &mdash; because a dose at 23:00 belongs
          to today, and a UTC day boundary would silently move it and break the streak for anyone
          east of Greenwich.</p>
        </div>
        <div class="s-card">
          <h3>One writer, and a real migration path</h3>
          <p>A single <span class="mono">commit(draft =&gt; draft)</span> mutates a deep copy,
          persists, then notifies subscribers. Load performs a shallow migration so new keys in a
          later version default in rather than crashing an old save.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 37 · DESIGN SYSTEM ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">The design system</div>
  <h2 class="s-title s-title-sm">One token set, two skins &mdash; because the two audiences are not the same person.</h2>
  <div class="s-body">
    <div class="s-cols s-2">
      <div class="s-card s-card-accent">
        <div class="s-kicker">data-skin="patient"</div>
        <h3>Warm paper, large type, generous space</h3>
        <p>Modelled on a prescription label: paper stock, printed rules, a monospace dosage line,
        an orange caution band. Warm, because this screen is read at home, often by someone anxious.</p>
        <ul class="s-list">
          <li>56px tap floor &middot; 1.0625rem body &middot; runtime text scale</li>
          <li>System font stack &mdash; four languages, verified coverage</li>
        </ul>
      </div>
      <div class="s-card s-card-info">
        <div class="s-kicker">data-skin="clinic"</div>
        <h3>Cool, dense, data-forward, dark at 3am</h3>
        <p>Staff scan lists rather than single cards, so the rhythm tightens and the accent shifts
        to a clinical teal &mdash; medical without the tired hospital-blue cliché.</p>
        <ul class="s-list s-list-info">
          <li>40px dense targets &middot; 0.85 density multiplier</li>
          <li>Branded faces, because this surface is English-only</li>
          <li>Full dark mode &mdash; night shifts are the normal case</li>
        </ul>
      </div>
    </div>
    <div class="s-cols s-3" style="flex:0 0 auto">
      <div class="s-note"><strong>Shared scale, rhythm and semantics.</strong> A component written
      once behaves correctly in either context. Nothing is forked between the two skins.</div>
      <div class="s-note"><strong>Every ink-on-surface pair meets WCAG AA.</strong> Status colours
      are the 700 weights precisely so they stay legible as text, not only as fills.</div>
      <div class="s-note"><strong>Colour is never the only carrier of meaning.</strong> Every
      status is paired with a text label or an icon in the markup itself.</div>
    </div>
  </div>
</section>

<!-- ═════════════ 38 · CHARTS AND TABLES ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Charts and tables</div>
  <h2 class="s-title s-title-sm">Eight chart types and one table engine, hand-rolled, no library.</h2>
  <div class="s-body">
    <div class="s-cols s-2a">
      <div class="s-card">
        <h3>Why not a charting library?</h3>
        <p>The app is offline-first, so a CDN is out. Venue wifi is not worth betting a demo on.
        And a charting library would dwarf the rest of the codebase &mdash; every one of these
        charts is smaller than the loader that would fetch it.</p>
        <div class="s-cols s-2" style="gap:4mm;flex:0 0 auto;margin-top:2mm">
          <div>
            <div class="s-kicker">Charts</div>
            <ul class="s-list s-list-info">
              <li>area &middot; bars &middot; stacked bars</li>
              <li>donut &middot; ring &middot; sparkline</li>
              <li>heat strip &middot; legend</li>
            </ul>
          </div>
          <div>
            <div class="s-kicker">Table engine</div>
            <ul class="s-list s-list-safe">
              <li>sort &middot; filter &middot; paginate</li>
              <li>sticky header &middot; density toggle</li>
              <li>custom cell renderers &middot; empty state</li>
            </ul>
          </div>
        </div>
      </div>
      <div style="display:flex;flex-direction:column;gap:4mm;min-height:0">
        <div class="s-card s-card-safe">
          <h3>Theme-aware with no JavaScript</h3>
          <p>Every chart returns an SVG string, scales through its viewBox, and takes colour from
          CSS custom properties &mdash; so all eight follow the theme into clinic dark mode without
          a line of script.</p>
        </div>
        <div class="s-card s-card-info">
          <h3>Accessible by construction</h3>
          <p>Each chart is <span class="mono">role="img"</span> with a written summary of what it
          shows &mdash; the actual values, not &ldquo;a chart&rdquo;. A chart nobody can read is
          decoration.</p>
        </div>
        <div class="s-note">
          Trend lines use Catmull-Rom smoothing tuned low, so a line stays readable without
          rounding away a real drop. Smoothing that lies about the data is worse than a jagged line.
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 39 · THE TYPOGRAPHY AUDIT ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">The thing that nearly broke it</div>
  <h2 class="s-title s-title-sm">Twenty characters our four languages need were missing from the brand fonts.</h2>
  <div class="s-body">
    <div class="s-cols s-2a">
      <div style="display:flex;flex-direction:column;gap:3mm;min-height:0">
        <div class="s-code">fontTools audit — bundled faces
Yorùbá   <span class="no gl">ẹ Ẹ ọ Ọ</span>            <span class="dim">U+1EB8–1ECD</span>  <span class="no">MISSING</span>
Igbo     <span class="no gl">ị Ị ọ Ọ ụ Ụ ṅ Ṅ</span>    <span class="dim">U+1E44–1EE5</span>  <span class="no">MISSING</span>
Hausa    <span class="no gl">ɓ Ɓ ɗ Ɗ ƙ Ƙ ƴ Ƴ</span>    <span class="dim">U+0181–0257</span>  <span class="no">MISSING</span>

<span class="dim">two separate causes:</span>
<span class="hi">1.</span> dot-below vowels sit in the Vietnamese
   subset. latin-ext runs to U+1E9F, then
   resumes at U+1EF2 — <span class="no">straight over them</span>
<span class="hi">2.</span> the hooked letters are inside latin-ext,
   but IBM Plex <span class="no">has no glyphs for them at all</span></div>
        <div class="s-band s-band-safe">
          <div>
            <b>Found with a script, not on stage</b>
            The fonts were inspected before adoption, and there is a diacritic-check page in the
            build to re-run on the actual demo device.
          </div>
        </div>
      </div>
      <div class="s-card s-card-accent">
        <h3>The fix: split the stacks by what each surface has to render</h3>
        <p><span class="mono">--font-ui</span> &mdash; Plex and Fraunces. Marketing, auth and the
        clinic console. English-only surfaces, so they keep full brand typography.</p>
        <p><span class="mono">--font-multi</span> &mdash; a verified system stack. The patient app
        and the reminder page, which render all four languages. Correct everywhere, just not branded.</p>
        <div class="s-quote" style="font-size:14pt;margin-top:auto">Correctness beat branding.</div>
        <p style="font-size:10pt;color:var(--ink-3)">The real fix is one webfont with full coverage.
        Noto Sans carries all of it: drop it in, repoint one variable, and this slide becomes history.</p>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 40 · ACCESSIBILITY ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Accessibility</div>
  <h2 class="s-title s-title-sm">The users who need this most are the ones every product designs out.</h2>
  <div class="s-body">
    <div class="s-cols s-4">
      <div class="s-card">
        <div class="s-kicker">Motor</div>
        <h3>56px floor</h3>
        <p>Not a target &mdash; a floor. Reduced dexterity, cracked screens and one-handed use are
        assumed, not accommodated.</p>
      </div>
      <div class="s-card">
        <div class="s-kicker">Vision</div>
        <h3>Runtime text scale</h3>
        <p>One variable multiplies the entire type scale at once, so nothing overflows or collides
        at the largest setting. WCAG AA throughout.</p>
      </div>
      <div class="s-card">
        <div class="s-kicker">Literacy</div>
        <h3>Glyphs before words</h3>
        <p>The symptom grid is chunky, recognisable symbols &mdash; the one place where an emoji
        beats a thin professional line icon.</p>
      </div>
      <div class="s-card">
        <div class="s-kicker">Screen readers</div>
        <h3>Real semantics</h3>
        <p>Charts are labelled images with written summaries. Toggles report pressed state. Live
        regions announce toasts. Skip links, focus rings, no motion when it is unwanted.</p>
      </div>
    </div>
    <div class="s-band s-band-info">
      <div>
        <b>And one accessibility feature that is a refusal</b>
        The read-aloud button is hidden for Yor&ugrave;b&aacute;, Hausa and Igbo, because the Web
        Speech API ships essentially no voices for them on any common platform. Reading those
        languages in an English voice would be worse than silence for a low-literacy user. Shipping
        nothing, and saying why, is the accessible choice.
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 41 · PRIVACY ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Privacy and data protection</div>
  <h2 class="s-title s-title-sm">Health data, a shared phone, and a message that arrives unasked.</h2>
  <div class="s-body">
    <div class="s-cols s-3">
      <div class="s-card s-card-safe">
        <h3>Already in the build</h3>
        <ul class="s-list s-list-safe">
          <li>No condition or drug name in any outbound message</li>
          <li>Consent per channel, timestamped and withdrawable</li>
          <li>Messaging and research consent kept separate</li>
          <li>Patient data stays on the patient&rsquo;s own device</li>
          <li>A one-tap erase-everything control</li>
        </ul>
      </div>
      <div class="s-card s-card-info">
        <h3>Designed for, needs the server</h3>
        <ul class="s-list s-list-info">
          <li>Append-only audit log &mdash; every read of a record</li>
          <li>Role-scoped access: physician, pharmacist, nurse</li>
          <li>Encryption at rest and in transit</li>
          <li>Signed, single-use, expiring tokens</li>
          <li>Retention and deletion policy</li>
        </ul>
      </div>
      <div class="s-card s-card-alert">
        <h3>Stated openly</h3>
        <ul class="s-list s-list-alert">
          <li>The demo cohort is entirely fabricated &mdash; no real patient data anywhere in this build</li>
          <li>The demo token is reversible and proves the UX, not the security model</li>
          <li>Photographs belong in IndexedDB, never localStorage &mdash; and the store fails loudly rather than silently on quota</li>
        </ul>
      </div>
    </div>
    <div class="s-note">
      <strong>The audit log is a regulatory requirement, not a feature.</strong> It is listed here
      as something the backend must deliver on day one, rather than as something to add once there
      is demand for it.
    </div>
  </div>
</section>

<!-- ═════════════ 42 · THE HONESTY LEDGER ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">The honesty ledger</div>
  <h2 class="s-title s-title-sm" style="font-size:20pt">What is real, what is staged, and what is deliberately absent.</h2>
  <div class="s-body">
    <table class="s-table s-table-dense" style="font-size:8.1pt">
      <thead><tr><th style="width:26%">Capability</th><th style="width:15%">Status</th><th>What that means, precisely</th></tr></thead>
      <tbody>
        <tr><td>Patient app, all ten screens</td><td><span class="badge badge-safe">Real</span></td><td>Doses, streaks, adherence and medicine management, all computed on-device and offline</td></tr>
        <tr><td>Emergency triage gate</td><td><span class="badge badge-safe">Real</span></td><td>Deterministic, synchronous, four languages, runs before any network call exists</td></tr>
        <tr><td>Reminder link &rarr; console loop</td><td><span class="badge badge-safe">Real</span></td><td>End to end and live in front of an audience &mdash; joined through localStorage, not a server</td></tr>
        <tr><td>Clinic console, all ten pages</td><td><span class="badge badge-safe">Real</span></td><td>Every chart and table computed from the seed cohort by the product&rsquo;s own analytics</td></tr>
        <tr><td>AI explain / identify / ask</td><td><span class="badge badge-safe">Real</span></td><td>Live against the API with a user-supplied key. Mock mode is the default, so it cannot fail on network</td></tr>
        <tr><td>The cohort itself</td><td><span class="badge badge-caution">Fabricated</span></td><td>Eight invented patients, shaped exactly like the API response the backend will return</td></tr>
        <tr><td>Sending SMS, WhatsApp, email</td><td><span class="badge badge-alert">Needs a server</span></td><td>Credentials cannot live in a browser, and a scheduler must run while the phone is asleep</td></tr>
        <tr><td>Signed single-use tokens</td><td><span class="badge badge-alert">Needs a server</span></td><td>HMAC signing requires a key that never reaches the client</td></tr>
        <tr><td>Audit log, roles, retention</td><td><span class="badge badge-alert">Needs a server</span></td><td>Designed for throughout the console; cannot be enforced client-side</td></tr>
        <tr><td>Voice output in yo / ha / ig</td><td><span class="badge badge-alert">Not possible yet</span></td><td>No platform voices exist. Fixed phrases can ship as audio; free text needs server-side TTS</td></tr>
      </tbody>
    </table>
    <p class="s-kicker" style="text-transform:none;letter-spacing:0;font-weight:600;font-size:9.5pt;color:var(--ink-2)">
      Every staged control in the build says so when you click it. A demo that silently pretends to
      work is the one that gets caught in Q&amp;A.
    </p>
  </div>
</section>

<!-- ═════════════ 43 · WHAT THE BACKEND UNLOCKS ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">What comes next</div>
  <h2 class="s-title s-title-sm">One server turns a working demo into a deployable programme.</h2>
  <div class="s-body">
    <div class="s-cols s-3">
      <div class="s-card s-card-accent">
        <div class="s-kicker">Phase one &middot; the server</div>
        <h3>The single highest-leverage build</h3>
        <ul class="s-list">
          <li>A real scheduler and messaging providers</li>
          <li>Signed, expiring, single-use tokens</li>
          <li>Server-held API key &mdash; the proxy mode is already wired</li>
          <li>Audit log, roles, encryption, retention</li>
          <li>Server-side TTS for the languages the browser cannot speak</li>
        </ul>
        <p class="s-kicker" style="margin-top:auto">The client changes in two places: one MODE
        constant, one endpoint.</p>
      </div>
      <div class="s-card s-card-info">
        <div class="s-kicker">Phase two &middot; make it correct</div>
        <h3>The things a demo cannot settle</h3>
        <ul class="s-list s-list-info">
          <li>Native-speaker review of every string and every emergency term</li>
          <li>Clinician review of the safety prompts and interaction dataset</li>
          <li>A full-coverage webfont, retiring the split font stack</li>
          <li>Automated tests for the message-body rule and the triage gate</li>
        </ul>
      </div>
      <div class="s-card s-card-safe">
        <div class="s-kicker">Phase three &middot; prove it</div>
        <h3>The measurement the product exists to make</h3>
        <ul class="s-list s-list-safe">
          <li>A control arm, so the adherence lift stops being an assumption</li>
          <li>Does the patient&rsquo;s own language change the response rate?</li>
          <li>Does closing the loop change outcomes, or only visibility?</li>
          <li>A route from a detected signal into national pharmacovigilance</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 44 · WHY THIS MATTERS ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Why this matters</div>
  <h2 class="s-title">The patients know exactly what went wrong. Nobody has ever asked them cheaply enough.</h2>
  <div class="s-body">
    <div class="s-cols s-3">
      <div class="s-stat">
        <strong>~50%</strong>
        <span>of medicine for long-term illness is not taken as directed &mdash; a failure measured
        in hospital admissions, not inconvenience</span>
      </div>
      <div class="s-stat">
        <strong>500+</strong>
        <span>living languages in Nigeria alone. Prescriptions are written in one of them</span>
      </div>
      <div class="s-stat">
        <strong>~1 in 20</strong>
        <span>adverse drug reactions are ever formally reported &mdash; and almost none of those
        come from the patient</span>
      </div>
    </div>
    <div class="s-quote">
      Adherence data tells you whether someone took the medicine.
      This tells you why they stopped &mdash; and that is the only one of the two a clinic can act on.
    </div>
    <div class="s-note">
      Figures are widely cited public-health estimates, included here for scale rather than as
      measurements from this cohort. Everything this build measures is measured on eight fabricated
      patients, and every page in the console says so.
    </div>
  </div>
</section>

<!-- ═════════════ 45 · CLOSE ═════════════ -->
<section class="slide slide-cover">
  <div class="s-eyebrow">Walk it yourself</div>
  <h1 class="s-title">Open the outbox. Answer a reminder.<br>Watch it land on the dashboard.</h1>
  <p class="s-lede">
    No account, no key, no internet needed. Everything in this deck is running in the build
    it describes &mdash; including the charts on these slides.
  </p>
  <div class="cover-meta">
    <div><strong>/clinic/outbox.html</strong>pick a patient, open their link</div>
    <div><strong>/respond/</strong>answer it the way a patient would</div>
    <div><strong>/clinic/index.html</strong>the report is already there</div>
    <div><strong>/tools/diacritics.html</strong>re-run the font audit on this device</div>
  </div>
</section>

@include('decks._furniture')
</body>
</html>
