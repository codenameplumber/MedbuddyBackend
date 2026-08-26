<?php

namespace Tests\Feature;

use App\Services\AiService;
use Tests\TestCase;

/**
 * The AI boundary.
 *
 * Everything a model returns is untrusted until it clears validate(), and the
 * UI contract decides what a given confidence is ALLOWED to render — so a
 * medium-confidence guess can never appear as one certain answer.
 */
class AiBoundaryTest extends TestCase
{
    private function ai(): AiService
    {
        config(['services.ai.mode' => 'mock']);
        return app(AiService::class);
    }

    public function test_explain_returns_every_required_field(): void
    {
        $result = $this->ai()->explain('Amlodipine 5mg once daily', 'English');

        foreach (AiService::EXPLAIN_SCHEMA['required'] as $key) {
            $this->assertArrayHasKey($key, $result);
        }
        $this->assertContains($result['readingConfidence'], ['high', 'medium', 'low', 'cannot_read']);
    }

    public function test_handwriting_lowers_confidence_and_defers_to_a_pharmacist(): void
    {
        // Misreading a doctor's handwriting is the highest-risk failure in the
        // product, so it is handled structurally rather than politely.
        $result = $this->ai()->explain('this note is handwritten and blurry', 'English');

        $this->assertSame('low', $result['readingConfidence']);
        $this->assertTrue($result['askPharmacist']);
        $this->assertSame('', $result['medicineName']);
    }

    public function test_medium_confidence_may_never_render_a_single_name(): void
    {
        $rule = AiService::displayRule('medium');
        $this->assertFalse($rule['showSingleName']);
        $this->assertTrue($rule['showAllCandidates']);

        $rule = AiService::displayRule('cannot_identify');
        $this->assertFalse($rule['showSingleName']);
        $this->assertFalse($rule['showAllCandidates']);

        $rule = AiService::displayRule('high');
        $this->assertTrue($rule['showSingleName']);
    }

    public function test_the_gate_short_circuits_before_the_model(): void
    {
        $out = $this->ai()->ask('I have chest pain', 'en', 'English');

        $this->assertTrue($out['crisis']);
        $this->assertSame('cardiac', $out['category']);
        $this->assertArrayNotHasKey('result', $out);
    }

    public function test_a_dose_change_question_is_refused(): void
    {
        $out = $this->ai()->ask('should I stop taking it?', 'en', 'English');

        $this->assertFalse($out['crisis']);
        $this->assertTrue($out['result']['refused']);
        $this->assertTrue($out['result']['seeClinician']);
    }

    public function test_an_ordinary_question_reaches_the_model(): void
    {
        $out = $this->ai()->ask('can I take this with food?', 'en', 'English');

        $this->assertFalse($out['crisis']);
        $this->assertNotEmpty($out['result']['answer']);
        $this->assertFalse($out['result']['refused']);
    }

    public function test_validate_rejects_a_missing_field(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->ai()->validate(['purpose' => 'x'], AiService::EXPLAIN_SCHEMA, 'explain');
    }

    public function test_validate_rejects_an_invalid_enum(): void
    {
        $good = $this->ai()->explain('Amlodipine', 'English');
        $good['readingConfidence'] = 'very-sure';

        $this->expectException(\RuntimeException::class);
        $this->ai()->validate($good, AiService::EXPLAIN_SCHEMA, 'explain');
    }

    public function test_mock_mode_needs_no_key(): void
    {
        config(['services.ai.mode' => 'mock', 'services.anthropic.key' => null, 'services.openai.key' => null]);
        $this->assertFalse(app(AiService::class)->isLive());
        $this->assertNotEmpty(app(AiService::class)->explain('test', 'English'));
    }
}
