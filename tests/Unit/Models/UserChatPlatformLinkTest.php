<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserChatPlatformLink;
use Carbon\CarbonInterface;

covers(UserChatPlatformLink::class);

describe('isLinked', function (): void {
    it('returns true when user_id, linked_at, and is_active are all set', function (): void {
        $user = User::factory()->create();

        $link = UserChatPlatformLink::factory()->linked($user)->create();

        expect($link->isLinked())->toBeTrue();
    });

    it('returns false when user_id is null', function (): void {
        $link = UserChatPlatformLink::factory()->create([
            'user_id' => null,
            'linked_at' => now(),
            'is_active' => true,
        ]);

        expect($link->isLinked())->toBeFalse();
    });

    it('returns false when linked_at is null', function (): void {
        $user = User::factory()->create();
        $link = UserChatPlatformLink::factory()->create([
            'user_id' => $user->id,
            'linked_at' => null,
            'is_active' => true,
        ]);

        expect($link->isLinked())->toBeFalse();
    });

    it('returns false when is_active is false', function (): void {
        $user = User::factory()->create();
        $link = UserChatPlatformLink::factory()->create([
            'user_id' => $user->id,
            'linked_at' => now(),
            'is_active' => false,
        ]);

        expect($link->isLinked())->toBeFalse();
    });
});

describe('isTokenValid', function (): void {
    it('returns true when token and future expiry are set', function (): void {
        $link = UserChatPlatformLink::factory()->withToken()->create();

        expect($link->isTokenValid())->toBeTrue();
    });

    it('returns false when token is null', function (): void {
        $link = UserChatPlatformLink::factory()->create([
            'linking_token' => null,
            'token_expires_at' => now()->addHours(1),
        ]);

        expect($link->isTokenValid())->toBeFalse();
    });

    it('returns false when token is expired', function (): void {
        $link = UserChatPlatformLink::factory()->create([
            'linking_token' => 'ABC123XY',
            'token_expires_at' => now()->subMinute(),
        ]);

        expect($link->isTokenValid())->toBeFalse();
    });
});

describe('generateToken', function (): void {
    it('creates an 8-char alphanumeric token', function (): void {
        $link = UserChatPlatformLink::factory()->create();

        $token = $link->generateToken();

        expect($token)->toMatch('/^[A-Z0-9]{8}$/')
            ->and($link->fresh()->linking_token)->toBe($token);
    });

    it('sets expiry based on hours argument', function (): void {
        $link = UserChatPlatformLink::factory()->create();

        $link->generateToken(expiresInHours: 12);

        expect($link->fresh()->token_expires_at?->diffInHours(now(), absolute: true))
            ->toBeGreaterThanOrEqual(11)
            ->toBeLessThanOrEqual(13);
    });
});

describe('markAsLinked', function (): void {
    it('stamps the row as linked and clears the token', function (): void {
        $user = User::factory()->create();
        $link = UserChatPlatformLink::factory()->withToken()->create([
            'user_id' => null,
            'is_active' => false,
        ]);

        $link->markAsLinked($user);

        $fresh = $link->fresh();

        expect($fresh->user_id)->toBe($user->id)
            ->and($fresh->is_active)->toBeTrue()
            ->and($fresh->linked_at)->not->toBeNull()
            ->and($fresh->linking_token)->toBeNull()
            ->and($fresh->token_expires_at)->toBeNull();
    });
});

describe('scopes', function (): void {
    it('forUser filters by platform and platform_user_id', function (): void {
        $match = UserChatPlatformLink::factory()->create([
            'platform' => 'telegram',
            'platform_user_id' => '111',
        ]);

        UserChatPlatformLink::factory()->create([
            'platform' => 'telegram',
            'platform_user_id' => '222',
        ]);

        UserChatPlatformLink::factory()->create([
            'platform' => 'mock',
            'platform_user_id' => '111',
        ]);

        $found = UserChatPlatformLink::query()->forUser('telegram', '111')->pluck('id')->all();

        expect($found)->toBe([$match->id]);
    });

    it('active filters by is_active=true', function (): void {
        $activeLink = UserChatPlatformLink::factory()->create(['is_active' => true]);
        UserChatPlatformLink::factory()->inactive()->create();

        $found = UserChatPlatformLink::query()->active()->pluck('id')->all();

        expect($found)->toBe([$activeLink->id]);
    });

    it('linked matches rows with user_id, linked_at and is_active', function (): void {
        $user = User::factory()->create();
        $linkedRow = UserChatPlatformLink::factory()->linked($user)->create();
        UserChatPlatformLink::factory()->create(['user_id' => null, 'linked_at' => null]);
        UserChatPlatformLink::factory()->inactive()->create(['user_id' => $user->id, 'linked_at' => now()]);

        $found = UserChatPlatformLink::query()->linked()->pluck('id')->all();

        expect($found)->toBe([$linkedRow->id]);
    });

    it('pendingLink matches rows without user_id that have a linking token', function (): void {
        $pending = UserChatPlatformLink::factory()->withToken('PENDING1')->create(['user_id' => null]);
        UserChatPlatformLink::factory()->create(['user_id' => null, 'linking_token' => null]);
        UserChatPlatformLink::factory()->withToken('OWNED123')->create(['user_id' => User::factory()]);

        $found = UserChatPlatformLink::query()->pendingLink()->pluck('id')->all();

        expect($found)->toBe([$pending->id]);
    });
});

describe('relationships', function (): void {
    it('belongs to a user', function (): void {
        $user = User::factory()->create();
        $link = UserChatPlatformLink::factory()->linked($user)->create();

        expect($link->user)->not->toBeNull()
            ->and($link->user->id)->toBe($user->id);
    });
});

describe('casts', function (): void {
    it('casts is_active to boolean and timestamps to Carbon', function (): void {
        $link = UserChatPlatformLink::factory()->linked()->create([
            'user_id' => User::factory(),
            'token_expires_at' => now()->addHour(),
        ]);

        expect($link->is_active)->toBeBool()
            ->and($link->linked_at)->toBeInstanceOf(CarbonInterface::class)
            ->and($link->token_expires_at)->toBeInstanceOf(CarbonInterface::class);
    });
});
