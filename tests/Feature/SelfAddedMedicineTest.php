<?php

namespace Tests\Feature;

use App\Livewire\Me;
use App\Models\Organisation;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A patient adding a medicine to their own list.
 *
 * The screen existed in the prototype and its translations were ported, but
 * the platform never had it — a patient could see only what a clinic had
 * prescribed, so anything bought over a counter was invisible to the
 * interaction and adherence views that are the point of the product.
 *
 * The thing that must not break: a self-added medicine has to stay
 * distinguishable from a prescribed one.
 */
class SelfAddedMedicineTest extends TestCase
{
    use RefreshDatabase;

    private Patient $me;
    private User $physician;

    protected function setUp(): void
    {
        parent::setUp();

        $org = Organisation::create(['name' => 'Test Clinic', 'emergency_number' => '112']);

        $this->physician = User::create([
            'organisation_id' => $org->id, 'name' => 'Dr Test', 'email' => 'dr@test.test',
            'role' => 'physician', 'password' => Hash::make('password'),
        ]);

        $this->me = Patient::create([
            'organisation_id' => $org->id, 'name' => 'Grace Okafor', 'email' => 'grace@example.test',
            'lang' => 'en', 'condition' => 'Hypertension', 'enrolled_at' => now()->subMonth(),
        ]);
    }

    private function signIn(): void
    {
        session(['patient_id' => $this->me->id]);
    }

    public function test_the_screen_is_closed_without_a_session(): void
    {
        $this->get(route('me.medicine.add'))->assertRedirect(route('me.login'));
    }

    public function test_a_patient_adds_a_medicine_and_it_appears_on_their_list(): void
    {
        $this->signIn();

        Livewire::test(Me\AddMedicine::class)
            ->set('name', 'Paracetamol')
            ->set('strength', '500 mg')
            ->set('form', 'tablet')
            ->set('purpose', 'headaches')
            ->set('times', ['20:00', '08:00'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('me.medicines'));

        $rx = Prescription::where('patient_id', $this->me->id)->first();

        $this->assertSame('Paracetamol', $rx->name);
        $this->assertSame('500 mg', $rx->strength);
        // Sorted, so Today shows the doses in the order they are taken.
        $this->assertSame(['08:00', '20:00'], $rx->times);
        $this->assertTrue($rx->active);
    }

    public function test_a_self_added_medicine_is_distinguishable_from_a_prescribed_one(): void
    {
        $this->signIn();

        Prescription::create([
            'patient_id' => $this->me->id, 'prescribed_by' => $this->physician->id,
            'name' => 'Amlodipine', 'strength' => '5 mg', 'times' => ['08:00'],
            'active' => true, 'starts_on' => now()->subMonth(),
        ]);

        Livewire::test(Me\AddMedicine::class)
            ->set('name', 'Paracetamol')
            ->set('times', ['08:00'])
            ->call('save');

        $prescribed = Prescription::where('name', 'Amlodipine')->first();
        $selfAdded  = Prescription::where('name', 'Paracetamol')->first();

        $this->assertNotNull($prescribed->prescribed_by, 'A prescribed medicine records who wrote it.');
        $this->assertNull($selfAdded->prescribed_by, 'Nothing a patient adds may look prescribed.');

        // And the list says so, rather than leaving staff to infer it.
        Livewire::test(Me\Medicines::class)
            ->assertSee('Paracetamol')
            ->assertSee('added by you');
    }

    public function test_a_medicine_needs_a_name_and_at_least_one_time(): void
    {
        $this->signIn();

        Livewire::test(Me\AddMedicine::class)
            ->set('name', '')
            ->call('save')
            ->assertHasErrors('name');

        Livewire::test(Me\AddMedicine::class)
            ->set('name', 'Paracetamol')
            ->set('times', [])
            ->call('save')
            ->assertHasErrors('times');

        $this->assertSame(0, Prescription::count());
    }

    public function test_the_last_dose_time_cannot_be_removed(): void
    {
        // A medicine with no schedule generates no reminder and never appears
        // on Today, which reads as the app having silently lost it.
        $this->signIn();

        Livewire::test(Me\AddMedicine::class)
            ->call('removeTime', 0)
            ->assertSet('times', ['08:00']);
    }

    public function test_duplicate_dose_times_are_collapsed(): void
    {
        $this->signIn();

        Livewire::test(Me\AddMedicine::class)
            ->set('name', 'Paracetamol')
            ->set('times', ['08:00', '08:00', '20:00'])
            ->call('save')
            ->assertHasNoErrors();

        // Two rows at 08:00 would mean two reminders in the same window,
        // and the dose_events unique index would reject the second tick.
        $this->assertSame(['08:00', '20:00'], Prescription::first()->times);
    }

    public function test_a_self_added_medicine_is_counted_in_the_patients_doses(): void
    {
        $this->signIn();

        Livewire::test(Me\AddMedicine::class)
            ->set('name', 'Paracetamol')
            ->set('times', ['08:00', '20:00'])
            ->call('save');

        $doses = app(\App\Services\ScheduleService::class)->dosesForDay($this->me->fresh(), today());

        $this->assertCount(2, $doses, 'A medicine the patient added still has to appear on Today.');
    }

    public function test_adding_a_medicine_is_audited(): void
    {
        $this->signIn();

        Livewire::test(Me\AddMedicine::class)
            ->set('name', 'Paracetamol')
            ->set('times', ['08:00'])
            ->call('save');

        $this->assertDatabaseHas('audits', ['action' => 'prescription.self_added']);
    }
}
