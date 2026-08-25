<?php

namespace Tests\Feature;

use App\Services\TriageService;
use Tests\TestCase;

/**
 * The deterministic emergency gate.
 *
 * This runs before any model call, so a crisis is handled at zero latency and
 * is never subject to an LLM deciding whether something is serious. It is
 * deliberately tuned to over-trigger: a false alarm costs one extra screen.
 */
class TriageGateTest extends TestCase
{
    private TriageService $triage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->triage = new TriageService();
    }

    public function test_it_catches_english_crises(): void
    {
        $this->assertSame('cardiac',   $this->triage->check('I have chest pain', 'en')['category']);
        $this->assertSame('breathing', $this->triage->check('I cannot breathe', 'en')['category']);
        $this->assertSame('selfharm',  $this->triage->check('I want to die', 'en')['category']);
        $this->assertSame('stroke',    $this->triage->check('my face is drooping', 'en')['category']);
    }

    public function test_it_catches_crises_in_all_four_languages(): void
    {
        $this->assertTrue($this->triage->check('irora aya mi', 'yo')['crisis']);
        $this->assertTrue($this->triage->check('ba zan iya numfashi', 'ha')['crisis']);
        $this->assertTrue($this->triage->check('enweghi m ike iku ume', 'ig')['crisis']);
        $this->assertTrue($this->triage->check('chest pain', 'en')['crisis']);
    }

    public function test_it_handles_code_switching(): void
    {
        // People code-switch constantly. A Yoruba speaker may well type
        // "chest pain", so scanning only the selected language would miss it.
        $this->assertTrue($this->triage->check('my chest pain is bad', 'yo')['crisis']);
        $this->assertTrue($this->triage->check('I cannot breathe', 'ig')['crisis']);
    }

    public function test_it_strips_diacritics_before_matching(): void
    {
        // "àyà" and "aya" must both match.
        $this->assertTrue($this->triage->check('ìrora àyà', 'yo')['crisis']);
        $this->assertTrue($this->triage->check('irora aya', 'yo')['crisis']);
    }

    public function test_ordinary_questions_pass_through(): void
    {
        $this->assertFalse($this->triage->check('can I take this with food?', 'en')['crisis']);
        $this->assertFalse($this->triage->check('what time should I take it', 'en')['crisis']);
        $this->assertFalse($this->triage->check('', 'en')['crisis']);
        $this->assertFalse($this->triage->check(null, 'en')['crisis']);
    }

    public function test_every_category_has_guidance(): void
    {
        foreach (array_keys(TriageService::GUIDANCE) as $category) {
            $this->assertNotEmpty($this->triage->guidance($category));
        }
    }
}
