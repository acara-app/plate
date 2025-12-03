<?php

declare(strict_types=1);

use App\DataObjects\DayMealsData;
use App\DataObjects\MealData;
use App\DataObjects\PreviousDayContext;
use App\DataObjects\SingleDayMealData;
use App\Enums\AiModel;
use App\Enums\MealPlanType;
use App\Enums\MealType;
use App\Enums\Sex;
use App\Models\Goal;
use App\Models\Lifestyle;
use App\Models\User;
use App\Workflows\CreateMealPlanActivity;
use App\Workflows\GenerateDayMealsActivity;
use App\Workflows\GenerateMealPlanWorkflow;
use App\Workflows\StoreDayMealsActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;
use Spatie\LaravelData\DataCollection;

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

    $result = GenerateMealPlanWorkflow::convertToMealDataCollection($allDaysMeals);

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
    expect(GenerateMealPlanWorkflow::getMealPlanType(7))->toBe(MealPlanType::Weekly);
    expect(GenerateMealPlanWorkflow::getMealPlanType(5))->toBe(MealPlanType::Weekly);
    expect(GenerateMealPlanWorkflow::getMealPlanType(14))->toBe(MealPlanType::Monthly);
    expect(GenerateMealPlanWorkflow::getMealPlanType(30))->toBe(MealPlanType::Monthly);
    expect(GenerateMealPlanWorkflow::getMealPlanType(45))->toBe(MealPlanType::Custom);
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

it('generates day meals using activity with mocked prism', function (): void {
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

    $fakeResponse = TextResponseFake::make()
        ->withText(json_encode($mockResponse, JSON_THROW_ON_ERROR))
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(100, 200))
        ->withMeta(new Meta('test-id', 'gemini-2.5-flash'));

    Prism::fake([$fakeResponse]);

    // Test using the action directly instead of activity instantiation
    $action = app(App\Actions\AiAgents\GenerateMealPlan::class);
    $result = $action->generateForDay(
        $this->user,
        dayNumber: 1,
        model: AiModel::Gemini25Flash,
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
    expect(class_exists(GenerateDayMealsActivity::class))->toBeTrue();
    expect(class_exists(CreateMealPlanActivity::class))->toBeTrue();
    expect(class_exists(StoreDayMealsActivity::class))->toBeTrue();
    expect(is_subclass_of(GenerateDayMealsActivity::class, Workflow\Activity::class))->toBeTrue();
    expect(is_subclass_of(CreateMealPlanActivity::class, Workflow\Activity::class))->toBeTrue();
    expect(is_subclass_of(StoreDayMealsActivity::class, Workflow\Activity::class))->toBeTrue();
});
