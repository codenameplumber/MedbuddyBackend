<?php

namespace App\Services\Ai;

/**
 * One model call, whoever is answering it.
 *
 * MedBuddy talks to exactly one provider at a time, chosen by
 * MEDBUDDY_AI_PROVIDER. The point of this interface is that the rest of the
 * application cannot tell which one it is: every screen asks AiService for an
 * explanation or an answer, and gets the same validated shape back either way.
 *
 * Implementations own their SDK's error handling and return null rather than
 * throwing, because a patient must never see a stack trace where an
 * explanation should be. AiService turns a null into the canned response.
 */
interface Provider
{
    /** Machine name — 'anthropic' or 'openai'. Matches the config key. */
    public function name(): string;

    /** What the console footer and the Settings page show a human. */
    public function label(): string;

    /** The model id actually being called. */
    public function model(): string;

    /** True when a key is present. False means fall back to mock answers. */
    public function isConfigured(): bool;

    /**
     * One structured-output call.
     *
     * @param  string  $task    explain | identify | chat | summary
     * @param  array   $schema  the JSON schema the response must satisfy
     * @param  ?string $imageDataUrl  a data: URL, when the patient sent a photo
     * @return array|null  decoded JSON, or null if the call could not be completed
     */
    public function complete(
        string $task,
        array $schema,
        string $system,
        string $user,
        ?string $imageDataUrl = null,
    ): ?array;
}
