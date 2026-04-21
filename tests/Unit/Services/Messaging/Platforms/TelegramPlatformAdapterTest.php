<?php

declare(strict_types=1);

use App\Enums\ChatPlatform;
use App\Services\Messaging\Platforms\TelegramPlatformAdapter;

it('reads bot username from messaging config', function (): void {
    config()->set('messaging.platforms.telegram.bot_username', 'SomeBot_bot');

    expect((new TelegramPlatformAdapter)->botUsername())->toBe('SomeBot_bot');
});

it('builds a deep link url from config', function (): void {
    config()->set('messaging.platforms.telegram.bot_username', 'SomeBot_bot');
    config()->set('messaging.platforms.telegram.deep_link_url', 'https://t.me');

    expect((new TelegramPlatformAdapter)->deepLinkUrl())->toBe('https://t.me/SomeBot_bot');
});

it('normalizes trailing slashes on deep_link_url', function (): void {
    config()->set('messaging.platforms.telegram.bot_username', 'SomeBot_bot');
    config()->set('messaging.platforms.telegram.deep_link_url', 'https://t.me/');

    expect((new TelegramPlatformAdapter)->deepLinkUrl())->toBe('https://t.me/SomeBot_bot');
});

it('falls back to empty strings when config values are missing', function (): void {
    config()->set('messaging.platforms.telegram.bot_username');
    config()->set('messaging.platforms.telegram.deep_link_url');

    $adapter = new TelegramPlatformAdapter;

    expect($adapter->botUsername())->toBe('');
    expect($adapter->deepLinkUrl())->toBe('/');
});

it('formats linking commands for telegram', function (): void {
    expect((new TelegramPlatformAdapter)->linkingCommandFor('ABC12345'))->toBe('/link ABC12345');
});

it('identifies itself as the telegram platform', function (): void {
    expect((new TelegramPlatformAdapter)->platform())->toBe(ChatPlatform::Telegram);
});
