<?php

declare(strict_types=1);

use App\Models\UserTelegramChat;
use Database\Factories\UserTelegramChatFactory;

arch()->preset()->php();
arch()->preset()->security()->ignoring([
    'assert',
    'sha1',
    UserTelegramChatFactory::class,
    UserTelegramChat::class,
]);

arch('strict rules')
    ->preset()->strict()
    ->ignoring([
        'App\Models',
        'App\Console\Commands',
        'App\Ai',
        'App\Http\Requests',
        'App\Services\Telegram',
    ]);

arch('controllers')
    ->expect('App\Http\Controllers')
    ->not->toBeUsed();
