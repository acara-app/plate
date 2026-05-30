<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Chat-first experience
    |--------------------------------------------------------------------------
    |
    | Server-delivered kill-switch for the chat-first mobile launch. Ships off;
    | flipping this env (and clearing config cache) reverts new installs without
    | an app release. Delivered to the client via /api/v2/auth/{me,capabilities}.
    |
    */

    'chat_first_enabled' => env('MOBILE_CHAT_FIRST_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Supported native auth methods
    |--------------------------------------------------------------------------
    |
    | Advertised to clients so a self-hosted instance can hide methods it does
    | not support. Apple is mandatory whenever Google is offered (Guideline 4.8).
    |
    */

    'auth_methods' => [
        'apple' => env('MOBILE_AUTH_APPLE_ENABLED', true),
        'google' => env('MOBILE_AUTH_GOOGLE_ENABLED', true),
        'email' => env('MOBILE_AUTH_EMAIL_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimum supported app version
    |--------------------------------------------------------------------------
    */

    'min_app_version' => env('MOBILE_MIN_APP_VERSION', '1.5.0'),

];
