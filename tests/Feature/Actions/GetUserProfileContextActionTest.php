<?php

declare(strict_types=1);

use App\Actions\GetUserProfileContextAction;
use App\Models\DietaryPreference;
use App\Models\HealthCondition;
use App\Models\User;
use App\Models\UserMedication;
use App\Models\UserProfile;

beforeEach(function (): void {
    $this->action = resolve(GetUserProfileContextAction::class);
});

it('returns profile not completed for user without profile', function (): void {
    $user = User::factory()->create();

    $result = $this->action->handle($user);

    expect($result)
        ->onboarding_completed->toBeFalse()
        ->missing_data->toContain('profile')
        ->context->toContain('not completed their profile');
});

it('returns complete profile data for onboarded user', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->create([
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
        ->toHaveKeys(['age', 'height_cm', 'weight_kg', 'bmi', 'bmr', 'tdee']);
});

it('includes dietary preferences in context', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'onboarding_completed' => true,
    ]);
    $preference = DietaryPreference::factory()->create(['name' => 'Vegetarian']);
    $profile->dietaryPreferences()->attach($preference->id, ['severity' => 'strict', 'notes' => 'No meat at all']);

    $result = $this->action->handle($user);

    expect($result['raw_data']['dietary_preferences'])
        ->toHaveCount(1)
        ->and($result['raw_data']['dietary_preferences'][0])
        ->toMatchArray([
            'name' => 'Vegetarian',
            'severity' => 'strict',
            'notes' => 'No meat at all',
        ]);
});

it('includes health conditions in context', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'onboarding_completed' => true,
    ]);
    $condition = HealthCondition::factory()->create(['name' => 'Type 2 Diabetes']);
    $profile->healthConditions()->attach($condition->id, ['notes' => 'Recently diagnosed']);

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
    UserMedication::factory()->create([
        'user_profile_id' => $profile->id,
        'name' => 'Metformin',
        'dosage' => '500mg',
        'frequency' => 'twice daily',
        'purpose' => 'Blood sugar control',
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
    // No preferences attached

    $result = $this->action->handle($user);

    expect($result['missing_data'])->toContain('dietary_preferences');
});

it('includes full goal details including diet type and macros', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'onboarding_completed' => true,
        'calculated_diet_type' => App\Enums\DietType::Keto,
        'additional_goals' => 'Build muscle and stay hydrated',
    ]);

    $result = $this->action->handle($user);

    expect($result['context'])
        ->toContain('Diet Type: keto')
        ->toContain('Recommended Macros: 5% carbs, 20% protein, 75% fat')
        ->toContain('Additional Goals: Build muscle and stay hydrated');
});
