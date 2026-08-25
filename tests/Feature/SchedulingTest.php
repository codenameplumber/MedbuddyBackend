<?php

namespace Tests\Feature;

use App\Jobs\DispatchDueReminders;
use App\Models\Consent;
use App\Models\Organisation;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Reminder;
use App\Notifications\DoseReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The scheduler is the whole reason this system needs a backend, so the parts
 * that decide WHEN a message goes out get their own tests.
 */
class SchedulingTest extends TestCase
{
    use RefreshDatabase;

    private function patient(array $times = ['08:00'], bool $consent = true, ?string $email = 'p@example.test'): Patient
    {
        $org = Organisation::create(['name' => 'Test Clinic']);

        $patient = Patient::create([
            'organisation_id' => $org->id, 'name' => 'Test Patient',
            'email' => $email, 'lang' => 'en', 'condition' => 'Hypertension',
            'enrolled_at' => now()->subMonth(),
        ]);

        Prescription::create([
            'patient_id' => $patient->id, 'name' => 'Amlodipine', 'strength' => '5 mg',
            'times' => $times, 'active' => true, 'starts_on' => now()->subMonth(),
        ]);

        if ($consent) {
            Consent::create([
                'patient_id' => $patient->id, 'scope' => 'messaging',
                'channel' => 'mail', 'granted' => true, 'granted_at' => now(),
            ]);
        }

        return $patient->refresh();
    }

    /**
     * Dose windows are wall-clock local times. If the app runs on UTC while
     * patients live in WAT, every reminder fires an hour late — silently.
     */
    public function test_the_application_timezone_is_not_utc_by_accident(): void
    {
        $this->assertSame(
            env('APP_TIMEZONE', 'UTC'),
            config('app.timezone'),
            'config/app.php is ignoring APP_TIMEZONE. Dose reminders would fire in the wrong hour.',
        );
    }

    public function test_a_reminder_fires_inside_its_window(): void
    {
        Notification::fake();
        $this->travelTo(now()->setTime(8, 5));      // 5 minutes after the window opened
        $this->patient(['08:00']);

        (new DispatchDueReminders)->handle(app(\App\Services\ScheduleService::class));

        Notification::assertSentTimes(DoseReminder::class, 1);
        $this->assertNotNull(Reminder::first()->sent_at);
    }

    public function test_a_reminder_does_not_fire_before_its_window(): void
    {
        Notification::fake();
        $this->travelTo(now()->setTime(7, 30));     // half an hour early
        $this->patient(['08:00']);

        (new DispatchDueReminders)->handle(app(\App\Services\ScheduleService::class));

        Notification::assertNothingSent();
    }

    public function test_a_reminder_does_not_fire_long_after_its_window(): void
    {
        // A scheduler that has been down must not spam the whole morning
        // the moment it comes back.
        Notification::fake();
        $this->travelTo(now()->setTime(11, 0));     // three hours late
        $this->patient(['08:00']);

        (new DispatchDueReminders)->handle(app(\App\Services\ScheduleService::class));

        Notification::assertNothingSent();
    }

    public function test_running_twice_in_the_same_window_sends_once(): void
    {
        Notification::fake();
        $this->travelTo(now()->setTime(8, 5));
        $this->patient(['08:00']);

        $job = new DispatchDueReminders;
        $job->handle(app(\App\Services\ScheduleService::class));
        $job->handle(app(\App\Services\ScheduleService::class));

        Notification::assertSentTimes(DoseReminder::class, 1);
        $this->assertSame(1, Reminder::count());
    }

    public function test_revoked_consent_stops_the_send(): void
    {
        Notification::fake();
        $this->travelTo(now()->setTime(8, 5));
        $patient = $this->patient(['08:00']);

        Consent::where('patient_id', $patient->id)->update(['revoked_at' => now()]);

        (new DispatchDueReminders)->handle(app(\App\Services\ScheduleService::class));

        Notification::assertNothingSent();
        $this->assertSame(0, Reminder::count());
    }

    public function test_no_consent_at_all_stops_the_send(): void
    {
        Notification::fake();
        $this->travelTo(now()->setTime(8, 5));
        $this->patient(['08:00'], consent: false);

        (new DispatchDueReminders)->handle(app(\App\Services\ScheduleService::class));

        Notification::assertNothingSent();
    }

    public function test_a_patient_with_no_email_is_skipped_on_an_email_only_system(): void
    {
        Notification::fake();
        $this->travelTo(now()->setTime(8, 5));
        $this->patient(['08:00'], email: null);

        (new DispatchDueReminders)->handle(app(\App\Services\ScheduleService::class));

        Notification::assertNothingSent();
    }

    public function test_two_medicines_in_one_window_produce_one_message(): void
    {
        // Four separate emails at 08:00 would train someone to ignore all of them.
        Notification::fake();
        $this->travelTo(now()->setTime(8, 5));
        $patient = $this->patient(['08:00']);

        Prescription::create([
            'patient_id' => $patient->id, 'name' => 'Hydrochlorothiazide',
            'strength' => '25 mg', 'times' => ['08:00'], 'active' => true,
            'starts_on' => now()->subMonth(),
        ]);

        (new DispatchDueReminders)->handle(app(\App\Services\ScheduleService::class));

        Notification::assertSentTimes(DoseReminder::class, 1);
        $this->assertSame(1, Reminder::count());
    }

    public function test_morning_and_evening_windows_produce_separate_messages(): void
    {
        Notification::fake();
        $patient = $this->patient(['08:00', '20:00']);
        $service = app(\App\Services\ScheduleService::class);

        $this->travelTo(now()->setTime(8, 5));
        (new DispatchDueReminders)->handle($service);

        $this->travelTo(now()->setTime(20, 5));
        (new DispatchDueReminders)->handle($service);

        Notification::assertSentTimes(DoseReminder::class, 2);
        $this->assertSame(2, Reminder::count());
    }
}
