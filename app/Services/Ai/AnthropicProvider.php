<?php

namespace App\Services\Ai;

use Anthropic\Client;
use Anthropic\Core\Exceptions\AnthropicException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\RateLimitException;
use Illuminate\Support\Facades\Log;

/**
 * Claude, through the official Anthropic PHP SDK.
 *
 * Structured outputs (output_config.format) guarantee the response shape, so
 * the UI can rely on a confidence field existing rather than hoping for one.
 */
class AnthropicProvider implements Provider
{
    public function name(): string  { return 'anthropic'; }
    public function label(): string { return 'Claude (Anthropic)'; }

    public function model(): string
    {
        return (string) config('services.anthropic.model', 'claude-opus-5');
    }

    public function isConfigured(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    public function complete(
        string $task,
        array $schema,
        string $system,
        string $user,
        ?string $imageDataUrl = null,
    ): ?array {
        // Image first, then the text: Claude reads a picture better when the
        // question comes after it.
        $content = [];
        if ($imageDataUrl && $image = ImagePayload::parse($imageDataUrl)) {
            $content[] = [
                'type' => 'image',
                'source' => ['type' => 'base64', 'media_type' => $image['media_type'], 'data' => $image['data']],
            ];
        }
        $content[] = ['type' => 'text', 'text' => $user];

        try {
            $message = app(Client::class)->beta->messages->create(
                maxTokens: (int) config('services.anthropic.max_tokens', 4000),
                messages: [['role' => 'user', 'content' => $content]],
                model: $this->model(),
                system: $system,
                outputConfig: [
                    'effort' => (string) config('services.anthropic.effort', 'medium'),
                    'format' => ['type' => 'json_schema', 'schema' => $schema],
                ],
                // A policy decline on a medicines question is a real
                // possibility. Rather than dropping the patient to a canned
                // answer, the API re-runs the request on a fallback model
                // inside the same call.
                fallbacks: 'default',
                betas: ['server-side-fallback-2026-07-01'],
            );
        } catch (RateLimitException $e) {
            Log::warning('[ai] anthropic rate limited', ['task' => $task]);

            return null;
        } catch (APIStatusException $e) {
            Log::warning('[ai] anthropic request failed', ['task' => $task, 'error' => $e->getMessage()]);

            return null;
        } catch (AnthropicException $e) {
            Log::warning('[ai] anthropic transport failure', ['task' => $task, 'error' => $e->getMessage()]);

            return null;
        } catch (\Throwable $e) {
            Log::warning('[ai] anthropic unexpected failure', ['task' => $task, 'error' => $e->getMessage()]);

            return null;
        }

        // The whole fallback chain declined. Degrade rather than show an error.
        if (($message->stopReason ?? null) === 'refusal') {
            Log::warning('[ai] anthropic refused', [
                'task' => $task, 'category' => $message->stopDetails->category ?? null,
            ]);

            return null;
        }

        $raw = '';
        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $raw .= $block->text;
            }
        }

        $parsed = json_decode(trim($raw), true);

        if (! is_array($parsed)) {
            Log::warning('[ai] anthropic returned an unparseable body', ['task' => $task]);

            return null;
        }

        return $parsed;
    }
}
