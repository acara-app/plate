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
        ->toHaveKey('last_synced_at', 'datetime');
});

it('belongs to a user', function (): void {
    $device = MobileSyncDevice::factory()->create();

    expect($device->user)->toBeInstanceOf(User::class);
});

describe('scopes', function (): void {
    it('paired scope returns only paired devices', function (): void {
        MobileSyncDevice::factory()->paired()->create();
        MobileSyncDevice::factory()->create(['paired_at' => null]);

        $results = MobileSyncDevice::paired()->get();

        expect($results)->toHaveCount(1);
    });
});
