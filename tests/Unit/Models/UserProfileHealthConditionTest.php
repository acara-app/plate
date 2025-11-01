<?php

declare(strict_types=1);

use App\Models\HealthCondition;
use App\Models\UserProfile;

test('to array', function (): void {
    $profile = UserProfile::factory()->create();
    $condition = HealthCondition::factory()->create();

    $profile->healthConditions()->attach($condition->id, ['notes' => 'Test notes']);

    $pivot = $profile->healthConditions()->first()->pivot;

    expect(array_keys($pivot->toArray()))
        ->toBe([
            'user_profile_id',
            'health_condition_id',
            'notes',
            'created_at',
            'updated_at',
        ]);
});

test('has correct table name', function (): void {
    $profile = UserProfile::factory()->create();
    $condition = HealthCondition::factory()->create();

    $profile->healthConditions()->attach($condition->id);

    $pivot = $profile->healthConditions()->first()->pivot;

    expect($pivot->getTable())->toBe('user_profile_health_condition');
});

test('belongs to user profile', function (): void {
    $profile = UserProfile::factory()->create();
    $condition = HealthCondition::factory()->create();

    $profile->healthConditions()->attach($condition->id);

    $pivot = $profile->healthConditions()->first()->pivot;

    expect($pivot->userProfile)
        ->toBeInstanceOf(UserProfile::class)
        ->id->toBe($profile->id);
});

test('belongs to health condition', function (): void {
    $profile = UserProfile::factory()->create();
    $condition = HealthCondition::factory()->create();

    $profile->healthConditions()->attach($condition->id);

    $pivot = $profile->healthConditions()->first()->pivot;

    expect($pivot->healthCondition)
        ->toBeInstanceOf(HealthCondition::class)
        ->id->toBe($condition->id);
});

test('can store notes', function (): void {
    $profile = UserProfile::factory()->create();
    $condition = HealthCondition::factory()->create();

    $profile->healthConditions()->attach($condition->id, ['notes' => 'Patient has severe reaction']);

    $pivot = $profile->healthConditions()->first()->pivot;

    expect($pivot->notes)->toBe('Patient has severe reaction');
});

test('notes can be null', function (): void {
    $profile = UserProfile::factory()->create();
    $condition = HealthCondition::factory()->create();

    $profile->healthConditions()->attach($condition->id);

    $pivot = $profile->healthConditions()->first()->pivot;

    expect($pivot->notes)->toBeNull();
});
