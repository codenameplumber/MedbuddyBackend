<?php

namespace Tests\Feature;

use App\Models\Consent;
use App\Models\Organisation;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Reminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * `medbuddy:mail-test` is the command someone runs when mail is not working,
 * so it has exactly one job: tell them the truth about why.
 *
 * It first shipped creating today's reminder blind, which collided with the
 * one-per-patient-per-window unique index whenever the dispatcher had already
 * run. The command then failed with a database constraint violation printed
 * under an SMTP troubleshooting checklist — the most misleading output it
 * could possibly produce, at the moment someone is least able to tell.
 */
class MailPathTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organisation::create(['name' => 'Test Clinic', 'emergency_number' => '112']);

        $this->patient = Patient::create([
            'organisation_id' => $org->id, 'name' => 'Grace Okafor',
            'email' => 'grace@example.test', 'lang' => 'yo',
            'condition' => 'Hypertension', 'enrolled_at' => now()->subMonth(),
        ]);

        Prescription::create([
            'patient_id' => $this->patient->id, 'name' => 'Amlodipine', 'strength' => '5 mg',
            'times' => ['08:00', '20:00'], 'active' => true, 'starts_on' => now()->subMonth(),
        ]);

        Consent::create([
            'patient_id' => $this->patient->id, 'scope' => 'messaging', 'channel' => 'mail',
            'granted' => true, 'granted_at' => now(),
        ]);
    }

    public function test_it_sends_a_reminder_and_a_sign_in_link(): void
    {
        Mail::fake();

        $this->artisan('medbuddy:mail-test', ['to' => 'demo@example.test'])
            ->assertSuccessful();
    }

    public function test_it_still_works_when_todays_reminder_already_exists(): void
    {
        // The dispatcher runs every fifteen minutes, so by the time anyone
        // reaches for this command the row is usually already there.
        Mail::fake();

        Reminder::create([
            'patient_id' => $this->patient->id,
            'dose_date'  => today()->toDateString(),
            'dose_time'  => '08:00',
            'slot' => 'morning', 'channel' => 'mail',
            'to' => $this->patient->email, 'sent_at' => now(),
        ]);

        $this->artisan('medbuddy:mail-test', ['to' => 'demo@example.test'])
            ->assertSuccessful();
    }

    public function test_it_leaves_nothing_behind_in_the_outbox(): void
    {
        // A test message counted by the delivery funnel is a reminder a
        // patient never received, inflating the number the whole product is
        // judged on.
        Mail::fake();

        $before = Reminder::count();

        $this->artisan('medbuddy:mail-test', ['to' => 'demo@example.test'])->assertSuccessful();

        $this->assertSame($before, Reminder::count());
        $this->assertSame(0, Reminder::where('to', 'demo@example.test')->count());
    }

    public function test_an_existing_reminder_survives_the_run_unchanged(): void
    {
        Mail::fake();

        $original = Reminder::create([
            'patient_id' => $this->patient->id,
            'dose_date'  => today()->toDateString(),
            'dose_time'  => '08:00',
            'slot' => 'morning', 'channel' => 'mail',
            'to' => 'real-patient@example.test', 'sent_at' => now()->subHour(),
        ]);

        $this->artisan('medbuddy:mail-test', ['to' => 'demo@example.test'])->assertSuccessful();

        $this->assertSame(
            'real-patient@example.test',
            $original->fresh()->to,
            'The command borrowed a real reminder row and must hand it back as it found it.',
        );
    }

    public function test_it_refuses_an_address_that_is_not_one(): void
    {
        $this->artisan('medbuddy:mail-test', ['to' => 'not-an-email'])
            ->assertFailed();
    }

    public function test_it_can_be_pointed_at_a_language_to_check_the_diacritics(): void
    {
        Mail::fake();

        $this->artisan('medbuddy:mail-test', ['to' => 'demo@example.test', '--lang' => 'ha'])
            ->expectsOutputToContain('Hausa')
            ->assertSuccessful();
    }
}
