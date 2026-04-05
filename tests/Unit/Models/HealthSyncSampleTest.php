<?php

declare(strict_types=1);

use App\Enums\HealthEntrySource;
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

it('filters by entry source scope', function (): void {
    $user = User::factory()->create();
    HealthSyncSample::factory()->for($user)->fromWeb()->create();
    HealthSyncSample::factory()->for($user)->fromMobileSync()->create();

    $webSamples = HealthSyncSample::query()->forEntrySource(HealthEntrySource::Web)->get();
    $mobileSamples = HealthSyncSample::query()->forEntrySource(HealthEntrySource::MobileSync)->get();

    expect($webSamples)->toHaveCount(1)
        ->and($mobileSamples)->toHaveCount(1);
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

it('returns correct category for known type identifiers', function (string $typeIdentifier, string $expectedCategory): void {
    expect(HealthSyncSample::categoryFor($typeIdentifier))->toBe($expectedCategory);
})->with([
    ['carbohydrates', 'food'],
    ['protein', 'food'],
    ['totalFat', 'food'],
    ['dietaryEnergy', 'food'],
    ['bloodGlucose', 'glucose'],
    ['weight', 'vitals'],
    ['heartRate', 'heart_rate'],
    ['stepCount', 'steps'],
    ['activeEnergy', 'active_energy'],
    ['walkingRunningDistance', 'distance'],
    ['flightsClimbed', 'flights_climbed'],
    ['standMinutes', 'stand_time'],
    ['walkingSpeed', 'mobility'],
    ['environmentalAudioExposure', 'environment'],
]);

it('returns other category for unknown type identifiers', function (): void {
    expect(HealthSyncSample::categoryFor('bloodOxygen'))->toBe('other');
});

it('resolves type filter to null for all', function (): void {
    $user = User::factory()->create();

    expect(HealthSyncSample::resolveTypeFilter('all', $user->id))->toBeNull();
});

it('resolves type filter by raw type identifier', function (): void {
    $user = User::factory()->create();
    HealthSyncSample::factory()->heartRate()->for($user)->create();

    expect(HealthSyncSample::resolveTypeFilter('heartRate', $user->id))->toBe(['heartRate']);
});

it('resolves type filter by category', function (): void {
    $user = User::factory()->create();
    HealthSyncSample::factory()->heartRate()->for($user)->create();

    $result = HealthSyncSample::resolveTypeFilter('heart_rate', $user->id);

    expect($result)->toContain('heartRate');
});

it('falls back to raw type when no category matches', function (): void {
    $user = User::factory()->create();

    expect(HealthSyncSample::resolveTypeFilter('unknownType', $user->id))->toBe(['unknownType']);
});
