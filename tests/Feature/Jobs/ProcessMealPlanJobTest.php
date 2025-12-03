<?php

declare(strict_types=1);

use App\DataObjects\DayMealsData;
use App\DataObjects\SingleDayMealData;
use App\Enums\AiModel;
use App\Enums\MealPlanType;
use App\Enums\MealType;
use App\Enums\Sex;
use App\Jobs\ProcessMealPlanJob;
use App\Models\Goal;
use App\Models\Lifestyle;
use App\Models\MealPlan;
use App\Models\User;
use App\Workflows\CreateMealPlanActivity;
use App\Workflows\GenerateDayMealsActivity;
use App\Workflows\StoreDayMealsActivity;
use Illuminate\Support\Facades\Queue;
use Spatie\LaravelData\DataCollection;
use Workflow\WorkflowStub;

it('dispatches meal plan generation job', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    dispatch(new ProcessMealPlanJob($user, AiModel::Gemini25Flash));

    Queue::assertPushed(ProcessMealPlanJob::class, fn ($job): bool => $job->user->id === $user->id
        && $job->aiModel === AiModel::Gemini25Flash);
});

it('generates and stores meal plan incrementally', function (): void {
    WorkflowStub::fake();

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

    // Mock CreateMealPlanActivity to create the meal plan
    WorkflowStub::mock(CreateMealPlanActivity::class, function ($context, $activityUser, $totalDays) use ($user) {
        return $user->mealPlans()->create([
            'type' => App\Workflows\GenerateMealPlanWorkflow::getMealPlanType($totalDays),
            'name' => "{$totalDays}-Day Test Plan",
            'description' => 'Test plan',
            'duration_days' => $totalDays,
            'metadata' => ['status' => 'generating', 'days_completed' => 0],
        ]);
    });

    // Mock GenerateDayMealsActivity to return fake meal data
    WorkflowStub::mock(GenerateDayMealsActivity::class, fn ($context, $activityUser, $dayNumber): DayMealsData => new DayMealsData(
        meals: new DataCollection(SingleDayMealData::class, [
            new SingleDayMealData(
                type: MealType::Breakfast,
                name: "Day {$dayNumber} Breakfast",
                description: 'Test breakfast',
                preparationInstructions: 'Test instructions',
                ingredients: new DataCollection(App\DataObjects\IngredientData::class, [
                    new App\DataObjects\IngredientData(name: 'Eggs', quantity: '2 large'),
                ]),
                portionSize: '1 serving',
                calories: 350.0,
                proteinGrams: 25.0,
                carbsGrams: 10.0,
                fatGrams: 20.0,
                preparationTimeMinutes: 10,
                sortOrder: 1,
            ),
        ]),
    ));

    // Mock StoreDayMealsActivity to store meals for each day
    WorkflowStub::mock(StoreDayMealsActivity::class, function ($context, $mealPlan, $dayMeals, $dayNumber) {
        foreach ($dayMeals->meals as $singleDayMeal) {
            $mealData = $singleDayMeal->toMealData($dayNumber);
            $mealPlan->meals()->create([
                'day_number' => $mealData->dayNumber,
                'type' => $mealData->type,
                'name' => $mealData->name,
                'description' => $mealData->description,
                'preparation_instructions' => $mealData->preparationInstructions,
                'ingredients' => $mealData->ingredients,
                'portion_size' => $mealData->portionSize,
                'calories' => $mealData->calories,
                'protein_grams' => $mealData->proteinGrams,
                'carbs_grams' => $mealData->carbsGrams,
                'fat_grams' => $mealData->fatGrams,
                'preparation_time_minutes' => $mealData->preparationTimeMinutes,
                'sort_order' => $mealData->sortOrder,
            ]);
        }

        return ['day_number' => $dayNumber, 'meals_count' => count($dayMeals->meals)];
    });

    $job = new ProcessMealPlanJob($user, AiModel::Gemini25Flash, totalDays: 7);
    $job->handle();

    expect(MealPlan::query()->where('user_id', $user->id)->count())->toBe(1);

    $mealPlan = MealPlan::query()->where('user_id', $user->id)->first();
    expect($mealPlan)
        ->type->toBe(MealPlanType::Weekly)
        ->meals->toHaveCount(7); // 7 days × 1 meal per day

    // Verify activities were dispatched in order
    WorkflowStub::assertDispatched(CreateMealPlanActivity::class, 1);
    WorkflowStub::assertDispatched(GenerateDayMealsActivity::class, 7);
    WorkflowStub::assertDispatched(StoreDayMealsActivity::class, 7);
});

it('has correct timeout configuration', function (): void {
    $user = User::factory()->create();
    $job = new ProcessMealPlanJob($user, AiModel::Gemini25Flash);

    expect($job->timeout)->toBe(120); // 2 minutes to start workflow
});
