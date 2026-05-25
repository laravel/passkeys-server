<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Relying Party Identity (global, applies to all guards)
    |--------------------------------------------------------------------------
    */
    'relying_party_id' => env('PASSKEYS_RP_ID', parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost'),

    'allowed_origins' => [config('app.url')],

    /*
    |--------------------------------------------------------------------------
    | WebAuthn Operation Timing (global)
    |--------------------------------------------------------------------------
    */
    'timeout' => env('PASSKEYS_TIMEOUT', 60000),

    /*
    |--------------------------------------------------------------------------
    | User Handle Secret (global)
    |--------------------------------------------------------------------------
    |
    | HMAC secret used to derive a stable WebAuthn user handle from each
    | user model. Falls back to APP_KEY.
    */
    'user_handle_secret' => env('PASSKEYS_USER_HANDLE_SECRET', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Per-Guard Configuration
    |--------------------------------------------------------------------------
    |
    | Define one block per auth guard that should support passkeys. Each
    | guard chooses its own user model, DB connection (null = default),
    | post-login redirect, route middleware, and passkey-management
    | middleware (typically password.confirm or equivalent re-auth gate).
    */
    'guards' => [

        'web' => [
            'user_model' => env('AUTH_MODEL', 'App\\Models\\User'),
            'connection' => null,
            'redirect' => '/',
            'middleware' => ['web'],
            'management_middleware' => ['password.confirm'],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Throttle (global, applies to all guards' passkey endpoints)
    |--------------------------------------------------------------------------
    */
    'throttle' => 'throttle:6,1',

];
