<?php

namespace App\Services;

use App\Models\Patient;
use Carbon\CarbonImmutable;

/**
 * Port of js/core/adherence.js plus the paired comparison that the browser
 * prototype gained after a test caught a confounded claim.
 */
class AdherenceService
{
    public function __construct(private ScheduleService $schedule) {}

    /** Percentage of scheduled doses taken over the last N days, excluding today. */
    public function rate(Patient $patient, int $days = 7): ?int
    {
        $scheduled = 0;
        $taken = 0;

        foreach ($this->schedule->recentDays($days) as $day) {
            foreach ($this->schedule->dosesForDay($patient, $day) as $dose) {
                $scheduled++;
                if ($dose['status'] === 'taken') {
                    $taken++;
                }
            }
        }

        return $scheduled === 0 ? null : (int) round(($taken / $scheduled) * 100);
    }

    /** A day counts only if EVERY scheduled dose was taken. */
    public function isDayComplete(Patient $patient, CarbonImmutable $day): bool
    {
        $doses = $this->schedule->dosesForDay($patient, $day);

        if (empty($doses)) {
            return false;   // nothing scheduled cannot extend a streak
        }

        foreach ($doses as $dose) {
            if ($dose['status'] !== 'taken') {
                return false;
            }
        }

        return true;
    }

    /** Consecutive complete days. Bounded so corrupt data cannot spin here. */
    public function streak(Patient $patient): int
    {
        $today = CarbonImmutable::today();
        $day = $this->isDayComplete($patient, $today) ? $today : $today->subDay();
        $n = 0;

        for ($i = 0; $i < 400; $i++) {
            if (! $this->isDayComplete($patient, $day)) {
                break;
            }
            $n++;
            $day = $day->subDay();
        }

        return $n;
    }

    public function todayProgress(Patient $patient): array
    {
        $doses = $this->schedule->dosesForDay($patient);
        $taken = count(array_filter($doses, fn ($d) => $d['status'] === 'taken'));

        return [
            'taken'     => $taken,
            'total'     => count($doses),
            'remaining' => count($doses) - $taken,
        ];
    }

    /** Daily percentage series for charting. */
    public function dailySeries(Patient $patient, int $days = 30): array
    {
        $labels = [];
        $values = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = CarbonImmutable::today()->subDays($i);
            $doses = $this->schedule->dosesForDay($patient, $date);
            $labels[] = $date->format('j M');
            $values[] = count($doses)
                ? (int) round(count(array_filter($doses, fn ($d) => $d['status'] === 'taken')) / count($doses) * 100)
                : 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Morning vs evening, PAIRED: only patients who take both.
     *
     * The naive version of this compares dose-window buckets, which is
     * confounded — a bucket containing one struggling TB patient and another
     * containing one excellent ARV patient compares PEOPLE, not times of day.
     * Restricting to patients with a dose in both windows makes each patient
     * their own control, which is the only version worth quoting.
     *
     * @return array{n:int, morning:int, evening:int, gap:int}|null
     */
    public function morningVsEvening(iterable $patients, int $days = 30): ?array
    {
        $mTaken = $mTotal = $eTaken = $eTotal = $n = 0;

        foreach ($patients as $patient) {
            $windows = $this->schedule->windowsFor($patient);
            $morning = collect($windows)->first(fn ($t) => (int) explode(':', $t)[0] < 12);
            $evening = collect($windows)->first(fn ($t) => (int) explode(':', $t)[0] >= 17);

            if (! $morning || ! $evening) {
                continue;   // not a paired patient
            }
            $n++;

            foreach ($this->schedule->recentDays($days, includeToday: true) as $day) {
                foreach ($this->schedule->dosesForDay($patient, $day) as $dose) {
                    if ($dose['time'] === $morning) {
                        $mTotal++;
                        if ($dose['status'] === 'taken') $mTaken++;
                    }
                    if ($dose['time'] === $evening) {
                        $eTotal++;
                        if ($dose['status'] === 'taken') $eTaken++;
                    }
                }
            }
        }

        if ($n === 0 || $mTotal === 0 || $eTotal === 0) {
            return null;
        }

        $morningPct = (int) round(($mTaken / $mTotal) * 100);
        $eveningPct = (int) round(($eTaken / $eTotal) * 100);

        return [
            'n'       => $n,
            'morning' => $morningPct,
            'evening' => $eveningPct,
            'gap'     => $morningPct - $eveningPct,
        ];
    }

    /**
     * Derived "needs attention" verdict. Thresholds live in config.
     *
     * $hasSevereReport lets a cohort fold pass in a value it already looked up
     * in one query. Left null — the case for any single-patient caller — this
     * asks the database itself, so the verdict is identical either way.
     */
    public function attention(Patient $patient, ?bool $hasSevereReport = null): array
    {
        $reasons = [];
        $level = 'ok';

        $rate = $this->rate($patient, 7) ?? 100;
        $critical = config('medbuddy.adherence_critical', 60);
        $concern  = config('medbuddy.adherence_concern', 80);

        if ($rate < $critical) {
            $level = 'critical';
            $reasons[] = "Adherence {$rate}%";
        } elseif ($rate < $concern) {
            $level = 'concern';
            $reasons[] = "Adherence {$rate}%";
        }

        $silentDays = config('medbuddy.silent_days', 3);
        if ($patient->last_response_at && $patient->last_response_at->diffInDays(now()) >= $silentDays) {
            $level = 'critical';
            $reasons[] = 'No reply for '.(int) $patient->last_response_at->diffInDays(now()).' days';
        }

        $severe = $hasSevereReport ?? $patient->reports()
            ->where('severity', '>=', config('medbuddy.severity_concern', 2))
            ->where('created_at', '>=', now()->subDays(3))
            ->exists();

        if ($severe) {
            $level = $level === 'ok' ? 'concern' : $level;
            $reasons[] = 'Symptom reported';
        }

        return ['level' => $level, 'reasons' => $reasons, 'rate' => $rate];
    }
}
