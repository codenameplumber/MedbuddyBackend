# MedBuddy — Laravel platform

Medication adherence and health literacy, in the patient's own language.
Laravel 13 · Livewire 4 · MySQL 9 · Claude (with a mock mode that needs no key).

## Run it

```bash
php artisan serve                 # http://127.0.0.1:8000
php artisan queue:work            # required — reminders are queued
php artisan schedule:work         # dev equivalent of the cron entry
```

### Every public surface

| URL | What |
|---|---|
| `/` | Front page, charts reading the live database |
| `/register` | Sign-up — magic link for patients, organisation for clinics |
| `/login` · `/me/login` | Clinic and patient sign-in |
| `/pitch` · `/technical-deck` | The decks, charts rendered server-side from the real cohort |
| `/tools/diacritics.html` | Verifies the yo/ig/ha characters render on the demo device |
| `/offline` | What the installed app shows with no connection |

The patient app is installable: open `/me` on a phone and add it to the home screen. It keeps
its styles and fonts offline and falls back to `/offline` rather than the browser's error page.
Ticking off a dose still needs a connection, and the app says so rather than failing silently.

`/` is the public front page. Staff sign in at `/login`; patients get a magic link at
`/me/login`. Both audiences can sign themselves up at `/register`.

The seeder creates 12 staff accounts in one organisation, all with the password
`password`. One of each role:

| Email | Role | May |
|---|---|---|
| `admin@ikejagh.ng` | admin | everything, plus staff and deletion |
| `a.adeyemi@ikejagh.ng` | physician | prescribe, enrol, manage consent |
| `n.balogun@ikejagh.ng` | pharmacist | enrol and manage consent, not prescribe |
| `c.eze@ikejagh.ng` | nurse | read, triage complaints, send reminders |

Patients sign in with a link rather than a password. Mint one without a mailbox:

```bash
php artisan tinker --execute="echo URL::temporarySignedRoute(
  'me.auth', now()->addHour(), ['patient' => 3]);"
```

A signed link is bound to the host it was generated for, so `APP_URL` has to match the
port you are serving on or every link returns 403.

## See the loop close

This is the demo worth showing — every step is real, none of it is mocked.

1. **Clinic → Outbox → "Dispatch due reminders now"**
2. `MAIL_MAILER=log`, so the email lands in `storage/logs/laravel.log`
3. Find the signed link (`grep -o 'http://127.0.0.1:8000/r/[^"]*' storage/logs/laravel.log`)
4. Open it — it renders in that patient's language, no login
5. Answer it
6. **Clinic → Overview** — the report is in the feed, on the patient record, and in the safety signals

Mail goes over real SMTP by default, pointed at the Mailtrap sandbox. Paste
`MAIL_USERNAME` and `MAIL_PASSWORD` from Mailtrap → Inboxes → SMTP Settings, then prove
the whole path with a real send:

```bash
php artisan medbuddy:mail-test you@example.com
```

That sends an actual dose reminder and an actual sign-in link — not a "hello world" — so
the Blade views, the signed links and the translations are exercised too. To read messages
locally instead, set `MAIL_MAILER=log` and they land in `storage/logs/laravel.log`.

## The three contracts

**Privacy.** A reminder names the *dose window* — never the condition, the drug, or the dose.
An email sits in an inbox and its subject shows in a lock-screen preview; a message naming an
antiretroviral can disclose a diagnosis to whoever is holding the phone.
`ReminderComposer::assertSafe()` enforces it on every send and `PrivacyContractTest` fails the
build if it is ever broken.

**Triage.** Emergency detection is deterministic and runs *before* any model call, in all four
languages. `TriageService` is the server-side mirror; the patient PWA keeps its own JS copy that
runs on-device at zero latency and zero bars. A model never decides whether something is an
emergency.

**Confidence.** The model supplies a confidence rating; `AiService::displayRule()` decides what
the interface is *allowed* to show. A medium-confidence pill identification can never render as
one certain answer. Misreading a doctor's handwriting is the highest-risk failure in the
product, so it is handled structurally rather than politely.

## Layout

```
app/
  Services/       TriageService · ScheduleService · AdherenceService
                  AnalyticsService · ReminderComposer · AiService
  Livewire/Clinic/  10 full-page components
  Jobs/           DispatchDueReminders     ← the scheduler entry point
  Notifications/  DoseReminder             ← via() is where SMS gets added
  Support/Chart   server-side SVG charts (survive Livewire DOM diffs)
lang/{en,yo,ha,ig}/   reminder + respond strings
```

Doses are **derived** from `prescriptions.times`; only deviations get a `dose_events` row.
That keeps adherence a pure function over events rather than a second source of truth.

## Adding SMS later

One line:

```php
// app/Notifications/DoseReminder.php
public function via($notifiable): array
{
    return ['mail'];              // today
    // return ['mail', 'vonage']; // + a provider key. No refactor.
}
```

That is why email-only is a simplification rather than technical debt.

## AI

`MEDBUDDY_AI_MODE=mock` (default) returns canned responses — no key, no network, every screen
demoable, and the edge cases (low confidence, refusal, cannot-identify) reachable on demand.

Set `ANTHROPIC_API_KEY` and `MEDBUDDY_AI_MODE=live` for real calls. The key lives in `.env`
and never reaches a browser — the single biggest security fix over the front-end-only
prototype, and `AiLiveModeTest` asserts no Blade view can read it.

```
MEDBUDDY_AI_MODE=live
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_MODEL=claude-opus-5
ANTHROPIC_EFFORT=medium        # low | medium | high | xhigh | max
```

`AiService` is the only class that talks to a model. It calls Claude through the official
`anthropic-ai/sdk` with structured outputs, so the response shape is guaranteed rather than
hoped for, and every failure — no key, rate limit, refusal, unparseable body — degrades to
a canned answer instead of showing a patient an error.

Live mode sends patient-adjacent text to servers outside Nigeria. Under the NDPA that is a
question to answer before real patients, not a technical detail.

## Production

```
* * * * * cd /path/to/platform && php artisan schedule:run >> /dev/null 2>&1
```

plus a queue worker under supervisor. Shared hosting will not do; budget for a VPS.

## Tests

```bash
php artisan test              # 181 tests, 546 assertions
php artisan medbuddy:doctor   # what is wired, what is dormant, what will not fire
php artisan medbuddy:i18n     # how much of the patient app exists in each language
```

Needs a `medbuddy_test` database (`phpunit.xml` points at it).

## Known gaps — stated, not hidden

- **61 of 158 interface strings have no yo/ha/ig** and fall back to English in front of someone
  who chose not to read English. `php artisan medbuddy:i18n --missing=yo` prints the list. The
  55 the prototype shipped are a first pass too — every `lang/{yo,ha,ig}` file needs a native
  speaker, and the `TriageService` term lists need a clinician. A missed term is a missed
  emergency.
- **Email reaches caregivers better than patients.** The hardest-to-reach users need SMS. Email
  is the right *first* channel, not the right *only* channel.
- **No speech-to-text for Yorùbá, Hausa or Igbo.** Voice notes in those languages are flagged for
  human review. No amount of Laravel fixes this.
- **The outcomes "lift" is against an assumed baseline.** Nobody tracked these patients before
  they enrolled, and everyone self-selected. It is a hypothesis, not a result.
- **No offline story.** The prototype was a PWA; Livewire needs a connection for every
  interaction, including ticking off a dose. The biggest functional regression from the
  prototype.
- **The AI endpoints are not rate-limited per patient.** `/me/ask` and `/me/scan` call a paid
  API on a session that costs nothing to obtain.

## Performance

Every adherence number is a fold over `ScheduleService::dosesForDay()`. That method used to
run one query per patient per day, which was invisible at 8 seeded patients and fatal at 120:
the cohort fold was ~4,500 queries and `/clinic` took 89 seconds.

It now reads from a per-patient cache of dose history, loaded in one query and shared for the
life of the request. Same page, same database, same machine: **89 s → 4.5 s**, and `cohort()`
went from ~4,500 queries to 5.

Caching a read has exactly one failure mode — a stale answer after a write — so the cache is
dropped on any `DoseEvent` write, and `SchedulePerformanceTest` exists to prove it. If you add
a caller that folds over the whole cohort, call `ScheduleService::warm()` first and eager-load
`prescriptions`.
