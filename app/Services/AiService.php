<?php

namespace App\Services;

use App\Services\Ai\Provider;

/**
 * The AI boundary. This class, and only this class, talks to a model.
 *
 * Two things the browser prototype could not do, both fixed by being here:
 *   1. The key lives in .env, never in a page. No key is ever shipped.
 *   2. Every call can be rate limited, cached, retried and logged.
 *
 * PROVIDER: Claude or GPT, chosen by MEDBUDDY_AI_PROVIDER. Both are wired
 * through App\Services\Ai\Provider, so nothing above this class — no screen,
 * no test of a screen — can tell which one answered. Swapping is an env change
 * and a config:clear.
 *
 * MODE:
 *   'mock' (default) returns canned responses. No key, no network, so the
 *          whole platform stays demoable and every UI state — low confidence,
 *          refusal, cannot_identify — can be exercised deliberately.
 *   'live' calls the configured provider from the server.
 *
 * Every failure path lands on the same mock response rather than an exception:
 * a patient asking about their medicine must never be shown a stack trace.
 */
class AiService
{
    public function __construct(private TriageService $triage) {}

    private function mode(): string { return config('services.ai.mode', 'mock'); }

    /** The provider currently selected. Resolved per call so config() wins. */
    public function provider(): Provider
    {
        return app(Provider::class);
    }

    public function providerName(): string { return $this->provider()->name(); }
    public function providerLabel(): string { return $this->provider()->label(); }
    public function model(): string { return $this->provider()->model(); }

    public function isLive(): bool
    {
        return $this->mode() === 'live' && $this->provider()->isConfigured();
    }

    // ── Schemas ──────────────────────────────────────────────────────
    // Structured outputs require every property listed in `required` and
    // additionalProperties:false at every level.

    public const EXPLAIN_SCHEMA = [
        'type' => 'object',
        'properties' => [
            'medicineName'      => ['type' => 'string'],
            'readingConfidence' => ['type' => 'string', 'enum' => ['high', 'medium', 'low', 'cannot_read']],
            'purpose'           => ['type' => 'string'],
            'dosing'            => ['type' => 'string'],
            'sideEffects'       => ['type' => 'array', 'items' => ['type' => 'string']],
            'warnings'          => ['type' => 'array', 'items' => ['type' => 'string']],
            'storage'           => ['type' => 'string'],
            'askPharmacist'     => ['type' => 'boolean'],
        ],
        'required' => ['medicineName', 'readingConfidence', 'purpose', 'dosing', 'sideEffects', 'warnings', 'storage', 'askPharmacist'],
        'additionalProperties' => false,
    ];

    public const IDENTIFY_SCHEMA = [
        'type' => 'object',
        'properties' => [
            'visualFeatures' => ['type' => 'string'],
            'confidence'     => ['type' => 'string', 'enum' => ['high', 'medium', 'low', 'cannot_identify']],
            'candidates'     => [
                'type'  => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'name'      => ['type' => 'string'],
                        'reasoning' => ['type' => 'string'],
                    ],
                    'required' => ['name', 'reasoning'],
                    'additionalProperties' => false,
                ],
            ],
        ],
        'required' => ['visualFeatures', 'confidence', 'candidates'],
        'additionalProperties' => false,
    ];

    public const CHAT_SCHEMA = [
        'type' => 'object',
        'properties' => [
            'answer'       => ['type' => 'string'],
            'seeClinician' => ['type' => 'boolean'],
            'refused'      => ['type' => 'boolean'],
        ],
        'required' => ['answer', 'seeClinician', 'refused'],
        'additionalProperties' => false,
    ];

    public const SUMMARY_SCHEMA = [
        'type' => 'object',
        'properties' => [
            'forCaregiver' => ['type' => 'string'],
            'forClinician' => ['type' => 'string'],
            'concerns'     => ['type' => 'array', 'items' => ['type' => 'string']],
        ],
        'required' => ['forCaregiver', 'forClinician', 'concerns'],
        'additionalProperties' => false,
    ];

    // ── Public tasks ─────────────────────────────────────────────────

    public function explain(string $text, string $languageName, ?string $imageDataUrl = null): array
    {
        return $this->run('explain', self::EXPLAIN_SCHEMA, $this->safety().'

Your task: read what is on a medicine label, pharmacy note, or a doctor handwritten
note, and explain it in plain language. Write every field in '.$languageName.'.

Be especially careful with handwriting. Doctors handwriting is often ambiguous and
reading it wrong could lead someone to take the wrong medicine. If the text is
handwritten, blurry, partly cut off, or could plausibly be read as more than one
medicine name, set readingConfidence to "low" or "cannot_read" and askPharmacist to
true. Do not guess a name to be helpful.',
            $text !== '' ? "Here is what the label or note says:\n\n".$text : 'Read the label in this photo and explain it.',
            $imageDataUrl,
        );
    }

    public function identify(string $text, string $languageName, ?string $imageDataUrl = null): array
    {
        return $this->run('identify', self::IDENTIFY_SCHEMA, $this->safety().'

Your task: look at a photo of a pill, tablet or medicine bottle and describe what
you can see. Write every field in '.$languageName.'.

Identifying a pill from a photo is genuinely unreliable. Many pills share a colour
and shape, markings are often too small to read, and generics vary by country. Use
"high" only when clear packaging text names the medicine. If you cannot tell, use
"cannot_identify" and return an empty candidates list. That is a correct and useful
answer, not a failure.',
            $text !== '' ? 'Extra detail from the patient: '.$text : 'What can you see in this photo?',
            $imageDataUrl,
        );
    }

    /**
     * Medication Q&A with the deterministic emergency gate in front of it.
     *
     * The gate is the point: triage runs BEFORE any network call, so a crisis
     * is handled at zero latency and is never subject to a model deciding
     * whether it is serious.
     *
     * @return array{crisis: true, category: string}|array{crisis: false, result: array}
     */
    public function ask(string $question, string $lang, string $languageName, array $medNames = []): array
    {
        $triage = $this->triage->check($question, $lang);
        if ($triage['crisis']) {
            return ['crisis' => true, 'category' => $triage['category']];
        }

        $meds = $medNames ? '- '.implode("\n- ", $medNames) : '(none recorded yet)';

        $result = $this->run('chat', self::CHAT_SCHEMA, $this->safety().'

Your task: answer the patient question about their medicines. Answer in '.$languageName.'.

The medicines this person has recorded:
'.$meds.'

Answer only what was asked. Keep it to a few short sentences: this is read on a
small phone screen, often aloud.

Set refused to true and give a gentle redirection if the question asks you to
diagnose a symptom, or to decide whether to start, stop or change a dose. Set
seeClinician to true whenever a pharmacist or doctor should really answer.',
            $question,
        );

        return ['crisis' => false, 'result' => $result];
    }

    public function summary(string $facts, string $languageName): array
    {
        return $this->run('summary', self::SUMMARY_SCHEMA, $this->safety().'

Your task: summarise this patient medicines and how well they have been taking them,
so a family member or a doctor understands the situation quickly.

Write forCaregiver in '.$languageName.', warm and plain. Write forClinician in English,
compact and factual. Report only what the data shows: do not infer a diagnosis from
the medicine list, and do not speculate about why doses were missed.',
            $facts,
        );
    }

    // ── Transport ────────────────────────────────────────────────────

    private function run(string $task, array $schema, string $system, string $user, ?string $imageDataUrl = null): array
    {
        if (! $this->isLive()) {
            return $this->mock($task, $user);
        }

        $parsed = $this->provider()->complete($task, $schema, $system, $user, $imageDataUrl);

        // The provider logged why. Anything it could not complete — no key,
        // rate limit, refusal, unparseable body — degrades to an answer the
        // patient can still act on.
        if ($parsed === null) {
            return $this->mock($task, $user);
        }

        return $this->validate($parsed, $schema, $task);
    }

    /**
     * Everything a model returns is untrusted until it clears this. Structural
     * only, but enough to guarantee every field the UI reads exists and that
     * enums hold real values.
     */
    public function validate(array $data, array $schema, string $task = ''): array
    {
        foreach ($schema['required'] ?? [] as $key) {
            if (! array_key_exists($key, $data)) {
                throw new \RuntimeException("AI response for {$task} is missing \"{$key}\".");
            }
            $spec = $schema['properties'][$key] ?? [];
            if (isset($spec['enum']) && ! in_array($data[$key], $spec['enum'], true)) {
                throw new \RuntimeException("AI response for {$task} has an invalid value for \"{$key}\".");
            }
            if (($spec['type'] ?? null) === 'array' && ! is_array($data[$key])) {
                throw new \RuntimeException("AI response for {$task} has an invalid value for \"{$key}\".");
            }
        }

        return $data;
    }

    /**
     * The UI contract for identification. The model supplies a confidence;
     * this decides what the interface is ALLOWED to show, so a medium-confidence
     * guess can never render as a single certain answer.
     */
    public static function displayRule(string $confidence): array
    {
        return match ($confidence) {
            'high' => [
                'showSingleName' => true, 'showAllCandidates' => false, 'tone' => 'info',
                'note' => 'Still worth confirming with your pharmacist.',
            ],
            'medium', 'low' => [
                'showSingleName' => false, 'showAllCandidates' => true, 'tone' => 'caution',
                'note' => 'I am not certain. These are possibilities only. Do not take anything based on this. Show the pill to your pharmacist.',
            ],
            default => [
                'showSingleName' => false, 'showAllCandidates' => false, 'tone' => 'caution',
                'note' => 'I could not tell what this is. Please show it to your pharmacist.',
            ],
        };
    }

    private function safety(): string
    {
        return trim('
You are helping a patient who may have limited reading ability and who may not speak
English as a first language. They are often elderly, and often managing a long-term
illness with several medicines.

Hard rules:
- Never diagnose. Never suggest starting, stopping or changing a dose.
- Never contradict what a doctor or pharmacist has told them.
- If you are unsure about anything, say so plainly and tell them to ask their
  pharmacist. An honest "I am not sure" is always better than a confident guess.
- Use short sentences and everyday words. No medical jargon. If you must use a
  medical word, explain it immediately in plain language.
- Do not use bullet symbols, markdown, asterisks or headings. Write plain sentences;
  the app does the formatting.
        ');
    }

    // ── Mock responses ───────────────────────────────────────────────
    // Demo insurance: on stage there is no venue wifi to fail, no key to have
    // expired, no rate limit to hit. Keywords force a branch so the edge cases
    // can be rehearsed rather than hoped for.

    private function mock(string $task, string $hint): array
    {
        $h = mb_strtolower($hint);
        $has = fn (array $words) => (bool) array_filter($words, fn ($w) => str_contains($h, $w));

        return match ($task) {
            'explain' => $has(['blurry', 'handwritten', 'unclear', 'cannot read'])
                ? [
                    'medicineName' => '', 'readingConfidence' => 'low',
                    'purpose' => 'I could not read this note clearly enough to be sure which medicine it is.',
                    'dosing'  => 'I cannot tell you how to take this, because I am not certain what it is.',
                    'sideEffects' => [], 'warnings' => ['Please show this note to your pharmacist before you take anything.'],
                    'storage' => '', 'askPharmacist' => true,
                ]
                : [
                    'medicineName' => 'Amlodipine 5 mg', 'readingConfidence' => 'high',
                    'purpose' => 'This medicine lowers high blood pressure. It helps your blood move through your body more easily, which protects your heart and kidneys over time.',
                    'dosing'  => 'Take one tablet each day, at the same time every day. You can take it with food or without food. Swallow it whole with water.',
                    'sideEffects' => [
                        'Your ankles or feet may swell a little.',
                        'You may feel dizzy, especially when you stand up quickly.',
                        'You may get headaches in the first few days.',
                    ],
                    'warnings' => [
                        'Stand up slowly from a chair or a bed so you do not feel faint.',
                        'Do not stop taking this even when you feel fine. Blood pressure has no warning signs.',
                        'Avoid grapefruit, as it can make this medicine too strong.',
                    ],
                    'storage' => 'Keep the tablets in a cool dry place, away from sunlight. Keep them where children cannot reach them.',
                    'askPharmacist' => false,
                ],

            'identify' => $has(['unknown', 'blurry'])
                ? [
                    'visualFeatures' => 'A white oval tablet. The photo is too blurred for me to read any markings, and there is no packaging visible.',
                    'confidence' => 'cannot_identify', 'candidates' => [],
                ]
                : [
                    'visualFeatures' => 'A small round white tablet. There is a line scored across one side so it can be split. I can see the letters AM pressed into the other side, but the marking is small and slightly worn.',
                    'confidence' => 'medium',
                    'candidates' => [
                        ['name' => 'Amlodipine 5 mg', 'reasoning' => 'The round white shape, the scoring line and an AM imprint are all common on amlodipine tablets.'],
                        ['name' => 'Amitriptyline 10 mg', 'reasoning' => 'Also round and white with an AM style imprint in some brands, so I cannot rule it out from the photo alone.'],
                    ],
                ],

            'chat' => $has(['stop', 'change my dose', 'double', 'quit', 'skip'])
                ? [
                    'answer' => 'I cannot tell you whether to stop or change this medicine. Only the doctor who prescribed it can decide that safely. Please call your pharmacist or your clinic and tell them exactly what you told me.',
                    'seeClinician' => true, 'refused' => true,
                ]
                : [
                    'answer' => 'You can take this medicine with food or on an empty stomach, either is fine. What matters most is taking it at the same time each day so the level in your body stays steady.',
                    'seeClinician' => false, 'refused' => false,
                ],

            'summary' => [
                'forCaregiver' => 'Over the last week she has taken most of her medicines on time. She has missed a few evening doses. The morning routine is going well. It may help to put the evening tablets somewhere she will see them after dinner.',
                'forClinician' => 'Adherence approximately 86% over 7 days. Missed doses cluster in the evening window. No adverse effects reported through the app.',
                'concerns' => ['Evening doses are missed more often than morning doses.', 'No blood pressure readings have been recorded.'],
            ],

            default => throw new \RuntimeException("No mock response for task \"{$task}\""),
        };
    }
}
