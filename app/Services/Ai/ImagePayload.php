<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Log;

/**
 * `data:image/jpeg;base64,…` is what the browser hands us. Both providers want
 * the media type and the payload separately, so the parsing lives here rather
 * than twice.
 *
 * Anything that is not an image both providers accept is dropped rather than
 * forwarded: a rejected request costs a patient their answer, and there is
 * nothing useful to do with a PDF here.
 */
class ImagePayload
{
    private const SUPPORTED = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    /** @return array{media_type: string, data: string, url: string}|null */
    public static function parse(string $dataUrl): ?array
    {
        if (! preg_match('#^data:([\w.+-]+/[\w.+-]+);base64,(.+)$#s', $dataUrl, $m)) {
            Log::warning('[ai] image payload is not a base64 data URL');

            return null;
        }

        if (! in_array($m[1], self::SUPPORTED, true)) {
            Log::warning('[ai] unsupported image type', ['type' => $m[1]]);

            return null;
        }

        return ['media_type' => $m[1], 'data' => $m[2], 'url' => $dataUrl];
    }
}
