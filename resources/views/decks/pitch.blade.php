<!DOCTYPE html>
<!-- ═══════════════════════════════════════════════════════════════════
     MedBuddy — pitch deck. AI Build Fest 2026.

     17 slides, 16:9, one argument per slide. This is the deck for the
     room. technical-deck.html is the 45-slide design document that sits
     behind it as an appendix.
     ═══════════════════════════════════════════════════════════════════ -->
<html lang="en" data-skin="patient">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MedBuddy — pitch</title>
<link rel="stylesheet" href="/styles/fonts.css">
<link rel="stylesheet" href="/styles/tokens.css">
<link rel="stylesheet" href="/styles/app.css">
<link rel="stylesheet" href="/styles/chart.css">
<link rel="stylesheet" href="/styles/deck.css">
</head>
<body class="deck pitch">

<div class="deck-bar">
  <strong>MedBuddy — pitch</strong>
  <span style="opacity:.6" id="count"></span>
  <span style="flex:1"></span>
  <a href="/me">Patient app</a>
  <a href="/clinic/outbox">Outbox → demo</a>
  <a href="/clinic">Clinic console</a>
  <a href="/technical-deck">Technical deck</a>
  <button type="button" onclick="window.print()">Print / Save as PDF</button>
</div>

<!-- ═════════════ 1 · COVER ═════════════ -->
<section class="slide slide-cover">
  <div class="s-eyebrow">AI Build Fest 2026</div>
  <h1 class="s-title">A prescription you can&rsquo;t read<br>is a prescription you can&rsquo;t follow.</h1>
  <p class="s-lede">
    MedBuddy explains medicines in the patient&rsquo;s own language, keeps them on their doses
    offline &mdash; and gives the clinic the first feedback loop from the people actually
    taking the medicine.
  </p>
  <div class="cover-meta">
    <div><strong>4</strong>languages, including the crisis detector</div>
    <div><strong>3</strong>working surfaces, one live loop</div>
    <div><strong>0</strong>bars needed to take a dose or call for help</div>
    <div><strong>Live</strong>demo you can walk in 60 seconds</div>
  </div>
</section>

<!-- ═════════════ 2 · THE PROBLEM ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">The problem</div>
  <div class="s-body">
    <div class="s-cols s-2a" style="align-items:center">
      <div>
        <div class="s-huge s-huge-alt">50%</div>
        <h2 class="s-title s-title-sm" style="margin-top:6mm">of medicine for long-term illness
        is never taken as directed.</h2>
      </div>
      <div style="display:flex;flex-direction:column;gap:5mm">
        <div class="s-card s-card-alert">
          <h3>It is not a minor leak</h3>
          <p>It is failed TB courses, drug resistance, avoidable strokes and hospital
          admissions &mdash; paid for over and over by the same health system.</p>
        </div>
        <div class="s-card s-card-alert">
          <h3>And it is invisible</h3>
          <p>A clinic finds out months later, at the next appointment, from a patient trying
          to remember what happened.</p>
        </div>
      </div>
    </div>
    <p class="s-source">World Health Organization estimate for adherence to long-term therapy.
    Lower again in low- and middle-income settings.</p>
  </div>
</section>

<!-- ═════════════ 3 · WHY IT FAILS ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Why it fails</div>
  <h2 class="s-title s-title-sm">Three reasons &mdash; and not one of them is that the patient
  did not care.</h2>
  <div class="s-body">
    <div class="s-cols s-3">
      <div class="s-card s-card-alert">
        <div class="s-huge" style="font-size:52pt;color:var(--ink-3)">01</div>
        <h3>They cannot read it</h3>
        <p>&ldquo;1 tab PO BD PRN&rdquo;, written by hand, in English. Nigeria has more than
        500 living languages. The label is a cipher.</p>
      </div>
      <div class="s-card s-card-alert">
        <div class="s-huge" style="font-size:52pt;color:var(--ink-3)">02</div>
        <h3>They forget the evening one</h3>
        <p>Nobody forgets the morning dose. Adherence is a routine problem, and routines break
        in a specific, measurable place.</p>
      </div>
      <div class="s-card s-card-alert">
        <div class="s-huge" style="font-size:52pt;color:var(--ink-3)">03</div>
        <h3>Something happened and they told nobody</h3>
        <p>A rash. Dizziness. They stopped. The clinic records a non-adherent patient and never
        learns the reason.</p>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 4 · THE BLIND SPOT ═════════════ -->
<section class="slide slide-dark">
  <div class="s-eyebrow">The blind spot</div>
  <h2 class="s-title">The people who know exactly what went wrong<br>have no cheap way to say so.</h2>
  <p class="s-lede">Every adherence product ever built asks the patient to install an app,
  make an account, remember a password, read English and type a sentence. Each step drops a
  slice of the population &mdash; and never a random slice.</p>
</section>

<!-- ═════════════ 5 · THE SOLUTION ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">MedBuddy</div>
  <h2 class="s-title s-title-sm">Ask them in their own language. Make answering cost one tap.</h2>
  <div class="s-body">
    <div class="s-cols s-3">
      <div class="s-card s-card-accent">
        <div class="s-kicker">For the patient</div>
        <h3>An app that explains the label</h3>
        <p>Point the camera at a prescription &mdash; even handwritten &mdash; and hear what it
        means in Yor&ugrave;b&aacute;, Hausa, Igbo or English. Doses, streaks and SOS all work
        with no signal.</p>
      </div>
      <div class="s-card s-card-info">
        <div class="s-kicker">For the reply</div>
        <h3>A reminder anyone can answer</h3>
        <p>Arrives on WhatsApp or SMS. Opens with no login and no install. Four taps on
        pictures &mdash; no reading, no typing, no smartphone required.</p>
      </div>
      <div class="s-card s-card-safe">
        <div class="s-kicker">For the clinic</div>
        <h3>A console that finally has data</h3>
        <p>Who is slipping, which drug is causing complaints, which dose window leaks &mdash;
        from patients, in near real time, instead of at the next appointment.</p>
      </div>
    </div>
    <div class="s-band s-band-info">
      <div>
        <b>The loop between them is the product.</b>
        A pill reminder alone is a commodity. A dashboard alone has nothing in it. The value is
        the path: a question that costs nothing to answer, feeding a view no clinic has today.
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 6 · THE LOOP ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">How it works</div>
  <h2 class="s-title s-title-sm">From a dose window to a clinical decision, in five steps.</h2>
  <div class="s-body">
    <div class="s-flow">
      <div class="s-flow-step">
        <div class="s-flow-n">01</div>
        <h4>A message goes out</h4>
        <p>&ldquo;Did you take your morning tablet?&rdquo; No drug name. No condition. Ever.</p>
      </div>
      <div class="s-flow-step">
        <div class="s-flow-n">02</div>
        <h4>The link just opens</h4>
        <p>No login, no install. In the patient&rsquo;s own language, on any phone.</p>
      </div>
      <div class="s-flow-step">
        <div class="s-flow-n">03</div>
        <h4>They tap a picture</h4>
        <p>Yes or no. Then how they feel, from a grid of symbols. Reading optional.</p>
      </div>
      <div class="s-flow-step">
        <div class="s-flow-n">04</div>
        <h4>It lands on the desk</h4>
        <p>Seconds later, in the feed, the patient record, and the safety table.</p>
      </div>
      <div class="s-flow-step">
        <div class="s-flow-n">05</div>
        <h4>Someone acts</h4>
        <p>A call before the TB course fails. A dose moved before the patient quits.</p>
      </div>
    </div>
    <div class="s-band s-band-safe">
      <div>
        <b>All five steps run today.</b> Open the outbox, pick a patient, answer their reminder,
        and watch the report appear on the dashboard. We will do it live.
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 7 · WHY PEOPLE ANSWER ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">The insight</div>
  <h2 class="s-title s-title-sm">A reply is only cheap if you can read the question.</h2>
  <div class="s-body">
    <div class="s-cols s-2">
      <div class="s-card s-card-alert">
        <h3>What every other product asks for</h3>
        <ul class="s-list s-list-alert">
          <li>Download an app</li>
          <li>Create an account</li>
          <li>Remember a password</li>
          <li>Read English</li>
          <li>Type a sentence</li>
        </ul>
        <p style="margin-top:auto">Five steps. Each one loses the least literate and least
        connected users first &mdash; the exact people with the worst outcomes.</p>
      </div>
      <div class="s-card s-card-safe">
        <h3>What MedBuddy asks for</h3>
        <ul class="s-list s-list-safe">
          <li>Tap the link in the message</li>
          <li>Tap yes or no</li>
          <li>Tap how you feel</li>
        </ul>
        <p style="margin-top:auto">Three taps, no words required, on a phone they already own.
        The voice note is a bonus on top &mdash; never the thing the system depends on.</p>
      </div>
    </div>
    <div class="s-quote">The responses are the dataset. Every step of friction is data you
    will never get.</div>
  </div>
</section>

<!-- ═════════════ 8 · PATIENT APP ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Product &middot; the patient app</div>
  <h2 class="s-title s-title-sm">Point your camera at the thing you cannot read.</h2>
  <div class="s-body">
    <div class="s-cols s-4">
      <div class="s-card s-card-accent">
        <div class="s-kicker">Scan</div>
        <h3>Explains any label</h3>
        <p>Printed, photocopied, or a doctor&rsquo;s handwriting. What it is for, how to take it,
        what to watch for &mdash; in plain words, in your language.</p>
      </div>
      <div class="s-card s-card-accent">
        <div class="s-kicker">Today</div>
        <h3>Never miss a dose</h3>
        <p>Doses, a progress ring and a streak. Every calculation happens on the phone, so it
        works with no signal at all.</p>
      </div>
      <div class="s-card s-card-accent">
        <div class="s-kicker">Ask</div>
        <h3>Answers real questions</h3>
        <p>&ldquo;Can I take this with food?&rdquo; in four languages &mdash; and a hard refusal
        on anything that amounts to changing a dose.</p>
      </div>
      <div class="s-card s-card-alert">
        <div class="s-kicker">SOS</div>
        <h3>Help in one tap</h3>
        <p>On every screen. Detects an emergency in four languages before any network call, and
        opens instantly offline.</p>
      </div>
    </div>
    <div class="s-band">
      <div>
        <b>Installs like an app, ships like a web page.</b> No app store, no 40MB download, no
        update nobody installs. It works on a five-year-old Android.
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 9 · THE REMINDER LINK ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Product &middot; the reminder</div>
  <h2 class="s-title s-title-sm">The whole reply, for someone who cannot read a word of it.</h2>
  <div class="s-body">
    <div class="s-cols s-2a">
      <div style="display:flex;flex-direction:column;gap:4mm;min-height:0">
        <div class="s-code">Hello Amina, did you take your <span class="hi">evening tablet</span>?
Tap to confirm and tell us how you feel.

https://medbuddy.app/r/<span class="dim">cDR8MjE6MDA</span></div>
        <div class="s-band s-band-alert">
          <div>
            <b>Notice what is missing</b>
            No drug. No condition. No dose. A lock-screen preview naming an HIV or TB medicine
            outs someone&rsquo;s diagnosis to whoever is holding the phone &mdash; and they leave
            the programme.
          </div>
        </div>
      </div>
      <div class="s-card s-card-safe">
        <h3>Four screens, then done</h3>
        <ul class="s-list s-list-safe">
          <li><strong>Did you take it?</strong> A tick and a cross</li>
          <li><strong>How do you feel?</strong> Seven large symbols</li>
          <li><strong>How bad is it?</strong> Only if something is wrong</li>
          <li><strong>Say more?</strong> Optional voice note, reviewed by a nurse</li>
        </ul>
        <p style="margin-top:auto">Structured, comparable data from a patient who has never
        typed a message &mdash; and it arrives in seconds, not months.</p>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 10 · CLINIC CONSOLE ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Product &middot; the clinic console</div>
  <h2 class="s-title s-title-sm">Ten pages that answer &ldquo;who needs me this morning?&rdquo;</h2>
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
      <div class="s-note"><strong>Who is slipping</strong> &mdash; flagged by low adherence,
      days of silence, or a symptom just reported. Not by someone remembering to look.</div>
      <div class="s-note"><strong>Where doses are lost</strong> &mdash; by dose window, by drug,
      by condition, by language. The evening dose is where programmes leak.</div>
      <div class="s-note"><strong>What patients are saying</strong> &mdash; a severity-sorted
      queue, with voice notes flagged for a nurse where transcription cannot cope.</div>
    </div>
  </div>
</section>

<!-- ═════════════ 11 · THE WEDGE ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">The part nobody else has</div>
  <h2 class="s-title s-title-sm">Side-effect reporting from patients &mdash; a layer that
  barely exists anywhere.</h2>
  <div class="s-body">
    <div class="s-cols s-2b">
      <div style="display:flex;flex-direction:column;gap:5mm;justify-content:center">
        <div class="s-stat"><strong>~1 in 20</strong>
          <span>adverse drug reactions are ever formally reported</span></div>
        <div class="s-stat"><strong>Almost none</strong>
          <span>of those reports come from the patient rather than a clinician</span></div>
      </div>
      <div style="display:flex;flex-direction:column;gap:4mm;min-height:0">
        <div class="s-card s-card-accent">
          <h3>The rash that never reached a clinic</h3>
          <p>The dizziness someone quietly decided to live with. The dose they stopped without
          telling anyone. That whole layer is invisible to every pharmacovigilance system.</p>
        </div>
        <div class="s-card s-card-safe">
          <h3>A reminder they can answer captures it by default</h3>
          <p>Grouped by drug and symptom, rated against how many patients are on that drug
          &mdash; the moment it happens, in the language it happened in.</p>
        </div>
        <div class="s-band s-band-info">
          <div><b>Adherence tells you whether they took it. This tells you why they stopped
          &mdash; the only one of the two a clinic can act on.</b></div>
        </div>
      </div>
    </div>
    <p class="s-source">Adverse-drug-reaction under-reporting is estimated at around 94% across published studies.</p>
  </div>
</section>

<!-- ═════════════ 12 · THE AI ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Where the AI is</div>
  <h2 class="s-title s-title-sm">The model explains. It never decides whether something is an
  emergency.</h2>
  <div class="s-body">
    <div class="s-cols s-2">
      <div class="s-card s-card-info">
        <h3>AI does the work</h3>
        <ul class="s-list s-list-info">
          <li>Reads a label, a pharmacy note, or handwriting</li>
          <li>Identifies a pill &mdash; with a confidence the interface enforces</li>
          <li>Answers medication questions in four languages</li>
          <li>Writes a summary for a caregiver and one for a doctor</li>
        </ul>
        <p style="margin-top:auto">All of it is <em>explanation</em>. Being wrong causes
        confusion, and the interface is built to catch it.</p>
      </div>
      <div class="s-card s-card-alert">
        <h3>Deterministic code does the work</h3>
        <ul class="s-list s-list-alert">
          <li>Emergency detection &mdash; before any model call, in all four languages</li>
          <li>Drug interaction severity &mdash; a curated dataset, never model recall</li>
          <li>Doses, reminders, streaks, adherence maths</li>
          <li>SOS contacts and emergency dialling</li>
        </ul>
        <p style="margin-top:auto">All of it is a <em>decision</em>. Being wrong causes harm,
        and no confidence score makes that acceptable.</p>
      </div>
    </div>
    <div class="s-quote">If being wrong could hurt someone, a model does not get to decide it.</div>
  </div>
</section>

<!-- ═════════════ 13 · DEFENSIBILITY ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Why this is hard to copy</div>
  <h2 class="s-title s-title-sm">Four things a competitor cannot add in a sprint.</h2>
  <div class="s-body">
    <div class="s-cols s-4">
      <div class="s-card s-card-accent">
        <h3>Language, all the way down</h3>
        <p>Not a translated menu. The reminder, the reply, the answers <em>and the emergency
        detector</em> all run in four languages &mdash; including the one the patient
        code-switches into.</p>
      </div>
      <div class="s-card s-card-accent">
        <h3>Privacy as a growth lever</h3>
        <p>No drug name ever leaves in a message. That is not compliance theatre &mdash; it is
        the reason patients with a stigmatised diagnosis stay enrolled instead of opting out.</p>
      </div>
      <div class="s-card s-card-accent">
        <h3>Offline as a default</h3>
        <p>Doses, reminders, the medicine list and the SOS path all work at zero bars. Built for
        the connection patients actually have, not the one a demo has.</p>
      </div>
      <div class="s-card s-card-accent">
        <h3>The data nobody else collects</h3>
        <p>Every answered reminder compounds into a patient-reported safety record. That dataset
        gets more valuable, and harder to replicate, every single day.</p>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 14 · IT WORKS NOW ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">This is not a mockup</div>
  <h2 class="s-title s-title-sm">Everything in this deck is running. Walk it in 60 seconds.</h2>
  <div class="s-body">
    <div class="s-cols s-3">
      <div class="s-card s-card-safe">
        <div class="s-flow-n">STEP 1</div>
        <h3>Open the outbox</h3>
        <p>Every reminder scheduled today, grouped by dose window, with the exact message body
        and a live link. Pick any patient.</p>
      </div>
      <div class="s-card s-card-safe">
        <div class="s-flow-n">STEP 2</div>
        <h3>Answer it as the patient</h3>
        <p>The page opens in their language. Say you missed the dose, tap a symptom, rate it,
        send. No login anywhere.</p>
      </div>
      <div class="s-card s-card-safe">
        <div class="s-flow-n">STEP 3</div>
        <h3>Watch it land</h3>
        <p>Go to the console. The report is in the feed, on their record, in the complaints
        queue and in the safety table.</p>
      </div>
    </div>
    <div class="s-cols s-4" style="flex:0 0 auto">
      <div class="s-stat"><strong>3</strong><span>working surfaces, all live</span></div>
      <div class="s-stat"><strong>20</strong><span>real screens, not mockups</span></div>
      <div class="s-stat"><strong>4</strong><span>languages, end to end</span></div>
      <div class="s-stat"><strong>0</strong><span>dependencies &mdash; nothing to break on stage</span></div>
    </div>
  </div>
</section>

<!-- ═════════════ 15 · MARKET AND MODEL ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Who pays, and why</div>
  <h2 class="s-title s-title-sm">Per-clinic subscription, priced against one avoided failure.</h2>
  <div class="s-body">
    <div class="s-cols s-2a">
      <div class="s-card s-card-accent">
        <h3>The model</h3>
        <p>Clinics and hospital outpatient programmes subscribe per enrolled patient per month.
        One number, no per-message billing, no hardware.</p>
        <table class="s-table" style="margin-top:2mm">
          <tbody>
            <tr><td><strong>Buyer</strong></td><td>Clinic, hospital outpatient dept, chronic-disease programme</td></tr>
            <tr><td><strong>Unit</strong></td><td>Enrolled patient, per month</td></tr>
            <tr><td><strong>Anchor</strong></td><td>A single failed TB course or avoidable admission costs a multiple of a clinic&rsquo;s annual subscription</td></tr>
            <tr><td><strong>Expansion</strong></td><td>Donor and government adherence programmes buy the same product at cohort scale</td></tr>
          </tbody>
        </table>
      </div>
      <div style="display:flex;flex-direction:column;gap:4mm;min-height:0">
        <div class="s-card s-card-info">
          <h3>Where the volume is</h3>
          <ul class="s-list s-list-info">
            <li>Around <strong>2 million</strong> people living with HIV in Nigeria</li>
            <li>A <strong>top-eight</strong> country worldwide for TB burden</li>
            <li>Roughly <strong>one in three</strong> adults with hypertension</li>
            <li><strong>500+</strong> languages, and one on the label</li>
          </ul>
        </div>
        <div class="s-note">
          <strong>Public health estimates, cited for scale.</strong> Pricing is a working model.
          The first pilot exists to test willingness to pay before we defend a number.
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 16 · WHERE WE ARE ═════════════ -->
<section class="slide">
  <div class="s-eyebrow">Where we are, and what is next</div>
  <h2 class="s-title s-title-sm">A working product, and one build between it and a pilot.</h2>
  <div class="s-body">
    <div class="s-cols s-3">
      <div class="s-card s-card-safe">
        <div class="s-kicker">Done &mdash; today</div>
        <h3>The whole product loop</h3>
        <ul class="s-list s-list-safe">
          <li>Patient app, reminder page and console, all live</li>
          <li>Four languages, emergency detection included</li>
          <li>Offline-first, installable, zero dependencies</li>
          <li>AI explanation and Q&amp;A, with safety rails</li>
        </ul>
      </div>
      <div class="s-card s-card-accent">
        <div class="s-kicker">Next &mdash; one build</div>
        <h3>The server</h3>
        <ul class="s-list">
          <li>WhatsApp and SMS sending, on a schedule</li>
          <li>Signed, single-use reminder links</li>
          <li>Audit log, roles and encryption</li>
          <li>Voice output in all four languages</li>
        </ul>
      </div>
      <div class="s-card s-card-info">
        <div class="s-kicker">Then &mdash; prove it</div>
        <h3>A pilot with a control arm</h3>
        <ul class="s-list s-list-info">
          <li>Native-speaker and clinician sign-off on every string</li>
          <li>One clinic, one condition, against a control arm</li>
          <li>The real question: does asking in your own language change whether people answer?</li>
        </ul>
      </div>
    </div>
    <div class="s-band">
      <div>
        <b>Today&rsquo;s cohort is fabricated demo data.</b> We say so on every page of the
        console, because a product that hides what it has not proven is not one a clinic
        should trust.
      </div>
    </div>
  </div>
</section>

<!-- ═════════════ 17 · CLOSE ═════════════ -->
<section class="slide slide-cover">
  <div class="s-eyebrow">MedBuddy</div>
  <h1 class="s-title">The medicine already works.<br>We fixed the instructions.</h1>
  <p class="s-lede">
    Explained in a language you speak. Answered with one tap. Landing on a clinician&rsquo;s
    screen while there is still time to do something about it.
  </p>
  <div class="cover-meta">
    <div><strong>Try it</strong>clinic/outbox.html &rarr; open a link &rarr; answer it</div>
    <div><strong>Then</strong>clinic/index.html &mdash; your report is already there</div>
    <div><strong>Detail</strong>technical-deck.html &mdash; 45 slides on how it is built</div>
  </div>
</section>

@include('decks._furniture')
</body>
</html>
