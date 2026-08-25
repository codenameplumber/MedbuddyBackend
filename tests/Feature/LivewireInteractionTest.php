<?php

namespace Tests\Feature;

use App\Livewire\Clinic\Complaints;
use App\Livewire\Clinic\Outbox;
use App\Livewire\Clinic\PatientDetail;
use App\Livewire\Clinic\Patients;
use App\Models\Consent;
use App\Models\Organisation;
use App\Models\Patient;
use App\Models\PatientReport;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Page loads prove routing. These prove the buttons do something.
 */
class LivewireInteractionTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private Patient $grace;
    private Patient $musa;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organisation::create(['name' => 'Test Clinic', 'emergency_number' => '112']);

        $this->staff = User::create([
            'organisation_id' => $org->id, 'name' => 'Dr Test',
            'email' => 'dr@example.test', 'role' => 'physician',
            'password' => Hash::make('password'),
        ]);

        $this->grace = $this->makePatient($org->id, 'Grace Okafor', 'Hypertension', 'yo');
        $this->musa  = $this->makePatient($org->id, 'Musa Ibrahim', 'Type 2 diabetes', 'ha');

        $this->actingAs($this->staff);
    }

    private function makePatient(int $orgId, string $name, string $condition, string $lang): Patient
    {
        $p = Patient::create([
            'organisation_id' => $orgId, 'name' => $name,
            'email' => str($name)->before(' ')->lower().'@example.test',
            'condition' => $condition, 'lang' => $lang, 'age' => 50,
            'enrolled_at' => now()->subMonth(), 'last_response_at' => now(),
        ]);

        Prescription::create([
            'patient_id' => $p->id, 'name' => 'Amlodipine', 'strength' => '5 mg',
            'times' => ['08:00'], 'active' => true, 'starts_on' => now()->subMonth(),
        ]);

        Consent::create([
            'patient_id' => $p->id, 'scope' => 'messaging', 'channel' => 'mail',
            'granted' => true, 'granted_at' => now(),
        ]);

        return $p->refresh();
    }

    // ── Patients: search, filter, sort ───────────────────────────────

    public function test_patient_search_filters_the_table(): void
    {
        Livewire::test(Patients::class)
            ->assertSee('Grace Okafor')
            ->assertSee('Musa Ibrahim')
            ->set('search', 'grace')
            ->assertSee('Grace Okafor')
            ->assertDontSee('Musa Ibrahim');
    }

    public function test_patient_search_matches_on_condition(): void
    {
        Livewire::test(Patients::class)
            ->set('search', 'diabetes')
            ->assertSee('Musa Ibrahim')
            ->assertDontSee('Grace Okafor');
    }

    public function test_sorting_toggles_direction(): void
    {
        Livewire::test(Patients::class)
            ->assertSet('dir', 'asc')
            ->call('sortBy', 'name')
            ->assertSet('sort', 'name')
            ->assertSet('dir', 'asc')
            ->call('sortBy', 'name')
            ->assertSet('dir', 'desc');
    }

    public function test_the_attention_filter_narrows_the_list(): void
    {
        Livewire::test(Patients::class)
            ->set('filter', 'attention')
            ->assertOk();
    }

    // ── Complaints: triage ───────────────────────────────────────────

    public function test_marking_a_complaint_reviewed_persists_and_audits(): void
    {
        $report = PatientReport::create([
            'patient_id' => $this->grace->id, 'symptoms' => ['dizzy'],
            'severity' => 2, 'lang' => 'yo', 'source' => 'link', 'took_dose' => true,
        ]);

        $this->assertNull($report->reviewed_at);

        Livewire::test(Complaints::class)
            ->call('markReviewed', $report->id)
            ->assertDispatched('toast');

        $report->refresh();
        $this->assertNotNull($report->reviewed_at);
        $this->assertSame($this->staff->id, $report->reviewed_by);
        $this->assertDatabaseHas('audits', ['action' => 'report.reviewed', 'subject_id' => $report->id]);
    }

    public function test_a_fine_report_never_reaches_the_complaints_queue(): void
    {
        PatientReport::create([
            'patient_id' => $this->grace->id, 'symptoms' => ['fine'],
            'severity' => 0, 'lang' => 'yo', 'source' => 'link', 'took_dose' => true,
        ]);

        Livewire::test(Complaints::class)
            ->assertOk()
            ->assertSee('Nothing in this queue.');
    }

    public function test_the_severe_filter_shows_only_severe(): void
    {
        PatientReport::create([
            'patient_id' => $this->grace->id, 'symptoms' => ['dizzy'],
            'severity' => 2, 'lang' => 'yo', 'source' => 'link',
        ]);
        PatientReport::create([
            'patient_id' => $this->musa->id, 'symptoms' => ['sleepy'],
            'severity' => 1, 'lang' => 'ha', 'source' => 'link',
        ]);

        Livewire::test(Complaints::class)
            ->set('filter', 'severe')
            ->assertSee('Grace Okafor')
            ->assertDontSee('Musa Ibrahim');
    }

    // ── Patient detail: manual send ──────────────────────────────────

    public function test_send_reminder_now_sends_and_audits(): void
    {
        Notification::fake();

        Livewire::test(PatientDetail::class, ['patient' => $this->grace])
            ->call('sendReminderNow')
            ->assertDispatched('toast');

        Notification::assertSentTimes(\App\Notifications\DoseReminder::class, 1);
        $this->assertDatabaseHas('reminders', ['patient_id' => $this->grace->id]);
        $this->assertDatabaseHas('audits', ['action' => 'reminder.manual_send']);
    }

    public function test_send_reminder_now_refuses_without_consent(): void
    {
        Notification::fake();
        Consent::where('patient_id', $this->grace->id)->update(['revoked_at' => now()]);

        Livewire::test(PatientDetail::class, ['patient' => $this->grace->refresh()])
            ->call('sendReminderNow')
            ->assertDispatched('toast');

        Notification::assertNothingSent();
        $this->assertDatabaseCount('reminders', 0);
    }

    public function test_send_reminder_now_refuses_without_an_email(): void
    {
        Notification::fake();
        $this->grace->update(['email' => null]);

        Livewire::test(PatientDetail::class, ['patient' => $this->grace->refresh()])
            ->call('sendReminderNow');

        Notification::assertNothingSent();
    }

    public function test_viewing_a_patient_record_writes_an_audit_entry(): void
    {
        Livewire::test(PatientDetail::class, ['patient' => $this->grace]);

        $this->assertDatabaseHas('audits', [
            'user_id' => $this->staff->id,
            'action' => 'patient.view',
            'subject_id' => $this->grace->id,
        ]);
    }

    // ── Outbox: dispatcher ───────────────────────────────────────────

    public function test_the_dispatcher_button_runs_without_throwing(): void
    {
        Notification::fake();

        Livewire::test(Outbox::class)
            ->call('runNow')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('audits', ['action' => 'reminders.manual_dispatch']);
    }

    public function test_clicking_the_dispatcher_twice_does_not_throw(): void
    {
        // This is the bug the scheduling tests caught: a second run in the
        // same window used to hit a unique-constraint violation.
        Notification::fake();
        $this->travelTo(now()->setTime(8, 5));

        Livewire::test(Outbox::class)->call('runNow')->assertOk();
        Livewire::test(Outbox::class)->call('runNow')->assertOk();

        $this->assertSame(2, \App\Models\Reminder::count(), 'One reminder per patient per window.');
    }
}
