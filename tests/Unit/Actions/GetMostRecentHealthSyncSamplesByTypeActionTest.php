<?php

declare(strict_types=1);

use App\Actions\GetMostRecentHealthSyncSamplesByTypeAction;
use App\Models\HealthSyncSample;
use App\Models\User;

covers(GetMostRecentHealthSyncSamplesByTypeAction::class);

it('returns most recent sample per type', function (): void {
    $user = User::factory()->create();
    $action = resolve(GetMostRecentHealthSyncSamplesByTypeAction::class);

    HealthSyncSample::factory()->for($user)->bloodGlucose()->create([
        'measured_at' => now()->subDays(5),
        'value' => 100,
    ]);

    $recent = HealthSyncSample::factory()->for($user)->bloodGlucose()->create([
        'measured_at' => now()->subDay(),
        'value' => 120,
    ]);

    $result = $action->handle($user);

    expect($result)->toHaveCount(1)
        ->and($result['bloodGlucose']->id)->toBe($recent->id)
        ->and($result['bloodGlucose']->value)->toBe(120.0);
});

it('respects type filter', function (): void {
    $user = User::factory()->create();
    $action = resolve(GetMostRecentHealthSyncSamplesByTypeAction::class);

    HealthSyncSample::factory()->for($user)->bloodGlucose()->create(['measured_at' => now()]);
    HealthSyncSample::factory()->for($user)->weight()->create(['measured_at' => now()]);

    $result = $action->handle($user, ['bloodGlucose']);

    expect($result)->toHaveCount(1)
        ->and($result)->toHaveKey('bloodGlucose')
        ->and($result)->not->toHaveKey('weight');
});

it('excludes user characteristics', function (): void {
    $user = User::factory()->create();
    $action = resolve(GetMostRecentHealthSyncSamplesByTypeAction::class);

    HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => 'biologicalSex',
        'value' => 1,
        'unit' => '',
        'measured_at' => now(),
    ]);

    HealthSyncSample::factory()->for($user)->bloodGlucose()->create(['measured_at' => now()]);

    $result = $action->handle($user);

    expect($result)->toHaveCount(1)
        ->and($result)->toHaveKey('bloodGlucose')
        ->and($result)->not->toHaveKey('biologicalSex');
});

it('returns empty collection for user with no samples', function (): void {
    $user = User::factory()->create();
    $action = resolve(GetMostRecentHealthSyncSamplesByTypeAction::class);

    $result = $action->handle($user);

    expect($result)->toBeEmpty();
});

it('isolates per user', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $action = resolve(GetMostRecentHealthSyncSamplesByTypeAction::class);

    HealthSyncSample::factory()->for($otherUser)->bloodGlucose()->create(['measured_at' => now()]);

    $result = $action->handle($user);

    expect($result)->toBeEmpty();
});
