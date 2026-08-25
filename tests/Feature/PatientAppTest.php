<?php

namespace Tests\Feature;

use App\Jobs\DispatchDueReminders;
use App\Livewire\Me;
use App\Models\Consent;
use App\Models\DoseEvent;
use App\Models\Organisation;
use App\Models\Patient;
use App\Models\Prescription;
use App\Notifications\DoseReminder;
use App\Notifications\PatientSignInLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The patient app has no password, so the tests that matter most are the ones
 * proving a URL alone cannot reach someone else's record.
 */
class PatientAppTest extends TestCase
{
    use RefreshDatabase;

    private Patient $me;
    private Patient $someoneElse;
    private Prescription $rx;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organisation::create(['name' => 'Test Clinic', 'emergency_number' => '112']);

        $this->me = Patient::create([
            'organisation_id' => $org->id, 'name' => 'Grace Okafor', 'email' => 'grace@example.test',
            'lang' => 'yo', 'condition' => 'Hypertension', 'age' => 67, 'enrolled_at' => now()->subMonth(),
        ]);

        $this->someoneElse = Patient::create([
            'organisation_id' => $org->id, 'name' => 'Musa Ibrahim', 'email' => 'musa@example.test',
            'lang' => 'ha', 'condition' => 'Type 2 diabetes', 'enrolled_at' => now()->subMonth(),
        ]);

        $this->rx = Prescription::create([
            'patient_id' => $this->me->id, 'name' => 'Amlodipine', 'strength' => '5 mg',
            'times' => ['08:00', '20:00'], 'active' => true, 'starts_on' => now()->subMonth(),
        ]);

        Consent::create([
            'patient_id' => $this->me->id, 'scope' => 'messaging', 'channel' => 'mail',
            'granted' => true, 'granted_at' => now(),
        ]);
    }

    private function signIn(?Patient $p = null): void
    {
        session(['patient_id' => ($p ?? $this->me)->id]);
    }

    // ── Sign-in ──────────────────────────────────────────────────────

    public function test_the_sign_in_page_renders_without_a_session(): void
    {
        $this->get(route('me.login'))->assertOk();
    }

    public function test_requesting_a_link_emails_a_signed_url(): void
    {
        Notification::fake();

        $this->post(route('me.login.send'), ['email' => 'grace@example.test'])->assertRedirect();

        Notification::assertSentOnDemand(PatientSignInLink::class);
        $this->assertDatabaseHas('audits', ['action' => 'patient.signin_link_sent']);
    }

    public function test_an_unknown_address_gets_the_same_answer_and_no_mail(): void
    {
        // Never reveal whether an address is enrolled.
        Notification::fake();

        $this->post(route('me.login.send'), ['email' => 'stranger@example.test'])
            ->assertRedirect()
            ->assertSessionHas('sent', true);

        Notification::assertNothingSent();
    }

    public function test_an_unsigned_sign_in_link_is_rejected(): void
    {
        $this->get('/me/auth/'.$this->me->id)->assertForbidden();
    }

    public function test_a_tampered_sign_in_link_is_rejected(): void
    {
        $url = URL::temporarySignedRoute('me.auth', now()->addMinutes(30), ['patient' => $this->me->id]);

        // Swap the patient id but keep the signature.
        $this->get(str_replace('/me/auth/'.$this->me->id, '/me/auth/'.$this->someoneElse->id, $url))
            ->assertForbidden();
    }

    public function test_an_expired_sign_in_link_is_rejected(): void
    {
        $url = URL::temporarySignedRoute('me.auth', now()->addMinutes(30), ['patient' => $this->me->id]);

        $this->travelTo(now()->addMinutes(31));

        $this->get($url)->assertForbidden();
    }

    public function test_a_valid_link_signs_in_and_lands_on_today(): void
    {
        $url = URL::temporarySignedRoute('me.auth', now()->addMinutes(30), ['patient' => $this->me->id]);

        $this->get($url)->assertRedirect(route('me.today'));
        $this->assertSame($this->me->id, session('patient_id'));
        $this->assertDatabaseHas('audits', ['action' => 'patient.signed_in']);
    }

    public function test_every_patient_screen_redirects_when_not_signed_in(): void
    {
        foreach (['me.today', 'me.medicines', 'me.ask', 'me.scan', 'me.history', 'me.report', 'me.profile', 'me.emergency'] as $route) {
            $this->get(route($route))->assertRedirect(route('me.login'));
        }
    }

    public function test_signing_out_clears_the_session(): void
    {
        $this->signIn();
        $this->post(route('me.logout'))->assertRedirect(route('me.login'));
        $this->assertNull(session('patient_id'));
    }

    // ── Today ────────────────────────────────────────────────────────

    public function test_today_lists_the_scheduled_doses(): void
    {
        $this->signIn();

        Livewire::test(Me\Today::class)
            ->assertOk()
            ->assertSee('Amlodipine')
            ->assertSee('08:00')
            ->assertSee('20:00');
    }

    public function test_ticking_a_dose_records_it_and_unticking_removes_it(): void
    {
        $this->signIn();

        Livewire::test(Me\Today::class)->call('toggle', $this->rx->id, '08:00');

        $this->assertDatabaseHas('dose_events', [
            'prescription_id' => $this->rx->id, 'dose_time' => '08:00',
            'status' => 'taken', 'source' => 'app',
        ]);

        Livewire::test(Me\Today::class)->call('toggle', $this->rx->id, '08:00');

        // Doses are derived, so an untaken dose is the absence of a row rather
        // than a row saying "not taken".
        $this->assertSame(0, DoseEvent::count());
    }

    public function test_a_patient_cannot_tick_off_someone_elses_prescription(): void
    {
        $other = Prescription::create([
            'patient_id' => $this->someoneElse->id, 'name' => 'Metformin',
            'times' => ['08:00'], 'active' => true, 'starts_on' => now()->subMonth(),
        ]);

        $this->signIn();

        Livewire::test(Me\Today::class)
            ->call('toggle', $other->id, '08:00')
            ->assertForbidden();

        $this->assertSame(0, DoseEvent::count());
    }

    public function test_a_patient_cannot_open_someone_elses_medicine(): void
    {
        $other = Prescription::create([
            'patient_id' => $this->someoneElse->id, 'name' => 'Metformin',
            'times' => ['08:00'], 'active' => true, 'starts_on' => now()->subMonth(),
        ]);

        $this->signIn();

        $this->get(route('me.medicine', $other))->assertForbidden();
    }

    // ── Ask: the triage gate ─────────────────────────────────────────

    public function test_a_crisis_question_never_reaches_the_model(): void
    {
        $this->signIn();

        Livewire::test(Me\Ask::class)
            ->set('question', 'I am having trouble breathing since this morning')
            ->call('send')
            ->assertSet('crisis', 'breathing');

        Livewire::test(Me\Ask::class)
            ->set('question', 'my chest is tight')
            ->call('send')
            ->assertSet('crisis', 'cardiac');

        // The question is echoed but no answer was generated.
        $this->assertDatabaseHas('audits', ['action' => 'patient.triage_tripped']);
    }

    public function test_the_gate_fires_on_a_question_asked_in_yoruba(): void
    {
        $this->signIn();

        Livewire::test(Me\Ask::class)
            ->set('question', 'Mi o le mi')
            ->call('send')
            ->assertNotSet('crisis', null);
    }

    public function test_an_ordinary_question_gets_an_answer(): void
    {
        $this->signIn();

        $c = Livewire::test(Me\Ask::class)
            ->set('question', 'Should I take this with food?')
            ->call('send')
            ->assertSet('crisis', null);

        $thread = $c->get('thread');
        $this->assertCount(2, $thread);
        $this->assertSame('ai', $thread[1]['role']);
        $this->assertNotEmpty($thread[1]['text']);
    }

    // ── Report ───────────────────────────────────────────────────────

    public function test_a_report_is_saved_with_app_provenance(): void
    {
        $this->signIn();

        Livewire::test(Me\Report::class)
            ->set('symptoms', ['dizzy'])
            ->set('severity', 2)
            ->set('note', 'It happens when I stand up')
            ->call('submit')
            ->assertSet('sent', true);

        $this->assertDatabaseHas('patient_reports', [
            'patient_id' => $this->me->id, 'severity' => 2, 'source' => 'app', 'lang' => 'yo',
        ]);

        $this->assertNotNull($this->me->refresh()->last_response_at);
    }

    public function test_feeling_fine_is_exclusive(): void
    {
        $this->signIn();

        Livewire::test(Me\Report::class)
            ->set('symptoms', ['dizzy'])
            ->set('symptoms', ['dizzy', 'fine'])
            ->assertSet('symptoms', ['fine']);
    }

    public function test_feeling_fine_carries_no_severity(): void
    {
        $this->signIn();

        Livewire::test(Me\Report::class)->set('symptoms', ['fine'])->set('severity', 3)->call('submit');

        $this->assertDatabaseHas('patient_reports', ['patient_id' => $this->me->id, 'severity' => 0]);
    }

    public function test_a_note_describing_a_crisis_is_flagged_on_the_report(): void
    {
        $this->signIn();

        Livewire::test(Me\Report::class)
            ->set('symptoms', ['other'])
            ->set('note', 'my face is swelling and I cannot breathe')
            ->call('submit')
            ->assertNotSet('crisis', null);

        $this->assertDatabaseMissing('patient_reports', ['triage_category' => null, 'source' => 'app']);
    }

    // ── Profile: consent the patient can actually reach ──────────────

    public function test_a_patient_can_revoke_their_own_reminders_and_the_mail_stops(): void
    {
        Notification::fake();
        $this->signIn();

        Livewire::test(Me\Profile::class)->set('messaging', false);

        $this->assertFalse($this->me->refresh()->hasConsent('messaging', 'mail'));
        $this->assertDatabaseHas('audits', ['action' => 'consent.revoked']);

        // The claim that matters: the dispatcher genuinely stops sending.
        $this->travelTo(now()->setTime(8, 5));
        (new DispatchDueReminders)->handle(app(\App\Services\ScheduleService::class));

        Notification::assertNothingSent();
    }

    public function test_a_patient_can_turn_reminders_back_on(): void
    {
        Notification::fake();
        $this->signIn();

        Livewire::test(Me\Profile::class)->set('messaging', false);
        Livewire::test(Me\Profile::class)->set('messaging', true);

        $this->assertTrue($this->me->refresh()->hasConsent('messaging', 'mail'));

        $this->travelTo(now()->setTime(8, 5));
        (new DispatchDueReminders)->handle(app(\App\Services\ScheduleService::class));

        Notification::assertSentTimes(DoseReminder::class, 1);
    }

    public function test_changing_language_persists_and_is_audited(): void
    {
        $this->signIn();

        Livewire::test(Me\Profile::class)->set('lang', 'ig');

        $this->assertSame('ig', $this->me->refresh()->lang);
        $this->assertDatabaseHas('audits', ['action' => 'patient.language_changed']);
    }

    // ── SOS ──────────────────────────────────────────────────────────

    public function test_the_emergency_screen_shows_the_number_and_the_medicine_list(): void
    {
        $this->signIn();

        $this->get(route('me.emergency'))
            ->assertOk()
            ->assertSee('112')
            ->assertSee('Amlodipine');
    }

    public function test_the_remaining_patient_screens_render(): void
    {
        $this->signIn();

        foreach (['me.medicines', 'me.scan', 'me.history'] as $route) {
            $this->get(route($route))->assertOk();
        }

        $this->get(route('me.medicine', $this->rx))->assertOk();
    }
}
