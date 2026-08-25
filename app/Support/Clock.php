<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Wall-clock formatting for the patient app.
 *
 * Dose windows are stored as "08:00" because that is what they are — a local
 * wall-clock time, not an instant. But "08:00" is not how anyone reads a time
 * off a phone, and the prototype formatted it (js/core/schedule.js
 * formatTime). The Laravel port showed the raw column, so every dose on Today
 * read like a database field.
 */
class Clock
{
    /**
     * "08:00" → "8:00 AM". Falls back to the stored value rather than
     * throwing: a malformed time should render oddly, not take down the one
     * screen a patient opens every morning.
     */
    public static function time(string $hhmm): string
    {
        if (! preg_match('/^(\d{1,2}):(\d{2})$/', $hhmm, $m)) {
            return $hhmm;
        }

        return CarbonImmutable::today()
            ->setTime((int) $m[1], (int) $m[2])
            ->format('g:i A');
    }
}
