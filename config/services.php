<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],


    /*
    | The AI engine.
    |
    | OpenAI answers by default. Claude remains wired behind the same
    | App\Services\Ai\Provider interface, so switching is an env change —
    | no screen and no test of a screen knows which one it is talking to.
    |
    | 'mock' keeps the whole app demoable with no key and no network; 'live'
    | calls the selected provider from the server. The key never reaches a
    | browser, and AiLiveModeTest asserts that no Blade view can read it.
    */
    'ai' => [
        'provider' => env('MEDBUDDY_AI_PROVIDER', 'openai'),      // openai | anthropic
        'mode'     => env('MEDBUDDY_AI_MODE', 'mock'),            // mock | live
    ],

    'anthropic' => [
        'key'        => env('ANTHROPIC_API_KEY'),
        'model'      => env('ANTHROPIC_MODEL', 'claude-opus-5'),
        'effort'     => env('ANTHROPIC_EFFORT', 'medium'),        // low | medium | high | xhigh | max
        'max_tokens' => env('ANTHROPIC_MAX_TOKENS', 4000),
        'base_url'   => env('ANTHROPIC_BASE_URL'),
    ],

    'openai' => [
        'key'        => env('OPENAI_API_KEY'),
        'model'      => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 1600),
        'base_url'   => env('OPENAI_BASE_URL'),
    ],

];
