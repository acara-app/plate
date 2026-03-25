<?php

declare(strict_types=1);

use App\Models\MobileSyncDevice;
use App\Models\User;

it('pairs a device with a valid token', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->withToken()->create();

    $response = $this->postJson('/api/v1/sync/pair', [
        'token' => $device->linking_token,
        'device_name' => 'iPhone 15 Pro',
        'device_identifier' => 'test-uuid-123',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message', 'api_token', 'user' => ['name']])
        ->assertJson([
            'message' => 'Device paired successfully.',
            'user' => ['name' => $user->name],
        ]);

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
        ->assertJson(['message' => 'Invalid pairing token.']);
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
        ->assertJson(['message' => 'Pairing token has expired.']);
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

it('handles case-insensitive token input', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->withToken()->create();
    $lowercaseToken = mb_strtolower((string) $device->linking_token);

    $this->postJson('/api/v1/sync/pair', [
        'token' => $lowercaseToken,
        'device_name' => 'iPhone 15 Pro',
    ])->assertOk();
});
