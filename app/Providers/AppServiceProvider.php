<?php

namespace App\Providers;

use Anthropic\Client;
use App\Services\Ai\AnthropicProvider;
use App\Services\Ai\OpenAiProvider;
use App\Services\Ai\Provider;
use App\Models\DoseEvent;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\User;
use App\Policies\PatientPolicy;
use App\Policies\PrescriptionPolicy;
use App\Policies\UserPolicy;
use App\Services\ScheduleService;
use OpenAI;
use OpenAI\Contracts\ClientContract;
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

        /*
         | The OpenAI client, built from config for the same reason as the
         | Anthropic one: config:cache and a test that overrides the key must
         | both behave, and a test needs somewhere to inject a fake transport.
         */
        $this->app->bind(ClientContract::class, function () {
            $factory = OpenAI::factory()->withApiKey((string) config('services.openai.key'));

            if ($base = config('services.openai.base_url')) {
                $factory = $factory->withBaseUri($base);
            }

            return $factory->make();
        });

        /*
         | Which model answers. OpenAI by default; Claude when asked for.
         | An unrecognised value falls back to the default rather than
         | throwing, because a typo in an env var should not take down every
         | AI screen at once.
         */
        $this->app->bind(Provider::class, fn () => match (config('services.ai.provider')) {
            'anthropic' => new AnthropicProvider,
            default     => new OpenAiProvider,
        });

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
