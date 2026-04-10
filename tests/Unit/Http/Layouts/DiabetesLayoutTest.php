<?php

declare(strict_types=1);

use App\Http\Layouts\DiabetesLayout;
use App\Models\HealthSyncSample;
use App\Models\User;

covers(DiabetesLayout::class);

it('dashboard data uses default time period when invalid period provided', function (): void {
    $user = User::factory()->create();

    $data = DiabetesLayout::dashboardData($user, 'invalid_period');

    expect($data['timePeriod'])->toBe('30d');
});

it('calculate weight stats determines upward trend', function (): void {
    $user = User::factory()->create();

    HealthSyncSample::factory()->weight()->create([
        'user_id' => $user->id,
        'value' => 80.0,
        'measured_at' => now()->subDays(2),
    ]);

    HealthSyncSample::factory()->weight()->create([
        'user_id' => $user->id,
        'value' => 81.0,
        'measured_at' => now(),
    ]);

    $data = DiabetesLayout::dashboardData($user);
    $stats = $data['summary']['weightStats'];

    expect($stats['trend'])->toBe('up')
        ->and($stats['diff'])->toBe(1.0);
});

it('calculate weight stats determines downward trend', function (): void {
    $user = User::factory()->create();

    HealthSyncSample::factory()->weight()->create([
        'user_id' => $user->id,
        'value' => 82.0,
        'measured_at' => now()->subDays(2),
    ]);

    HealthSyncSample::factory()->weight()->create([
        'user_id' => $user->id,
        'value' => 81.0,
        'measured_at' => now(),
    ]);

    $data = DiabetesLayout::dashboardData($user);
    $stats = $data['summary']['weightStats'];

    expect($stats['trend'])->toBe('down')
        ->and($stats['diff'])->toBe(1.0);
});

it('calculate weight stats determines stable trend', function (): void {
    $user = User::factory()->create();

    HealthSyncSample::factory()->weight()->create([
        'user_id' => $user->id,
        'value' => 81.0,
        'measured_at' => now()->subDays(2),
    ]);

    HealthSyncSample::factory()->weight()->create([
        'user_id' => $user->id,
        'value' => 81.0,
        'measured_at' => now(),
    ]);

    $data = DiabetesLayout::dashboardData($user);
    $stats = $data['summary']['weightStats'];

    expect($stats['trend'])->toBe('stable')
        ->and($stats['diff'])->toBe(0.0);
});

it('getRecentMedications returns recent unique medications', function (): void {
    $user = User::factory()->create();

    HealthSyncSample::factory()->medication()->for($user)->create([
        'metadata' => ['medication_name' => 'Metformin', 'medication_dosage' => '500mg'],
    ]);

    HealthSyncSample::factory()->medication()->for($user)->create([
        'metadata' => ['medication_name' => 'Aspirin', 'medication_dosage' => '100mg'],
    ]);

    HealthSyncSample::factory()->medication()->for($user)->create([
        'metadata' => ['medication_name' => 'Metformin', 'medication_dosage' => '500mg'],
    ]);

    HealthSyncSample::factory()->medication()->for($user)->create([
        'metadata' => ['some_other_key' => 'value'],
    ]);

    $result = DiabetesLayout::getRecentMedications($user);

    expect($result)->toHaveCount(2)
        ->and($result[0]['name'])->toBeIn(['Metformin', 'Aspirin'])
        ->and($result[0]['dosage'])->toBeIn(['500mg', '100mg']);
});

it('getRecentInsulins returns recent unique insulin entries', function (): void {
    $user = User::factory()->create();

    HealthSyncSample::factory()->insulin()->for($user)->create([
        'value' => 10,
        'metadata' => ['insulin_type' => 'bolus'],
    ]);

    HealthSyncSample::factory()->insulin()->for($user)->create([
        'value' => 20,
        'metadata' => ['insulin_type' => 'basal'],
    ]);

    HealthSyncSample::factory()->insulin()->for($user)->create([
        'value' => 10,
        'metadata' => ['insulin_type' => 'bolus'],
    ]);

    $result = DiabetesLayout::getRecentInsulins($user);

    expect($result)->toHaveCount(2)
        ->and($result[0]['units'])->toBeIn([10.0, 20.0])
        ->and($result[0]['type'])->toBeIn(['bolus', 'basal']);
});

it('calculate streak continues if log exists yesterday but not today', function (): void {
    $user = User::factory()->create();

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $user->id,
        'measured_at' => now()->subDay(),
    ]);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $user->id,
        'measured_at' => now()->subDays(2),
    ]);

    $data = DiabetesLayout::dashboardData($user);
    $stats = $data['summary']['streakStats'];

    expect($stats['currentStreak'])->toBe(2);
});
