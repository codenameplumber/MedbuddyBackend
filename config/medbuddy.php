<?php

return [
    /*
     | Attention thresholds. These decide who appears in "Needs attention".
     | Kept in one place so a clinical lead can change what the word means
     | without touching a screen.
     */
    'adherence_concern'  => env('MEDBUDDY_ADHERENCE_CONCERN', 80),
    'adherence_critical' => env('MEDBUDDY_ADHERENCE_CRITICAL', 60),
    'silent_days'        => env('MEDBUDDY_SILENT_DAYS', 3),
    'severity_concern'   => env('MEDBUDDY_SEVERITY_CONCERN', 2),

    /*
     | How long a reminder link stays valid. It is scoped to one dose and
     | expires with the window it belongs to.
     */
    'link_ttl_hours' => env('MEDBUDDY_LINK_TTL_HOURS', 6),

    /*
     | Channels currently enabled. Laravel Notifications make adding 'vonage'
     | or a WhatsApp channel a one-line change, so email-only today is a
     | simplification rather than a dead end.
     */
    'channels' => ['mail'],

    /*
     | The display names patients actually see. Diacritics are load-bearing
     | here and several common font stacks drop them silently — styles/fonts.css
     | documents the audit and tools/diacritics.html verifies it on a device.
     */
    'languages' => ['en' => 'English', 'yo' => 'Yorùbá', 'ha' => 'Hausa', 'ig' => 'Igbo'],

    'symptoms' => ['fine', 'dizzy', 'rash', 'stomach', 'sleepy', 'swelling', 'other'],
];
