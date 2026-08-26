<?php

namespace Tests\Support;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A PSR-18 client that never touches the network.
 *
 * Both SDKs — Anthropic's and OpenAI's — take their transport as an injectable
 * option, which is the seam this uses. The request that would really have gone
 * out is captured here, so a test can assert on the exact wire payload (model,
 * system prompt, image block, output schema) with no key and no connection.
 *
 * The named constructors come in pairs because only the response envelope
 * differs between the two providers; everything a test does with the captured
 * request is identical.
 */
class FakeAiTransport implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $sent = [];

    /** @param \Closure(RequestInterface): ResponseInterface $responder */
    public function __construct(private \Closure $responder) {}

    // ── Anthropic envelopes ──────────────────────────────────────────

    /** One Claude message whose text block carries this JSON. */
    public static function claude(array $payload): self
    {
        return self::claudeRaw(json_encode($payload));
    }

    /** Same, but with a body that may not be valid JSON. */
    public static function claudeRaw(string $text, string $stopReason = 'end_turn'): self
    {
        return new self(fn () => self::json([
            'id' => 'msg_test', 'type' => 'message', 'role' => 'assistant',
            'model' => 'claude-opus-5',
            'content' => [['type' => 'text', 'text' => $text]],
            'stop_reason' => $stopReason, 'stop_sequence' => null,
            'usage' => ['input_tokens' => 10, 'output_tokens' => 20],
        ]));
    }

    /** A refusal — the whole fallback chain declined. */
    public static function claudeRefusal(): self
    {
        return new self(fn () => self::json([
            'id' => 'msg_test', 'type' => 'message', 'role' => 'assistant',
            'model' => 'claude-opus-5',
            'content' => [],
            'stop_reason' => 'refusal',
            'stop_details' => ['type' => 'refusal', 'category' => 'bio', 'explanation' => 'no'],
            'stop_sequence' => null,
            'usage' => ['input_tokens' => 10, 'output_tokens' => 0],
        ]));
    }

    // ── OpenAI envelopes ─────────────────────────────────────────────

    /** One chat completion whose message content carries this JSON. */
    public static function gpt(array $payload): self
    {
        return self::gptRaw(json_encode($payload));
    }

    public static function gptRaw(string $content): self
    {
        return new self(fn () => self::json([
            'id' => 'chatcmpl-test', 'object' => 'chat.completion',
            'created' => 1735689600, 'model' => 'gpt-4o-mini',
            'choices' => [[
                'index' => 0,
                'message' => ['role' => 'assistant', 'content' => $content],
                'finish_reason' => 'stop',
            ]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30],
        ]));
    }

    /** A refusal — populated `refusal`, no content. */
    public static function gptRefusal(): self
    {
        return new self(fn () => self::json([
            'id' => 'chatcmpl-test', 'object' => 'chat.completion',
            'created' => 1735689600, 'model' => 'gpt-4o-mini',
            'choices' => [[
                'index' => 0,
                'message' => ['role' => 'assistant', 'content' => null, 'refusal' => 'I cannot help with that.'],
                'finish_reason' => 'stop',
            ]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 0, 'total_tokens' => 10],
        ]));
    }

    // ── Failures, shared ─────────────────────────────────────────────

    /** An API error, so the degradation path can be exercised. */
    public static function failing(int $status, string $type = 'api_error'): self
    {
        return new self(fn () => self::json(
            ['type' => 'error', 'error' => ['type' => $type, 'message' => 'nope']],
            $status,
        ));
    }

    /** The network never answered at all. */
    public static function unreachable(): self
    {
        return new self(function (RequestInterface $request) {
            throw new class('Connection refused', $request) extends \RuntimeException implements \Psr\Http\Client\NetworkExceptionInterface
            {
                public function __construct(string $message, private RequestInterface $request)
                {
                    parent::__construct($message);
                }

                public function getRequest(): RequestInterface
                {
                    return $this->request;
                }
            };
        });
    }

    // ── Plumbing ─────────────────────────────────────────────────────

    private static function json(array $body, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body));
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->sent[] = $request;

        return ($this->responder)($request);
    }

    /** The decoded JSON body of the request the SDK built. */
    public function lastBody(): array
    {
        $request = $this->sent[array_key_last($this->sent)] ?? null;

        if (! $request) {
            return [];
        }

        $request->getBody()->rewind();

        return json_decode((string) $request->getBody(), true) ?: [];
    }
}
