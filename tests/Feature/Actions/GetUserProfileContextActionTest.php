<?php

declare(strict_types=1);

use App\Actions\BuildAiSafeUserProfile;
use App\Actions\GetUserProfileContextAction;
use App\Data\Ai\AiSafeUserProfileData;
use App\Enums\BloodType;
use App\Enums\DietType;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserProfileAttribute;
use App\Services\Ai\UserProfileContextFormatter;

covers([
    AiSafeUserProfileData::class,
    BuildAiSafeUserProfile::class,
    GetUserProfileContextAction::class,
    UserProfileContextFormatter::class,
]);

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
        ->raw_data->toHaveKeys([
            'biometrics',
            'dietary_preferences',
            'goals',
            'health_conditions',
            'medications',
            'household',
        ]);
    expect($result['raw_data']['biometrics'])
        ->toHaveKeys(['age', 'height_cm', 'weight_kg', 'sex', 'bmi', 'bmr', 'tdee']);
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

it('includes medications in the AI-safe profile sections without model internals', function (): void {
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
            'category' => 'medication',
            'name' => 'Metformin',
            'metadata' => [
                'dosage' => '500mg',
                'frequency' => 'twice daily',
                'purpose' => 'Blood sugar control',
            ],
        ])
        ->not->toHaveKeys(['id', 'user_profile_id', 'created_at', 'updated_at']);

    expect($result['context'])
        ->toContain('MEDICATIONS')
        ->toContain('Metformin')
        ->toContain('500mg');
});

it('includes health conditions in the AI-safe profile sections', function (): void {
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
            'category' => 'health_condition',
            'name' => 'Type 2 Diabetes',
            'notes' => 'Recently diagnosed',
        ]);

    expect($result['context'])
        ->toContain('HEALTH CONDITIONS')
        ->toContain('Type 2 Diabetes')
        ->toContain('Recently diagnosed');
});

it('keeps household context out of prompt-formatted context unless explicitly requested', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'onboarding_completed' => true,
        'household_context' => 'My husband Bataa is 38, has type 2 diabetes. Kids: Tana (12, peanut allergy).',
    ]);

    $result = $this->action->handle($user);

    expect($result['raw_data']['household'])->toHaveKey(
        'summary',
        'My husband Bataa is 38, has type 2 diabetes. Kids: Tana (12, peanut allergy).',
    );

    expect($result['context'])
        ->not->toContain('HOUSEHOLD/FAMILY')
        ->not->toContain('Bataa');
});

it('never exposes date_of_birth or blood_type in raw_data or AI-facing context', function (): void {
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
        ->not->toHaveKey('date_of_birth')
        ->not->toHaveKey('blood_type');

    expect($result['context'])
        ->not->toContain('Date of Birth')
        ->not->toContain('1996-04-04')
        ->not->toContain('Blood Type')
        ->not->toContain('A+');
});

it('includes additional goals in explicit profile prompt context', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'onboarding_completed' => true,
        'calculated_diet_type' => DietType::Keto,
        'additional_goals' => 'Build muscle and stay hydrated',
    ]);

    $result = $this->action->handle($user);

    expect($result['raw_data']['goals']['additional_goals'])->toBe('Build muscle and stay hydrated');

    expect($result['context'])
        ->toContain('Diet Type: keto')
        ->toContain('Recommended Macros: 5% carbs, 20% protein, 75% fat')
        ->toContain('Additional Goals: Build muscle and stay hydrated');
});

it('builds a safety section for tool calls from allergies health conditions medications and household', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->create([
        'user_id' => $user->id,
        'household_context' => 'My child has a peanut allergy.',
    ]);
    UserProfileAttribute::factory()->allergy('Peanuts')->create([
        'user_profile_id' => $profile->id,
    ]);
    UserProfileAttribute::factory()->healthCondition('Type 2 Diabetes')->create([
        'user_profile_id' => $profile->id,
    ]);
    UserProfileAttribute::factory()->medication('Metformin')->create([
        'user_profile_id' => $profile->id,
    ]);

    $result = resolve(BuildAiSafeUserProfile::class)->handle($user);

    expect($result->section('safety'))
        ->toHaveKeys(['allergies', 'health_conditions', 'medications', 'household'])
        ->and($result->section('safety')['allergies'][0]['name'])->toBe('Peanuts')
        ->and($result->section('safety')['health_conditions'][0]['name'])->toBe('Type 2 Diabetes')
        ->and($result->section('safety')['medications'][0]['name'])->toBe('Metformin')
        ->and($result->section('safety')['household']['summary'])->toBe('My child has a peanut allergy.');
});
