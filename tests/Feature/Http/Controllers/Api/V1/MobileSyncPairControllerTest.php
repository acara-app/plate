<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\MobileSyncPairController;
use App\Models\HealthSyncSample;
use App\Models\MobileSyncDevice;
use App\Models\User;

covers(MobileSyncPairController::class);

it('pairs a device with a valid token', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->withToken()->create();

    $response = $this->postJson('/api/v1/sync/pair', [
        'token' => $device->linking_token,
        'device_name' => 'iPhone 15 Pro',
        'device_identifier' => 'test-uuid-123',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message', 'api_token', 'encryption_key', 'user' => ['name']])
        ->assertJson([
            'message' => 'Device paired successfully.',
            'user' => ['name' => $user->name],
        ]);

    expect($response->json('encryption_key'))->toBeString()->not->toBeEmpty();

    $fresh = $device->fresh();
    expect($fresh->paired_at)->not->toBeNull()
        ->and($fresh->device_name)->toBe('iPhone 15 Pro')
        ->and($fresh->device_identifier)->toBe('test-uuid-123')
        ->and($fresh->linking_token)->toBeNull();
});

it('returns 422 for invalid token', function (): void {
    $this->postJson('/api/v1/sync/pair', [
        'token' => 'INVALID1',
        'device_name' => 'iPhone 15 Pro',
    ])->assertUnprocessable()
        ->assertJson(['message' => 'Invalid pairing token. Please generate a new one in Settings → Mobile Sync on your Acara Plate dashboard.']);
});

it('returns 422 for expired token', function (): void {
    $device = MobileSyncDevice::factory()->create([
        'linking_token' => 'EXPIRED1',
        'token_expires_at' => now()->subHour(),
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/sync/pair', [
        'token' => 'EXPIRED1',
        'device_name' => 'iPhone 15 Pro',
    ])->assertUnprocessable()
        ->assertJson(['message' => 'Pairing token has expired. Please generate a new one in Settings → Mobile Sync on your Acara Plate dashboard.']);
});

it('returns 422 for already used token', function (): void {
    $device = MobileSyncDevice::factory()->paired()->create([
        'linking_token' => null,
    ]);

    $this->postJson('/api/v1/sync/pair', [
        'token' => 'NOTOKEN1',
        'device_name' => 'iPhone 15 Pro',
    ])->assertUnprocessable();
});

it('validates required fields', function (): void {
    $this->postJson('/api/v1/sync/pair', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token', 'device_name']);
});

it('validates token length', function (): void {
    $this->postJson('/api/v1/sync/pair', [
        'token' => 'SHORT',
        'device_name' => 'iPhone 15 Pro',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['token']);
});

it('creates a sanctum token for the paired device', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->withToken()->create();

    $response = $this->postJson('/api/v1/sync/pair', [
        'token' => $device->linking_token,
        'device_name' => 'iPhone 15 Pro',
    ]);

    $response->assertOk();
    expect($user->tokens()->count())->toBe(1)
        ->and($user->tokens()->first()->name)->toBe('mobile-sync:'.$device->id);
});

it('deactivates old device when re-pairing with the same device_identifier', function (): void {
    $user = User::factory()->create();

    $oldDevice = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'REUSE-UUID-123',
    ]);
    $user->createToken('mobile-sync:'.$oldDevice->id, ['sync:push']);

    $newDevice = MobileSyncDevice::factory()->for($user)->withToken()->create();

    $response = $this->postJson('/api/v1/sync/pair', [
        'token' => $newDevice->linking_token,
        'device_name' => 'iPhone 16',
        'device_identifier' => 'REUSE-UUID-123',
    ]);

    $response->assertOk();

    expect($oldDevice->fresh())
        ->not->toBeNull()
        ->is_active->toBeFalse()
        ->device_identifier->toBeNull()
        ->and($user->tokens()->where('name', 'mobile-sync:'.$oldDevice->id)->count())->toBe(0)
        ->and($newDevice->fresh()->device_identifier)->toBe('REUSE-UUID-123');
});

it('preserves health sync samples when re-pairing with the same device_identifier', function (): void {
    $user = User::factory()->create();

    $oldDevice = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'REUSE-UUID-123',
    ]);

    $sample = HealthSyncSample::factory()->for($user)->create([
        'mobile_sync_device_id' => $oldDevice->id,
        'type_identifier' => 'stepCount',
        'value' => 1500,
    ]);

    $newDevice = MobileSyncDevice::factory()->for($user)->withToken()->create();

    $this->postJson('/api/v1/sync/pair', [
        'token' => $newDevice->linking_token,
        'device_name' => 'iPhone 16',
        'device_identifier' => 'REUSE-UUID-123',
    ])->assertOk();

    $freshSample = $sample->fresh();

    expect($freshSample)->not->toBeNull()
        ->and($freshSample->value)->toBe(1500.0)
        ->and($freshSample->mobile_sync_device_id)->toBe($oldDevice->id);
});

it('handles case-insensitive token input', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->withToken()->create();
    $lowercaseToken = mb_strtolower((string) $device->linking_token);

    $this->postJson('/api/v1/sync/pair', [
        'token' => $lowercaseToken,
        'device_name' => 'iPhone 15 Pro',
    ])->assertOk();
});
