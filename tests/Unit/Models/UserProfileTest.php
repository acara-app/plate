<?php

declare(strict_types=1);

use App\Enums\DietaryPreferenceType;
use App\Enums\Sex;
use App\Models\DietaryPreference;
use App\Models\HealthCondition;
use App\Models\User;
use App\Models\UserProfile;

test('to array', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->for($user)->create()->refresh();

    expect(array_keys($profile->toArray()))
        ->toContain(
            'id',
            'user_id',
            'age',
            'height',
            'weight',
            'sex',
            'goal_choice',
            'target_weight',
            'additional_goals',
            'onboarding_completed',
            'onboarding_completed_at',
            'created_at',
            'updated_at',
            'bmi',
            'bmr',
            'tdee',
            'units_preference'
        );
});

test('belongs to user', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->for($user)->create();

    expect($profile->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

test('belongs to many dietary preferences', function (): void {
    $profile = UserProfile::factory()->create();

    $pref1 = DietaryPreference::factory()->create(['name' => 'Peanuts', 'type' => DietaryPreferenceType::Allergy->value]);
    $pref2 = DietaryPreference::factory()->create(['name' => 'Gluten', 'type' => DietaryPreferenceType::Intolerance->value]);
    $pref3 = DietaryPreference::factory()->create(['name' => 'Vegan', 'type' => DietaryPreferenceType::Pattern->value]);

    $profile->dietaryPreferences()->attach([$pref1->id, $pref2->id, $pref3->id]);

    expect($profile->dietaryPreferences)
        ->toHaveCount(3)
        ->each->toBeInstanceOf(DietaryPreference::class);
});

test('belongs to many health conditions', function (): void {
    $profile = UserProfile::factory()->create();

    $condition1 = HealthCondition::factory()->create(['name' => 'Type 2 Diabetes']);
    $condition2 = HealthCondition::factory()->create(['name' => 'Hypertension']);

    $profile->healthConditions()->attach($condition1->id, ['notes' => 'Test note']);
    $profile->healthConditions()->attach($condition2->id);

    expect($profile->healthConditions)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(HealthCondition::class);

    expect($profile->healthConditions->first()->pivot->notes)->toBe('Test note');
});

test('calculate bmi returns null when height is missing', function (): void {
    $profile = UserProfile::factory()->create([
        'height' => null,
        'weight' => 70,
    ]);

    expect($profile->bmi)->toBeNull();
});

test('calculate bmi returns null when weight is missing', function (): void {
    $profile = UserProfile::factory()->create([
        'height' => 175,
        'weight' => null,
    ]);

    expect($profile->bmi)->toBeNull();
});

test('calculate bmi returns correct value', function (): void {
    $profile = UserProfile::factory()->create([
        'height' => 175,
        'weight' => 70,
    ]);

    $expectedBMI = round(70 / (1.75 * 1.75), 2);

    expect($profile->bmi)->toBe($expectedBMI);
});

test('calculate bmr returns null when weight is missing', function (): void {
    $profile = UserProfile::factory()->create([
        'weight' => null,
        'height' => 175,
        'age' => 30,
        'sex' => Sex::Male,
    ]);

    expect($profile->bmr)->toBeNull();
});

test('calculate bmr returns null when height is missing', function (): void {
    $profile = UserProfile::factory()->create([
        'weight' => 70,
        'height' => null,
        'age' => 30,
        'sex' => Sex::Male,
    ]);

    expect($profile->bmr)->toBeNull();
});

test('calculate bmr returns null when age is missing', function (): void {
    $profile = UserProfile::factory()->create([
        'weight' => 70,
        'height' => 175,
        'age' => null,
        'sex' => Sex::Male,
    ]);

    expect($profile->bmr)->toBeNull();
});

test('calculate bmr returns null when sex is missing', function (): void {
    $profile = UserProfile::factory()->create([
        'weight' => 70,
        'height' => 175,
        'age' => 30,
        'sex' => null,
    ]);

    expect($profile->bmr)->toBeNull();
});

test('calculate bmr returns correct value for male', function (): void {
    $profile = UserProfile::factory()->create([
        'weight' => 70,
        'height' => 175,
        'age' => 30,
        'sex' => Sex::Male,
    ]);

    $expectedBMR = round((10 * 70) + (6.25 * 175) - (5 * 30) + 5, 2);

    expect($profile->bmr)->toBe($expectedBMR);
});

test('calculate bmr returns correct value for female', function (): void {
    $profile = UserProfile::factory()->create([
        'weight' => 60,
        'height' => 165,
        'age' => 25,
        'sex' => Sex::Female,
    ]);

    $expectedBMR = round((10 * 60) + (6.25 * 165) - (5 * 25) - 161, 2);

    expect($profile->bmr)->toBe($expectedBMR);
});

test('calculate tdee returns null when bmr cannot be calculated', function (): void {
    $profile = UserProfile::factory()->create([
        'weight' => null,
        'height' => 175,
        'age' => 30,
        'sex' => Sex::Male,
    ]);

    expect($profile->tdee)->toBeNull();
});

test('calculate tdee returns correct value', function (): void {
    $profile = UserProfile::factory()->create([
        'weight' => 70,
        'height' => 175,
        'age' => 30,
        'sex' => Sex::Male,
        'derived_activity_multiplier' => 1.55,
    ]);

    $bmr = $profile->bmr;
    $expectedTDEE = round($bmr * 1.55, 2);

    expect($profile->tdee)->toBe($expectedTDEE);
});
