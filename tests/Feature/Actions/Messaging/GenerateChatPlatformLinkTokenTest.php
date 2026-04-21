<?php

declare(strict_types=1);

use App\Actions\Messaging\GenerateChatPlatformLinkToken;
use App\Enums\ChatPlatform;
use App\Models\User;
use App\Models\UserChatPlatformLink;

it('creates a pending link with an 8-char token', function (): void {
    $user = User::factory()->create();

    $result = resolve(GenerateChatPlatformLinkToken::class)->handle($user, ChatPlatform::Telegram);

    expect($result['token'])->toMatch('/^[A-Z0-9]{8}$/');
    expect($result['link'])->toBeInstanceOf(UserChatPlatformLink::class);
    expect($result['link']->user_id)->toBe($user->id);
    expect($result['link']->platform)->toBe(ChatPlatform::Telegram);
    expect($result['link']->is_active)->toBeTrue();
    expect($result['link']->linked_at)->toBeNull();
});

it('deactivates any previously active link for the same platform', function (): void {
    $user = User::factory()->create();
    $previous = UserChatPlatformLink::factory()->linked($user)->create();

    resolve(GenerateChatPlatformLinkToken::class)->handle($user, ChatPlatform::Telegram);

    expect($previous->fresh()->is_active)->toBeFalse();
    expect($user->chatPlatformLinks()->where('is_active', true)->count())->toBe(1);
});

it('respects a custom expiration', function (): void {
    $user = User::factory()->create();

    $result = resolve(GenerateChatPlatformLinkToken::class)->handle($user, ChatPlatform::Telegram, expiresInHours: 2);

    expect($result['link']->token_expires_at?->diffInHours(now(), true))->toEqualWithDelta(2.0, 0.01);
});
