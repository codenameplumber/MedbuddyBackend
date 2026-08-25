<?php

namespace Tests\Feature;

use App\Models\Consent;
use App\Models\DoseEvent;
use App\Models\Organisation;
use App\Models\Patient;
use App\Models\PatientReport;
use App\Models\Prescription;
use App\Models\Reminder;
use App\Notifications\DoseReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * The loop: reminder -> signed link -> response -> data the clinic can see.
 */
class ReminderLoopTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;
    private Prescription $rx;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organisation::create(['name' => 'Test Clinic', 'emergency_number' => '112']);

        $this->patient = Patient::create([
            'organisation_id' => $org->id,
            'name' => 'Grace Test',
            'email' => 'grace@example.test',
            'condition' => 'Hypertension',
            'lang' => 'yo',
            'enrolled_at' => now()->subMonth(),
        ]);

        $this->rx = Prescription::create([
            'patient_id' => $this->patient->id,
            'name' => 'Amlodipine',
            'strength' => '5 mg',
            'times' => ['08:00'],
            'active' => true,
        ]);

        Consent::create([
            'patient_id' => $this->patient->id, 'scope' => 'messaging',
            'channel' => 'mail', 'granted' => true, 'granted_at' => now(),
        ]);
    }

    private function reminder(): Reminder
    {
        return Reminder::create([
            'patient_id' => $this->patient->id,
            'dose_date' => today(),
            'dose_time' => '08:00',
            'slot' => 'morning',
            'channel' => 'mail',
            'to' => $this->patient->email,
        ]);
    }

    public function test_an_unsigned_link_is_rejected(): void
    {
        // No login is the point, so the signature IS the security boundary.
        $this->get('/r/'.$this->reminder()->id)->assertForbidden();
    }

    public function test_a_tampered_signature_is_rejected(): void
    {
        $url = URL::temporarySignedRoute('respond.show', now()->addHours(6), ['reminder' => $this->reminder()->id]);
        $this->get($url.'X')->assertForbidden();
    }

    public function test_an_expired_link_is_rejected(): void
    {
        $url = URL::temporarySignedRoute('respond.show', now()->subMinute(), ['reminder' => $this->reminder()->id]);
        $this->get($url)->assertForbidden();
    }

    public function test_a_valid_link_opens_in_the_patients_own_language(): void
    {
        $url = URL::temporarySignedRoute('respond.show', now()->addHours(6), ['reminder' => $this->reminder()->id]);

        $this->get($url)
            ->assertOk()
            ->assertSee(__('respond.yes', [], 'yo'))
            ->assertSee(__('respond.no', [], 'yo'));
    }

    public function test_confirming_a_dose_records_it_with_link_provenance(): void
    {
        $reminder = $this->reminder();
        $url = URL::temporarySignedRoute('respond.store', now()->addHours(6), ['reminder' => $reminder->id]);

        $this->post($url, ['took' => 'yes'])->assertOk();

        $dose = DoseEvent::first();
        $this->assertNotNull($dose);
        $this->assertSame('taken', $dose->status);
        // Provenance matters: confirmed from a reminder beats recalled later.
        $this->assertSame('link', $dose->source);
        $this->assertNotNull($reminder->refresh()->responded_at);
        $this->assertNotNull($this->patient->refresh()->last_response_at);
    }

    public function test_a_symptom_report_is_attributed_to_the_right_medicine(): void
    {
        $url = URL::temporarySignedRoute('respond.store', now()->addHours(6), ['reminder' => $this->reminder()->id]);

        $this->post($url, ['took' => 'yes', 'symptoms' => ['dizzy'], 'severity' => 2])->assertOk();

        $report = PatientReport::first();
        $this->assertSame(['dizzy'], $report->symptoms);
        $this->assertSame(2, $report->severity);
        $this->assertSame($this->rx->id, $report->prescription_id);
        $this->assertSame('link', $report->source);
    }

    public function test_feeling_fine_is_exclusive(): void
    {
        // Otherwise the cohort fills with "fine + rash", which means nothing.
        $url = URL::temporarySignedRoute('respond.store', now()->addHours(6), ['reminder' => $this->reminder()->id]);

        $this->post($url, ['took' => 'yes', 'symptoms' => ['fine', 'rash'], 'severity' => 3])->assertOk();

        $report = PatientReport::first();
        $this->assertSame(['fine'], $report->symptoms);
        $this->assertSame(0, $report->severity, 'A "fine" report must carry zero severity.');
    }

    public function test_a_crisis_note_trips_the_gate_and_is_still_recorded(): void
    {
        $url = URL::temporarySignedRoute('respond.store', now()->addHours(6), ['reminder' => $this->reminder()->id]);

        $this->post($url, ['took' => 'yes', 'note' => 'I have chest pain'])
            ->assertOk()
            ->assertSee('This may be an emergency')
            ->assertSee('tel:112', false);

        $this->assertSame('cardiac', PatientReport::first()->triage_category);
    }

    public function test_a_missed_dose_records_no_dose_event_but_does_record_a_report(): void
    {
        $url = URL::temporarySignedRoute('respond.store', now()->addHours(6), ['reminder' => $this->reminder()->id]);

        $this->post($url, ['took' => 'no'])->assertOk();

        $this->assertSame(0, DoseEvent::count());
        $this->assertFalse(PatientReport::first()->took_dose);
    }

    public function test_a_reminder_is_sent_only_once_per_patient_per_window(): void
    {
        Notification::fake();

        $first = $this->reminder();
        $this->assertSame(1, Reminder::count());

        // The unique index is the real guard against double-sending.
        $this->expectException(\Illuminate\Database\QueryException::class);
        Reminder::create([
            'patient_id' => $this->patient->id,
            'dose_date' => today(),
            'dose_time' => '08:00',
            'slot' => 'morning',
            'channel' => 'mail',
        ]);
    }
}
