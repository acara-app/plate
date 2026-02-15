<?php

declare(strict_types=1);

use App\Ai\Agents\MealPlanAgent;
use App\Enums\DietType;
use App\Enums\GoalChoice;
use App\Enums\MealPlanType;
use App\Enums\Sex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Workflow\WorkflowStub;

uses(RefreshDatabase::class);

it('returns fluent interface when setting diet type', function (): void {
    $action = resolve(MealPlanAgent::class);
    $result = $action->withDietType(DietType::Mediterranean);

    expect($result)->toBeInstanceOf(MealPlanAgent::class);
});

it('returns max tokens', function (): void {
    $action = resolve(MealPlanAgent::class);
    $maxTokens = $action->maxTokens();

    expect($maxTokens)->toBe(64000);
});

it('returns client options', function (): void {
    $action = resolve(MealPlanAgent::class);
    $options = $action->clientOptions();

    expect($options)->toBeArray()
        ->and($options)->toHaveKey('timeout')
        ->and($options['timeout'])->toBe(180);
});

it('generates a meal plan using Laravel AI SDK', function (): void {
    $user = User::factory()->create();

    $user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.5,
        'target_weight' => 75.0,
    ]);

    $mockResponse = [
        'type' => 'weekly',
        'name' => 'Weight Loss Weekly Plan',
        'description' => 'A balanced meal plan for weight loss',
        'duration_days' => 7,
        'target_daily_calories' => 1800.0,
        'macronutrient_ratios' => [
            'protein' => 35,
            'carbs' => 30,
            'fat' => 35,
        ],
        'meals' => [
            [
                'day_number' => 1,
                'type' => 'breakfast',
                'name' => 'Greek Yogurt Bowl',
                'description' => 'High protein breakfast',
                'preparation_instructions' => 'Mix yogurt with toppings',
                'ingredients' => [
                    ['name' => 'Greek yogurt', 'quantity' => '200g'],
                    ['name' => 'Berries', 'quantity' => '100g'],
                    ['name' => 'Nuts', 'quantity' => '30g'],
                ],
                'portion_size' => '1 bowl',
                'calories' => 350.0,
                'protein_grams' => 25.0,
                'carbs_grams' => 30.0,
                'fat_grams' => 10.0,
                'preparation_time_minutes' => 5,
                'sort_order' => 1,
            ],
        ],
    ];

    MealPlanAgent::fake([$mockResponse]);

    $action = resolve(MealPlanAgent::class);
    $mealPlanData = $action->generate($user);

    expect($mealPlanData)
        ->type->toBe(MealPlanType::Weekly)
        ->name->toBe('Weight Loss Weekly Plan')
        ->durationDays->toBe(7)
        ->targetDailyCalories->toBe(1800.0)
        ->macronutrientRatios->toBe(['protein' => 35, 'carbs' => 30, 'fat' => 35])
        ->meals->toHaveCount(1);

    expect($mealPlanData->meals[0])
        ->dayNumber->toBe(1)
        ->name->toBe('Greek Yogurt Bowl');

    expect($mealPlanData->meals[0]->calories)->toBeGreaterThan(0);
});

it('generates meal plan with minimal data', function (): void {
    $user = User::factory()->create();

    $user->profile()->create([
        'age' => 25,
        'height' => 170.0,
        'weight' => 65.0,
        'sex' => Sex::Female,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.3,
    ]);

    $mockResponse = [
        'type' => 'weekly',
        'name' => 'Test Plan',
        'description' => 'Test',
        'duration_days' => 7,
        'target_daily_calories' => 2000.0,
        'macronutrient_ratios' => ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        'meals' => [],
    ];

    MealPlanAgent::fake([$mockResponse]);

    $action = resolve(MealPlanAgent::class);
    $result = $action->generate($user);

    expect($result)->not->toBeNull();
});

it('starts workflow when handle is called', function (): void {
    WorkflowStub::fake();

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
    expect($mealPlan)->not->toBeNull();
    expect($mealPlan->metadata['status'])->toBe('generating');
});

it('handles meals with no ingredients', function (): void {
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
        'type' => 'weekly',
        'name' => 'Test Plan',
        'description' => 'A test meal plan',
        'duration_days' => 7,
        'target_daily_calories' => 2000.0,
        'macronutrient_ratios' => ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        'meals' => [
            [
                'day_number' => 1,
                'type' => 'breakfast',
                'name' => 'Simple Meal',
                'description' => 'No ingredients specified',
                'preparation_instructions' => 'Quick prep',
                'ingredients' => [],
                'portion_size' => '1 serving',
                'calories' => 300.0,
                'protein_grams' => 20.0,
                'carbs_grams' => 30.0,
                'fat_grams' => 10.0,
                'preparation_time_minutes' => 5,
                'sort_order' => 1,
            ],
            [
                'day_number' => 1,
                'type' => 'lunch',
                'name' => 'Another Meal',
                'description' => 'Null ingredients',
                'preparation_instructions' => 'Simple',
                'ingredients' => null,
                'portion_size' => '1 serving',
                'calories' => 400.0,
                'protein_grams' => 25.0,
                'carbs_grams' => 40.0,
                'fat_grams' => 15.0,
                'preparation_time_minutes' => 10,
                'sort_order' => 2,
            ],
        ],
    ];

    MealPlanAgent::fake([$mockResponse]);

    $action = resolve(MealPlanAgent::class);
    $mealPlanData = $action->generate($user);

    expect($mealPlanData->meals)->toHaveCount(2);
    expect($mealPlanData->meals[0]->ingredients)->toBeInstanceOf(Spatie\LaravelData\DataCollection::class);
    expect($mealPlanData->meals[0]->ingredients->count())->toBe(0);
    expect($mealPlanData->meals[1]->ingredients)->toBeNull();
});

it('works without file search store configured', function (): void {
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
        'type' => 'weekly',
        'name' => 'Test Plan',
        'description' => 'A test meal plan',
        'duration_days' => 7,
        'target_daily_calories' => 2000.0,
        'macronutrient_ratios' => ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        'meals' => [],
    ];

    MealPlanAgent::fake([$mockResponse]);

    $action = resolve(MealPlanAgent::class);
    $mealPlanData = $action->generate($user);

    expect($mealPlanData)
        ->type->toBe(MealPlanType::Weekly)
        ->name->toBe('Test Plan');
});

it('uses file search store when configured', function (): void {
    App\Models\Setting::set(App\Enums\SettingKey::GeminiFileSearchStoreName, 'test-store-name');

    $user = User::factory()->create();

    $user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::HealthyEating->value,
        'derived_activity_multiplier' => 1.3,
    ]);

    $mockResponse = [
        'type' => 'weekly',
        'name' => 'File Search Plan',
        'description' => 'Plan using file search',
        'duration_days' => 7,
        'target_daily_calories' => 2000.0,
        'macronutrient_ratios' => ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        'meals' => [],
    ];

    MealPlanAgent::fake([$mockResponse]);

    $action = resolve(MealPlanAgent::class);
    $mealPlanData = $action->generate($user);

    expect($mealPlanData)
        ->type->toBe(MealPlanType::Weekly)
        ->name->toBe('File Search Plan');
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
