<?php

namespace App\Services;

use App\Models\DoseEvent;
use App\Models\Patient;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Doses are DERIVED from prescriptions.times; only deviations are stored.
 * Port of js/core/schedule.js so the two surfaces agree exactly.
 *
 * PERFORMANCE CONTRACT. Every adherence number in the app is a fold over
 * dosesForDay(), so this method is called on the order of patients × days per
 * page — at 120 patients the Overview alone asks for it ~9,000 times. It used
 * to run one query each time, which put the page at 89 seconds. It now reads
 * from a per-patient cache of that patient's whole dose history, loaded in one
 * query and shared for the life of the request.
 *
 * The cache is only safe because two things are true:
 *   1. This service is a singleton, so every caller sees the same cache.
 *   2. AppServiceProvider flushes a patient's entry on any DoseEvent write,
 *      so a Livewire component that ticks off a dose and re-renders in the
 *      same request sees the new value.
 */
class ScheduleService
{
    /**
     * @var array<int, array<string, object>> patient id => "Y-m-d|rxId|time" => row
     *
     * Deliberately raw rows, not DoseEvent models. Warming the cohort means
     * touching every dose event in the database — 10,000+ at 120 patients —
     * and hydrating that many Eloquent models cost about five seconds on its
     * own. Nothing outside this class reads the model off a dose, so the rows
     * carry the four columns a dose actually needs.
     */
    private array $events = [];

    /**
     * Memoised dosesForDay() results, patient id => "Y-m-d" => rows.
     *
     * The cohort fold asks for the same patient-day more than once — rate(7)
     * and rate(30) overlap by a week, and the streak walks back over days the
     * verdict already looked at. Recomputing meant re-running Carbon
     * comparisons that dominated the page: at 120 patients the fold spent
     * seconds building date objects it had already built.
     */
    private array $doses = [];

    /**
     * Load the dose history for many patients in one query.
     *
     * Callers that are about to fold over a whole cohort should warm first;
     * everything else falls back to a single-patient load on first use.
     */
    public function warm(iterable $patients): void
    {
        $ids = [];
        foreach ($patients as $patient) {
            $id = $patient instanceof Patient ? $patient->id : (int) $patient;
            if (! array_key_exists($id, $this->events)) {
                $ids[] = $id;
            }
        }

        if (! $ids) {
            return;
        }

        foreach ($ids as $id) {
            $this->events[$id] = [];
        }

        foreach (array_chunk($ids, 500) as $chunk) {
            DoseEvent::whereIn('patient_id', $chunk)
                ->toBase()
                ->select('id', 'patient_id', 'prescription_id', 'dose_date', 'dose_time', 'status', 'source', 'recorded_at')
                ->cursor()
                ->each(function (object $row) {
                    // dose_date arrives as the driver formats it; normalise to
                    // a bare date so a lookup key always matches.
                    $day = substr((string) $row->dose_date, 0, 10);
                    $this->events[$row->patient_id][$day.'|'.$row->prescription_id.'|'.$row->dose_time] = $row;
                });
        }
    }

    /** Drop a patient's cached history — or the whole cache when given nothing. */
    public function forget(int|Patient|null $patient = null): void
    {
        if ($patient === null) {
            $this->events = [];
            $this->doses = [];
            return;
        }

        $id = $patient instanceof Patient ? $patient->id : $patient;
        unset($this->events[$id], $this->doses[$id]);
    }

    /**
     * The prescriptions to fold over.
     *
     * activeOn() re-checks the `active` flag itself, so the activePrescriptions
     * relation gives an identical result to the full one — which means a caller
     * that eager-loaded either is served without a query. The last branch loads
     * once and attaches the relation, so a cohort fold does not re-query the
     * same patient on every one of thirty days.
     *
     * @return iterable<\App\Models\Prescription>
     */
    private function prescriptionsFor(Patient $patient): iterable
    {
        if ($patient->relationLoaded('prescriptions')) {
            return $patient->prescriptions;
        }

        if ($patient->relationLoaded('activePrescriptions')) {
            return $patient->activePrescriptions;
        }

        $patient->setRelation('prescriptions', $patient->prescriptions()->get());

        return $patient->prescriptions;
    }

    /** @return array<string, object> */
    private function eventsFor(Patient $patient): array
    {
        if (! array_key_exists($patient->id, $this->events)) {
            $this->warm([$patient]);
        }

        return $this->events[$patient->id] ?? [];
    }

    /**
     * Every dose scheduled for a patient on a given day, sorted by time,
     * with any recorded event merged in.
     *
     * `event` is the raw dose_events row, or null when nothing was recorded.
     *
     * @return array<int, array{prescription: \App\Models\Prescription, time: string, status: string, source: ?string, event: ?object}>
     */
    public function dosesForDay(Patient $patient, ?CarbonInterface $date = null): array
    {
        $date ??= now();

        // Callers in a fold hand us the same CarbonImmutable they built for the
        // whole cohort — recentDays() exists so they can. Re-parsing it was
        // what this method spent most of its time on.
        $date = $date instanceof CarbonImmutable ? $date : CarbonImmutable::parse($date);
        if ($date->hour || $date->minute || $date->second) {
            $date = $date->startOfDay();
        }

        $day = $date->format('Y-m-d');

        if (isset($this->doses[$patient->id][$day])) {
            return $this->doses[$patient->id][$day];
        }

        $events = $this->eventsFor($patient);

        $out = [];

        foreach ($this->prescriptionsFor($patient) as $rx) {
            if (! $rx->activeOn($date)) {
                continue;
            }

            foreach ($rx->times ?? [] as $time) {
                $event = $events[$day.'|'.$rx->id.'|'.$time] ?? null;
                $out[] = [
                    'prescription' => $rx,
                    'time'         => $time,
                    'status'       => $event?->status ?? 'pending',
                    'source'       => $event?->source,
                    'event'        => $event,
                ];
            }
        }

        usort($out, fn ($a, $b) => strcmp($a['time'], $b['time']));

        return $this->doses[$patient->id][$day] = $out;
    }

    /**
     * The last $days days ending yesterday, newest first — built once and
     * shared, because every patient in a fold wants the identical list and
     * constructing them per patient is what made the cohort page slow.
     *
     * @return array<int, CarbonImmutable>
     */
    public function recentDays(int $days, bool $includeToday = false): array
    {
        static $cache = [];

        $today = CarbonImmutable::today();
        $key = $today->format('Y-m-d').'|'.$days.'|'.(int) $includeToday;

        return $cache[$key] ??= array_map(
            fn (int $i) => $today->subDays($i),
            range($includeToday ? 0 : 1, $includeToday ? $days - 1 : $days),
        );
    }

    /** morning | afternoon | evening — what a reminder is allowed to name. */
    public function slotFor(string $time): string
    {
        $hour = (int) explode(':', $time)[0];

        return match (true) {
            $hour < 12 => 'morning',
            $hour < 17 => 'afternoon',
            default    => 'evening',
        };
    }

    /** All distinct dose windows a patient has on a date, sorted. */
    public function windowsFor(Patient $patient, ?CarbonInterface $date = null): array
    {
        $times = collect($this->dosesForDay($patient, $date))->pluck('time')->unique()->values()->all();
        sort($times);

        return $times;
    }
}
