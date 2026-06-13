<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\MobileSyncDeviceRegistrationController;
use App\Models\MobileSyncDevice;
use App\Models\User;

covers(MobileSyncDeviceRegistrationController::class);

it('requires authentication', function (): void {
    $this->postJson(route('api.v2.sync.devices'), [
        'device_name' => 'iPhone',
        'device_identifier' => 'device-uuid-1',
    ])->assertUnauthorized();
});

it('upgrades a chat-only token with sync:push and returns an encryption key', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('mobile:device-uuid-1', ['chat:converse'])->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v2.sync.devices'), [
            'device_name' => 'iPhone 17 Pro',
            'device_identifier' => 'device-uuid-1',
        ])
        ->assertOk()
        ->assertJsonStructure(['api_token', 'encryption_key']);

    $tokens = $user->tokens()->where('name', 'mobile:device-uuid-1')->get();
    expect($tokens)->toHaveCount(1)
        ->and($tokens->first()->abilities)->toBe(['chat:converse', 'sync:push']);

    $this->assertDatabaseHas('mobile_sync_devices', [
        'user_id' => $user->id,
        'device_identifier' => 'device-uuid-1',
        'is_active' => true,
    ]);
});

it('blocks a chat-only token from pushing health data before upgrade', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('mobile:device-uuid-1', ['chat:converse'])->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v2.sync.health-entries'), [
            'device_identifier' => 'device-uuid-1',
            'encrypted_payload' => 'x',
        ])->assertForbidden();
});

it('reuses the existing encryption key on re-registration', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('mobile:device-uuid-1', ['chat:converse'])->plainTextToken;

    $first = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v2.sync.devices'), [
            'device_name' => 'iPhone',
            'device_identifier' => 'device-uuid-1',
        ]);
    $first->assertOk();

    $second = $this->withHeader('Authorization', 'Bearer '.$first->json('api_token'))
        ->postJson(route('api.v2.sync.devices'), [
            'device_name' => 'iPhone',
            'device_identifier' => 'device-uuid-1',
        ]);
    $second->assertOk();

    expect($second->json('encryption_key'))->toBe($first->json('encryption_key'));
});

it('revokes legacy pairing tokens when re-registering a device', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'device-uuid-1',
    ]);
    $user->createToken('mobile-sync:'.$device->id, ['sync:push', 'chat:converse']);
    $token = $user->createToken('mobile:device-uuid-1', ['chat:converse'])->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v2.sync.devices'), [
            'device_name' => 'iPhone',
            'device_identifier' => 'device-uuid-1',
        ])->assertOk();

    expect($user->tokens()->where('name', 'mobile-sync:'.$device->id)->count())->toBe(0)
        ->and($user->tokens()->where('name', 'mobile:device-uuid-1')->count())->toBe(1);
});

it('reassigns a device identifier from another user and revokes their tokens', function (): void {
    $otherUser = User::factory()->create();
    $otherDevice = MobileSyncDevice::factory()->for($otherUser)->paired()->create([
        'device_identifier' => 'shared-uuid',
    ]);
    $otherUser->createToken('mobile:shared-uuid', ['chat:converse', 'sync:push']);
    $otherUser->createToken('mobile-sync:'.$otherDevice->id, ['sync:push', 'chat:converse']);

    $user = User::factory()->create();
    $token = $user->createToken('mobile:shared-uuid', ['chat:converse'])->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v2.sync.devices'), [
            'device_name' => 'iPhone',
            'device_identifier' => 'shared-uuid',
        ])->assertOk();

    expect($otherDevice->fresh()->is_active)->toBeFalse()
        ->and($otherDevice->fresh()->device_identifier)->toBeNull()
        ->and($otherUser->tokens()->where('name', 'mobile:shared-uuid')->count())->toBe(0)
        ->and($otherUser->tokens()->where('name', 'mobile-sync:'.$otherDevice->id)->count())->toBe(0)
        ->and($user->mobileSyncDevices()->where('device_identifier', 'shared-uuid')->where('is_active', true)->count())->toBe(1);
});
