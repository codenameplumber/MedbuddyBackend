<?php

namespace Tests\Feature;

use App\Models\DoseEvent;
use App\Models\Organisation;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\AdherenceService;
use App\Services\ScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdherenceTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organisation::create(['name' => 'Test Clinic']);
        $this->patient = Patient::create([
            'organisation_id' => $org->id, 'name' => 'Test', 'lang' => 'en',
            'enrolled_at' => now()->subMonths(3),
        ]);
    }

    private function prescribe(array $times): Prescription
    {
        return Prescription::create([
            'patient_id' => $this->patient->id,
            'name' => 'Test Drug', 'times' => $times, 'active' => true,
            'starts_on' => now()->subMonths(3),
        ]);
    }

    private function take(Prescription $rx, string $date, string $time): void
    {
        DoseEvent::create([
            'patient_id' => $this->patient->id, 'prescription_id' => $rx->id,
            'dose_date' => $date, 'dose_time' => $time,
            'status' => 'taken', 'source' => 'app', 'recorded_at' => now(),
        ]);
    }

    public function test_doses_are_derived_and_returned_in_time_order(): void
    {
        $this->prescribe(['20:00', '08:00']);

        $doses = app(ScheduleService::class)->dosesForDay($this->patient->refresh());

        $this->assertCount(2, $doses);
        $this->assertSame('08:00', $doses[0]['time']);
        $this->assertSame('20:00', $doses[1]['time']);
        $this->assertSame('pending', $doses[0]['status']);
    }

    public function test_a_streak_needs_every_dose_of_the_day(): void
    {
        $rx = $this->prescribe(['08:00', '20:00']);
        $today = today()->toDateString();

        $this->take($rx, $today, '08:00');
        $this->assertSame(0, app(AdherenceService::class)->streak($this->patient->refresh()),
            'A partially complete day must not count.');

        $this->take($rx, $today, '20:00');
        $this->assertSame(1, app(AdherenceService::class)->streak($this->patient->refresh()));
    }

    public function test_a_day_with_nothing_scheduled_cannot_extend_a_streak(): void
    {
        $this->prescribe([]);
        $this->assertSame(0, app(AdherenceService::class)->streak($this->patient->refresh()));
    }

    public function test_slots_are_named_correctly(): void
    {
        $s = app(ScheduleService::class);
        $this->assertSame('morning',   $s->slotFor('08:00'));
        $this->assertSame('afternoon', $s->slotFor('13:00'));
        $this->assertSame('evening',   $s->slotFor('21:00'));
    }

    public function test_the_paired_comparison_only_counts_patients_taking_both(): void
    {
        // The naive bucket comparison is confounded: a window taken by one
        // struggling patient and a window taken by one excellent patient
        // compares PEOPLE, not times of day. Pairing fixes that.
        $rx = $this->prescribe(['08:00', '20:00']);

        for ($i = 0; $i < 10; $i++) {
            $date = today()->subDays($i)->toDateString();
            $this->take($rx, $date, '08:00');                 // morning always taken
            if ($i % 2 === 0) $this->take($rx, $date, '20:00'); // evening half the time
        }

        $paired = app(AdherenceService::class)->morningVsEvening([$this->patient->refresh()], 10);

        $this->assertNotNull($paired);
        $this->assertSame(1, $paired['n']);
        $this->assertGreaterThan($paired['evening'], $paired['morning']);
        $this->assertSame($paired['morning'] - $paired['evening'], $paired['gap']);
    }

    public function test_a_morning_only_patient_is_excluded_from_the_pairing(): void
    {
        $this->prescribe(['08:00']);
        $this->assertNull(app(AdherenceService::class)->morningVsEvening([$this->patient->refresh()]));
    }

    public function test_adherence_rate_is_a_percentage_or_null(): void
    {
        $rx = $this->prescribe(['08:00']);
        for ($i = 1; $i <= 7; $i++) {
            $this->take($rx, today()->subDays($i)->toDateString(), '08:00');
        }

        $rate = app(AdherenceService::class)->rate($this->patient->refresh(), 7);
        $this->assertSame(100, $rate);
    }
}
