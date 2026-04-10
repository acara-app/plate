<?php

declare(strict_types=1);

use App\Models\MobileSyncDevice;
use App\Models\User;

covers(MobileSyncDevice::class);

it('has correct casts', function (): void {
    $device = new MobileSyncDevice();
    $casts = $device->casts();

    expect($casts)
        ->toHaveKey('is_active', 'boolean')
        ->toHaveKey('paired_at', 'datetime')
        ->toHaveKey('last_synced_at', 'datetime')
        ->toHaveKey('token_expires_at', 'datetime');
});

it('belongs to a user', function (): void {
    $device = MobileSyncDevice::factory()->create();

    expect($device->user)->toBeInstanceOf(User::class);
});

describe('isTokenValid', function (): void {
    it('returns false when token is null', function (): void {
        $device = MobileSyncDevice::factory()->create([
            'linking_token' => null,
            'token_expires_at' => now()->addHours(24),
        ]);

        expect($device->isTokenValid())->toBeFalse();
    });

    it('returns false when token_expires_at is null', function (): void {
        $device = MobileSyncDevice::factory()->create([
            'linking_token' => 'ABC12345',
            'token_expires_at' => null,
        ]);

        expect($device->isTokenValid())->toBeFalse();
    });

    it('returns false when token has expired', function (): void {
        $device = MobileSyncDevice::factory()->create([
            'linking_token' => 'ABC12345',
            'token_expires_at' => now()->subHour(),
        ]);

        expect($device->isTokenValid())->toBeFalse();
    });

    it('returns true when token is valid and not expired', function (): void {
        $device = MobileSyncDevice::factory()->create([
            'linking_token' => 'ABC12345',
            'token_expires_at' => now()->addHours(24),
        ]);

        expect($device->isTokenValid())->toBeTrue();
    });
});

describe('generateToken', function (): void {
    it('generates an 8 character uppercase token', function (): void {
        $device = MobileSyncDevice::factory()->create();

        $token = $device->generateToken();

        expect($token)->toHaveLength(8)
            ->and($token)->toBe(mb_strtoupper($token));
    });

    it('stores the token on the model', function (): void {
        $device = MobileSyncDevice::factory()->create();

        $token = $device->generateToken();

        expect($device->fresh()->linking_token)->toBe($token);
    });

    it('sets token expiration to 30 days by default', function (): void {
        $device = MobileSyncDevice::factory()->create();

        $device->generateToken();

        $diffHours = $device->fresh()->token_expires_at->diffInHours(now(), absolute: true);
        expect($diffHours)->toBeGreaterThanOrEqual(719)
            ->toBeLessThanOrEqual(721);
    });
});

describe('markAsPaired', function (): void {
    it('sets device name and paired_at', function (): void {
        $device = MobileSyncDevice::factory()->withToken()->create();

        $device->markAsPaired('iPhone 15 Pro', 'test-uuid');

        $fresh = $device->fresh();

        expect($fresh->device_name)->toBe('iPhone 15 Pro')
            ->and($fresh->device_identifier)->toBe('test-uuid')
            ->and($fresh->paired_at)->not->toBeNull()
            ->and($fresh->linking_token)->toBeNull()
            ->and($fresh->token_expires_at)->toBeNull()
            ->and($fresh->is_active)->toBeTrue();
    });

    it('allows null device identifier', function (): void {
        $device = MobileSyncDevice::factory()->withToken()->create();

        $device->markAsPaired('iPhone 15 Pro');

        expect($device->fresh()->device_identifier)->toBeNull();
    });
});

describe('scopes', function (): void {
    it('active scope returns only active devices', function (): void {
        MobileSyncDevice::factory()->create(['is_active' => true]);
        MobileSyncDevice::factory()->create(['is_active' => false]);

        $results = MobileSyncDevice::active()->get();

        expect($results)->toHaveCount(1);
    });

    it('paired scope returns only paired devices', function (): void {
        MobileSyncDevice::factory()->paired()->create();
        MobileSyncDevice::factory()->create(['paired_at' => null]);

        $results = MobileSyncDevice::paired()->get();

        expect($results)->toHaveCount(1);
    });

    it('pending scope returns devices with token but not paired', function (): void {
        MobileSyncDevice::factory()->withToken()->create(['paired_at' => null]);
        MobileSyncDevice::factory()->paired()->create();
        MobileSyncDevice::factory()->create(['linking_token' => null, 'paired_at' => null]);

        $results = MobileSyncDevice::pending()->get();

        expect($results)->toHaveCount(1);
    });
});
