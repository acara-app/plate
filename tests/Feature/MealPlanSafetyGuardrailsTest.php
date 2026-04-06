<?php

declare(strict_types=1);

use App\Ai\MealPlanPromptBuilder;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserProfileAttribute;

it('includes critical safety warning when user has type 2 diabetes', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    UserProfileAttribute::factory()->healthCondition('Type 2 Diabetes')->create([
        'user_profile_id' => $user->profile->id,
        'metadata' => [
            'safety_level' => 'critical',
            'dietary_rules' => [
                'Strictly avoid high-GI foods',
                'Limit carbs to 45–60g per meal',
            ],
            'foods_to_avoid' => [
                'White bread, white rice, instant oatmeal',
            ],
            'foods_to_prioritize' => [
                'Legumes',
            ],
        ],
    ]);
    $user->refresh();

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('CRITICAL SAFETY GUARDRAILS')
        ->toContain('Type 2 Diabetes')
        ->toContain('CRITICAL — Strict dietary rules apply')
        ->toContain('Dietary Rules');
});

it('includes dietary rules for type 2 diabetes in metadata', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    UserProfileAttribute::factory()->healthCondition('Type 2 Diabetes')->create([
        'user_profile_id' => $user->profile->id,
        'metadata' => [
            'safety_level' => 'critical',
            'dietary_rules' => [
                'Strictly avoid high-GI foods',
                'Limit carbs to 45–60g per meal',
            ],
        ],
    ]);
    $user->refresh();

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('Strictly avoid high-GI foods')
        ->toContain('Limit carbs to 45–60g per meal')
        ->toContain('Dietary Rules');
});

it('includes foods to avoid for diabetic users from metadata', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    UserProfileAttribute::factory()->healthCondition('Type 2 Diabetes')->create([
        'user_profile_id' => $user->profile->id,
        'metadata' => [
            'safety_level' => 'critical',
            'foods_to_avoid' => [
                'White bread, white rice, instant oatmeal',
            ],
        ],
    ]);
    $user->refresh();

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('White bread, white rice, instant oatmeal')
        ->toContain('Foods To Avoid');
});

it('includes foods to prioritize for diabetic users from metadata', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    UserProfileAttribute::factory()->healthCondition('Type 2 Diabetes')->create([
        'user_profile_id' => $user->profile->id,
        'metadata' => [
            'safety_level' => 'critical',
            'foods_to_prioritize' => [
                'Legumes',
            ],
        ],
    ]);
    $user->refresh();

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('Foods To Prioritize')
        ->toContain('Legumes');
});

it('includes diabetes safety guardrails for gestational diabetes', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    UserProfileAttribute::factory()->healthCondition('Gestational Diabetes')->create([
        'user_profile_id' => $user->profile->id,
        'metadata' => [
            'safety_level' => 'critical',
            'dietary_rules' => [
                'Follow carbohydrate counting guidelines',
            ],
        ],
    ]);
    $user->refresh();

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('Gestational Diabetes')
        ->toContain('CRITICAL — Strict dietary rules apply')
        ->toContain('Dietary Rules');
});

it('does not show critical warning for healthy users', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('CRITICAL SAFETY GUARDRAILS')
        ->toContain('General Safety Rules')
        ->not->toContain('CRITICAL — Strict dietary rules apply');
});

it('includes general safety rules for all users', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('ALLERGEN AWARENESS')
        ->toContain('PORTION REALISM')
        ->toContain('MEDICAL DISCLAIMER')
        ->toContain('HYDRATION');
});

it('includes halal requirements from metadata', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    UserProfileAttribute::factory()->restriction('Halal')->create([
        'user_profile_id' => $user->profile->id,
        'metadata' => [
            'requirements' => [
                'No pork or pork-derived products',
                'No alcohol or alcohol-based ingredients',
            ],
        ],
    ]);
    $user->refresh();

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('Halal')
        ->toContain('No pork or pork-derived products')
        ->toContain('No alcohol or alcohol-based ingredients');
});

it('includes kosher requirements from metadata', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    UserProfileAttribute::factory()->restriction('Kosher')->create([
        'user_profile_id' => $user->profile->id,
        'metadata' => [
            'requirements' => [
                'No pork or shellfish',
                'Never combine meat and dairy',
            ],
        ],
    ]);
    $user->refresh();

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('Kosher')
        ->toContain('No pork or shellfish')
        ->toContain('Never combine meat and dairy');
});

it('shows glucose monitoring data when available', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    HealthSyncSample::factory()->bloodGlucose()->count(5)->create([
        'user_id' => $user->id,
        'value' => 150,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('Glucose Monitoring Data')
        ->toContain('Total Readings');
});

it('includes caution warning for prediabetes from metadata', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    UserProfileAttribute::factory()->healthCondition('Prediabetes')->create([
        'user_profile_id' => $user->profile->id,
        'metadata' => [
            'safety_level' => 'warning',
        ],
    ]);
    $user->refresh();

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('Prediabetes')
        ->toContain('CAUTION — Dietary considerations required');
});
