<?php

return [
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'timeout' => (int) env('GEMINI_REQUEST_TIMEOUT', 120),
    ],
    'deepseek' => [
        'key' => env('DEEPSEEK_API_KEY'),
    ],
    'arkesel' => [
        // live = real Arkesel API (default). log = laravel.log only (ARKESEL_DRIVER=log).
        'driver' => env('ARKESEL_DRIVER', 'live'),
        'api_key' => env('ARKESEL_API_KEY', env('OTP_ARKESEL_API_KEY')),
        'base_url' => env('ARKESEL_BASE_URL', 'https://sms.arkesel.com'),
        'connect_timeout' => (int) env('ARKESEL_CONNECT_TIMEOUT', 12),
        'timeout' => (int) env('ARKESEL_TIMEOUT', 25),
        'retries' => (int) env('ARKESEL_RETRIES', 1),
        // When local dev cannot reach Arkesel, still complete OTP flows and show the code in admin UI.
        'fallback_on_connection_error' => env('ARKESEL_FALLBACK_ON_CONNECTION_ERROR', env('APP_ENV') === 'local'),
    ],
    'webpush' => [
        'vapid_public' => env('VAPID_PUBLIC_KEY'),
        'vapid_private' => env('VAPID_PRIVATE_KEY'),
    ],
];
