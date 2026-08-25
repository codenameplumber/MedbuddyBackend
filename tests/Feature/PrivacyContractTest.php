<?php

namespace Tests\Feature;

use App\Models\Consent;
use App\Models\Organisation;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Reminder;
use App\Notifications\DoseReminder;
use App\Services\ReminderComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * THE PRIVACY CONTRACT.
 *
 * A reminder names the DOSE WINDOW. Never the condition, never the drug,
 * never the dose.
 *
 * These tests exist so that "helpfully" adding the medicine name to the
 * template fails the build. An email sits in an inbox, often on a shared
 * computer, and its subject shows in a lock-screen preview — a message naming
 * an antiretroviral can disclose someone's diagnosis to whoever is holding
 * the phone.
 */
class PrivacyContractTest extends TestCase
{
    use RefreshDatabase;

    private function patient(string $drug, string $condition = 'HIV', string $lang = 'en'): Patient
    {
        $org = Organisation::create(['name' => 'Test Clinic', 'kind' => 'hospital']);

        $patient = Patient::create([
            'organisation_id' => $org->id,
            'name' => 'Test Patient',
            'email' => 'test@example.test',
            'condition' => $condition,
            'lang' => $lang,
            'enrolled_at' => now(),
        ]);

        Prescription::create([
            'patient_id' => $patient->id,
            'name' => $drug,
            'strength' => '50 mg',
            'times' => ['08:00'],
            'active' => true,
        ]);

        Consent::create([
            'patient_id' => $patient->id, 'scope' => 'messaging',
            'channel' => 'mail', 'granted' => true, 'granted_at' => now(),
        ]);

        return $patient->refresh();
    }

    public function test_message_never_contains_the_medicine_name(): void
    {
        $patient = $this->patient('Dolutegravir');
        $composed = app(ReminderComposer::class)->compose($patient, 'morning');

        $this->assertStringNotContainsStringIgnoringCase('dolutegravir', $composed['subject']);
        $this->assertStringNotContainsStringIgnoringCase('dolutegravir', $composed['body']);
    }

    public function test_message_never_contains_the_condition(): void
    {
        $patient = $this->patient('Dolutegravir', 'HIV');
        $composed = app(ReminderComposer::class)->compose($patient, 'evening');

        foreach (['hiv', 'tuberculosis', 'diabetes', 'hypertension', 'epilepsy', 'asthma'] as $word) {
            $this->assertStringNotContainsStringIgnoringCase($word, $composed['subject']);
            $this->assertStringNotContainsStringIgnoringCase($word, $composed['body']);
        }
    }

    public function test_message_never_contains_a_dose(): void
    {
        $patient = $this->patient('Metformin');
        $composed = app(ReminderComposer::class)->compose($patient, 'morning');

        $this->assertDoesNotMatchRegularExpression('/\d+\s*(mg|mcg|ml|g|iu)\b/i', $composed['subject']);
        $this->assertDoesNotMatchRegularExpression('/\d+\s*(mg|mcg|ml|g|iu)\b/i', $composed['body']);
    }

    public function test_message_does_name_the_dose_window(): void
    {
        $patient = $this->patient('Metformin', 'Type 2 diabetes');

        foreach (['morning', 'afternoon', 'evening'] as $slot) {
            $composed = app(ReminderComposer::class)->compose($patient, $slot);
            $this->assertStringContainsStringIgnoringCase(__("reminder.slot_{$slot}"), $composed['body']);
        }
    }

    public function test_the_guard_actually_throws_when_violated(): void
    {
        // If the guard silently passed everything, the tests above would be
        // decorative. This proves it can fail.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Privacy contract violated/');

        app(ReminderComposer::class)->assertSafe(
            'Did you take your Dolutegravir tablet?',
            ['Dolutegravir / Lamivudine / Tenofovir'],
        );
    }

    public function test_the_guard_rejects_an_embedded_dose(): void
    {
        $this->expectException(\RuntimeException::class);
        app(ReminderComposer::class)->assertSafe('Take your 500 mg tablet now.', []);
    }

    public function test_the_guard_rejects_a_condition_name(): void
    {
        $this->expectException(\RuntimeException::class);
        app(ReminderComposer::class)->assertSafe('Your diabetes reminder.', []);
    }

    public function test_the_contract_holds_in_every_language(): void
    {
        foreach (['en', 'yo', 'ha', 'ig'] as $lang) {
            $patient = $this->patient('Rifampicin', 'Tuberculosis', $lang);
            $composed = app(ReminderComposer::class)->compose($patient, 'morning');

            $this->assertStringNotContainsStringIgnoringCase('rifampicin', $composed['body']);
            $this->assertStringNotContainsStringIgnoringCase('tuberculosis', $composed['body']);
            $this->assertNotEmpty($composed['subject'], "Subject empty for {$lang}");

            \Illuminate\Support\Facades\DB::table('prescriptions')->delete();
            \Illuminate\Support\Facades\DB::table('consents')->delete();
            \Illuminate\Support\Facades\DB::table('patients')->delete();
        }
    }

    public function test_the_rendered_email_leaks_nothing(): void
    {
        $patient = $this->patient('Dolutegravir', 'HIV');

        $reminder = Reminder::create([
            'patient_id' => $patient->id,
            'dose_date' => today(),
            'dose_time' => '21:00',
            'slot' => 'evening',
            'channel' => 'mail',
            'to' => $patient->email,
        ]);

        $mail = (new DoseReminder($reminder))->toMail($patient);
        $rendered = $mail->render();

        $this->assertStringNotContainsStringIgnoringCase('dolutegravir', $rendered);
        $this->assertStringNotContainsStringIgnoringCase('hiv', strip_tags($rendered));
        $this->assertStringNotContainsStringIgnoringCase('21:00', $rendered);
    }

    /**
     * The subject was translated and the button was not.
     *
     * ReminderComposer passes the patient's locale explicitly; the Blade view
     * called __() with none and fell back to the app locale. A Yoruba subject
     * over an English call to action is exactly the kind of half-translation
     * that makes an interface untrustworthy.
     */
    public function test_the_whole_reminder_email_is_in_one_language(): void
    {
        $patient  = $this->patient('Amlodipine', 'Hypertension', 'yo');
        $reminder = \App\Models\Reminder::create([
            'patient_id' => $patient->id, 'dose_date' => today()->toDateString(),
            'dose_time' => '08:00', 'slot' => 'morning', 'channel' => 'mail',
            'to' => $patient->email,
        ]);

        $mail = (new \App\Notifications\DoseReminder($reminder))->toMail($patient);
        $html = (string) app('mailer')->render($mail->view, $mail->viewData);

        $this->assertStringContainsString(__('reminder.cta', [], 'yo'), $html);
        $this->assertStringNotContainsString(__('reminder.cta', [], 'en'), $html);
        $this->assertStringContainsString(__('reminder.footer', [], 'yo'), $html);
    }
}
