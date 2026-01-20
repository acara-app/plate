<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'openfoodfacts' => [
        'url' => env('OPENFOODFACTS_URL', 'https://world.openfoodfacts.org'),
        'cache_minutes' => env('OPENFOODFACTS_CACHE_MINUTES', 10080), // 7 days
        'user_agent' => env('OPENFOODFACTS_USER_AGENT', 'AcaraPlate/1.0 (https://github.com/acara-app/plate)'),
    ],

    'usda' => [
        'api_key' => env('USDA_API_KEY'),
        'url' => env('USDA_URL', 'https://api.nal.usda.gov/fdc/v1'),
        'cache_minutes' => env('USDA_CACHE_MINUTES', 10080), // 7 days
    ],

    'turnstile' => [
        'key' => env('TURNSTILE_SITE_KEY'),
        'secret' => env('TURNSTILE_SECRET_KEY'),
    ],

    'indexnow' => [
        'key' => env('INDEXNOW_KEY'),
        'host' => env('INDEXNOW_HOST'),
        'key_location' => env('INDEXNOW_KEY_LOCATION'),
    ],

];
