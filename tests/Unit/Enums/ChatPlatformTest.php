<?php

declare(strict_types=1);

use App\Contracts\Messaging\ChatPlatformAdapter;
use App\Enums\ChatPlatform;
use App\Services\Messaging\Platforms\TelegramPlatformAdapter;

it('exposes a label for every case', function (ChatPlatform $platform): void {
    expect($platform->label())->toBeString()->not->toBeEmpty();
})->with(ChatPlatform::cases());

it('stores telegram under the expected value', function (): void {
    expect(ChatPlatform::Telegram->value)->toBe('telegram');
});

it('resolves the adapter registered in config', function (): void {
    $adapter = ChatPlatform::Telegram->adapter();

    expect($adapter)
        ->toBeInstanceOf(ChatPlatformAdapter::class)
        ->toBeInstanceOf(TelegramPlatformAdapter::class);

    expect($adapter->platform())->toBe(ChatPlatform::Telegram);
});

it('throws when no adapter is configured for a platform', function (): void {
    config()->set('messaging.platforms.telegram.adapter');

    ChatPlatform::Telegram->adapter();
})->throws(InvalidArgumentException::class);

it('throws when the configured adapter class does not exist', function (): void {
    config()->set('messaging.platforms.telegram.adapter', 'App\\Does\\Not\\Exist');

    ChatPlatform::Telegram->adapter();
})->throws(InvalidArgumentException::class);

it('round-trips through tryFrom for valid values', function (): void {
    expect(ChatPlatform::tryFrom('telegram'))->toBe(ChatPlatform::Telegram);
    expect(ChatPlatform::tryFrom('whatsapp'))->toBeNull();
});
