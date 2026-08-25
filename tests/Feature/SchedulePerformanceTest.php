<?php

namespace Tests\Feature;

use App\Models\DoseEvent;
use App\Models\Organisation;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\AdherenceService;
use App\Services\AnalyticsService;
use App\Services\ScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ScheduleService caches every patient's dose history for the life of the
 * request. That took the cohort pages from around a minute to a few seconds at
 * 120 patients — and introduced exactly one way to be wrong: serving a stale
 * answer after a write.
 *
 * These tests exist for that hazard. The performance assertion is a query
 * count rather than a stopwatch, because a stopwatch on CI measures the CI box.
 */
class SchedulePerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Organisation, 1: \Illuminate\Support\Collection<int, Patient>} */
    private function cohort(int $n = 12): array
    {
        $org = Organisation::create(['name' => 'Test Clinic', 'emergency_number' => '112']);

        $patients = collect(range(1, $n))->map(function (int $i) use ($org) {
            $patient = Patient::create([
                'organisation_id' => $org->id,
                'name' => "Patient {$i}", 'email' => "p{$i}@example.test",
                'lang' => 'en', 'condition' => 'Hypertension',
                'enrolled_at' => now()->subMonths(3),
                'last_response_at' => now()->subDay(),
            ]);

            $rx = Prescription::create([
                'patient_id' => $patient->id, 'name' => 'Amlodipine', 'strength' => '5 mg',
                'times' => ['08:00', '20:00'], 'active' => true, 'starts_on' => now()->subMonths(3),
            ]);

            // Every morning dose taken, no evening ones: a known 50%.
            for ($d = 1; $d <= 30; $d++) {
                DoseEvent::create([
                    'patient_id' => $patient->id, 'prescription_id' => $rx->id,
                    'dose_date' => today()->subDays($d)->toDateString(), 'dose_time' => '08:00',
                    'status' => 'taken', 'source' => 'link',
                    'recorded_at' => today()->subDays($d)->setTime(8, 10),
                ]);
            }

            return $patient;
        });

        return [$org, $patients];
    }

    public function test_the_cached_fold_gives_the_same_answer_as_an_uncached_one(): void
    {
        [, $patients] = $this->cohort(4);
        $patient = $patients->first();

        $cold = app(AdherenceService::class)->rate($patient->fresh(), 30);

        // Warm the whole cohort, the way a clinic page does, then ask again.
        $schedule = app(ScheduleService::class);
        $schedule->warm(Patient::all());
        $warm = app(AdherenceService::class)->rate($patient->fresh(), 30);

        $this->assertSame(50, $cold, 'Half the scheduled doses were taken.');
        $this->assertSame($cold, $warm, 'Warming the cache must not change the number.');
    }

    public function test_a_recorded_dose_is_visible_immediately_after_it_is_written(): void
    {
        // This is the hazard. A Livewire component ticks off a dose and
        // re-renders inside the same request; a cache that survived the write
        // would show the patient their dose had not registered.
        [, $patients] = $this->cohort(2);
        $patient = $patients->first();

        $schedule = app(ScheduleService::class);
        $schedule->warm(Patient::all());

        $before = collect($schedule->dosesForDay($patient, today()))
            ->firstWhere('time', '08:00');
        $this->assertSame('pending', $before['status']);

        DoseEvent::create([
            'patient_id' => $patient->id,
            'prescription_id' => $patient->prescriptions()->first()->id,
            'dose_date' => today()->toDateString(), 'dose_time' => '08:00',
            'status' => 'taken', 'source' => 'app', 'recorded_at' => now(),
        ]);

        $after = collect($schedule->dosesForDay($patient->fresh(), today()))
            ->firstWhere('time', '08:00');

        $this->assertSame('taken', $after['status'], 'The cache must be dropped when a dose is written.');
    }

    public function test_deleting_a_dose_event_also_drops_the_cache(): void
    {
        [, $patients] = $this->cohort(2);
        $patient = $patients->first();

        $schedule = app(ScheduleService::class);
        $yesterday = today()->subDay();

        $this->assertSame('taken', collect($schedule->dosesForDay($patient, $yesterday))
            ->firstWhere('time', '08:00')['status']);

        DoseEvent::where('patient_id', $patient->id)
            ->whereDate('dose_date', $yesterday)->first()->delete();

        $this->assertSame('pending', collect($schedule->dosesForDay($patient->fresh(), $yesterday))
            ->firstWhere('time', '08:00')['status']);
    }

    public function test_the_cohort_fold_does_not_scale_its_queries_with_the_cohort(): void
    {
        /*
         | The regression this guards. dosesForDay() used to run one query per
         | patient per day, so cohort() was roughly patients x 37 queries —
         | 4,500 of them at 120 patients, and about a minute of wall clock.
         | A constant bound is the property that matters; the exact number is
         | allowed to drift a little.
         */
        $this->cohort(20);

        $queries = 0;
        DB::listen(function () use (&$queries) { $queries++; });

        app(AnalyticsService::class)->cohort();

        $this->assertLessThan(
            15,
            $queries,
            "cohort() ran {$queries} queries over 20 patients. It must not scale with the cohort.",
        );
    }

    public function test_warming_twice_does_not_query_twice(): void
    {
        $this->cohort(3);

        // Eager-loaded, the way every cohort page loads it. dosesForDay reads
        // the relation off the model rather than querying per day.
        $patients = Patient::with('prescriptions')->get();

        $schedule = app(ScheduleService::class);
        $schedule->warm($patients);

        $queries = 0;
        DB::listen(function () use (&$queries) { $queries++; });

        $schedule->warm($patients);
        foreach ($patients as $patient) {
            $schedule->dosesForDay($patient, today()->subDay());
        }

        $this->assertSame(0, $queries, 'Everything asked for was already loaded.');
    }

    public function test_a_patient_with_no_dose_events_is_cached_as_empty_not_re_queried(): void
    {
        // The empty case is the one a naive cache gets wrong: "no entry" and
        // "an entry that is empty" have to be told apart, or every lookup for
        // a new patient hits the database again.
        $org = Organisation::create(['name' => 'Test Clinic', 'emergency_number' => '112']);
        $patient = Patient::create([
            'organisation_id' => $org->id, 'name' => 'New', 'email' => 'new@example.test',
            'lang' => 'en', 'enrolled_at' => now(),
        ]);
        Prescription::create([
            'patient_id' => $patient->id, 'name' => 'Amlodipine', 'times' => ['08:00'],
            'active' => true, 'starts_on' => now(),
        ]);

        $patient = Patient::with('prescriptions')->find($patient->id);

        $schedule = app(ScheduleService::class);
        $schedule->warm([$patient]);

        $queries = 0;
        DB::listen(function () use (&$queries) { $queries++; });

        for ($d = 1; $d <= 5; $d++) {
            $schedule->dosesForDay($patient, today()->subDays($d));
        }

        $this->assertSame(0, $queries);
    }
}
