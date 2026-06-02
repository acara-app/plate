<?php

declare(strict_types=1);

use App\Ai\Agents\MealPlanAgent;
use App\Enums\DietType;
use App\Enums\GoalChoice;
use App\Enums\MealPlanGenerationStatus;
use App\Enums\Sex;
use App\Jobs\GenerateMealPlanDayJob;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;
use App\Workflows\MealPlanDayGeneratorActivity;
use App\Workflows\MealPlanPipeline\BuildPreviousDaysContextStep;
use App\Workflows\MealPlanPipeline\FinalizeMealPlanDayStep;
use App\Workflows\MealPlanPipeline\GenerateDayMealsStep;
use App\Workflows\MealPlanPipeline\MealPlanDayContext;
use App\Workflows\MealPlanPipeline\SaveDayMealsStep;
use App\Workflows\SaveDayMealsActivity;

covers(
    GenerateMealPlanDayJob::class,
    BuildPreviousDaysContextStep::class,
    GenerateDayMealsStep::class,
    SaveDayMealsStep::class,
    FinalizeMealPlanDayStep::class,
    MealPlanDayContext::class,
    MealPlanDayGeneratorActivity::class,
    SaveDayMealsActivity::class,
);

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

    $this->mockResponse = [
        'meals' => [
            [
                'type' => 'lunch',
                'name' => 'Test Lunch',
                'description' => 'Test description',
                'preparation_instructions' => 'Test instructions',
                'ingredients' => [['name' => 'Chicken', 'quantity' => '200g']],
                'portion_size' => '1 serving',
                'calories' => 500.0,
                'protein_grams' => 40.0,
                'carbs_grams' => 30.0,
                'fat_grams' => 18.0,
                'preparation_time_minutes' => 20,
                'sort_order' => 1,
            ],
        ],
    ];
});

it('generates a day using prior meal context and diet from metadata then stays pending', function (): void {
    MealPlanAgent::fake([$this->mockResponse]);

    $mealPlan = MealPlan::factory()->for($this->user)->weekly()->create([
        'duration_days' => 7,
        'metadata' => [
            'status' => MealPlanGenerationStatus::Pending->value,
            'days_completed' => 1,
            'diet_type' => DietType::Keto->value,
            'day_2_status' => MealPlanGenerationStatus::Generating->value,
        ],
    ]);

    Meal::factory()->for($mealPlan)->forDay(1)->create();

    (new GenerateMealPlanDayJob($mealPlan, 2))->handle();

    $fresh = $mealPlan->fresh();

    expect($fresh->meals()->where('day_number', 2)->count())->toBe(1)
        ->and($fresh->metadata['days_completed'])->toBe(2)
        ->and($fresh->metadata['status'])->toBe(MealPlanGenerationStatus::Pending->value)
        ->and($fresh->metadata)->toHaveKey('day_2_generated_at')
        ->and($fresh->metadata)->not->toHaveKey('day_2_status');
});

it('marks the plan completed when the final day is generated', function (): void {
    MealPlanAgent::fake([$this->mockResponse]);

    $mealPlan = MealPlan::factory()->for($this->user)->custom(2)->create([
        'duration_days' => 2,
        'metadata' => [
            'status' => MealPlanGenerationStatus::Pending->value,
            'days_completed' => 1,
            'diet_type' => DietType::Balanced->value,
        ],
    ]);

    Meal::factory()->for($mealPlan)->forDay(1)->create();

    (new GenerateMealPlanDayJob($mealPlan, 2))->handle();

    expect($mealPlan->fresh()->metadata['status'])
        ->toBe(MealPlanGenerationStatus::Completed->value);
});

it('generates the first day with no prior context and no diet metadata', function (): void {
    MealPlanAgent::fake([$this->mockResponse]);

    $mealPlan = MealPlan::factory()->for($this->user)->weekly()->create([
        'duration_days' => 7,
        'metadata' => [
            'status' => MealPlanGenerationStatus::Pending->value,
            'days_completed' => 0,
        ],
    ]);

    (new GenerateMealPlanDayJob($mealPlan, 1))->handle();

    expect($mealPlan->fresh()->meals()->where('day_number', 1)->count())->toBe(1);
});

it('marks the day failed when the job fails', function (): void {
    $mealPlan = MealPlan::factory()->for($this->user)->weekly()->create([
        'metadata' => [
            'status' => MealPlanGenerationStatus::Pending->value,
            'days_completed' => 0,
        ],
    ]);

    (new GenerateMealPlanDayJob($mealPlan, 2))->failed(new RuntimeException('boom'));

    expect($mealPlan->fresh()->metadata['day_2_status'])
        ->toBe(MealPlanGenerationStatus::Failed->value);
});

it('builds a stable unique id from the meal plan and day', function (): void {
    $mealPlan = MealPlan::factory()->for($this->user)->weekly()->create();

    expect((new GenerateMealPlanDayJob($mealPlan, 3))->uniqueId())
        ->toBe('meal-plan-day:'.$mealPlan->id.':3');
});
