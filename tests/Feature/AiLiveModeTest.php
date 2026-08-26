<?php

namespace Tests\Feature;

use Anthropic\Client as AnthropicClient;
use App\Services\AiService;
use OpenAI;
use OpenAI\Contracts\ClientContract;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakeAiTransport;
use Tests\TestCase;

/**
 * Mock mode is what the demo runs on. This covers the other branch: what
 * actually goes to a provider when a key is present, and what happens when the
 * call fails.
 *
 * MedBuddy supports two providers. The contract they owe the application is
 * identical — a validated object, or a graceful degradation, never an
 * exception at a patient — so most of this file runs twice, once against each.
 * Where the wire format genuinely differs, the test says which provider it is
 * about.
 *
 * Nothing here reaches the network: both SDKs take an injectable PSR-18
 * transport, so the suite stays runnable with no keys and no connection.
 */
class AiLiveModeTest extends TestCase
{
    private const GOOD_EXPLAIN = [
        'medicineName' => 'Amlodipine 5 mg', 'readingConfidence' => 'high',
        'purpose' => 'It lowers blood pressure.', 'dosing' => 'One tablet each day.',
        'sideEffects' => ['Swollen ankles'], 'warnings' => ['Stand up slowly.'],
        'storage' => 'Somewhere cool and dry.', 'askPharmacist' => false,
    ];

    /** Both providers, for the tests that must hold either way. */
    public static function providers(): array
    {
        return ['openai' => ['openai'], 'anthropic' => ['anthropic']];
    }

    /**
     * Put the service in live mode on $provider, with $transport standing in
     * for the network.
     */
    private function live(string $provider = 'openai', ?FakeAiTransport $transport = null): AiService
    {
        config([
            'services.ai.provider'     => $provider,
            'services.ai.mode'         => 'live',
            'services.anthropic.key'   => 'sk-ant-test-not-a-real-key',
            'services.anthropic.model' => 'claude-opus-5',
            'services.openai.key'      => 'sk-openai-test-not-a-real-key',
            'services.openai.model'    => 'gpt-4o-mini',
        ]);

        if ($transport) {
            $this->app->instance(AnthropicClient::class, new AnthropicClient(
                apiKey: 'sk-ant-test-not-a-real-key',
                // No retries: a degradation test should not sit through backoff.
                requestOptions: ['transporter' => $transport, 'maxRetries' => 0],
            ));

            $this->app->instance(ClientContract::class, OpenAI::factory()
                ->withApiKey('sk-openai-test-not-a-real-key')
                ->withHttpClient($transport)
                ->make());
        }

        return app(AiService::class);
    }

    /** The canned reply in whichever envelope $provider speaks. */
    private function replying(string $provider, array $payload): FakeAiTransport
    {
        return $provider === 'openai'
            ? FakeAiTransport::gpt($payload)
            : FakeAiTransport::claude($payload);
    }

    // ── Switching ────────────────────────────────────────────────────

    public function test_openai_is_the_default_provider(): void
    {
        // Nothing set: the shipped default answers.
        config(['services.ai.provider' => null]);

        $this->assertSame('openai', app(AiService::class)->providerName());
    }

    public function test_the_provider_is_chosen_by_one_env_var(): void
    {
        config(['services.ai.provider' => 'openai']);
        $this->assertSame('openai', app(AiService::class)->providerName());
        $this->assertSame('gpt-4o-mini', app(AiService::class)->model());

        config(['services.ai.provider' => 'anthropic']);
        $this->assertSame('anthropic', app(AiService::class)->providerName());
        $this->assertSame('claude-opus-5', app(AiService::class)->model());
    }

    public function test_an_unknown_provider_falls_back_rather_than_breaking_every_screen(): void
    {
        // A typo in an env var should not take the AI screens down.
        config(['services.ai.provider' => 'gemini-typo']);

        $this->assertSame('openai', app(AiService::class)->providerName());
    }

    #[DataProvider('providers')]
    public function test_a_key_plus_live_mode_is_what_switches_it_on(string $provider): void
    {
        config(['services.ai.provider' => $provider, 'services.ai.mode' => 'mock']);
        $this->assertFalse(app(AiService::class)->isLive(), 'Default must be mock so the demo needs no key.');

        $this->assertTrue($this->live($provider)->isLive());

        config(["services.{$provider}.key" => '']);
        $this->assertFalse(app(AiService::class)->isLive(), 'Live mode with no key must fall back, not fail.');
    }

    public function test_each_provider_only_needs_its_own_key(): void
    {
        // Having only an OpenAI key must not make the Anthropic provider look
        // live, or every call silently degrades with no explanation.
        config([
            'services.ai.mode' => 'live',
            'services.anthropic.key' => '',
            'services.openai.key' => 'sk-openai-test',
        ]);

        config(['services.ai.provider' => 'openai']);
        $this->assertTrue(app(AiService::class)->isLive());

        config(['services.ai.provider' => 'anthropic']);
        $this->assertFalse(app(AiService::class)->isLive());
    }

    // ── The wire, per provider ───────────────────────────────────────

    public function test_anthropic_gets_a_json_output_schema(): void
    {
        $t = FakeAiTransport::claude(self::GOOD_EXPLAIN);
        $this->live('anthropic', $t)->explain('AMLODIPINE 5MG', 'Yorùbá');

        $request = $t->sent[0];
        $body = $t->lastBody();

        $this->assertSame('api.anthropic.com', $request->getUri()->getHost());
        $this->assertStringContainsString('/v1/messages', $request->getUri()->getPath());
        $this->assertSame('sk-ant-test-not-a-real-key', $request->getHeaderLine('x-api-key'));
        $this->assertSame('claude-opus-5', $body['model']);
        $this->assertSame('json_schema', $body['output_config']['format']['type']);
        $this->assertSame(AiService::EXPLAIN_SCHEMA, $body['output_config']['format']['schema']);

        // A medicines question is exactly the shape a safety classifier may
        // decline, so a fallback model is offered rather than dropping the
        // patient to a canned answer on the first refusal.
        $this->assertSame('default', $body['fallbacks']);
        $this->assertContains('server-side-fallback-2026-07-01', $request->getHeader('anthropic-beta'));
    }

    public function test_openai_gets_a_strict_json_schema(): void
    {
        $t = FakeAiTransport::gpt(self::GOOD_EXPLAIN);
        $this->live('openai', $t)->explain('AMLODIPINE 5MG', 'Yorùbá');

        $request = $t->sent[0];
        $body = $t->lastBody();

        $this->assertSame('api.openai.com', $request->getUri()->getHost());
        $this->assertStringContainsString('/chat/completions', $request->getUri()->getPath());
        $this->assertSame('Bearer sk-openai-test-not-a-real-key', $request->getHeaderLine('Authorization'));
        $this->assertSame('gpt-4o-mini', $body['model']);
        $this->assertSame('json_schema', $body['response_format']['type']);
        $this->assertTrue($body['response_format']['json_schema']['strict']);
        $this->assertSame(AiService::EXPLAIN_SCHEMA, $body['response_format']['json_schema']['schema']);
    }

    #[DataProvider('providers')]
    public function test_the_patients_language_reaches_the_system_prompt(string $provider): void
    {
        $t = $this->replying($provider, self::GOOD_EXPLAIN);
        $this->live($provider, $t)->explain('AMLODIPINE 5MG', 'Hausa');

        $body = $t->lastBody();
        $system = $provider === 'openai'
            ? $body['messages'][0]['content']
            : $body['system'];

        $this->assertStringContainsString('Hausa', $system);
        $this->assertStringContainsString('Never diagnose', $system);
    }

    public function test_anthropic_gets_the_photo_as_an_image_block_before_the_question(): void
    {
        $t = FakeAiTransport::claude(self::GOOD_EXPLAIN);
        $this->live('anthropic', $t)->explain('', 'English', 'data:image/jpeg;base64,AAAA');

        $content = $t->lastBody()['messages'][0]['content'];

        $this->assertCount(2, $content);
        $this->assertSame('image', $content[0]['type']);
        $this->assertSame('base64', $content[0]['source']['type']);
        $this->assertSame('image/jpeg', $content[0]['source']['media_type']);
        $this->assertSame('AAAA', $content[0]['source']['data']);
        $this->assertSame('text', $content[1]['type']);
    }

    public function test_openai_gets_the_photo_as_an_image_url_part(): void
    {
        $t = FakeAiTransport::gpt(self::GOOD_EXPLAIN);
        $this->live('openai', $t)->explain('', 'English', 'data:image/jpeg;base64,AAAA');

        $content = $t->lastBody()['messages'][1]['content'];

        $this->assertCount(2, $content);
        $this->assertSame('text', $content[0]['type']);
        $this->assertSame('image_url', $content[1]['type']);
        $this->assertSame('data:image/jpeg;base64,AAAA', $content[1]['image_url']['url']);
    }

    #[DataProvider('providers')]
    public function test_a_payload_that_is_not_an_image_is_dropped_rather_than_sent(string $provider): void
    {
        $t = $this->replying($provider, self::GOOD_EXPLAIN);
        $this->live($provider, $t)->explain('AMLODIPINE 5MG', 'English', 'data:application/pdf;base64,AAAA');

        $body = $t->lastBody();
        $content = $provider === 'openai'
            ? $body['messages'][1]['content']
            : $body['messages'][0]['content'];

        $this->assertCount(1, $content, 'A rejected request costs a patient their answer.');
        $this->assertSame('text', $content[0]['type']);
    }

    // ── The contract both owe ────────────────────────────────────────

    #[DataProvider('providers')]
    public function test_a_live_answer_is_parsed_and_returned(string $provider): void
    {
        $result = $this->live($provider, $this->replying($provider, self::GOOD_EXPLAIN))
            ->explain('AMLODIPINE 5MG', 'English');

        $this->assertSame('Amlodipine 5 mg', $result['medicineName']);
        $this->assertSame('high', $result['readingConfidence']);
    }

    #[DataProvider('providers')]
    public function test_the_triage_gate_still_runs_before_any_request(string $provider): void
    {
        // The whole point of the gate: a crisis is handled at zero latency and
        // is never subject to a model, or a network, being available.
        $t = $this->replying($provider, self::GOOD_EXPLAIN);

        $answer = $this->live($provider, $t)->ask('I cannot breathe', 'en', 'English');

        $this->assertTrue($answer['crisis']);
        $this->assertSame([], $t->sent, 'Triage must short-circuit before the model is called.');
    }

    #[DataProvider('providers')]
    public function test_a_rate_limit_degrades_instead_of_breaking_the_screen(string $provider): void
    {
        $result = $this->live($provider, FakeAiTransport::failing(429, 'rate_limit_error'))
            ->explain('AMLODIPINE 5MG', 'English');

        $this->assertArrayHasKey('readingConfidence', $result);
        $this->assertArrayHasKey('askPharmacist', $result);
    }

    #[DataProvider('providers')]
    public function test_a_server_error_degrades_too(string $provider): void
    {
        $result = $this->live($provider, FakeAiTransport::failing(500))
            ->identify('white tablet', 'English');

        $this->assertContains($result['confidence'], ['high', 'medium', 'low', 'cannot_identify']);
    }

    #[DataProvider('providers')]
    public function test_an_unreachable_network_degrades_too(string $provider): void
    {
        // The venue wifi case. This is the one that actually happens on stage.
        $result = $this->live($provider, FakeAiTransport::unreachable())
            ->explain('AMLODIPINE 5MG', 'English');

        $this->assertArrayHasKey('readingConfidence', $result);
    }

    #[DataProvider('providers')]
    public function test_an_unparseable_body_degrades_too(string $provider): void
    {
        $t = $provider === 'openai'
            ? FakeAiTransport::gptRaw('not json')
            : FakeAiTransport::claudeRaw('not json');

        $result = $this->live($provider, $t)->identify('white tablet', 'English');

        $this->assertContains($result['confidence'], ['high', 'medium', 'low', 'cannot_identify']);
    }

    #[DataProvider('providers')]
    public function test_a_refusal_degrades_to_a_usable_answer(string $provider): void
    {
        $t = $provider === 'openai'
            ? FakeAiTransport::gptRefusal()
            : FakeAiTransport::claudeRefusal();

        $result = $this->live($provider, $t)->explain('AMLODIPINE 5MG', 'English');

        $this->assertArrayHasKey('readingConfidence', $result);
        $this->assertArrayHasKey('askPharmacist', $result);
    }

    #[DataProvider('providers')]
    public function test_a_response_missing_a_required_field_is_rejected(string $provider): void
    {
        // Everything a model returns is untrusted until it clears validate().
        $service = $this->live($provider, $this->replying($provider, ['medicineName' => 'Amlodipine']));

        $this->expectException(\RuntimeException::class);
        $service->explain('AMLODIPINE 5MG', 'English');
    }

    public function test_no_key_is_ever_written_into_a_rendered_page(): void
    {
        $this->live();

        foreach (glob(resource_path('views/**/*.blade.php')) as $view) {
            $body = (string) file_get_contents($view);

            foreach (['services.anthropic.key', 'services.openai.key'] as $secret) {
                $this->assertStringNotContainsString(
                    $secret,
                    $body,
                    basename($view).' reads an API key. It must never reach a browser.',
                );
            }
        }
    }
}
