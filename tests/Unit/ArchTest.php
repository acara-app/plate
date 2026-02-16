<?php

declare(strict_types=1);

arch()->preset()->php();
arch()->preset()->security()->ignoring([
    'assert',
    'sha1',
    Database\Factories\UserTelegramChatFactory::class,
    App\Models\UserTelegramChat::class,
]);

// Apply strict preset but exclude protected method check for models with accessors
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

//
