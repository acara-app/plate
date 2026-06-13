<?php

declare(strict_types=1);

use App\Http\Layouts\DiabetesLayout;
use App\Models\HealthSyncSample;
use App\Models\Meal;
use App\Models\MealPlan;
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

it('getRecentMedications includes synced medications that only have a name', function (): void {
    $user = User::factory()->create();

    HealthSyncSample::factory()->medication()->for($user)->create([
        'metadata' => ['name' => 'Melatonin', 'form' => 'capsule'],
    ]);

    $result = DiabetesLayout::getRecentMedications($user);

    expect($result)->toHaveCount(1)
        ->and($result[0]['name'])->toBe('Melatonin')
        ->and($result[0]['dosage'])->toBe('1 capsule');
});

it('getTodaysMeals returns meals for the current plan day', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);
    Meal::factory()->count(2)->create(['meal_plan_id' => $mealPlan->id, 'day_number' => 1]);

    $result = DiabetesLayout::getTodaysMeals($user);

    expect($result)->toHaveCount(2);
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
