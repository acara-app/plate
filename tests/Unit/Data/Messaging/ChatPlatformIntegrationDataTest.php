<?php

declare(strict_types=1);

use App\Services\Messaging\Platforms\TelegramPlatformAdapter;
use App\Data\Messaging\ChatPlatformIntegrationData;
use App\Enums\ChatPlatform;
use App\Models\User;
use App\Models\UserChatPlatformLink;

covers(ChatPlatformIntegrationData::class);

beforeEach(function (): void {
    config([
        'messaging.platforms.telegram.adapter' => TelegramPlatformAdapter::class,
        'messaging.platforms.telegram.bot_username' => 'test_bot',
        'messaging.platforms.telegram.deep_link_url' => 'https://t.me',
    ]);
});

it('can be created from a null link', function (): void {
    $data = ChatPlatformIntegrationData::fromLink(ChatPlatform::Telegram, null);

    expect($data)
        ->platform->toBe(ChatPlatform::Telegram)
        ->label->toBe('Telegram')
        ->botUsername->toBe('test_bot')
        ->deepLinkUrl->toBe('https://t.me/test_bot')
        ->linkingCommand->toBe('/link YOUR_TOKEN')
        ->isConnected->toBeFalse()
        ->linkingToken->toBeNull()
        ->tokenExpiresAt->toBeNull()
        ->connectedAt->toBeNull();
});

it('can be created from a linked platform link', function (): void {
    $user = User::factory()->create();
    $link = UserChatPlatformLink::factory()->linked($user)->create();

    $data = ChatPlatformIntegrationData::fromLink(ChatPlatform::Telegram, $link);

    expect($data)
        ->platform->toBe(ChatPlatform::Telegram)
        ->label->toBe('Telegram')
        ->botUsername->toBe('test_bot')
        ->deepLinkUrl->toBe('https://t.me/test_bot')
        ->linkingCommand->toBe('/link YOUR_TOKEN')
        ->isConnected->toBeTrue()
        ->linkingToken->toBeNull()
        ->tokenExpiresAt->toBeNull()
        ->connectedAt->not->toBeNull();
});

it('can be created from a pending platform link with valid token', function (): void {
    $link = UserChatPlatformLink::factory()->withToken()->create();

    $data = ChatPlatformIntegrationData::fromLink(ChatPlatform::Telegram, $link);

    expect($data)
        ->platform->toBe(ChatPlatform::Telegram)
        ->isConnected->toBeFalse()
        ->linkingToken->toBe($link->linking_token)
        ->tokenExpiresAt->not->toBeNull();
});
