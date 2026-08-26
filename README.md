# MedBuddy — Laravel platform

**Competition submission:** https://claude.ai/code/artifact/df8ae83b-243b-492d-90ce-8b91b9c1a7ff
· [Demo access & logins](https://claude.ai/code/artifact/956cf05e-02a1-46b2-9486-f439c5b8b18f)
· [Technical overview](https://claude.ai/code/artifact/e2bc7582-8b90-49c3-9dac-5015349e812c)
· [Deploying to Laravel Cloud](DEPLOY.md)

Medication adherence and health literacy, in the patient's own language.
Laravel 13 · Livewire 4 · MySQL 9 · OpenAI, with Claude switchable behind the same interface (and a mock mode that needs no key).

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

## The database

MySQL, database `medbuddy`, on `127.0.0.1:3306` as `root` with no password.

```bash
php artisan migrate:fresh --seed     # rebuilds the whole cohort, deterministically
```

| Table | Rows | What is in it |
|---|---:|---|
| `patients` | 120 | Igbo 46 · Yorùbá 41 · Hausa 24 · English 9, across 10 long-term conditions |
| `prescriptions` | 183 | Real regimens — TB triple therapy, ARVs, metformin twice daily |
| `dose_events` | 10,487 | 60 days of history; evening doses missed more often, on purpose |
| `reminders` | 1,281 | A week of delivery history: 1,281 sent → 1,095 opened → 797 answered |
| `patient_reports` | 205 | 33 carry a voice note, clustered in the non-English cohort |
| `consents` | 240 | Messaging and research recorded separately |
| `audits` | 180 | A week of staff activity |
| `users` | 12 | 2 admins · 3 physicians · 2 pharmacists · 5 nurses |

> **If phpMyAdmin shows no `medbuddy` database, you are looking at the wrong server.**
> WAMP ships two engines and runs both: **MySQL 9 on port 3306** (where this app lives)
> and **MariaDB 11 on port 3307** (where it does not). phpMyAdmin often defaults to
> MariaDB. Pick the MySQL server from the dropdown on the phpMyAdmin login page.
>
> To check from the command line without phpMyAdmin at all:
> ```bash
> php artisan tinker --execute="echo App\Models\Patient::count();"   # 120
> ```

## Logins

Every staff account uses the password `password`. It is pre-filled on the sign-in form.
Roles are enforced, not decorative — a nurse who opens a prescribing screen gets a 403,
not a hidden button.

| Email | Name | Role | May |
|---|---|---|---|
| `admin@ikejagh.ng` | K. Adesina | admin | Everything, plus staff and deleting a patient |
| `m.sanni@ikejagh.ng` | M. Sanni | admin | Everything, plus staff and deleting a patient |
| `a.adeyemi@ikejagh.ng` | Dr. A. Adeyemi | physician | Prescribe, enrol, manage consent |
| `h.ibrahim@ikejagh.ng` | Dr. H. Ibrahim | physician | Prescribe, enrol, manage consent |
| `c.nwachukwu@ikejagh.ng` | Dr. C. Nwachukwu | physician | Prescribe, enrol, manage consent |
| `n.balogun@ikejagh.ng` | N. Balogun | pharmacist | Enrol and manage consent — **not** prescribe |
| `s.okeke@ikejagh.ng` | S. Okeke | pharmacist | Enrol and manage consent — **not** prescribe |
| `c.eze@ikejagh.ng` | C. Eze | nurse | Read, triage complaints, send reminders |
| `b.lawal@ikejagh.ng` | B. Lawal | nurse | Read, triage complaints, send reminders |
| `f.ogunleye@ikejagh.ng` | F. Ogunleye | nurse | Read, triage complaints, send reminders |
| `r.danjuma@ikejagh.ng` | R. Danjuma | nurse | Read, triage complaints, send reminders |
| `t.okonkwo@ikejagh.ng` | T. Okonkwo | nurse | Read, triage complaints, send reminders |

### The patient app — one tap, no password

**Open `/me/login` and tap a name.** The sign-in page lists the seeded cohort, two people
per language, and one tap signs you in as them with their real dose history. Staff can also
reach it from **Patient app** at the bottom of the clinic sidebar, or **Open patient view**
on any patient's record.

That panel is a demo affordance behind `MEDBUDDY_DEMO_SIGNIN`. It is a deliberate
authentication bypass, so it is guarded twice: off unless the flag is set, and it only ever
offers patients on the fabricated `@patient.medbuddy.test` domain — it refuses anything
else, so it cannot reach a real record even if left on. `medbuddy:doctor` reports loudly
while it is enabled. **Turn it off before a real patient is enrolled.**

### How a real patient signs in

The link in the email *is* the credential — scoped to one dose and expiring with its
window. Enter an address at `/me/login` and read the link out of Mailtrap, or mint one
directly:

```bash
php artisan tinker --execute="echo URL::temporarySignedRoute(
  'me.auth', now()->addHour(), ['patient' => 3]);"
```

| id | Patient | Sign in with | Language | Condition | 7-day |
|---:|---|---|---|---|---:|
| 3 | Dupe Fashola | `dupe.fashola@patient.medbuddy.test` | Yorùbá | Hypertension | 100% |
| 7 | Bisi Aderinto | `bisi.aderinto@patient.medbuddy.test` | Yorùbá | Heart failure | 100% |
| 1 | Amina Garba | `amina.garba@patient.medbuddy.test` | Hausa | Epilepsy | 79% |
| 2 | Zainab Dikko | `zainab.dikko@patient.medbuddy.test` | Hausa | Peptic ulcer | 86% |
| 4 | Ifeoma Okafor | `ifeoma.okafor@patient.medbuddy.test` | Igbo | Asthma | 86% |
| 18 | Oluchi Onyeka | `oluchi.onyeka@patient.medbuddy.test` | Igbo | HIV | 43% |
| 9 | Tunde Ogunleye | `tunde.ogunleye@patient.medbuddy.test` | Yorùbá | Type 2 diabetes | 57% |
| 16 | Ifeoma Umar | `ifeoma.umar@patient.medbuddy.test` | English | HIV | 79% |

Open **Oluchi Onyeka** or **Tunde Ogunleye** first — they are why the console has a
"Needs attention" list. A signed link is bound to the host it was minted for, so `APP_URL`
must match the port you are actually serving on or every link returns 403.

## See the loop close

This is the demo worth showing — every step is real, none of it is mocked.

1. **Clinic → Outbox → "Dispatch due reminders now"**
2. `MAIL_MAILER=log`, so the email lands in `storage/logs/laravel.log`
3. Find the signed link (`grep -o 'http://127.0.0.1:8000/r/[^"]*' storage/logs/laravel.log`)
4. Open it — it renders in that patient's language, no login
5. Answer it
6. **Clinic → Overview** — the report is in the feed, on the patient record, and in the safety signals

Mail goes over real SMTP. Two providers are configured in `.env`, one active at a time —
swapping is a comment, not a code change.

- **Mailtrap sandbox (active).** Real SMTP, but every message is trapped in one inbox
  rather than reaching a patient. Correct while the cohort is 120 fabricated people on a
  `.test` domain.
- **Resend (commented out).** For when mail should really leave the building. Two options
  in `.env`: SMTP (`smtp.resend.com`, no extra package) or the API driver
  (`composer require resend/resend-laravel`). The from-domain has to be verified in Resend
  first — a `.test` address will be rejected.

Prove whichever is active with a real send:

```bash
php artisan config:clear
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

**OpenAI is the active provider.** Claude stays wired behind the same
`App\Services\Ai\Provider` interface, so switching is one env var — no screen, and no test
of a screen, knows which one answered. `AiService` remains the only class in the codebase
that talks to a model.

```ini
MEDBUDDY_AI_PROVIDER=openai      # openai | anthropic
MEDBUDDY_AI_MODE=live            # mock | live

OPENAI_API_KEY=sk-...            # GPT, via openai-php/client
OPENAI_MODEL=gpt-4o-mini
OPENAI_MAX_TOKENS=1600

ANTHROPIC_API_KEY=               # Claude, via anthropic-ai/sdk — not currently selected
ANTHROPIC_MODEL=claude-opus-5
```

Paste `OPENAI_API_KEY`, run `php artisan config:clear`, and the five AI screens go live.
Both providers use structured outputs, so the response shape is guaranteed rather than hoped
for — which is why the UI can rely on a confidence field existing at all.

`MEDBUDDY_AI_MODE=mock` (or live mode with no key for the selected provider) returns canned
responses — no key, no network, every screen demoable, and the edge cases (low confidence,
refusal, cannot-identify) reachable on demand. Every failure path — no key, rate limit,
refusal, unreachable network, unparseable body — degrades to that same canned answer rather
than showing a patient an error.

The key lives in `.env` and never reaches a browser; `AiLiveModeTest` asserts that no Blade
view can read either one. Check which is live with:

```bash
php artisan medbuddy:doctor      # names the provider, the model, and whether a key is present
```

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
