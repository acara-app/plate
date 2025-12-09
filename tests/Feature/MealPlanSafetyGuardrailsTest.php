<?php

declare(strict_types=1);

use App\Ai\Agents\MealPlanPromptBuilder;
use App\Models\GlucoseReading;
use App\Models\HealthCondition;
use App\Models\User;
use App\Models\UserProfile;

it('includes diabetes safety guardrails when user has type 2 diabetes', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    $diabetes = HealthCondition::factory()->create([
        'name' => 'Type 2 Diabetes',
    ]);

    $user->profile->healthConditions()->attach($diabetes);
    $user->refresh(); // Reload the user with relationships

    $builder = app(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    // Check for critical safety section
    expect($prompt)->toContain('CRITICAL SAFETY GUARDRAILS')
        ->and($prompt)->toContain('DIABETES/GLUCOSE MANAGEMENT ACTIVE')
        ->and($prompt)->toContain('FORBIDDEN HIGH-GI FOODS')
        ->and($prompt)->toContain('Glycemic Index (GI) over 70');

    // Check for specific forbidden foods
    expect($prompt)->toContain('White bread, white rice')
        ->and($prompt)->toContain('fruit juice')
        ->and($prompt)->toContain('Watermelon');

    // Check for GI categories
    expect($prompt)->toContain('PRIORITIZE LOW-GI FOODS (GI ≤55)')
        ->and($prompt)->toContain('EXERCISE CAUTION (GI 56-69)');
});

it('includes diabetes safety guardrails when user has glucose data', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    // Add glucose readings
    GlucoseReading::factory()->count(5)->create([
        'user_id' => $user->id,
        'reading_value' => 140,
    ]);

    $builder = app(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)->toContain('CRITICAL SAFETY GUARDRAILS')
        ->and($prompt)->toContain('DIABETES/GLUCOSE MANAGEMENT ACTIVE')
        ->and($prompt)->toContain('Glucose Monitoring Data');
});

it('includes diabetes safety guardrails for gestational diabetes', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    $gestational = HealthCondition::factory()->create([
        'name' => 'Gestational Diabetes',
    ]);

    $user->profile->healthConditions()->attach($gestational);
    $user->refresh();

    $builder = app(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)->toContain('DIABETES/GLUCOSE MANAGEMENT ACTIVE')
        ->and($prompt)->toContain('FORBIDDEN HIGH-GI FOODS');
});

it('does not include diabetes-specific rules for healthy users', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    $builder = app(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)->toContain('CRITICAL SAFETY GUARDRAILS')
        ->and($prompt)->toContain('General Safety Rules')
        ->and($prompt)->not->toContain('DIABETES/GLUCOSE MANAGEMENT ACTIVE');
});

it('includes general safety rules for all users', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    $builder = app(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)->toContain('ALLERGEN AWARENESS')
        ->and($prompt)->toContain('PORTION REALISM')
        ->and($prompt)->toContain('MEDICAL DISCLAIMER')
        ->and($prompt)->toContain('HYDRATION');
});

it('includes meal composition requirements for diabetic users', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    $diabetes = HealthCondition::factory()->create([
        'name' => 'Type 2 Diabetes',
    ]);

    $user->profile->healthConditions()->attach($diabetes);
    $user->refresh();

    $builder = app(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)->toContain('MEAL COMPOSITION')
        ->and($prompt)->toContain('Lean protein')
        ->and($prompt)->toContain('Healthy fats')
        ->and($prompt)->toContain('minimum 5g per meal');
});

it('includes GI food categories with specific examples', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    $diabetes = HealthCondition::factory()->create([
        'name' => 'Type 2 Diabetes',
    ]);

    $user->profile->healthConditions()->attach($diabetes);
    $user->refresh();

    $builder = app(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    // High GI examples
    expect($prompt)->toContain('White bread')
        ->and($prompt)->toContain('French fries')
        ->and($prompt)->toContain('Sugary cereals');

    // Medium GI examples
    expect($prompt)->toContain('Whole wheat bread')
        ->and($prompt)->toContain('Sweet potatoes');

    // Low GI examples
    expect($prompt)->toContain('Non-starchy vegetables')
        ->and($prompt)->toContain('legumes')
        ->and($prompt)->toContain('berries');
});

it('reinforces safety rules in glucose monitoring section for users with glucose data', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    GlucoseReading::factory()->count(10)->create([
        'user_id' => $user->id,
        'reading_value' => 150,
    ]);

    $builder = app(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)->toContain('VERIFICATION CHECKLIST')
        ->and($prompt)->toContain('No high-GI foods (GI >70)')
        ->and($prompt)->toContain('Total meal carbohydrates do not exceed 60g')
        ->and($prompt)->toContain('Fiber content is adequate');
});

it('includes final safety check before output for diabetic users', function (): void {
    $user = User::factory()
        ->has(UserProfile::factory(), 'profile')
        ->create();

    $diabetes = HealthCondition::factory()->create([
        'name' => 'Type 2 Diabetes',
    ]);

    $user->profile->healthConditions()->attach($diabetes);
    $user->refresh();

    $builder = app(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)->toContain('FINAL SAFETY CHECK BEFORE GENERATING OUTPUT')
        ->and($prompt)->toContain('Review your meal plan against the Safety Guardrails')
        ->and($prompt)->toContain('If any meal violates these rules, revise it immediately');
});
