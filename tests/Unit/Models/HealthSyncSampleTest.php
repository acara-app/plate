<?php

declare(strict_types=1);

use App\Models\HealthSyncSample;
use App\Models\MobileSyncDevice;
use App\Models\User;

it('belongs to a user', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->for($user)->create();

    expect($sample->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

it('belongs to a mobile sync device', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create();
    $sample = HealthSyncSample::factory()->for($user)->create([
        'mobile_sync_device_id' => $device->id,
    ]);

    expect($sample->mobileSyncDevice)
        ->toBeInstanceOf(MobileSyncDevice::class)
        ->id->toBe($device->id);
});
