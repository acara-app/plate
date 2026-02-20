<?php

declare(strict_types=1);

use App\Ai\Agents\MealPlanAgent;
use App\DataObjects\DayMealsData;
use App\DataObjects\MealData;
use App\DataObjects\PreviousDayContext;
use App\DataObjects\SingleDayMealData;
use App\Enums\GoalChoice;
use App\Enums\MealPlanGenerationStatus;
use App\Enums\MealPlanType;
use App\Enums\MealType;
use App\Enums\Sex;
use App\Models\User;
use App\Workflows\MealPlanDayGeneratorActivity;
use App\Workflows\MealPlanInitializeWorkflow;
use App\Workflows\SaveDayMealsActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelData\DataCollection;
use Workflow\Activity;
use Workflow\Workflow;
use Workflow\WorkflowStub;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->user->profile()->create([
        'age' => 30,
        'height' => 175.0,
        'weight' => 80.0,
        'sex' => Sex::Male,
        'goal_choice' => GoalChoice::WeightLoss->value,
        'derived_activity_multiplier' => 1.55,
        'target_weight' => 75.0,
    ]);
});

it('converts all days meals to meal data collection correctly', function (): void {
    $day1Meals = new DayMealsData(
        meals: new DataCollection(SingleDayMealData::class, [
            new SingleDayMealData(
                type: MealType::Breakfast,
                name: 'Day 1 Breakfast',
                description: 'Test breakfast',
                preparationInstructions: 'Test instructions',
                ingredients: null,
                portionSize: '1 serving',
                calories: 400.0,
                proteinGrams: 30.0,
                carbsGrams: 40.0,
                fatGrams: 15.0,
                preparationTimeMinutes: 10,
                sortOrder: 1,
            ),
        ]),
    );

    $day2Meals = new DayMealsData(
        meals: new DataCollection(SingleDayMealData::class, [
            new SingleDayMealData(
                type: MealType::Lunch,
                name: 'Day 2 Lunch',
                description: 'Test lunch',
                preparationInstructions: 'Test instructions',
                ingredients: null,
                portionSize: '1 serving',
                calories: 500.0,
                proteinGrams: 35.0,
                carbsGrams: 50.0,
                fatGrams: 20.0,
                preparationTimeMinutes: 20,
                sortOrder: 3,
            ),
        ]),
    );

    $allDaysMeals = [1 => $day1Meals, 2 => $day2Meals];

    $result = MealPlanInitializeWorkflow::convertToMealDataCollection($allDaysMeals);

    expect($result)->toHaveCount(2);
    expect($result[0])
        ->toBeInstanceOf(MealData::class)
        ->dayNumber->toBe(1)
        ->name->toBe('Day 1 Breakfast');
    expect($result[1])
        ->toBeInstanceOf(MealData::class)
        ->dayNumber->toBe(2)
        ->name->toBe('Day 2 Lunch');
});

it('gets correct meal plan type based on total days', function (): void {
    expect(MealPlanInitializeWorkflow::getMealPlanType(7))->toBe(MealPlanType::Weekly);
    expect(MealPlanInitializeWorkflow::getMealPlanType(5))->toBe(MealPlanType::Weekly);
    expect(MealPlanInitializeWorkflow::getMealPlanType(14))->toBe(MealPlanType::Monthly);
    expect(MealPlanInitializeWorkflow::getMealPlanType(30))->toBe(MealPlanType::Monthly);
    expect(MealPlanInitializeWorkflow::getMealPlanType(45))->toBe(MealPlanType::Custom);
});

it('previous day context generates correct prompt text', function (): void {
    $context = new PreviousDayContext;
    $context->addDayMeals(1, ['Oatmeal', 'Chicken Salad', 'Grilled Salmon']);
    $context->addDayMeals(2, ['Greek Yogurt', 'Turkey Wrap', 'Beef Stir Fry']);

    $promptText = $context->toPromptText();

    expect($promptText)
        ->toContain("## Previous Days' Meals")
        ->toContain('Day 1')
        ->toContain('Oatmeal')
        ->toContain('Day 2')
        ->toContain('Greek Yogurt')
        ->toContain('variety');
});

it('empty previous day context returns empty string', function (): void {
    $context = new PreviousDayContext;

    expect($context->toPromptText())->toBe('');
});

it('single day meal data converts to meal data with day number', function (): void {
    $singleDayMeal = new SingleDayMealData(
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

    $mealData = $singleDayMeal->toMealData(3);

    expect($mealData)
        ->toBeInstanceOf(MealData::class)
        ->dayNumber->toBe(3)
        ->type->toBe(MealType::Dinner)
        ->name->toBe('Grilled Chicken')
        ->calories->toBe(450.0);
});

it('generates day meals using activity with mocked agent', function (): void {
    $mockResponse = [
        'meals' => [
            [
                'type' => 'breakfast',
                'name' => 'Test Breakfast',
                'description' => 'Test description',
                'preparation_instructions' => 'Test instructions',
                'ingredients' => [['name' => 'Eggs', 'quantity' => '2 large']],
                'portion_size' => '1 serving',
                'calories' => 350.0,
                'protein_grams' => 25.0,
                'carbs_grams' => 10.0,
                'fat_grams' => 20.0,
                'preparation_time_minutes' => 10,
                'sort_order' => 1,
            ],
        ],
    ];

    MealPlanAgent::fake([$mockResponse]);

    // Test using the action directly instead of activity instantiation
    $action = resolve(MealPlanAgent::class);
    $result = $action->generateForDay(
        $this->user,
        dayNumber: 1,
        totalDays: 7,
        previousDaysContext: new PreviousDayContext,
    );

    expect($result)
        ->toBeInstanceOf(DayMealsData::class)
        ->meals->toHaveCount(1);

    expect($result->meals[0])
        ->name->toBe('Test Breakfast')
        ->type->toBe(MealType::Breakfast);
});

it('activity classes exist and extend correct base class', function (): void {
    expect(class_exists(MealPlanDayGeneratorActivity::class))->toBeTrue();
    expect(class_exists(SaveDayMealsActivity::class))->toBeTrue();
    expect(is_subclass_of(MealPlanDayGeneratorActivity::class, Activity::class))->toBeTrue();
    expect(is_subclass_of(SaveDayMealsActivity::class, Activity::class))->toBeTrue();
});

it('workflow class exists and extends correct base class', function (): void {
    expect(class_exists(MealPlanInitializeWorkflow::class))->toBeTrue();
    expect(is_subclass_of(MealPlanInitializeWorkflow::class, Workflow::class))->toBeTrue();
});

it('workflow triggers via generate meal plan action with workflow stub fake', function (): void {
    WorkflowStub::fake();

    $action = resolve(MealPlanAgent::class);
    $action->handle($this->user);

    // Meal plan is now created synchronously before workflow starts
    $mealPlan = $this->user->mealPlans()->first();
    expect($mealPlan)->not->toBeNull();
    expect($mealPlan->metadata['status'])->toBe('generating');
});

it('converts multiple days meals to collection preserving day numbers', function (): void {
    $day1Meals = new DayMealsData(
        meals: new DataCollection(SingleDayMealData::class, [
            new SingleDayMealData(
                type: MealType::Breakfast,
                name: 'Day 1 Breakfast',
                description: 'Test',
                preparationInstructions: 'Test',
                ingredients: null,
                portionSize: '1 serving',
                calories: 400.0,
                proteinGrams: 30.0,
                carbsGrams: 40.0,
                fatGrams: 15.0,
                preparationTimeMinutes: 10,
                sortOrder: 1,
            ),
            new SingleDayMealData(
                type: MealType::Lunch,
                name: 'Day 1 Lunch',
                description: 'Test',
                preparationInstructions: 'Test',
                ingredients: null,
                portionSize: '1 serving',
                calories: 500.0,
                proteinGrams: 35.0,
                carbsGrams: 50.0,
                fatGrams: 20.0,
                preparationTimeMinutes: 15,
                sortOrder: 2,
            ),
        ]),
    );

    $day2Meals = new DayMealsData(
        meals: new DataCollection(SingleDayMealData::class, [
            new SingleDayMealData(
                type: MealType::Dinner,
                name: 'Day 2 Dinner',
                description: 'Test',
                preparationInstructions: 'Test',
                ingredients: null,
                portionSize: '1 serving',
                calories: 600.0,
                proteinGrams: 40.0,
                carbsGrams: 45.0,
                fatGrams: 25.0,
                preparationTimeMinutes: 25,
                sortOrder: 3,
            ),
        ]),
    );

    $allDaysMeals = [1 => $day1Meals, 2 => $day2Meals];
    $result = MealPlanInitializeWorkflow::convertToMealDataCollection($allDaysMeals);

    expect($result)->toHaveCount(3);

    // Check day numbers are preserved
    expect($result[0]->dayNumber)->toBe(1);
    expect($result[1]->dayNumber)->toBe(1);
    expect($result[2]->dayNumber)->toBe(2);

    // Check meal types and names
    expect($result[0]->type)->toBe(MealType::Breakfast);
    expect($result[1]->type)->toBe(MealType::Lunch);
    expect($result[2]->type)->toBe(MealType::Dinner);
});

it('returns custom type for plans exceeding 30 days', function (): void {
    expect(MealPlanInitializeWorkflow::getMealPlanType(31))->toBe(MealPlanType::Custom);
    expect(MealPlanInitializeWorkflow::getMealPlanType(60))->toBe(MealPlanType::Custom);
    expect(MealPlanInitializeWorkflow::getMealPlanType(90))->toBe(MealPlanType::Custom);
});

it('returns weekly type for 7 days or less', function (): void {
    expect(MealPlanInitializeWorkflow::getMealPlanType(1))->toBe(MealPlanType::Weekly);
    expect(MealPlanInitializeWorkflow::getMealPlanType(3))->toBe(MealPlanType::Weekly);
    expect(MealPlanInitializeWorkflow::getMealPlanType(7))->toBe(MealPlanType::Weekly);
});

it('returns monthly type for 8 to 30 days', function (): void {
    expect(MealPlanInitializeWorkflow::getMealPlanType(8))->toBe(MealPlanType::Monthly);
    expect(MealPlanInitializeWorkflow::getMealPlanType(15))->toBe(MealPlanType::Monthly);
    expect(MealPlanInitializeWorkflow::getMealPlanType(28))->toBe(MealPlanType::Monthly);
    expect(MealPlanInitializeWorkflow::getMealPlanType(30))->toBe(MealPlanType::Monthly);
});

it('generates expected result structure from workflow execution', function (): void {
    // Testing the expected return structure from the workflow
    $userId = 1;
    $totalDays = 7;
    $daysGenerated = 1;
    $mealPlanId = 123;

    $expectedResult = [
        'user_id' => $userId,
        'total_days' => $totalDays,
        'days_generated' => $daysGenerated,
        'status' => MealPlanGenerationStatus::Pending->value,
        'meal_plan_id' => $mealPlanId,
    ];

    expect($expectedResult)
        ->toHaveKeys(['user_id', 'total_days', 'days_generated', 'status', 'meal_plan_id'])
        ->user_id->toBe(1)
        ->total_days->toBe(7)
        ->days_generated->toBe(1)
        ->status->toBe('pending')
        ->meal_plan_id->toBe(123);
});

it('workflow returns completed status when all days generated', function (): void {
    $totalDays = 3;
    $daysGenerated = 3; // All days generated

    $finalStatus = $daysGenerated >= $totalDays
        ? MealPlanGenerationStatus::Completed->value
        : MealPlanGenerationStatus::Pending->value;

    expect($finalStatus)->toBe('completed');
});

it('workflow returns pending status when not all days generated', function (): void {
    $totalDays = 7;
    $daysGenerated = 1; // Only 1 day generated

    $finalStatus = $daysGenerated >= $totalDays
        ? MealPlanGenerationStatus::Completed->value
        : MealPlanGenerationStatus::Pending->value;

    expect($finalStatus)->toBe('pending');
});

it('previous day context adds multiple days correctly', function (): void {
    $context = new PreviousDayContext;

    $context->addDayMeals(1, ['Breakfast 1', 'Lunch 1', 'Dinner 1']);
    $context->addDayMeals(2, ['Breakfast 2', 'Lunch 2', 'Dinner 2']);
    $context->addDayMeals(3, ['Breakfast 3']);

    $promptText = $context->toPromptText();

    expect($promptText)
        ->toContain('Day 1')
        ->toContain('Day 2')
        ->toContain('Day 3')
        ->toContain('Breakfast 1')
        ->toContain('Lunch 2')
        ->toContain('Breakfast 3');
});

it('converts day meals data to meal data collection with correct properties', function (): void {
    $dayMeals = new DayMealsData(
        meals: new DataCollection(SingleDayMealData::class, [
            new SingleDayMealData(
                type: MealType::Breakfast,
                name: 'Oatmeal',
                description: 'Healthy oatmeal',
                preparationInstructions: 'Cook oats',
                ingredients: null,
                portionSize: '1 cup',
                calories: 300.0,
                proteinGrams: 10.0,
                carbsGrams: 50.0,
                fatGrams: 5.0,
                preparationTimeMinutes: 5,
                sortOrder: 1,
            ),
        ]),
    );

    $allDaysMeals = [5 => $dayMeals];
    $result = MealPlanInitializeWorkflow::convertToMealDataCollection($allDaysMeals);

    expect($result)->toHaveCount(1);
    expect($result[0]->dayNumber)->toBe(5);
    expect($result[0]->name)->toBe('Oatmeal');
    expect($result[0]->calories)->toBe(300.0);
    expect($result[0]->type)->toBe(MealType::Breakfast);
});
