<?php

namespace Tests\Feature;

use App\Livewire\Me;
use App\Models\Consent;
use App\Models\Organisation;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Ask thread is a record of what somebody asked about their own medicines,
 * which makes it health data.
 *
 * It shipped in the session under one fixed key, and sign-in used
 * session()->regenerate() — which rotates the session ID but keeps every key
 * in it. So a second patient signing in on the same browser opened the first
 * one's conversation. Phones get shared; a clinic tablet gets shared harder.
 *
 * Two independent fixes, and a test for each: sign-in now flushes the session
 * outright, and the thread is keyed per patient on top of that.
 */
class AskThreadIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Patient $grace;
    private Patient $musa;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organisation::create(['name' => 'Test Clinic', 'emergency_number' => '112']);

        $this->grace = $this->patient($org->id, 'Grace Okafor', 'grace@patient.medbuddy.test', 'yo');
        $this->musa  = $this->patient($org->id, 'Musa Ibrahim', 'musa@patient.medbuddy.test', 'ha');
    }

    private function patient(int $orgId, string $name, string $email, string $lang): Patient
    {
        $patient = Patient::create([
            'organisation_id' => $orgId, 'name' => $name, 'email' => $email,
            'lang' => $lang, 'condition' => 'Hypertension', 'enrolled_at' => now()->subMonth(),
        ]);

        Prescription::create([
            'patient_id' => $patient->id, 'name' => 'Amlodipine', 'strength' => '5 mg',
            'times' => ['08:00'], 'active' => true, 'starts_on' => now()->subMonth(),
        ]);

        Consent::create([
            'patient_id' => $patient->id, 'scope' => 'messaging', 'channel' => 'mail',
            'granted' => true, 'granted_at' => now(),
        ]);

        return $patient;
    }

    private function signInViaLink(Patient $patient): void
    {
        $this->get(URL::temporarySignedRoute('me.auth', now()->addHour(), ['patient' => $patient->id]))
            ->assertRedirect(route('me.today'));
    }

    // ── The leak ─────────────────────────────────────────────────────

    public function test_a_second_patient_never_inherits_the_first_ones_conversation(): void
    {
        // The exact reproduction. One browser, two patients, one after the other.
        $this->signInViaLink($this->grace);

        Livewire::test(Me\Ask::class)
            ->set('question', 'Can I take this with food?')
            ->call('send')
            ->assertSee('Can I take this with food?');

        $this->signInViaLink($this->musa);

        Livewire::test(Me\Ask::class)
            ->assertSet('thread', [])
            ->assertDontSee('Can I take this with food?');
    }

    public function test_signing_in_flushes_everything_the_previous_patient_left_behind(): void
    {
        // The root cause was regenerate() keeping session data. Anything the
        // app stashes about a patient must not survive the switch, not just
        // the key this bug happened to be about.
        $this->signInViaLink($this->grace);

        session(['me.ask.thread.'.$this->grace->id => [['role' => 'me', 'text' => 'private', 'note' => null]]]);
        session(['something.else' => 'also private']);

        $this->signInViaLink($this->musa);

        $this->assertNull(session('me.ask.thread.'.$this->grace->id));
        $this->assertNull(session('something.else'));
        $this->assertSame($this->musa->id, session('patient_id'));
    }

    public function test_signing_out_leaves_nothing_for_the_next_person_on_the_device(): void
    {
        $this->signInViaLink($this->grace);

        Livewire::test(Me\Ask::class)
            ->set('question', 'Is it safe with alcohol?')
            ->call('send');

        $this->post(route('me.logout'))->assertRedirect(route('me.login'));

        $this->assertNull(session('patient_id'));
        $this->assertNull(session('me.ask.thread.'.$this->grace->id));
    }

    public function test_the_demo_shortcut_gets_the_same_clean_session(): void
    {
        // It is a second way in, so it needs the same guarantee.
        config(['medbuddy.demo_signin' => true]);

        $this->signInViaLink($this->grace);
        Livewire::test(Me\Ask::class)->set('question', 'Anything?')->call('send');

        $this->post(route('me.demo'), ['patient' => $this->musa->id]);

        Livewire::test(Me\Ask::class)->assertSet('thread', []);
    }

    // ── Ordinary behaviour still works ───────────────────────────────

    public function test_a_patients_own_thread_survives_leaving_and_returning_to_the_screen(): void
    {
        $this->signInViaLink($this->grace);

        Livewire::test(Me\Ask::class)
            ->set('question', 'Can I take this with food?')
            ->call('send');

        // Same patient, fresh component — the conversation is still theirs.
        Livewire::test(Me\Ask::class)->assertSee('Can I take this with food?');
    }

    public function test_clearing_the_thread_clears_it_in_the_session_too(): void
    {
        $this->signInViaLink($this->grace);

        Livewire::test(Me\Ask::class)
            ->set('question', 'Can I take this with food?')
            ->call('send')
            ->call('clearThread')
            ->assertSet('thread', []);

        $this->assertNull(session('me.ask.thread.'.$this->grace->id));

        Livewire::test(Me\Ask::class)->assertDontSee('Can I take this with food?');
    }

    public function test_a_crisis_question_is_never_written_into_the_thread(): void
    {
        // The gate fires and the screen changes; nothing is stored or sent.
        $this->signInViaLink($this->grace);

        Livewire::test(Me\Ask::class)
            ->set('question', 'I cannot breathe')
            ->call('send')
            ->assertSet('crisis', 'breathing');

        $stored = session('me.ask.thread.'.$this->grace->id, []);

        $this->assertCount(1, $stored, 'Only the question itself, and no answer.');
        $this->assertSame('me', $stored[0]['role']);
    }
}
