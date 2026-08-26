<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Log;
use OpenAI\Contracts\ClientContract;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\RateLimitException;
use OpenAI\Exceptions\ServerException;
use OpenAI\Exceptions\TransporterException;
use OpenAI\Exceptions\UnserializableResponse;

/**
 * GPT, through the official OpenAI PHP client.
 *
 * Same contract as the Anthropic provider: structured outputs guarantee the
 * shape, failures return null rather than throwing, and nothing above this
 * class knows which of the two answered.
 */
class OpenAiProvider implements Provider
{
    public function name(): string  { return 'openai'; }
    public function label(): string { return 'GPT (OpenAI)'; }

    public function model(): string
    {
        return (string) config('services.openai.model', 'gpt-4o-mini');
    }

    public function isConfigured(): bool
    {
        return filled(config('services.openai.key'));
    }

    public function complete(
        string $task,
        array $schema,
        string $system,
        string $user,
        ?string $imageDataUrl = null,
    ): ?array {
        $content = [['type' => 'text', 'text' => $user]];

        if ($imageDataUrl && $image = ImagePayload::parse($imageDataUrl)) {
            // OpenAI takes the whole data URL rather than the parts.
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => $image['url']]];
        }

        try {
            $response = app(ClientContract::class)->chat()->create([
                'model' => $this->model(),
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $content],
                ],
                // Strict mode requires every property listed in `required` and
                // additionalProperties:false at every level — which the schemas
                // on AiService already satisfy, because Anthropic wants the
                // same thing.
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => ['name' => $task, 'strict' => true, 'schema' => $schema],
                ],
                'max_completion_tokens' => (int) config('services.openai.max_tokens', 1600),
            ]);
        } catch (RateLimitException $e) {
            Log::warning('[ai] openai rate limited', ['task' => $task]);

            return null;
        } catch (ErrorException|ServerException $e) {
            Log::warning('[ai] openai request failed', ['task' => $task, 'error' => $e->getMessage()]);

            return null;
        } catch (TransporterException|UnserializableResponse $e) {
            Log::warning('[ai] openai transport failure', ['task' => $task, 'error' => $e->getMessage()]);

            return null;
        } catch (\Throwable $e) {
            Log::warning('[ai] openai unexpected failure', ['task' => $task, 'error' => $e->getMessage()]);

            return null;
        }

        $choice = $response->choices[0] ?? null;

        // A refusal comes back as a populated `refusal` field with no content,
        // which is the OpenAI equivalent of Anthropic's refusal stop reason.
        if (filled($choice?->message?->refusal ?? null)) {
            Log::warning('[ai] openai refused', ['task' => $task]);

            return null;
        }

        $parsed = json_decode(trim((string) ($choice?->message?->content ?? '')), true);

        if (! is_array($parsed)) {
            Log::warning('[ai] openai returned an unparseable body', ['task' => $task]);

            return null;
        }

        return $parsed;
    }
}
