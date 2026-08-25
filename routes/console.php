<?php

use App\Jobs\DispatchDueReminders;
use Illuminate\Support\Facades\Schedule;

/*
 | THE thing a browser can never do: fire while the patient's phone is asleep.
 |
 | Every 15 minutes, find dose windows that just opened and queue exactly one
 | message per patient per window. In production this needs one cron entry:
 |
 |   * * * * * cd /path/to/platform && php artisan schedule:run >> /dev/null 2>&1
 |
 | and a queue worker under supervisor:
 |
 |   php artisan queue:work --tries=3
 */
Schedule::job(new DispatchDueReminders)->everyFifteenMinutes()->withoutOverlapping();
