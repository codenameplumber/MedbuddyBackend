<?php

namespace Tests\Feature;

use App\Models\Consent;
use App\Models\Organisation;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\PatientSignInLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Sign-up has two outcomes from one form, and they must not leak into each
 * other: a patient must never end up with a password, and a clinic admin must
 * never end up as a Patient row in somebody's cohort.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function patientPayload(array $overrides = []): array
    {
        return array_merge([
            'role'  => 'patient',
            'name'  => 'Grace Okafor',
            'email' => 'grace@example.test',
            'phone' => '0803 000 0000',
            'lang'  => 'yo',
            'messaging_consent' => '1',
        ], $overrides);
    }

    private function clinicPayload(array $overrides = []): array
    {
        return array_merge([
            'role'  => 'clinic',
            'name'  => 'Dr A. Adeyemi',
            'email' => 'dr@clinic.test',
            'organisation_name' => 'Surulere Family Clinic',
            'city'  => 'Lagos',
            'kind'  => 'hospital',
            'password' => 'correct-horse',
            'password_confirmation' => 'correct-horse',
        ], $overrides);
    }

    public function test_the_form_renders_for_a_guest(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create an account')
            ->assertSee('Pharmacy or hospital');
    }

    public function test_a_patient_signs_up_and_is_mailed_a_link_rather_than_given_a_password(): void
    {
        Notification::fake();

        $this->post(route('register'), $this->patientPayload())
            ->assertRedirect(route('me.login'));

        $patient = Patient::where('email', 'grace@example.test')->first();

        $this->assertNotNull($patient);
        $this->assertSame('yo', $patient->lang, 'The chosen language must survive sign-up.');
        $this->assertNotNull($patient->enrolled_at);

        // No User row: a patient has no console account and no password.
        $this->assertSame(0, User::where('email', 'grace@example.test')->count());

        Notification::assertSentOnDemand(PatientSignInLink::class);
    }

    public function test_the_two_consents_are_recorded_separately(): void
    {
        Notification::fake();

        $this->post(route('register'), $this->patientPayload(['research_consent' => null]));

        $patient = Patient::where('email', 'grace@example.test')->first();

        $this->assertTrue($patient->hasConsent('messaging', 'mail'));
        $this->assertFalse(
            $patient->hasConsent('research'),
            'Research consent is opt-in. Leaving the box alone must not grant it.',
        );

        // Both decisions exist as rows, so declining is recorded rather than absent.
        $this->assertSame(2, Consent::where('patient_id', $patient->id)->count());
    }

    public function test_a_patient_cannot_sign_up_without_agreeing_to_be_messaged(): void
    {
        Notification::fake();

        $this->post(route('register'), $this->patientPayload(['messaging_consent' => null]))
            ->assertSessionHasErrors('messaging_consent');

        $this->assertSame(0, Patient::count());
        Notification::assertNothingSent();
    }

    public function test_one_mailbox_cannot_become_two_patients(): void
    {
        // The address IS the credential, so a duplicate would make the sign-in
        // link ambiguous — it could open either record.
        Notification::fake();

        $this->post(route('register'), $this->patientPayload());
        $this->post(route('register'), $this->patientPayload(['name' => 'Someone Else']))
            ->assertSessionHasErrors('email');

        $this->assertSame(1, Patient::count());
    }

    public function test_a_clinic_signs_up_and_gets_an_organisation_and_an_admin(): void
    {
        $this->post(route('register'), $this->clinicPayload())
            ->assertRedirect(route('clinic.overview'));

        $user = User::where('email', 'dr@clinic.test')->first();

        $this->assertNotNull($user);
        $this->assertSame('admin', $user->role, 'Whoever creates the organisation is its first admin.');
        $this->assertTrue(Hash::check('correct-horse', $user->password));
        $this->assertSame('Surulere Family Clinic', $user->organisation->name);

        // And is not also a patient in their own cohort.
        $this->assertSame(0, Patient::where('email', 'dr@clinic.test')->count());
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_clinic_signup_with_a_mismatched_password_creates_nothing(): void
    {
        $this->post(route('register'), $this->clinicPayload(['password_confirmation' => 'different']))
            ->assertSessionHasErrors('password');

        // The transaction must take the organisation with it.
        $this->assertSame(0, User::count());
        $this->assertSame(0, Organisation::where('name', 'Surulere Family Clinic')->count());
    }

    public function test_a_signed_in_user_is_not_shown_the_signup_form(): void
    {
        $org = Organisation::create(['name' => 'Test', 'kind' => 'hospital']);
        $user = User::create([
            'organisation_id' => $org->id, 'name' => 'Staff',
            'email' => 'staff@test.test', 'role' => 'nurse', 'password' => Hash::make('password'),
        ]);

        $this->actingAs($user)->get(route('register'))->assertRedirect();
    }
}
