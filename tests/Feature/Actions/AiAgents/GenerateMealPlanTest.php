<?php

declare(strict_types=1);

use App\Actions\AiAgents\GenerateMealPlan;
use App\Enums\AiModel;
use App\Enums\JobStatus;
use App\Enums\MealPlanType;
use App\Enums\Sex;
use App\Jobs\ProcessMealPlanJob;
use App\Models\Goal;
use App\Models\Lifestyle;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

it('generates a meal plan using PrismPHP', function (): void {
    Http::fake([
        'world.openfoodfacts.org/*' => Http::response([
            'products' => [
                [
                    'product_name' => 'Greek Yogurt',
                    'nutriments' => [
                        'energy-kcal_100g' => 59,
                        'proteins_100g' => 10,
                        'carbohydrates_100g' => 3.6,
                        'fat_100g' => 0.4,
                    ],
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $goal = Goal::factory()->create(['name' => 'Weight Loss']);
    $lifestyle = Lifestyle::factory()->create([
        'name' => 'Active',
        'activity_level' => 'moderate',
        'activity_multiplier' => 1.5,
    ]);

    $user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
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

    $fakeResponse = StructuredResponseFake::make()
        ->withText(json_encode($mockResponse, JSON_THROW_ON_ERROR))
        ->withStructured($mockResponse)
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(100, 200))
        ->withMeta(new Meta('test-id', 'gemini-2.5-flash'));

    Prism::fake([$fakeResponse]);

    $action = app(GenerateMealPlan::class);
    $mealPlanData = $action->generate($user, AiModel::Gemini25Flash);

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

    // Note: calories may be adjusted by nutrition verification/correction
    expect($mealPlanData->meals[0]->calories)->toBeGreaterThan(0);
});

it('uses the correct AI model from enum', function (): void {
    $user = User::factory()->create();
    $goal = Goal::factory()->create();
    $lifestyle = Lifestyle::factory()->create();

    $user->profile()->create([
        'age' => 25,
        'height' => 170.0,
        'weight' => 65.0,
        'sex' => Sex::Female,
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
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

    $fakeResponse = StructuredResponseFake::make()
        ->withText(json_encode($mockResponse, JSON_THROW_ON_ERROR))
        ->withStructured($mockResponse)
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(100, 200))
        ->withMeta(new Meta('test-id', 'gemini-2.5-flash'));

    Prism::fake([$fakeResponse]);

    $action = app(GenerateMealPlan::class);
    $result = $action->generate($user, AiModel::Gemini25Flash);

    expect($result)->not->toBeNull();
    expect(AiModel::Gemini25Flash->value)->toBe('gemini-2.5-flash');
});

it('dispatches a job and creates job tracking when handle is called', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $goal = Goal::factory()->create();
    $lifestyle = Lifestyle::factory()->create();

    $user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
    ]);

    $action = app(GenerateMealPlan::class);
    $tracking = $action->handle($user, AiModel::Gemini25Flash);

    expect($tracking)
        ->user_id->toBe($user->id)
        ->job_type->toBe(ProcessMealPlanJob::JOB_TYPE)
        ->status->toBe(JobStatus::Pending)
        ->progress->toBe(0);

    Queue::assertPushed(ProcessMealPlanJob::class, fn (ProcessMealPlanJob $job): bool => $job->userId === $user->id && $job->model === AiModel::Gemini25Flash);
});

it('handles meals with no ingredients', function (): void {
    // No HTTP mock needed since meals have no ingredients to verify
    $user = User::factory()->create();
    $goal = Goal::factory()->create();
    $lifestyle = Lifestyle::factory()->create();

    $user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
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
                'ingredients' => [], // Empty ingredients array
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
                'ingredients' => null, // Null ingredients
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

    $fakeResponse = StructuredResponseFake::make()
        ->withText(json_encode($mockResponse, JSON_THROW_ON_ERROR))
        ->withStructured($mockResponse)
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(100, 200))
        ->withMeta(new Meta('test-id', 'gemini-2.5-flash'));

    Prism::fake([$fakeResponse]);

    $action = app(GenerateMealPlan::class);
    $mealPlanData = $action->generate($user, AiModel::Gemini25Flash);

    expect($mealPlanData->meals)->toHaveCount(2);
    expect($mealPlanData->meals[0]->ingredients)->toBe([]);
    expect($mealPlanData->meals[1]->ingredients)->toBeNull();
});
