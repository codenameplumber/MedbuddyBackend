<?php

namespace Tests\Feature;

use App\Livewire\Clinic;
use App\Models\Consent;
use App\Models\Organisation;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Notifications\CaregiverSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The audit found there was no way to enrol a patient, issue a prescription,
 * record consent or add staff through the interface. These prove those paths
 * exist AND that they are scoped by role.
 */
class ClinicWriteTest extends TestCase
{
    use RefreshDatabase;

    private Organisation $org;
    private User $admin;
    private User $physician;
    private User $nurse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organisation::create(['name' => 'Test Clinic', 'emergency_number' => '112']);

        foreach (['admin', 'physician', 'nurse'] as $role) {
            $this->{$role} = User::create([
                'organisation_id' => $this->org->id, 'name' => ucfirst($role),
                'email' => $role.'@example.test', 'role' => $role,
                'password' => Hash::make('password'),
            ]);
        }
    }

    private function patient(array $attrs = []): Patient
    {
        return Patient::create(array_merge([
            'organisation_id' => $this->org->id, 'name' => 'Grace Okafor',
            'email' => 'grace@example.test', 'lang' => 'yo', 'condition' => 'Hypertension',
            'enrolled_at' => now()->subMonth(),
        ], $attrs));
    }

    // ── Enrolment ────────────────────────────────────────────────────

    public function test_a_physician_can_enrol_a_patient_with_consent(): void
    {
        $this->actingAs($this->physician);

        Livewire::test(Clinic\PatientForm::class)
            ->set('name', 'Tunde Bakare')
            ->set('email', 'tunde@example.test')
            ->set('age', 72)
            ->set('condition', 'Hypertension')
            ->set('lang', 'yo')
            ->set('consentMessaging', true)
            ->set('consentResearch', true)
            ->call('save');

        $patient = Patient::where('email', 'tunde@example.test')->firstOrFail();

        $this->assertSame($this->org->id, $patient->organisation_id);
        $this->assertNotNull($patient->enrolled_at);
        $this->assertTrue($patient->hasConsent('messaging', 'mail'));
        $this->assertTrue($patient->hasConsent('research'));
        $this->assertDatabaseHas('audits', ['action' => 'patient.enrolled']);
    }

    public function test_messaging_consent_without_an_address_is_refused(): void
    {
        // Email is the only enabled channel, so this would record a promise
        // the system cannot keep.
        $this->actingAs($this->physician);

        Livewire::test(Clinic\PatientForm::class)
            ->set('name', 'No Address')
            ->set('email', '')
            ->set('consentMessaging', true)
            ->call('save')
            ->assertHasErrors('email');

        $this->assertDatabaseMissing('patients', ['name' => 'No Address']);
    }

    public function test_a_nurse_cannot_enrol_a_patient(): void
    {
        $this->actingAs($this->nurse);

        Livewire::test(Clinic\PatientForm::class)->assertForbidden();
    }

    public function test_revoking_consent_from_the_form_keeps_the_record(): void
    {
        $this->actingAs($this->physician);
        $patient = $this->patient();

        Consent::create([
            'patient_id' => $patient->id, 'scope' => 'messaging', 'channel' => 'mail',
            'granted' => true, 'granted_at' => now(),
        ]);

        Livewire::test(Clinic\PatientForm::class, ['patient' => $patient])
            ->set('consentMessaging', false)
            ->call('save');

        $this->assertFalse($patient->refresh()->hasConsent('messaging', 'mail'));
        // Revoked, not deleted: the record of the decision is the point.
        $this->assertDatabaseCount('consents', 2);
        $this->assertNotNull(Consent::where('scope', 'messaging')->first()->revoked_at);
        $this->assertDatabaseHas('audits', ['action' => 'consent.revoked']);
    }

    // ── Prescribing ──────────────────────────────────────────────────

    public function test_a_physician_can_issue_a_prescription(): void
    {
        $this->actingAs($this->physician);
        $patient = $this->patient();

        Livewire::test(Clinic\PrescriptionForm::class, ['patient' => $patient])
            ->set('name', 'Amlodipine')
            ->set('strength', '5 mg')
            ->set('purpose', 'blood pressure')
            ->set('times', ['20:00', '08:00'])
            ->call('save');

        $rx = Prescription::where('patient_id', $patient->id)->firstOrFail();

        // Sorted, because dose windows drive reminder grouping.
        $this->assertSame(['08:00', '20:00'], $rx->times);
        $this->assertSame($this->physician->id, $rx->prescribed_by);
        $this->assertDatabaseHas('audits', ['action' => 'prescription.issued']);
    }

    public function test_duplicate_dose_times_are_collapsed(): void
    {
        // Two identical times would mean two messages in the same window.
        $this->actingAs($this->physician);
        $patient = $this->patient();

        Livewire::test(Clinic\PrescriptionForm::class, ['patient' => $patient])
            ->set('name', 'Amlodipine')
            ->set('times', ['08:00', '08:00'])
            ->call('save');

        $this->assertSame(['08:00'], Prescription::first()->times);
    }

    public function test_a_nurse_cannot_prescribe(): void
    {
        // Prescribing is a clinical act, and the policy is what stops it —
        // not a hidden button.
        $this->actingAs($this->nurse);

        Livewire::test(Clinic\PrescriptionForm::class, ['patient' => $this->patient()])
            ->assertForbidden();
    }

    public function test_removing_the_last_dose_time_leaves_a_usable_default(): void
    {
        $this->actingAs($this->physician);

        Livewire::test(Clinic\PrescriptionForm::class, ['patient' => $this->patient()])
            ->call('removeTime', 0)
            ->assertSet('times', ['08:00']);
    }

    // ── Team ─────────────────────────────────────────────────────────

    public function test_an_admin_can_add_a_staff_member(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(Clinic\Team::class)
            ->set('name', 'New Nurse')
            ->set('email', 'new@example.test')
            ->set('role', 'nurse')
            ->set('password', 'password123')
            ->call('save')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('users', ['email' => 'new@example.test', 'role' => 'nurse']);
        $this->assertDatabaseHas('audits', ['action' => 'user.created']);
    }

    public function test_a_physician_cannot_add_a_staff_member(): void
    {
        $this->actingAs($this->physician);

        Livewire::test(Clinic\Team::class)
            ->set('name', 'Sneaky')
            ->set('email', 'sneaky@example.test')
            ->set('password', 'password123')
            ->call('save')
            ->assertForbidden();
    }

    public function test_only_an_admin_can_change_a_role(): void
    {
        $this->actingAs($this->nurse);

        Livewire::test(Clinic\Team::class)
            ->call('changeRole', $this->nurse->id, 'admin')
            ->assertForbidden();

        $this->assertSame('nurse', $this->nurse->refresh()->role);
    }

    // ── AI screens ───────────────────────────────────────────────────

    public function test_the_explain_screen_reads_a_label(): void
    {
        $this->actingAs($this->physician);

        Livewire::test(Clinic\Explain::class)
            ->set('text', 'AMLODIPINE 5MG TAKE ONE TABLET DAILY')
            ->call('run')
            ->assertSet('error', null)
            ->assertSee('Amlodipine');

        $this->assertDatabaseHas('audits', ['action' => 'ai.explain']);
    }

    public function test_an_unreadable_label_refuses_to_name_a_medicine(): void
    {
        // Reading handwriting wrong could mean the wrong medicine, so "I could
        // not read this" is the correct answer rather than a failure.
        $this->actingAs($this->physician);

        $c = Livewire::test(Clinic\Explain::class)
            ->set('text', 'handwritten and blurry')
            ->call('run');

        $result = $c->get('result');
        $this->assertSame('low', $result['readingConfidence']);
        $this->assertTrue($result['askPharmacist']);
        $this->assertSame('', $result['medicineName']);
    }

    public function test_a_low_confidence_reading_cannot_be_attached_to_a_prescription(): void
    {
        $this->actingAs($this->physician);
        $patient = $this->patient();

        $rx = Prescription::create([
            'patient_id' => $patient->id, 'name' => 'Amlodipine',
            'times' => ['08:00'], 'active' => true, 'starts_on' => now()->subMonth(),
        ]);

        Livewire::test(Clinic\Explain::class)
            ->set('patientId', $patient->id)
            ->set('text', 'blurry')
            ->call('run')
            ->set('prescriptionId', $rx->id)
            ->call('attachToPrescription');

        $this->assertNull($rx->refresh()->ai_summary);
    }

    public function test_a_confident_reading_is_cached_on_the_prescription(): void
    {
        $this->actingAs($this->physician);
        $patient = $this->patient();

        $rx = Prescription::create([
            'patient_id' => $patient->id, 'name' => 'Amlodipine',
            'times' => ['08:00'], 'active' => true, 'starts_on' => now()->subMonth(),
        ]);

        Livewire::test(Clinic\Explain::class)
            ->set('patientId', $patient->id)
            ->set('text', 'AMLODIPINE 5MG')
            ->call('run')
            ->set('prescriptionId', $rx->id)
            ->call('attachToPrescription')
            ->assertDispatched('toast');

        $this->assertIsArray($rx->refresh()->ai_summary);
        $this->assertDatabaseHas('audits', ['action' => 'prescription.summary_attached']);
    }

    public function test_identification_of_a_pill_never_renders_one_certain_name(): void
    {
        $this->actingAs($this->physician);

        $c = Livewire::test(Clinic\Explain::class)
            ->set('mode', 'identify')
            ->set('text', 'small round white tablet')
            ->call('run');

        $this->assertSame('medium', $c->get('result')['confidence']);
        $c->assertSee('Possibilities')->assertSee('not an answer');
    }

    // ── Caregiver summary ────────────────────────────────────────────

    public function test_a_caregiver_summary_is_drafted_but_not_sent(): void
    {
        Notification::fake();
        $this->actingAs($this->physician);

        $patient = $this->patient(['caregiver_name' => 'Tolu Okafor', 'caregiver_email' => 'tolu@example.test']);

        $c = Livewire::test(Clinic\PatientDetail::class, ['patient' => $patient])
            ->call('draftCaregiverSummary');

        $this->assertNotNull($c->get('summary'));
        // A model drafts; a person signs it off.
        Notification::assertNothingSent();
        $this->assertDatabaseHas('audits', ['action' => 'ai.summary_drafted']);
    }

    public function test_sending_the_summary_mails_the_caregiver_and_clears_the_draft(): void
    {
        Notification::fake();
        $this->actingAs($this->physician);

        $patient = $this->patient(['caregiver_name' => 'Tolu Okafor', 'caregiver_email' => 'tolu@example.test']);

        Livewire::test(Clinic\PatientDetail::class, ['patient' => $patient])
            ->call('draftCaregiverSummary')
            ->call('sendCaregiverSummary')
            ->assertSet('summary', null);

        Notification::assertSentOnDemand(CaregiverSummary::class);
        $this->assertDatabaseHas('audits', ['action' => 'caregiver.summary_sent']);
    }

    public function test_no_caregiver_email_means_no_draft(): void
    {
        $this->actingAs($this->physician);

        Livewire::test(Clinic\PatientDetail::class, ['patient' => $this->patient()])
            ->call('draftCaregiverSummary')
            ->assertSet('summary', null)
            ->assertDispatched('toast');
    }
}
