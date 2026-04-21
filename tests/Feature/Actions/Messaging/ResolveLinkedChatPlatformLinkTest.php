<?php

declare(strict_types=1);

use App\Actions\Messaging\ResolveLinkedChatPlatformLink;
use App\Enums\ChatPlatform;
use App\Models\User;
use App\Models\UserChatPlatformLink;

it('returns the linked record for a platform user', function (): void {
    $user = User::factory()->create();
    $link = UserChatPlatformLink::factory()->linked($user)->create([
        'platform_user_id' => '777',
    ]);

    $found = resolve(ResolveLinkedChatPlatformLink::class)->handle(ChatPlatform::Telegram, '777');

    expect($found?->id)->toBe($link->id);
    expect($found?->relationLoaded('user'))->toBeTrue();
});

it('returns null for unlinked platform users', function (): void {
    $result = resolve(ResolveLinkedChatPlatformLink::class)->handle(ChatPlatform::Telegram, '404');

    expect($result)->toBeNull();
});

it('does not return inactive links', function (): void {
    $user = User::factory()->create();
    UserChatPlatformLink::factory()->linked($user)->inactive()->create([
        'platform_user_id' => '555',
    ]);

    $result = resolve(ResolveLinkedChatPlatformLink::class)->handle(ChatPlatform::Telegram, '555');

    expect($result)->toBeNull();
});
