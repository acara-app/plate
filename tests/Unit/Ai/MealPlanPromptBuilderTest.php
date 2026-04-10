<?php

declare(strict_types=1);

use App\Ai\MealPlanPromptBuilder;
use App\Enums\GlucoseReadingType;
use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Models\UserProfile;

covers(MealPlanPromptBuilder::class);

it('includes glucose analysis in the prompt when glucose data exists', function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $user->id,
        'value' => 150.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::PostMeal->value],
        'measured_at' => now()->subDays(1),
    ]);

    HealthSyncSample::factory()->bloodGlucose()->create([
        'user_id' => $user->id,
        'value' => 155.0,
        'metadata' => ['glucose_reading_type' => GlucoseReadingType::PostMeal->value],
        'measured_at' => now()->subDays(2),
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('Glucose Monitoring Data')
        ->toContain('Total Readings')
        ->toContain('Average Glucose Levels')
        ->toContain('Detected Patterns')
        ->toContain('Key Insights')
        ->toContain('Identified Concerns');
});

it('includes message when no glucose data exists', function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 25,
        'height' => 170.0,
        'weight' => 70.0,
        'sex' => Sex::Female,
        'goal_choice' => GoalChoice::HealthyEating->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('Glucose Monitoring Data')
        ->toContain('No glucose monitoring data available for this user')
        ->toContain('Generate a balanced meal plan without specific glucose considerations');
});

it('includes glucose concerns when post-meal spikes are detected', function (): void {
    /** @var User $user */
    $user = User::factory()->create();
    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 40,
        'height' => 180.0,
        'weight' => 95.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.55,
    ]);

    for ($i = 0; $i < 5; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $user->id,
            'value' => 90.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
            'measured_at' => now()->subDays($i * 2),
        ]);
    }

    for ($i = 0; $i < 5; $i++) {
        HealthSyncSample::factory()->bloodGlucose()->create([
            'user_id' => $user->id,
            'value' => 160.0,
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::PostMeal->value],
            'measured_at' => now()->subDays($i * 2 + 1),
        ]);
    }

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('Post-Meal Spikes')
        ->toContain('Glucose Management Goal');
});
