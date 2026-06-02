<?php

declare(strict_types=1);

use App\Ai\Agents\MealPlanAgent;
use App\Enums\DietType;
use App\Enums\GoalChoice;
use App\Enums\Sex;
use App\Jobs\GenerateInitialMealPlanJob;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;

covers(MealPlanAgent::class);

it('returns fluent interface when setting diet type', function (): void {
    $action = resolve(MealPlanAgent::class);
    $result = $action->withDietType(DietType::Mediterranean);

    expect($result)->toBeInstanceOf(MealPlanAgent::class);
});

it('has correct attributes configured', function (): void {
    $reflection = new ReflectionClass(MealPlanAgent::class);

    $maxTokens = $reflection->getAttributes(MaxTokens::class);
    $timeout = $reflection->getAttributes(Timeout::class);

    expect($maxTokens)->toHaveCount(1)
        ->and($maxTokens[0]->newInstance()->value)->toBe(64000)
        ->and($timeout)->toHaveCount(1)
        ->and($timeout[0]->newInstance()->value)->toBe(180);
});

it('dispatches the initial meal plan job when handle is called', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.5,
    ]);

    $action = resolve(MealPlanAgent::class);
    $action->handle($user);

    $mealPlan = $user->mealPlans()->first();
    expect($mealPlan)->not->toBeNull()
        ->and($mealPlan->metadata['status'])->toBe('generating');

    Queue::assertPushed(
        GenerateInitialMealPlanJob::class,
        fn (GenerateInitialMealPlanJob $job): bool => $job->mealPlan->is($mealPlan),
    );
});

it('stores custom prompt in meal plan metadata when provided', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.5,
    ]);

    $action = resolve(MealPlanAgent::class);
    $action->handle($user, 7, 'No spicy food please');

    $mealPlan = $user->mealPlans()->first();
    expect($mealPlan)->not->toBeNull()
        ->and($mealPlan->metadata['custom_prompt'])->toBe('No spicy food please');
});

it('generates meals for a single day', function (): void {
    $user = User::factory()->create();

    $user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.5,
    ]);

    $mockResponse = [
        'meals' => [
            [
                'type' => 'breakfast',
                'name' => 'Oatmeal',
                'description' => 'Healthy breakfast',
                'preparation_instructions' => 'Cook oats',
                'ingredients' => [
                    ['name' => 'Oats', 'quantity' => '50g'],
                ],
                'portion_size' => '1 bowl',
                'calories' => 300,
                'protein_grams' => 10,
                'carbs_grams' => 50,
                'fat_grams' => 5,
                'preparation_time_minutes' => 10,
                'sort_order' => 1,
            ],
        ],
    ];

    MealPlanAgent::fake([$mockResponse]);

    $action = resolve(MealPlanAgent::class);
    $dayMeals = $action->generateForDay($user, 1, 7);

    expect($dayMeals)
        ->meals->toHaveCount(1)
        ->and($dayMeals->meals[0]->name)->toBe('Oatmeal');
});
