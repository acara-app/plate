<?php

declare(strict_types=1);

use App\Actions\Auth\RevokeDeviceTokens;
use App\Models\User;

covers(RevokeDeviceTokens::class);

it('revokes both the new and legacy token names for a device', function (): void {
    $user = User::factory()->create();
    $user->createToken('mobile:device-uuid', ['chat:converse']);
    $user->createToken('mobile-sync:42', ['sync:push', 'chat:converse']);
    $user->createToken('mobile:other-device', ['chat:converse']);

    resolve(RevokeDeviceTokens::class)->handle($user, 'device-uuid', 42);

    $names = $user->tokens()->pluck('name');

    expect($names)->toHaveCount(1)
        ->and($names->all())->toBe(['mobile:other-device']);
});

it('revokes only the new name when no legacy id is given', function (): void {
    $user = User::factory()->create();
    $user->createToken('mobile:device-uuid', ['chat:converse']);

    resolve(RevokeDeviceTokens::class)->handle($user, 'device-uuid');

    expect($user->tokens()->count())->toBe(0);
});

it('does nothing when neither name resolves', function (): void {
    $user = User::factory()->create();
    $user->createToken('mobile:device-uuid', ['chat:converse']);

    resolve(RevokeDeviceTokens::class)->handle($user, null);

    expect($user->tokens()->count())->toBe(1);
});
