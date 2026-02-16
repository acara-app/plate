<?php

declare(strict_types=1);

use App\Actions\GetUserProfileContextAction;
use App\Ai\SingleMealPromptBuilder;
use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();

    $this->action = new GetUserProfileContextAction;
    $this->builder = new SingleMealPromptBuilder($this->action);
});

it('builds prompt with all parameters', function (): void {
    $this->user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.5,
    ]);

    $prompt = $this->builder->handle(
        $this->user,
        'lunch',
        'Mediterranean',
        500,
        'healthy and light'
    );

    expect($prompt)
        ->toContain('# Meal Generation Task')
        ->toContain('Generate a personalized single meal suggestion')
        ->toContain('BIOMETRICS:')
        ->toContain('Age: 30')
        ->toContain('Meal Type**: lunch')
        ->toContain('Cuisine Style**: Mediterranean')
        ->toContain('Maximum Calories**: 500')
        ->toContain('Specific Request**: healthy and light');
});

it('builds prompt without optional parameters', function (): void {
    $this->user->profile()->create([
        'age' => 25,
        'height' => 165.0,
        'weight' => 60.0,
        'sex' => Sex::Female,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.3,
    ]);

    $prompt = $this->builder->handle(
        $this->user,
        'breakfast'
    );

    expect($prompt)
        ->toContain('# Meal Generation Task')
        ->toContain('Meal Type**: breakfast')
        ->not->toContain('Cuisine Style**')
        ->not->toContain('Maximum Calories**')
        ->not->toContain('Specific Request**');
});

it('builds prompt with partial optional parameters', function (): void {
    $this->user->profile()->create([
        'age' => 35,
        'height' => 180.0,
        'weight' => 85.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::HealthyEating->value,
        'derived_activity_multiplier' => 1.4,
    ]);

    $prompt = $this->builder->handle(
        $this->user,
        'dinner',
        'Italian',
        null,
        null
    );

    expect($prompt)
        ->toContain('Meal Type**: dinner')
        ->toContain('Cuisine Style**: Italian')
        ->not->toContain('Maximum Calories**')
        ->not->toContain('Specific Request**');
});

it('builds prompt with only max calories', function (): void {
    $this->user->profile()->create([
        'age' => 40,
        'height' => 170.0,
        'weight' => 75.0,
        'sex' => Sex::Female,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.2,
    ]);

    $prompt = $this->builder->handle(
        $this->user,
        'snack',
        null,
        200,
        null
    );

    expect($prompt)
        ->toContain('Meal Type**: snack')
        ->toContain('Maximum Calories**: 200')
        ->not->toContain('Cuisine Style**')
        ->not->toContain('Specific Request**');
});

it('builds prompt with only specific request', function (): void {
    $this->user->profile()->create([
        'age' => 28,
        'height' => 175.0,
        'weight' => 70.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.5,
    ]);

    $prompt = $this->builder->handle(
        $this->user,
        'lunch',
        null,
        null,
        'high protein and low carb'
    );

    expect($prompt)
        ->toContain('Meal Type**: lunch')
        ->toContain('Specific Request**: high protein and low carb')
        ->not->toContain('Cuisine Style**')
        ->not->toContain('Maximum Calories**');
});

it('includes instructions section in prompt', function (): void {
    $this->user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
    ]);

    $prompt = $this->builder->handle($this->user, 'dinner');

    expect($prompt)
        ->toContain('## Instructions')
        ->toContain('Create a single, complete meal suggestion')
        ->toContain('Provide accurate nutritional estimates')
        ->toContain('Consider glucose impact')
        ->toContain('Ensure the meal fits within any specified calorie limits')
        ->toContain('Use common, accessible ingredients');
});
