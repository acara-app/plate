<?php

declare(strict_types=1);

use App\Enums\ChatPlatform;
use App\Services\Messaging\Platforms\TelegramPlatformAdapter;

return [
    'platforms' => [
        ChatPlatform::Telegram->value => [
            'adapter' => TelegramPlatformAdapter::class,
            'bot_username' => env('TELEGRAM_BOT_USERNAME', 'AcaraPlate_bot'),
            'deep_link_url' => 'https://t.me',
        ],
    ],
];
