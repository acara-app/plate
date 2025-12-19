<?php

declare(strict_types=1);

use App\Ai\Agents\MealPlanPromptBuilder;
use App\Enums\ReadingType;
use App\Enums\Sex;
use App\Models\GlucoseReading;
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
    GlucoseReading::factory()->create([
        'user_id' => $user->id,
        'reading_value' => 150.0,
        'reading_type' => ReadingType::PostMeal,
        'measured_at' => now()->subDays(1),
    ]);

    GlucoseReading::factory()->create([
        'user_id' => $user->id,
        'reading_value' => 155.0,
        'reading_type' => ReadingType::PostMeal,
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
        GlucoseReading::factory()->create([
            'user_id' => $user->id,
            'reading_value' => 90.0,
            'reading_type' => ReadingType::Fasting,
            'measured_at' => now()->subDays($i * 2),
        ]);
    }

    for ($i = 0; $i < 5; $i++) {
        GlucoseReading::factory()->create([
            'user_id' => $user->id,
            'reading_value' => 160.0,
            'reading_type' => ReadingType::PostMeal,
            'measured_at' => now()->subDays($i * 2 + 1),
        ]);
    }

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('Post-Meal Spikes')
        ->toContain('Glucose Management Goal');
});

it('throws exception when user has no profile', function (): void {
    $user = User::factory()->create();

    $builder = resolve(MealPlanPromptBuilder::class);
    $builder->handle($user);
})->throws(RuntimeException::class, 'User profile is required to create a meal plan.');

it('calculates calorie deficit for weight loss goal', function (): void {
    $user = User::factory()->create();

    $goal = Goal::factory()->create(['name' => 'Weight Loss']);
    $lifestyle = Lifestyle::factory()->create();

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)->toBeString()->not->toBeEmpty();
});

it('calculates calorie surplus for weight gain goal', function (): void {
    $user = User::factory()->create();

    $goal = Goal::factory()->create(['name' => 'Muscle Gain']);
    $lifestyle = Lifestyle::factory()->create();

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 25,
        'height' => 180.0,
        'weight' => 75.0,
        'sex' => Sex::Male,
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)->toBeString()->not->toBeEmpty();
});

it('includes user profile information in prompt', function (): void {
    $user = User::factory()->create();

    $goal = Goal::factory()->create(['name' => 'Maintenance']);
    $lifestyle = Lifestyle::factory()->create(['activity_level' => 'Moderate']);

    UserProfile::factory()->create([
        'user_id' => $user->id,
        'age' => 35,
        'height' => 170.0,
        'weight' => 70.0,
        'sex' => Sex::Female,
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
    ]);

    $builder = resolve(MealPlanPromptBuilder::class);
    $prompt = $builder->handle($user);

    expect($prompt)
        ->toContain('35')
        ->toContain('170')
        ->toContain('70');
});
