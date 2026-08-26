<?php

use App\Jobs\DispatchDueReminders;
use Illuminate\Support\Facades\Schedule;

/*
 | THE thing a browser can never do: fire while the patient's phone is asleep.
 |
 | Every 15 minutes, find dose windows that just opened and queue exactly one
 | message per patient per window.
 |
 | ── Running it ───────────────────────────────────────────────────────
 |
 | Laravel Cloud: enable the "Scheduler" toggle on the App compute cluster
 | (or on a Worker cluster) and redeploy. Cloud then invokes schedule:run
 | every minute for you. See DEPLOY.md.
 |
 | Anywhere else, one cron entry:
 |
 |   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
 |
 | plus a queue worker, because the reminders are queued notifications:
 |
 |   php artisan queue:work --tries=3
 |
 | ── onOneServer ──────────────────────────────────────────────────────
 |
 | Without this, a deployment running two replicas runs the dispatcher twice
 | at the same minute. The reminders table's unique index on
 | (patient_id, dose_date, dose_time) means the second run cannot actually
 | send a duplicate — but it does mean a race, a wasted pass over the whole
 | cohort, and a constraint violation in the logs every quarter hour.
 |
 | onOneServer needs a shared cache store (database or Redis), not the file
 | driver: two replicas each holding their own file lock is no lock at all.
 */
Schedule::job(new DispatchDueReminders)
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();
