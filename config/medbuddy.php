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

    /*
     | One-click patient sign-in on /me/login, for demos.
     |
     | The patient app has no passwords — the link in the email is the
     | credential — which makes it genuinely awkward to show someone. This
     | lists the seeded cohort on the sign-in page and lets you in with one tap.
     |
     | It is guarded twice. Off unless MEDBUDDY_DEMO_SIGNIN is true, AND it
     | only ever offers patients on the fabricated .test domain, so it cannot
     | sign anyone in as a real patient even if it is left switched on by
     | accident. `medbuddy:doctor` reports loudly when it is enabled.
     */
    'demo_signin' => (bool) env('MEDBUDDY_DEMO_SIGNIN', false),

    /* The mailbox suffix that marks a record as fabricated demo data. */
    'demo_domain' => '@patient.medbuddy.test',

    'symptoms' => ['fine', 'dizzy', 'rash', 'stomach', 'sleepy', 'swelling', 'other'],
];
