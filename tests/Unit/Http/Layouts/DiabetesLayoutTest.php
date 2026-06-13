<?php

declare(strict_types=1);

use App\Http\Layouts\DiabetesLayout;
use App\Models\HealthSyncSample;
use App\Models\User;

covers(DiabetesLayout::class);

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
