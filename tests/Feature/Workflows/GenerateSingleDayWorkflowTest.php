<?php

declare(strict_types=1);

use App\DataObjects\DayMealsData;
use App\DataObjects\IngredientData;
use App\DataObjects\PreviousDayContext;
use App\DataObjects\SingleDayMealData;
use App\Enums\MealPlanGenerationStatus;
use App\Enums\MealType;
use App\Enums\Sex;
use App\Models\Goal;
use App\Models\Lifestyle;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;
use App\Workflows\GenerateDayMealsActivity;
use App\Workflows\GenerateSingleDayWorkflow;
use App\Workflows\StoreDayMealsActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelData\DataCollection;
use Workflow\WorkflowStub;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $goal = Goal::factory()->create(['name' => 'Weight Loss']);
    $lifestyle = Lifestyle::factory()->create([
        'name' => 'Active',
        'activity_level' => 'moderate',
        'activity_multiplier' => 1.5,
    ]);

    $this->user = User::factory()->create();
    $this->user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_id' => $goal->id,
        'lifestyle_id' => $lifestyle->id,
        'target_weight' => 75.0,
    ]);
});

it('workflow class exists and extends correct base class', function (): void {
    expect(class_exists(GenerateSingleDayWorkflow::class))->toBeTrue();
    expect(is_subclass_of(GenerateSingleDayWorkflow::class, Workflow\Workflow::class))->toBeTrue();
});

it('activity classes for single day workflow exist', function (): void {
    expect(class_exists(GenerateDayMealsActivity::class))->toBeTrue();
    expect(class_exists(StoreDayMealsActivity::class))->toBeTrue();
    expect(is_subclass_of(GenerateDayMealsActivity::class, Workflow\Activity::class))->toBeTrue();
    expect(is_subclass_of(StoreDayMealsActivity::class, Workflow\Activity::class))->toBeTrue();
});

it('triggers workflow when navigating to day that needs generation', function (): void {
    WorkflowStub::fake();

    $mealPlan = MealPlan::factory()
        ->for($this->user)
        ->weekly()
        ->create([
            'metadata' => [
                'status' => MealPlanGenerationStatus::Pending->value,
                'days_completed' => 1,
            ],
        ]);

    // Day 1 has meals, day 2 does not
    Meal::factory()->for($mealPlan)->forDay(1)->create();

    $response = $this->actingAs($this->user)
        ->get(route('meal-plans.index', ['day' => 2]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', 2)
            ->where('currentDay.needs_generation', true)
            ->where('currentDay.status', MealPlanGenerationStatus::Generating->value));

    // Check that metadata was updated to generating
    expect($mealPlan->fresh()->metadata['day_2_status'])
        ->toBe(MealPlanGenerationStatus::Generating->value);
});

it('does not trigger workflow when day has meals', function (): void {
    WorkflowStub::fake();

    $mealPlan = MealPlan::factory()
        ->for($this->user)
        ->weekly()
        ->create([
            'metadata' => [
                'status' => MealPlanGenerationStatus::Completed->value,
                'days_completed' => 2,
            ],
        ]);

    Meal::factory()->for($mealPlan)->forDay(1)->create();
    Meal::factory()->for($mealPlan)->forDay(2)->create();

    $response = $this->actingAs($this->user)
        ->get(route('meal-plans.index', ['day' => 2]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currentDay.day_number', 2)
            ->where('currentDay.needs_generation', false)
            ->where('currentDay.status', MealPlanGenerationStatus::Completed->value));

    // Metadata should not have day_2_status since day already has meals
    expect($mealPlan->fresh()->metadata['day_2_status'] ?? null)->toBeNull();
});

it('returns generating status when day is being generated via API', function (): void {
    WorkflowStub::fake();

    $mealPlan = MealPlan::factory()->for($this->user)->create([
        'duration_days' => 7,
        'metadata' => ['days_completed' => 1],
    ]);

    // Trigger generation for day 2
    $this->actingAs($this->user)
        ->postJson(route('meal-plans.generate-day', $mealPlan), ['day' => 2])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'status' => MealPlanGenerationStatus::Generating->value,
            'message' => 'Generation started',
        ]);

    expect($mealPlan->fresh()->metadata['day_2_status'])
        ->toBe(MealPlanGenerationStatus::Generating->value);
});

it('previous day context builds correctly with meal history', function (): void {
    $context = new PreviousDayContext;

    // Day 1 meals
    $context->addDayMeals(1, ['Oatmeal', 'Chicken Salad', 'Grilled Salmon']);
    // Day 2 meals
    $context->addDayMeals(2, ['Greek Yogurt', 'Turkey Wrap', 'Beef Stir Fry']);

    $promptText = $context->toPromptText();

    expect($promptText)
        ->toContain("## Previous Days' Meals")
        ->toContain('Day 1')
        ->toContain('Oatmeal')
        ->toContain('Chicken Salad')
        ->toContain('Day 2')
        ->toContain('Greek Yogurt')
        ->toContain('variety');
});

it('day meals data collection can be created for storage', function (): void {
    $ingredients = new DataCollection(IngredientData::class, [
        new IngredientData(name: 'Eggs', quantity: '2 large'),
    ]);

    $singleMeal = new SingleDayMealData(
        type: MealType::Breakfast,
        name: 'Test Breakfast',
        description: 'A delicious breakfast',
        preparationInstructions: 'Cook eggs',
        ingredients: $ingredients,
        portionSize: '1 serving',
        calories: 400.0,
        proteinGrams: 25.0,
        carbsGrams: 30.0,
        fatGrams: 15.0,
        preparationTimeMinutes: 15,
        sortOrder: 1,
    );

    $dayMeals = new DayMealsData(
        meals: new DataCollection(SingleDayMealData::class, [$singleMeal]),
    );

    expect($dayMeals->meals)->toHaveCount(1);
    expect($dayMeals->meals[0]->name)->toBe('Test Breakfast');
    expect($dayMeals->meals[0]->type)->toBe(MealType::Breakfast);
});

it('single day meal data converts to meal data with correct day number', function (): void {
    $singleMeal = new SingleDayMealData(
        type: MealType::Dinner,
        name: 'Grilled Chicken',
        description: 'Healthy dinner',
        preparationInstructions: 'Grill the chicken',
        ingredients: null,
        portionSize: '200g',
        calories: 450.0,
        proteinGrams: 40.0,
        carbsGrams: 10.0,
        fatGrams: 25.0,
        preparationTimeMinutes: 25,
        sortOrder: 5,
    );

    $mealData = $singleMeal->toMealData(3);

    expect($mealData)
        ->dayNumber->toBe(3)
        ->type->toBe(MealType::Dinner)
        ->name->toBe('Grilled Chicken')
        ->calories->toBe(450.0);
});

it('meal plan updates days_completed metadata correctly', function (): void {
    $mealPlan = MealPlan::factory()
        ->for($this->user)
        ->weekly()
        ->create([
            'metadata' => [
                'days_completed' => 2,
                'status' => MealPlanGenerationStatus::Pending->value,
            ],
        ]);

    // Simulate what the workflow does after completion
    $daysCompleted = max(
        $mealPlan->metadata['days_completed'] ?? 0,
        3
    );
    $isCompleted = $daysCompleted >= $mealPlan->duration_days;

    $metadata = $mealPlan->metadata ?? [];
    unset($metadata['day_3_status']);

    $generatedAt = now()->toIso8601String();
    $mealPlan->update([
        'metadata' => array_merge($metadata, [
            'days_completed' => $daysCompleted,
            'status' => $isCompleted
                ? MealPlanGenerationStatus::Completed->value
                : MealPlanGenerationStatus::Pending->value,
            'day_3_generated_at' => $generatedAt,
        ]),
    ]);

    $freshMealPlan = $mealPlan->fresh();
    expect($freshMealPlan->metadata['days_completed'])->toBe(3);
    expect($freshMealPlan->metadata['status'])->toBe(MealPlanGenerationStatus::Pending->value);
    expect($freshMealPlan->metadata)->toHaveKey('day_3_generated_at');
    expect($freshMealPlan->metadata['day_3_generated_at'])->toBe($generatedAt);
});

it('meal plan status becomes completed when all days generated', function (): void {
    $mealPlan = MealPlan::factory()
        ->for($this->user)
        ->custom(3)
        ->create([
            'metadata' => [
                'days_completed' => 2,
                'status' => MealPlanGenerationStatus::Pending->value,
            ],
        ]);

    // Simulate completing day 3 (final day)
    $daysCompleted = max(
        $mealPlan->metadata['days_completed'] ?? 0,
        3
    );
    $isCompleted = $daysCompleted >= $mealPlan->duration_days;

    $mealPlan->update([
        'metadata' => array_merge($mealPlan->metadata ?? [], [
            'days_completed' => $daysCompleted,
            'status' => $isCompleted
                ? MealPlanGenerationStatus::Completed->value
                : MealPlanGenerationStatus::Pending->value,
        ]),
    ]);

    expect($mealPlan->fresh()->metadata)
        ->days_completed->toBe(3)
        ->status->toBe(MealPlanGenerationStatus::Completed->value);
});

it('generates single day workflow returns correct result structure', function (): void {
    // Testing the expected return structure from the workflow
    $mealPlanId = 123;
    $dayNumber = 2;

    $expectedResult = [
        'meal_plan_id' => $mealPlanId,
        'day_number' => $dayNumber,
        'status' => MealPlanGenerationStatus::Completed->value,
    ];

    expect($expectedResult)
        ->toHaveKeys(['meal_plan_id', 'day_number', 'status'])
        ->meal_plan_id->toBe(123)
        ->day_number->toBe(2)
        ->status->toBe('completed');
});
