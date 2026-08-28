# Deploying MedBuddy to Laravel Cloud

Two background processes carry the whole product, and neither runs by default:

| | What breaks without it |
|---|---|
| **Scheduler** (`schedule:run` every minute) | No dose window is ever noticed. No reminder is ever queued. |
| **Queue worker** | Reminders are queued notifications. Without a worker they sit in the queue forever and no patient hears anything. |

Everything else — migrations, the app itself — Cloud handles. These two you turn on.

---

## 1. The scheduler

Open the environment → click the **App compute cluster** on the infrastructure canvas →
enable the **Scheduler** toggle → **save and redeploy**.

Cloud then invokes `schedule:run` every minute. That is the whole setup; there is no crontab.

If you would rather keep background work off the compute serving web traffic, enable the
same toggle on a **Worker cluster** instead.

> **Already handled in code:** `routes/console.php` uses `->onOneServer()`, which is
> required once the App cluster runs more than one replica — otherwise every replica runs
> the dispatcher on the same minute. It needs a shared cache store, so keep
> `CACHE_STORE=database` (or point it at Valkey/Redis). The file driver will not do.

> **Scale to Zero:** a sleeping environment wakes automatically to run scheduled tasks, so
> reminders still fire. Cloud works out when to wake from `php artisan schedule:list`,
> captured **at deploy time** — so a change to the schedule only takes effect on the next
> deploy. Keep the sleep timeout **under 15 minutes** or the environment never sleeps
> between dispatcher runs anyway.

---

## 2. The queue

Three options. For this app, pick **managed queues**.

### Managed queues — recommended

Workers run on their own instances, scale with the backlog (down to zero on Flex), and
failed jobs get a dashboard with retry.

1. **Add the AWS SDK first**, or the deploy fails with an explicit error:

   ```bash
   composer require aws/aws-sdk-php
   git commit -am "Add aws-sdk for Laravel Cloud managed queues"
   ```

   Also needs Laravel 11.55+, 12.63+, or 13.19+. This app is on Laravel 13.26 — fine.

2. Environment → **Add compute** on the canvas toolbar → **Managed queue**.

3. Settings that matter here:

   | Setting | Use | Why |
   |---|---|---|
   | Name | `reminders` | Becomes the environment's default queue, so `dispatch()` with no `->onQueue()` lands on it — which is what this app does |
   | Type | **Standard** | Dose reminders have no ordering requirement. FIFO costs more and blocks per-job delays |
   | Compute class | **Flex** | Every job here is one email. Nowhere near the 90-second Flex ceiling |
   | Memory | **256 MiB** | The default, and correct for sending mail |
   | Workers | min **0**, max **3–5** | Zero costs nothing while idle. Cap it low: the dispatcher fires a burst of ~120 at once and there is no reason to hit the mail provider with more parallelism than that |

4. **Deploy.** Cloud sets `QUEUE_CONNECTION=cloud` for you.

   > ⚠️ This app currently sets `QUEUE_CONNECTION=database`. Once a managed queue exists,
   > Cloud overrides it and *everything* routes to the managed queue. That is what you
   > want here — just don't be surprised that the `jobs` table stops filling.

5. Watch a real one land: **Monitoring → Queues**.

### Worker cluster — if you want to keep the database driver

Create a worker cluster, then **Background processes → New background process**, and add
`queue:work` with the process count you want. You keep `QUEUE_CONNECTION=database`. No
failed-job dashboard.

### App cluster background process — smallest setup

Same **Background processes → New background process**, but on the App cluster, so queue
workers share CPU and memory with web traffic. Fine for a demo or a pilot; not for volume.

> If the environment can scale to zero, prefer managed queues. A queued job still running
> when the sleep timeout elapses gets interrupted when the App cluster stops.

---

## 3. Environment variables

```ini
APP_TIMEZONE=Africa/Lagos          # dose windows are wall-clock local. UTC fires every
                                   # Nigerian reminder an hour late.
APP_URL=https://your-domain        # signed links are bound to this. Wrong value = every
                                   # reminder and magic link returns 403.

CACHE_STORE=database               # onOneServer needs a shared lock store
APP_MAINTENANCE_DRIVER=cache       # so `artisan down` applies across replicas
APP_MAINTENANCE_STORE=database

MEDBUDDY_AI_PROVIDER=openai        # openai | anthropic
MEDBUDDY_AI_MODE=live
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini

MAIL_MAILER=smtp                   # Mailtrap — see the note below
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=reminders@medbuddy.test
```

### Mail, with no domain of your own

A Laravel Cloud staging URL (`your-app.laravel.cloud`) **cannot be verified with an
email provider** — you do not control its DNS, so there are no records to add. That rules
out sending as yourself from it.

That leaves two honest options:

| | Works for | Does not |
|---|---|---|
| **Mailtrap sandbox** *(what this app ships with)* | Captures a message to **any** recipient, so all 120 seeded reminders land in one inbox you can open and read | Nothing leaves Mailtrap — no real person ever receives anything |
| **Resend** | Real delivery to real inboxes | Needs a verified domain. Without one it sends **only to the address that owns the Resend account** |

For a demo or a staging environment, **Mailtrap is the right answer, not a compromise.**
Every seeded patient is on `@patient.medbuddy.test`, an address that cannot receive mail
anywhere on earth — so "real delivery" is meaningless for this cohort, while a single
inbox holding every reminder in four languages is exactly what you want to show someone.

Tested against the Resend shared sender, not assumed:

```
michaelcharsac@gmail.com            OK    (the account owner)
dupe.fashola@patient.medbuddy.test  FAIL  "You can only send testing emails
                                           to your own email address"
someone.else@example.com            FAIL  "Invalid `to` field"
```

All 120 reminders would fail. **Before a real pilot**, buy any cheap domain — it does not
have to be the one serving the app — verify it at <https://resend.com/domains>, then
uncomment the Resend block in `.env`. The driver (`resend/resend-laravel`) is already
installed and the key is already on file, so it is three lines and a `config:clear`.

`APP_URL` is the one people get wrong. Every reminder link and every patient sign-in link
is a signed URL bound to that host — if it disagrees with the domain actually serving the
app, every link 403s and the response rate goes to zero with no error anywhere.

---

## 4. Verify after deploying

Run these from the environment's **Commands** tab:

```bash
php artisan medbuddy:doctor              # mail, queue, scheduler window, consent, AI, data integrity
php artisan medbuddy:mail-test you@you.com   # a real reminder and a real sign-in link
php artisan schedule:list                # confirm the dispatcher is registered
```

`medbuddy:doctor` reads the running system rather than the code, so it is the fastest way
to find out that a worker is not attached or a key never made it into the environment.

---

## Deliberately not automated

**Seeding.** `php artisan migrate:fresh --seed` drops every table. It builds the 120-patient
demo cohort and is right for a preview environment; it is never right for one with real
patients in it. Run it by hand, from the Commands tab, knowing which environment you are in.
