<?php

namespace App\Providers;

use Anthropic\Client;
use App\Models\DoseEvent;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Policies\PatientPolicy;
use App\Policies\PrescriptionPolicy;
use App\Policies\UserPolicy;
use App\Services\ScheduleService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
         | The Anthropic client, built from config rather than the environment so
         | `config:cache` and a test that overrides the key both behave.
         | Bound here — not new'd inside AiService — so a test can
         | swap in a client with a fake PSR-18 transporter and assert on the
         | request that would have gone out.
         */
        $this->app->bind(Client::class, fn () => new Client(
            apiKey: (string) config('services.anthropic.key'),
            baseUrl: config('services.anthropic.base_url') ?: null,
        ));

        // Singleton so its dose-event cache is shared by every caller in the
        // request. See the performance contract on the class.
        $this->app->singleton(ScheduleService::class);
    }

    public function boot(): void
    {
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(Prescription::class, PrescriptionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        /*
         | The other half of the schedule cache's safety. A Livewire component
         | that ticks off a dose re-renders inside the same request, so a cache
         | that survived the write would show the patient the old state.
         |
         | Bulk DoseEvent::insert() does not fire these — it is only used by the
         | seeder, in its own process, with nothing cached yet.
         */
        foreach (['saved', 'deleted'] as $event) {
            DoseEvent::{$event}(fn (DoseEvent $e) => app(ScheduleService::class)->forget($e->patient_id));
        }
    }
}
