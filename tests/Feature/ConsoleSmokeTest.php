<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Every clinic page renders, and none of them render to anyone signed out.
 */
class ConsoleSmokeTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = [
        'clinic.overview', 'clinic.patients', 'clinic.adherence', 'clinic.medicines',
        'clinic.complaints', 'clinic.outcomes', 'clinic.signals', 'clinic.outbox',
        'clinic.settings',
    ];

    private function staff(): User
    {
        $org = Organisation::create(['name' => 'Test Clinic', 'city' => 'Lagos']);

        $patient = Patient::create([
            'organisation_id' => $org->id, 'name' => 'Seed Patient',
            'email' => 'seed@example.test', 'condition' => 'Hypertension',
            'lang' => 'en', 'enrolled_at' => now()->subMonth(),
        ]);

        Prescription::create([
            'patient_id' => $patient->id, 'name' => 'Amlodipine',
            'strength' => '5 mg', 'times' => ['08:00', '20:00'], 'active' => true,
            'starts_on' => now()->subMonth(),
        ]);

        return User::create([
            'organisation_id' => $org->id, 'name' => 'Dr Test',
            'email' => 'dr@example.test', 'role' => 'physician',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_the_root_is_a_public_front_page_not_a_login_wall(): void
    {
        // It used to redirect straight to the sign-in form, which left the
        // product with no front door: someone sent the link had nowhere to
        // find out what this is before being asked for a password.
        $this->get('/')
            ->assertOk()
            ->assertSee('MedBuddy')
            ->assertSee(route('login'))       // clinic console
            ->assertSee(route('me.login'));   // patient app
    }

    public function test_the_front_page_renders_the_real_cohort_not_a_screenshot(): void
    {
        $this->staff();

        $this->get('/')
            ->assertOk()
            // The chart markup is the console's own, generated server-side.
            ->assertSee('<svg class="ch ', false)
            ->assertSee('patients in the seeded cohort');
    }

    public function test_every_clinic_page_is_closed_to_guests(): void
    {
        foreach (self::PAGES as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_every_clinic_page_renders_for_staff(): void
    {
        $user = $this->staff();

        foreach (self::PAGES as $route) {
            $this->actingAs($user)
                ->get(route($route))
                ->assertOk("Route {$route} did not render.");
        }
    }

    public function test_the_patient_record_renders_and_is_audited(): void
    {
        $user = $this->staff();
        $patient = Patient::first();

        $this->actingAs($user)->get(route('clinic.patient', $patient))->assertOk();

        // Every read of a patient record is written to an append-only table.
        // That is a regulatory requirement, not a feature.
        $this->assertDatabaseHas('audits', [
            'user_id' => $user->id,
            'action' => 'patient.view',
            'subject_id' => $patient->id,
        ]);
    }

    public function test_sign_in_and_sign_out(): void
    {
        $user = $this->staff();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('clinic.overview'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_bad_credentials_are_rejected(): void
    {
        $user = $this->staff();

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
