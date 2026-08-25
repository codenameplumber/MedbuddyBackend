<?php

namespace Tests\Feature;

use Anthropic\Client;
use App\Services\AiService;
use Tests\Support\FakeAnthropicTransport;
use Tests\TestCase;

/**
 * Mock mode is what the demo runs on. This covers the other branch: what
 * actually goes to Claude when a key is present, and what happens when the
 * call fails.
 *
 * Nothing here reaches the network. The Anthropic SDK's transport is a
 * constructor option, so the tests swap in a fake PSR-18 client and assert on
 * the exact request the SDK would have sent — which means the suite stays
 * runnable with no key and no connection.
 */
class AiLiveModeTest extends TestCase
{
    private function live(?FakeAnthropicTransport $transport = null): AiService
    {
        config([
            'services.anthropic.mode'  => 'live',
            'services.anthropic.key'   => 'sk-ant-test-not-a-real-key',
            'services.anthropic.model' => 'claude-opus-5',
        ]);

        if ($transport) {
            $this->app->instance(Client::class, new Client(
                apiKey: 'sk-ant-test-not-a-real-key',
                // No retries: a degradation test should not sit through the
                // SDK's backoff before falling back.
                requestOptions: ['transporter' => $transport, 'maxRetries' => 0],
            ));
        }

        return app(AiService::class);
    }

    private const GOOD_EXPLAIN = [
        'medicineName' => 'Amlodipine 5 mg', 'readingConfidence' => 'high',
        'purpose' => 'It lowers blood pressure.', 'dosing' => 'One tablet each day.',
        'sideEffects' => ['Swollen ankles'], 'warnings' => ['Stand up slowly.'],
        'storage' => 'Somewhere cool and dry.', 'askPharmacist' => false,
    ];

    public function test_a_key_plus_live_mode_is_what_switches_it_on(): void
    {
        $this->assertFalse(app(AiService::class)->isLive(), 'Default must be mock so the demo needs no key.');
        $this->assertTrue($this->live()->isLive());

        config(['services.anthropic.key' => '']);
        $this->assertFalse(app(AiService::class)->isLive(), 'Live mode with no key must fall back, not fail.');
    }

    public function test_the_request_goes_to_claude_with_a_json_output_schema(): void
    {
        // Structured outputs are the reason the UI can trust that a
        // confidence field exists at all.
        $t = FakeAnthropicTransport::replying(self::GOOD_EXPLAIN);

        $this->live($t)->explain('AMLODIPINE 5MG', 'Yorùbá');

        $request = $t->sent[0];
        $body = $t->lastBody();

        $this->assertSame('api.anthropic.com', $request->getUri()->getHost());
        $this->assertStringContainsString('/v1/messages', $request->getUri()->getPath());
        $this->assertSame('sk-ant-test-not-a-real-key', $request->getHeaderLine('x-api-key'));

        $this->assertSame('claude-opus-5', $body['model']);
        $this->assertSame('json_schema', $body['output_config']['format']['type']);
        $this->assertSame(AiService::EXPLAIN_SCHEMA, $body['output_config']['format']['schema']);
    }

    public function test_a_refusal_is_given_a_fallback_model_to_land_on(): void
    {
        // A medicines question is exactly the shape a safety classifier may
        // decline. Rather than dropping the patient to a canned answer on the
        // first refusal, the API re-runs it on a fallback inside the same call.
        $t = FakeAnthropicTransport::replying(self::GOOD_EXPLAIN);

        $this->live($t)->explain('AMLODIPINE 5MG', 'English');

        $body = $t->lastBody();
        $this->assertSame('default', $body['fallbacks']);
        $this->assertContains('server-side-fallback-2026-07-01', $t->sent[0]->getHeader('anthropic-beta'));
    }

    public function test_the_patients_language_reaches_the_system_prompt(): void
    {
        $t = FakeAnthropicTransport::replying(self::GOOD_EXPLAIN);

        $this->live($t)->explain('AMLODIPINE 5MG', 'Hausa');

        $system = $t->lastBody()['system'];

        $this->assertStringContainsString('Hausa', $system);
        $this->assertStringContainsString('Never diagnose', $system);
    }

    public function test_a_photo_is_sent_as_an_image_block_before_the_question(): void
    {
        $t = FakeAnthropicTransport::replying(self::GOOD_EXPLAIN);

        $this->live($t)->explain('', 'English', 'data:image/jpeg;base64,AAAA');

        $content = $t->lastBody()['messages'][0]['content'];

        $this->assertCount(2, $content);
        $this->assertSame('image', $content[0]['type']);
        $this->assertSame('base64', $content[0]['source']['type']);
        $this->assertSame('image/jpeg', $content[0]['source']['media_type']);
        $this->assertSame('AAAA', $content[0]['source']['data']);
        $this->assertSame('text', $content[1]['type']);
    }

    public function test_a_payload_that_is_not_an_image_is_dropped_rather_than_sent(): void
    {
        $t = FakeAnthropicTransport::replying(self::GOOD_EXPLAIN);

        $this->live($t)->explain('AMLODIPINE 5MG', 'English', 'data:application/pdf;base64,AAAA');

        $content = $t->lastBody()['messages'][0]['content'];

        $this->assertCount(1, $content);
        $this->assertSame('text', $content[0]['type']);
    }

    public function test_a_live_answer_is_parsed_and_returned(): void
    {
        $result = $this->live(FakeAnthropicTransport::replying(self::GOOD_EXPLAIN))
            ->explain('AMLODIPINE 5MG', 'English');

        $this->assertSame('Amlodipine 5 mg', $result['medicineName']);
        $this->assertSame('high', $result['readingConfidence']);
    }

    public function test_the_triage_gate_still_runs_before_any_request(): void
    {
        // The whole point of the gate: a crisis is handled at zero latency and
        // is never subject to a model, or a network, being available.
        $t = FakeAnthropicTransport::replying(self::GOOD_EXPLAIN);

        $answer = $this->live($t)->ask('I cannot breathe', 'en', 'English');

        $this->assertTrue($answer['crisis']);
        $this->assertSame([], $t->sent, 'Triage must short-circuit before the model is called.');
    }

    public function test_an_api_failure_degrades_instead_of_breaking_the_screen(): void
    {
        $result = $this->live(FakeAnthropicTransport::failing(429, 'rate_limit_error'))
            ->explain('AMLODIPINE 5MG', 'English');

        // Falls back to a usable answer rather than throwing at a patient.
        $this->assertArrayHasKey('readingConfidence', $result);
        $this->assertArrayHasKey('askPharmacist', $result);
    }

    public function test_a_server_error_degrades_too(): void
    {
        $result = $this->live(FakeAnthropicTransport::failing(500))
            ->identify('white tablet', 'English');

        $this->assertContains($result['confidence'], ['high', 'medium', 'low', 'cannot_identify']);
    }

    public function test_an_unparseable_body_degrades_too(): void
    {
        $result = $this->live(FakeAnthropicTransport::replyingRaw('not json'))
            ->identify('white tablet', 'English');

        $this->assertArrayHasKey('confidence', $result);
        $this->assertContains($result['confidence'], ['high', 'medium', 'low', 'cannot_identify']);
    }

    public function test_a_response_missing_a_required_field_is_rejected(): void
    {
        // Everything a model returns is untrusted until it clears validate().
        $service = $this->live(FakeAnthropicTransport::replying(['medicineName' => 'Amlodipine']));

        $this->expectException(\RuntimeException::class);
        $service->explain('AMLODIPINE 5MG', 'English');
    }

    public function test_the_key_is_never_written_into_a_rendered_page(): void
    {
        $this->live();

        foreach (glob(resource_path('views/**/*.blade.php')) as $view) {
            $this->assertStringNotContainsString(
                'services.anthropic.key',
                (string) file_get_contents($view),
                basename($view).' reads the API key. It must never reach a browser.',
            );
        }
    }
}
