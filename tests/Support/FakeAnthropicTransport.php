<?php

namespace Tests\Support;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A PSR-18 client that never touches the network.
 *
 * The Anthropic SDK takes its transport as a constructor option, which is the
 * seam this uses: the request the SDK would really have sent is captured here,
 * so a test can assert on the exact wire payload — model, system prompt, image
 * block, output schema — without a key or a connection.
 */
class FakeAnthropicTransport implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $sent = [];

    /** @param \Closure(RequestInterface): ResponseInterface $responder */
    public function __construct(private \Closure $responder) {}

    /** Answer every request with one Claude message carrying this JSON. */
    public static function replying(array $payload, int $status = 200): self
    {
        return new self(fn () => new Response($status, ['Content-Type' => 'application/json'], json_encode([
            'id' => 'msg_test', 'type' => 'message', 'role' => 'assistant',
            'model' => 'claude-opus-5',
            'content' => [['type' => 'text', 'text' => json_encode($payload)]],
            'stop_reason' => 'end_turn', 'stop_sequence' => null,
            'usage' => ['input_tokens' => 10, 'output_tokens' => 20],
        ])));
    }

    /** Answer with a raw body, for the cases where it is not valid JSON. */
    public static function replyingRaw(string $text): self
    {
        return new self(fn () => new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'id' => 'msg_test', 'type' => 'message', 'role' => 'assistant',
            'model' => 'claude-opus-5',
            'content' => [['type' => 'text', 'text' => $text]],
            'stop_reason' => 'end_turn', 'stop_sequence' => null,
            'usage' => ['input_tokens' => 10, 'output_tokens' => 20],
        ])));
    }

    /** Answer with an API error, so the degradation path can be exercised. */
    public static function failing(int $status, string $type = 'api_error'): self
    {
        return new self(fn () => new Response($status, ['Content-Type' => 'application/json'], json_encode([
            'type' => 'error',
            'error' => ['type' => $type, 'message' => 'nope'],
        ])));
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
