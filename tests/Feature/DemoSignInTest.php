<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Patient;
use App\Models\Prescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One-click patient sign-in, for demos.
 *
 * The patient app has no passwords, which left it with no way in that did not
 * involve reading a mailbox — so a demo of the half of the product patients
 * actually use was awkward to give. This panel fixes that, and in doing so
 * introduces a deliberate authentication bypass.
 *
 * It is therefore guarded twice, and this file exists for the guards rather
 * than the feature: off unless the flag is set, and never able to reach a
 * record that is not on the fabricated domain. The second guard is the one
 * that matters, because a flag left on after a pilot starts is a realistic
 * mistake and must not be a breach.
 */
class DemoSignInTest extends TestCase
{
    use RefreshDatabase;

    private Patient $seeded;
    private Patient $real;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organisation::create(['name' => 'Test Clinic', 'emergency_number' => '112']);

        $this->seeded = $this->patient($org->id, 'Grace Okafor', 'grace@patient.medbuddy.test', 'yo');
        $this->real   = $this->patient($org->id, 'A Real Person', 'someone@gmail.com', 'en');
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

        return $patient;
    }

    // ── The flag ─────────────────────────────────────────────────────

    public function test_it_is_off_by_default(): void
    {
        $this->assertFalse(config('medbuddy.demo_signin'), 'A sign-in bypass must be opt-in.');
    }

    public function test_the_panel_is_absent_when_the_flag_is_off(): void
    {
        config(['medbuddy.demo_signin' => false]);

        $this->get(route('me.login'))
            ->assertOk()
            ->assertDontSee('Open the demo as a patient')
            ->assertDontSee('grace@patient.medbuddy.test');
    }

    public function test_the_route_404s_when_the_flag_is_off(): void
    {
        // Not 403: with the flag off the endpoint should not appear to exist.
        config(['medbuddy.demo_signin' => false]);

        $this->post(route('me.demo'), ['patient' => $this->seeded->id])->assertNotFound();
        $this->assertNull(session('patient_id'));
    }

    // ── The domain guard ─────────────────────────────────────────────

    public function test_it_refuses_a_patient_who_is_not_fabricated(): void
    {
        /*
         | The guard that actually matters. A flag left on after a pilot begins
         | is a realistic mistake; it must be a dead end rather than a breach.
         */
        config(['medbuddy.demo_signin' => true]);

        $this->post(route('me.demo'), ['patient' => $this->real->id])->assertForbidden();

        $this->assertNull(session('patient_id'), 'No session may be created for a real patient.');
    }

    public function test_a_real_patient_is_never_listed(): void
    {
        config(['medbuddy.demo_signin' => true]);

        $this->get(route('me.login'))
            ->assertOk()
            ->assertSee('Grace Okafor')
            ->assertDontSee('A Real Person');
    }

    public function test_an_unknown_patient_is_a_404_not_a_session(): void
    {
        config(['medbuddy.demo_signin' => true]);

        $this->post(route('me.demo'), ['patient' => 999999])->assertNotFound();
        $this->assertNull(session('patient_id'));
    }

    // ── The feature ──────────────────────────────────────────────────

    public function test_one_tap_signs_you_in_as_a_seeded_patient(): void
    {
        config(['medbuddy.demo_signin' => true]);

        $this->post(route('me.demo'), ['patient' => $this->seeded->id])
            ->assertRedirect(route('me.today'));

        $this->assertSame($this->seeded->id, session('patient_id'));

        // And the app opens in that patient's language.
        $this->get(route('me.today'))
            ->assertOk()
            ->assertSee(__('me.today.title', [], 'yo'));
    }

    public function test_it_is_audited_like_every_other_way_into_a_record(): void
    {
        config(['medbuddy.demo_signin' => true]);

        $this->post(route('me.demo'), ['patient' => $this->seeded->id]);

        $this->assertDatabaseHas('audits', [
            'action' => 'patient.demo_signed_in',
            'subject_id' => $this->seeded->id,
        ]);
    }

    public function test_the_shortlist_covers_every_language_without_listing_the_whole_cohort(): void
    {
        config(['medbuddy.demo_signin' => true]);

        $org = Organisation::first();
        foreach (['yo', 'ha', 'ig', 'en'] as $lang) {
            for ($i = 0; $i < 5; $i++) {
                $this->patient($org->id, ucfirst($lang)." Person {$i}", "{$lang}{$i}@patient.medbuddy.test", $lang);
            }
        }

        $list = \App\Http\Controllers\PatientAuthController::demoPatients();

        // Two per language: enough to reach every translation, short enough to scan.
        $this->assertCount(8, $list);
        $this->assertEqualsCanonicalizing(['en', 'ha', 'ig', 'yo'], $list->pluck('lang')->unique()->sort()->values()->all());
    }

    public function test_a_patient_with_no_medicines_is_not_offered(): void
    {
        // Signing in as someone with an empty Today screen demonstrates nothing.
        config(['medbuddy.demo_signin' => true]);

        Patient::create([
            'organisation_id' => Organisation::first()->id,
            'name' => 'Empty Person', 'email' => 'empty@patient.medbuddy.test',
            'lang' => 'en', 'enrolled_at' => now(),
        ]);

        $this->get(route('me.login'))->assertOk()->assertDontSee('Empty Person');
    }
}
