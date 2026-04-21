<?php

declare(strict_types=1);

use App\Enums\ChatPlatform;
use App\Models\User;
use App\Models\UserChatPlatformLink;

it('is linked only when user, linked_at and is_active are all set', function (): void {
    $user = User::factory()->create();
    $pending = UserChatPlatformLink::factory()->withToken()->create();
    $linked = UserChatPlatformLink::factory()->linked($user)->create();
    $inactive = UserChatPlatformLink::factory()->linked($user)->inactive()->create();

    expect($pending->isLinked())->toBeFalse();
    expect($linked->isLinked())->toBeTrue();
    expect($inactive->isLinked())->toBeFalse();
});

it('reports token validity correctly', function (): void {
    $valid = UserChatPlatformLink::factory()->withToken()->create();
    $expired = UserChatPlatformLink::factory()->create([
        'linking_token' => 'EXPIRED1',
        'token_expires_at' => now()->subMinute(),
    ]);
    $missing = UserChatPlatformLink::factory()->create();

    expect($valid->isTokenValid())->toBeTrue();
    expect($expired->isTokenValid())->toBeFalse();
    expect($missing->isTokenValid())->toBeFalse();
});

it('generates and persists an 8-char uppercase alphanumeric token', function (): void {
    $link = UserChatPlatformLink::factory()->create();

    $token = $link->generateToken(expiresInHours: 2);

    expect($token)->toMatch('/^[A-Z0-9]{8}$/');
    expect($link->fresh()->linking_token)->toBe($token);
    expect($link->fresh()->token_expires_at?->diffInHours(now(), true))->toEqualWithDelta(2.0, 0.01);
});

it('defaults generateToken expiry to 24 hours', function (): void {
    $link = UserChatPlatformLink::factory()->create();

    $link->generateToken();

    expect($link->fresh()->token_expires_at?->diffInHours(now(), true))->toEqualWithDelta(24.0, 0.01);
});

it('marks a pending link as linked and clears token state', function (): void {
    $user = User::factory()->create();
    $link = UserChatPlatformLink::factory()->withToken()->create();

    $link->markAsLinked($user, platformUserId: '12345');

    $fresh = $link->fresh();
    expect($fresh->user_id)->toBe($user->id);
    expect($fresh->platform_user_id)->toBe('12345');
    expect($fresh->linked_at)->not->toBeNull();
    expect($fresh->is_active)->toBeTrue();
    expect($fresh->linking_token)->toBeNull();
    expect($fresh->token_expires_at)->toBeNull();
});

it('preserves platform_user_id when markAsLinked is called without one', function (): void {
    $user = User::factory()->create();
    $link = UserChatPlatformLink::factory()->create(['platform_user_id' => '999']);

    $link->markAsLinked($user);

    expect($link->fresh()->platform_user_id)->toBe('999');
});

it('overrides an existing platform_user_id when markAsLinked provides a new one', function (): void {
    $user = User::factory()->create();
    $link = UserChatPlatformLink::factory()->create(['platform_user_id' => '999']);

    $link->markAsLinked($user, platformUserId: '12345');

    expect($link->fresh()->platform_user_id)->toBe('12345');
});

it('allows persisting a link without a user (pending state)', function (): void {
    $link = UserChatPlatformLink::factory()->create(['user_id' => null]);

    expect($link->user_id)->toBeNull();
    expect($link->isLinked())->toBeFalse();
    expect($link->user)->toBeNull();
});

it('scopes forPlatformUser by platform + platform_user_id', function (): void {
    $match = UserChatPlatformLink::factory()->create([
        'platform' => ChatPlatform::Telegram,
        'platform_user_id' => '777',
    ]);
    UserChatPlatformLink::factory()->create([
        'platform' => ChatPlatform::Telegram,
        'platform_user_id' => '999',
    ]);

    $found = UserChatPlatformLink::query()
        ->forPlatformUser(ChatPlatform::Telegram, '777')
        ->get();

    expect($found)->toHaveCount(1);
    expect($found->first()?->id)->toBe($match->id);
});

it('scopes active to is_active=true', function (): void {
    UserChatPlatformLink::factory()->create(['is_active' => true]);
    UserChatPlatformLink::factory()->inactive()->create();

    expect(UserChatPlatformLink::query()->active()->count())->toBe(1);
});

it('scopes linked to rows with user_id, linked_at, and is_active', function (): void {
    $user = User::factory()->create();
    UserChatPlatformLink::factory()->linked($user)->create();
    UserChatPlatformLink::factory()->withToken()->create();
    UserChatPlatformLink::factory()->linked($user)->inactive()->create();

    expect(UserChatPlatformLink::query()->linked()->count())->toBe(1);
});

it('scopes pendingLink to valid-token, not-yet-linked rows', function (): void {
    UserChatPlatformLink::factory()->withToken()->create();
    UserChatPlatformLink::factory()->linked(User::factory()->create())->create();
    UserChatPlatformLink::factory()->create([
        'linking_token' => 'EXPIRED1',
        'token_expires_at' => now()->subHour(),
    ]);

    expect(UserChatPlatformLink::query()->pendingLink()->count())->toBe(1);
});

it('casts platform to the ChatPlatform enum', function (): void {
    $link = UserChatPlatformLink::factory()->create(['platform' => 'telegram']);

    expect($link->platform)->toBe(ChatPlatform::Telegram);
});

it('belongs to a user', function (): void {
    $user = User::factory()->create();
    $link = UserChatPlatformLink::factory()->linked($user)->create();

    expect($link->user)->not->toBeNull();
    expect($link->user?->id)->toBe($user->id);
});
