<?php

declare(strict_types=1);

use App\Ai\Agents\MealPlanPromptBuilder;
use App\Enums\GlucoseReadingType;
use App\Enums\Sex;
use App\Models\DiabetesLog;
use App\Models\Goal;
use App\Models\Lifestyle;
use App\Models\User;
use App\Models\UserProfile;

it('includes glucose analysis in the prompt when glucose data exists', function (): void {
    /** @var User $user */
    $user = User::factory()->create();

    $goal = Goal::factory()->create(['name' => 'Weight Loss']);
    $lifestyle = Lifestyle::factory()->create(['activity_level' => 'Moderate']);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
    ]);

    // Create some glucose readings
    DiabetesLog::factory()->create([
        'user_id' => $user->id,
        'glucose_value' => 150.0,
        'glucose_reading_type' => GlucoseReadingType::PostMeal,
        'measured_at' => now()->subDays(1),
    ]);

    DiabetesLog::factory()->create([
        'user_id' => $user->id,
        'glucose_value' => 155.0,
        'glucose_reading_type' => GlucoseReadingType::PostMeal,
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

    $goal = Goal::factory()->create(['name' => 'Maintenance']);
    $lifestyle = Lifestyle::factory()->create(['activity_level' => 'Moderate']);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 25,
        'height' => 170.0,
        'weight' => 70.0,
        'sex' => Sex::Female,
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
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

    $goal = Goal::factory()->create(['name' => 'Weight Loss']);
    $lifestyle = Lifestyle::factory()->create(['activity_level' => 'Low']);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 40,
        'height' => 180.0,
        'weight' => 95.0,
        'sex' => Sex::Male,
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
    ]);

    // Create normal fasting and high post-meal readings to trigger spike detection without consistentlyHigh
    for ($i = 0; $i < 5; $i++) {
        DiabetesLog::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => 90.0,
            'glucose_reading_type' => GlucoseReadingType::Fasting,
            'measured_at' => now()->subDays($i * 2),
        ]);
    }

    for ($i = 0; $i < 5; $i++) {
        DiabetesLog::factory()->create([
            'user_id' => $user->id,
            'glucose_value' => 160.0,
            'glucose_reading_type' => GlucoseReadingType::PostMeal,
            'measured_at' => now()->subDays($i * 2 + 1),
        ]);
    }

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('Post-Meal Spikes')
        ->toContain('Glucose Management Goal');
});
