<?php

declare(strict_types=1);

use App\Actions\GetUserProfileContextAction;
use App\Enums\BloodType;
use App\Enums\DietType;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserProfileAttribute;

covers(GetUserProfileContextAction::class);

beforeEach(function (): void {
    $this->action = resolve(GetUserProfileContextAction::class);
});

it('auto-creates profile and reports missing fields for user without profile', function (): void {
    $user = User::factory()->create();

    $result = $this->action->handle($user);

    expect($result)
        ->onboarding_completed->toBeFalsy()
        ->missing_data->toContain('age')
        ->missing_data->toContain('height')
        ->missing_data->toContain('weight')
        ->missing_data->toContain('sex')
        ->missing_data->toContain('primary_goal')
        ->context->toContain('MISSING PROFILE DATA');

    expect($user->refresh()->profile)->toBeInstanceOf(UserProfile::class);
});

it('returns complete profile data for onboarded user', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175.0,
        'weight' => 70.0,
        'onboarding_completed' => true,
    ]);

    $result = $this->action->handle($user);

    expect($result)
        ->onboarding_completed->toBeTrue()
        ->raw_data->toHaveKeys(['biometrics', 'dietary_preferences', 'health_conditions', 'medications', 'goals']);
    expect($result['raw_data']['biometrics'])
        ->toHaveKeys(['age', 'date_of_birth', 'height_cm', 'weight_kg', 'sex', 'blood_type', 'bmi', 'bmr', 'tdee']);
});

it('includes dietary preferences in context', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'onboarding_completed' => true,
    ]);
    UserProfileAttribute::factory()->dietaryPattern('Vegetarian')->create([
        'user_profile_id' => $profile->id,
        'notes' => 'No meat at all',
    ]);

    $result = $this->action->handle($user);

    expect($result['raw_data']['dietary_preferences'])
        ->toHaveCount(1)
        ->and($result['raw_data']['dietary_preferences'][0])
        ->toMatchArray([
            'name' => 'Vegetarian',
            'notes' => 'No meat at all',
        ]);
});

it('includes health conditions in context', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'onboarding_completed' => true,
    ]);
    UserProfileAttribute::factory()->healthCondition('Type 2 Diabetes')->create([
        'user_profile_id' => $profile->id,
        'notes' => 'Recently diagnosed',
    ]);

    $result = $this->action->handle($user);

    expect($result['raw_data']['health_conditions'])
        ->toHaveCount(1)
        ->and($result['raw_data']['health_conditions'][0])
        ->toMatchArray([
            'name' => 'Type 2 Diabetes',
            'notes' => 'Recently diagnosed',
        ]);
});

it('includes medications in context', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'onboarding_completed' => true,
    ]);
    UserProfileAttribute::factory()->medication('Metformin', [
        'dosage' => '500mg',
        'frequency' => 'twice daily',
        'purpose' => 'Blood sugar control',
    ])->create([
        'user_profile_id' => $profile->id,
    ]);

    $result = $this->action->handle($user);

    expect($result['raw_data']['medications'])
        ->toHaveCount(1)
        ->and($result['raw_data']['medications'][0])
        ->toMatchArray([
            'name' => 'Metformin',
            'dosage' => '500mg',
            'frequency' => 'twice daily',
            'purpose' => 'Blood sugar control',
        ]);
});

it('identifies missing biometric data', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => null,
        'height' => null,
        'weight' => null,
        'sex' => null,
        'goal_choice' => null,
        'onboarding_completed' => false,
    ]);

    $result = $this->action->handle($user);

    expect($result['missing_data'])
        ->toContain('age')
        ->toContain('height')
        ->toContain('weight')
        ->toContain('sex')
        ->toContain('primary_goal');
});

it('formats context as natural language string', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175.0,
        'weight' => 70.0,
        'target_weight' => 65.0,
        'onboarding_completed' => true,
    ]);

    $result = $this->action->handle($user);

    expect($result['context'])
        ->toContain('BIOMETRICS')
        ->toContain('Age: 30')
        ->toContain('Height: 175cm')
        ->toContain('Weight: 70kg')
        ->toContain('Target Weight: 65kg');
});

it('identifies missing dietary preferences', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'onboarding_completed' => true,
    ]);

    $result = $this->action->handle($user);

    expect($result['missing_data'])->toContain('dietary_preferences');
});

it('includes household context in formatted output', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'onboarding_completed' => true,
        'household_context' => 'My husband Bataa is 38, has type 2 diabetes. Kids: Tana (12, peanut allergy).',
    ]);

    $result = $this->action->handle($user);

    expect($result['context'])
        ->toContain('HOUSEHOLD/FAMILY: My husband Bataa is 38, has type 2 diabetes.')
        ->and($result['raw_data']['household_context'])
        ->toBe('My husband Bataa is 38, has type 2 diabetes. Kids: Tana (12, peanut allergy).');
});

it('omits household section when context is null', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'onboarding_completed' => true,
        'household_context' => null,
    ]);

    $result = $this->action->handle($user);

    expect($result['context'])->not->toContain('HOUSEHOLD/FAMILY');
});

it('includes date_of_birth and blood_type in biometrics', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'date_of_birth' => '1996-04-04',
        'blood_type' => BloodType::APositive,
        'height' => 175.0,
        'weight' => 70.0,
        'onboarding_completed' => true,
    ]);

    $result = $this->action->handle($user);

    expect($result['raw_data']['biometrics'])
        ->date_of_birth->toBe('1996-04-04')
        ->blood_type->toBe('A+')
        ->and($result['context'])
        ->toContain('Date of Birth: 1996-04-04')
        ->toContain('Blood Type: A+');
});

it('includes full goal details including diet type and macros', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'onboarding_completed' => true,
        'calculated_diet_type' => DietType::Keto,
        'additional_goals' => 'Build muscle and stay hydrated',
    ]);

    $result = $this->action->handle($user);

    expect($result['context'])
        ->toContain('Diet Type: keto')
        ->toContain('Recommended Macros: 5% carbs, 20% protein, 75% fat')
        ->toContain('Additional Goals: Build muscle and stay hydrated');
});
