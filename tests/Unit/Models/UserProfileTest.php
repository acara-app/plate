<?php

declare(strict_types=1);

use App\Enums\AnimalProductChoice;
use App\Enums\DietType;
use App\Enums\GoalChoice;
use App\Enums\IntensityChoice;
use App\Enums\Sex;
use App\Enums\UserProfileAttributeCategory;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserProfileAttribute;

covers(UserProfile::class);

it('to array', function (): void {
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

it('belongs to user', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->for($user)->create();

    expect($profile->user)
        ->toBeInstanceOf(User::class)
        ->id->toBe($user->id);
});

it('has many attributes', function (): void {
    $profile = UserProfile::factory()->create();

    UserProfileAttribute::factory()->allergy('Peanuts')->create(['user_profile_id' => $profile->id]);
    UserProfileAttribute::factory()->dietaryPattern('Vegan')->create(['user_profile_id' => $profile->id]);
    UserProfileAttribute::factory()->healthCondition('Type 2 Diabetes')->create(['user_profile_id' => $profile->id]);

    expect($profile->attributes)
        ->toHaveCount(3)
        ->each->toBeInstanceOf(UserProfileAttribute::class);
});

it('dietary attributes excludes health conditions and medications', function (): void {
    $profile = UserProfile::factory()->create();

    UserProfileAttribute::factory()->allergy('Peanuts')->create(['user_profile_id' => $profile->id]);
    UserProfileAttribute::factory()->dietaryPattern('Vegan')->create(['user_profile_id' => $profile->id]);
    UserProfileAttribute::factory()->healthCondition('Type 2 Diabetes')->create(['user_profile_id' => $profile->id]);
    UserProfileAttribute::factory()->medication('Metformin')->create(['user_profile_id' => $profile->id]);

    expect($profile->dietaryAttributes)
        ->toHaveCount(2)
        ->each(fn ($attr) => $attr->category->not->toBe(UserProfileAttributeCategory::HealthCondition)
            ->category->not->toBe(UserProfileAttributeCategory::Medication));
});

it('health condition attributes returns only health conditions', function (): void {
    $profile = UserProfile::factory()->create();

    UserProfileAttribute::factory()->allergy('Peanuts')->create(['user_profile_id' => $profile->id]);
    UserProfileAttribute::factory()->healthCondition('Type 2 Diabetes')->create(['user_profile_id' => $profile->id]);
    UserProfileAttribute::factory()->healthCondition('Hypertension')->create(['user_profile_id' => $profile->id]);

    expect($profile->healthConditionAttributes)
        ->toHaveCount(2)
        ->each(fn ($attr) => $attr->category->toBe(UserProfileAttributeCategory::HealthCondition));
});

it('medication attributes returns only medications', function (): void {
    $profile = UserProfile::factory()->create();

    UserProfileAttribute::factory()->allergy('Peanuts')->create(['user_profile_id' => $profile->id]);
    UserProfileAttribute::factory()->medication('Metformin')->create(['user_profile_id' => $profile->id]);

    expect($profile->medicationAttributes)
        ->toHaveCount(1)
        ->first()->category->toBe(UserProfileAttributeCategory::Medication);
});

it('calculate bmi returns null when height is missing', function (): void {
    $profile = UserProfile::factory()->create([
        'height' => null,
        'weight' => 70,
    ]);

    expect($profile->bmi)->toBeNull();
});

it('calculate bmi returns null when weight is missing', function (): void {
    $profile = UserProfile::factory()->create([
        'height' => 175,
        'weight' => null,
    ]);

    expect($profile->bmi)->toBeNull();
});

it('calculate bmi returns correct value', function (): void {
    $profile = UserProfile::factory()->create([
        'height' => 175,
        'weight' => 70,
    ]);

    $expectedBMI = round(70 / (1.75 * 1.75), 2);

    expect($profile->bmi)->toBe($expectedBMI);
});

it('calculate bmr returns null when weight is missing', function (): void {
    $profile = UserProfile::factory()->create([
        'weight' => null,
        'height' => 175,
        'age' => 30,
        'sex' => Sex::Male,
    ]);

    expect($profile->bmr)->toBeNull();
});

it('calculate bmr returns null when height is missing', function (): void {
    $profile = UserProfile::factory()->create([
        'weight' => 70,
        'height' => null,
        'age' => 30,
        'sex' => Sex::Male,
    ]);

    expect($profile->bmr)->toBeNull();
});

it('calculate bmr returns null when age is missing', function (): void {
    $profile = UserProfile::factory()->create([
        'weight' => 70,
        'height' => 175,
        'age' => null,
        'sex' => Sex::Male,
    ]);

    expect($profile->bmr)->toBeNull();
});

it('calculate bmr returns null when sex is missing', function (): void {
    $profile = UserProfile::factory()->create([
        'weight' => 70,
        'height' => 175,
        'age' => 30,
        'sex' => null,
    ]);

    expect($profile->bmr)->toBeNull();
});

it('calculate bmr returns correct value for male', function (): void {
    $profile = UserProfile::factory()->create([
        'weight' => 70,
        'height' => 175,
        'age' => 30,
        'sex' => Sex::Male,
    ]);

    $expectedBMR = round((10 * 70) + (6.25 * 175) - (5 * 30) + 5, 2);

    expect($profile->bmr)->toBe($expectedBMR);
});

it('calculate bmr returns correct value for female', function (): void {
    $profile = UserProfile::factory()->create([
        'weight' => 60,
        'height' => 165,
        'age' => 25,
        'sex' => Sex::Female,
    ]);

    $expectedBMR = round((10 * 60) + (6.25 * 165) - (5 * 25) - 161, 2);

    expect($profile->bmr)->toBe($expectedBMR);
});

it('calculate tdee returns null when bmr cannot be calculated', function (): void {
    $profile = UserProfile::factory()->create([
        'weight' => null,
        'height' => 175,
        'age' => 30,
        'sex' => Sex::Male,
    ]);

    expect($profile->tdee)->toBeNull();
});

it('calculate tdee returns correct value', function (): void {
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

it('calculates bmr from the date of birth when it is known', function (): void {
    $profile = UserProfile::factory()->create([
        'weight' => 70,
        'height' => 175,
        'age' => 30,
        'date_of_birth' => now()->subYears(40)->subDay(),
        'sex' => Sex::Male,
    ]);

    expect($profile->bmr)->toBe(round((10 * 70) + (6.25 * 175) - (5 * 40) + 5, 2));
});

it('maps the onboarding choices to a diet type with defaults for missing ones', function (array $choices, DietType $dietType): void {
    expect(UserProfile::factory()->create($choices)->dietType())->toBe($dietType);
})->with([
    'nothing chosen' => [['goal_choice' => null, 'animal_product_choice' => null, 'intensity_choice' => null], DietType::Balanced],
    'steady glucose, aggressive' => [['goal_choice' => GoalChoice::Spikes, 'animal_product_choice' => AnimalProductChoice::Omnivore, 'intensity_choice' => IntensityChoice::Aggressive], DietType::LowCarb],
]);
