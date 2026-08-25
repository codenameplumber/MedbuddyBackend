<?php

namespace Tests\Feature;

use App\Livewire\Me;
use App\Models\Consent;
use App\Models\Organisation;
use App\Models\Patient;
use App\Models\PatientReport;
use App\Models\Prescription;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A voice note is health data. These tests are less about the upload working
 * and more about it not becoming a guessable public URL.
 */
class VoiceNoteTest extends TestCase
{
    use RefreshDatabase;

    private Organisation $org;
    private Patient $patient;
    private Prescription $rx;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('voice');

        $this->org = Organisation::create(['name' => 'Test Clinic', 'emergency_number' => '112']);

        $this->patient = Patient::create([
            'organisation_id' => $this->org->id, 'name' => 'Grace Okafor',
            'email' => 'grace@example.test', 'lang' => 'yo', 'condition' => 'Hypertension',
            'enrolled_at' => now()->subMonth(),
        ]);

        $this->rx = Prescription::create([
            'patient_id' => $this->patient->id, 'name' => 'Amlodipine', 'strength' => '5 mg',
            'times' => ['08:00'], 'active' => true, 'starts_on' => now()->subMonth(),
        ]);

        Consent::create([
            'patient_id' => $this->patient->id, 'scope' => 'messaging', 'channel' => 'mail',
            'granted' => true, 'granted_at' => now(),
        ]);
    }

    private function reminder(): Reminder
    {
        return Reminder::create([
            'patient_id' => $this->patient->id, 'dose_date' => today()->toDateString(),
            'dose_time' => '08:00', 'slot' => 'morning', 'channel' => 'mail',
            'to' => $this->patient->email, 'sent_at' => now(),
        ]);
    }

    // ── Capture ──────────────────────────────────────────────────────

    public function test_a_voice_note_posted_from_a_reminder_link_is_stored_privately(): void
    {
        $reminder = $this->reminder();

        $this->post(
            URL::signedRoute('respond.store', ['reminder' => $reminder->id]),
            [
                'took' => 'yes',
                'symptoms' => ['dizzy'],
                'severity' => 2,
                'audio' => UploadedFile::fake()->create('note.webm', 40, 'audio/webm'),
            ],
        )->assertOk();

        $report = PatientReport::firstOrFail();

        $this->assertNotNull($report->audio_path);
        Storage::disk('voice')->assertExists($report->audio_path);

        // Not on the public disk, so there is no guessable URL for it.
        $this->assertStringNotContainsString('public', $report->audio_path);
    }

    public function test_a_voice_note_alone_still_produces_a_report(): void
    {
        // Someone who cannot read the labels holds the button and says nothing
        // else. That must not be silently discarded.
        $reminder = $this->reminder();

        $this->post(
            URL::signedRoute('respond.store', ['reminder' => $reminder->id]),
            ['took' => 'yes', 'audio' => UploadedFile::fake()->create('note.webm', 20, 'audio/webm')],
        )->assertOk();

        $this->assertSame(1, PatientReport::count());
        $this->assertNotNull(PatientReport::first()->audio_path);
    }

    public function test_a_non_audio_upload_is_rejected(): void
    {
        $reminder = $this->reminder();

        $this->post(
            URL::signedRoute('respond.store', ['reminder' => $reminder->id]),
            ['took' => 'yes', 'audio' => UploadedFile::fake()->create('payload.php', 8, 'text/x-php')],
        )->assertSessionHasErrors('audio');

        $this->assertSame(0, PatientReport::count());
    }

    public function test_a_patient_can_attach_a_voice_note_from_the_app(): void
    {
        session(['patient_id' => $this->patient->id]);

        Livewire::test(Me\Report::class)
            ->set('symptoms', ['swelling'])
            ->set('audio', UploadedFile::fake()->create('note.webm', 30, 'audio/webm'))
            ->call('submit')
            ->assertSet('sent', true);

        $report = PatientReport::firstOrFail();
        $this->assertNotNull($report->audio_path);
        Storage::disk('voice')->assertExists($report->audio_path);
    }

    // ── Playback ─────────────────────────────────────────────────────

    private function report(): PatientReport
    {
        $path = UploadedFile::fake()->create('note.webm', 20, 'audio/webm')
            ->store('p'.$this->patient->id, 'voice');

        return PatientReport::create([
            'patient_id' => $this->patient->id, 'symptoms' => ['dizzy'],
            'severity' => 2, 'lang' => 'yo', 'source' => 'app', 'audio_path' => $path,
        ]);
    }

    public function test_playback_requires_a_signed_in_staff_member(): void
    {
        $this->get(route('clinic.voice', $this->report()))->assertRedirect(route('login'));
    }

    public function test_staff_can_play_a_voice_note_and_the_play_is_audited(): void
    {
        $staff = User::create([
            'organisation_id' => $this->org->id, 'name' => 'C. Eze',
            'email' => 'nurse@example.test', 'role' => 'nurse', 'password' => Hash::make('password'),
        ]);

        $report = $this->report();

        $this->actingAs($staff)->get(route('clinic.voice', $report))->assertOk();

        // Listening to it IS reading the record, so it lands in the audit log.
        $this->assertDatabaseHas('audits', [
            'action' => 'report.voice_played', 'subject_id' => $report->id, 'user_id' => $staff->id,
        ]);
    }

    public function test_staff_from_another_organisation_cannot_play_it(): void
    {
        $other = Organisation::create(['name' => 'Other Clinic']);

        $stranger = User::create([
            'organisation_id' => $other->id, 'name' => 'Stranger',
            'email' => 'stranger@example.test', 'role' => 'admin', 'password' => Hash::make('password'),
        ]);

        $this->actingAs($stranger)->get(route('clinic.voice', $this->report()))->assertForbidden();
    }

    public function test_a_report_with_no_audio_returns_404(): void
    {
        $report = PatientReport::create([
            'patient_id' => $this->patient->id, 'symptoms' => ['fine'],
            'severity' => 0, 'lang' => 'yo', 'source' => 'app',
        ]);

        $staff = User::create([
            'organisation_id' => $this->org->id, 'name' => 'C. Eze',
            'email' => 'nurse2@example.test', 'role' => 'nurse', 'password' => Hash::make('password'),
        ]);

        $this->actingAs($staff)->get(route('clinic.voice', $report))->assertNotFound();
    }

    public function test_a_non_english_voice_note_is_flagged_for_a_human(): void
    {
        // There is no reliable speech-to-text for yo/ha/ig, so the system says
        // so rather than pretending a transcript exists.
        $this->assertTrue($this->report()->needsHumanReview());
    }
}
