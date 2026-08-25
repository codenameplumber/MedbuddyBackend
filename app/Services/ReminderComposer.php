<?php

namespace App\Services;

use App\Models\Patient;

/**
 * Builds reminder subject and body — and enforces the privacy contract.
 *
 * THE RULE: a reminder names the DOSE WINDOW. Never the condition, never the
 * drug, never the dose.
 *
 * An email sits in an inbox, often on a shared family computer, and its
 * subject line shows in a lock-screen preview. A message naming an
 * antiretroviral or a TB regimen can disclose someone's diagnosis to whoever
 * is holding the phone. The specifics live behind the signed link, after the
 * signature is checked.
 *
 * assertSafe() is called on every composed message and there is a test that
 * fails the build if a drug name ever appears. This is enforced, not promised.
 */
class ReminderComposer
{
    /** Anything here appearing in a message body is a bug, not a style choice. */
    private const FORBIDDEN_PATTERNS = [
        '/\b\d+\s*(mg|mcg|ml|g|iu)\b/i',                                   // any dose
        '/\b(hiv|aids|tuberculosis|\btb\b|diabetes|hypertension|epilepsy|asthma|cancer|depression)\b/i',
    ];

    public function __construct(private ScheduleService $schedule) {}

    public function subject(Patient $patient, string $slot): string
    {
        return __('reminder.subject', ['slot' => __("reminder.slot_{$slot}", [], $patient->lang)], $patient->lang);
    }

    public function body(Patient $patient, string $slot): string
    {
        return __('reminder.body', [
            'name' => $patient->firstName(),
            'slot' => __("reminder.slot_{$slot}", [], $patient->lang),
        ], $patient->lang);
    }

    /**
     * @param  string[]  $drugNames  every medicine this patient takes
     * @throws \RuntimeException when the privacy contract would be broken
     */
    public function assertSafe(string $text, array $drugNames = []): void
    {
        $haystack = mb_strtolower($text);

        foreach ($drugNames as $drug) {
            // Compare on the first word: "Dolutegravir / Lamivudine" -> "dolutegravir"
            $token = mb_strtolower(explode(' ', trim($drug))[0]);
            if ($token !== '' && str_contains($haystack, $token)) {
                throw new \RuntimeException(
                    "Privacy contract violated: message body contains the medicine name \"{$drug}\"."
                );
            }
        }

        foreach (self::FORBIDDEN_PATTERNS as $pattern) {
            if (preg_match($pattern, $text)) {
                throw new \RuntimeException(
                    "Privacy contract violated: message body matches forbidden pattern {$pattern}."
                );
            }
        }
    }

    /** Convenience: compose and verify in one step. */
    public function compose(Patient $patient, string $slot): array
    {
        $subject = $this->subject($patient, $slot);
        $body    = $this->body($patient, $slot);

        $drugs = $patient->activePrescriptions()->pluck('name')->all();
        $this->assertSafe($subject, $drugs);
        $this->assertSafe($body, $drugs);

        return ['subject' => $subject, 'body' => $body];
    }
}
