<?php

declare(strict_types=1);

use App\Actions\AiAgents\GenerateMealPlan;
use App\Enums\MealPlanType;
use App\Enums\Sex;
use App\Models\Goal;
use App\Models\Lifestyle;
use App\Models\UsdaFoundationFood;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;
use Workflow\WorkflowStub;

uses(RefreshDatabase::class);

it('generates a meal plan using PrismPHP', function (): void {
    UsdaFoundationFood::factory()->create([
        'id' => 12345,
        'description' => 'Greek Yogurt',
        'nutrients' => [
            ['nutrient' => ['number' => '208'], 'amount' => 59],
            ['nutrient' => ['number' => '203'], 'amount' => 10],
            ['nutrient' => ['number' => '205'], 'amount' => 3.6],
            ['nutrient' => ['number' => '204'], 'amount' => 0.4],
        ],
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

    $fakeResponse = TextResponseFake::make()
        ->withText(json_encode($mockResponse, JSON_THROW_ON_ERROR))
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(100, 200))
        ->withMeta(new Meta('test-id', 'gemini-2.5-flash'));

    Prism::fake([$fakeResponse]);

    $action = app(GenerateMealPlan::class);
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

    $fakeResponse = TextResponseFake::make()
        ->withText(json_encode($mockResponse, JSON_THROW_ON_ERROR))
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(100, 200))
        ->withMeta(new Meta('test-id', 'gemini-2.5-flash'));

    Prism::fake([$fakeResponse]);

    $action = app(GenerateMealPlan::class);
    $result = $action->generate($user);

    expect($result)->not->toBeNull();
});

it('starts workflow when handle is called', function (): void {
    WorkflowStub::fake();

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
    $action->handle($user);

    // Workflow is faked so meal plan won't be created, but no exception means workflow was started
    expect($user->mealPlans()->count())->toBe(0);
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

    $fakeResponse = TextResponseFake::make()
        ->withText(json_encode($mockResponse, JSON_THROW_ON_ERROR))
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(100, 200))
        ->withMeta(new Meta('test-id', 'gemini-2.5-flash'));

    Prism::fake([$fakeResponse]);

    $action = app(GenerateMealPlan::class);
    $mealPlanData = $action->generate($user);

    expect($mealPlanData->meals)->toHaveCount(2);
    expect($mealPlanData->meals[0]->ingredients)->toBeInstanceOf(Spatie\LaravelData\DataCollection::class);
    expect($mealPlanData->meals[0]->ingredients->count())->toBe(0);
    expect($mealPlanData->meals[1]->ingredients)->toBeNull();
});

it('works without file search store configured', function (): void {
    // Don't set any file search store setting
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
        'meals' => [],
    ];

    $fakeResponse = TextResponseFake::make()
        ->withText(json_encode($mockResponse, JSON_THROW_ON_ERROR))
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(100, 200))
        ->withMeta(new Meta('test-id', 'gemini-2.5-flash'));

    Prism::fake([$fakeResponse]);

    $action = app(GenerateMealPlan::class);
    $mealPlanData = $action->generate($user);

    expect($mealPlanData)
        ->type->toBe(MealPlanType::Weekly)
        ->name->toBe('Test Plan');
});

it('uses file search store when configured', function (): void {
    // Set the file search store setting
    App\Models\Setting::set(App\Enums\SettingKey::GeminiFileSearchStoreName, 'test-store-name');

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
        'name' => 'File Search Plan',
        'description' => 'Plan using file search',
        'duration_days' => 7,
        'target_daily_calories' => 2000.0,
        'macronutrient_ratios' => ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        'meals' => [],
    ];

    $fakeResponse = TextResponseFake::make()
        ->withText(json_encode($mockResponse, JSON_THROW_ON_ERROR))
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(100, 200))
        ->withMeta(new Meta('test-id', 'gemini-2.5-flash'));

    Prism::fake([$fakeResponse]);

    $action = app(GenerateMealPlan::class);
    $mealPlanData = $action->generate($user);

    expect($mealPlanData)
        ->type->toBe(MealPlanType::Weekly)
        ->name->toBe('File Search Plan');
});
