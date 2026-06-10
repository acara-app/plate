<?php

declare(strict_types=1);

use App\Ai\Agents\MealPlanAgent;
use App\Enums\DietType;
use App\Enums\GoalChoice;
use App\Enums\MealPlanGenerationStatus;
use App\Enums\Sex;
use App\Jobs\GenerateInitialMealPlanJob;
use App\Models\MealPlan;
use App\Models\User;
use App\Workflows\MealPlanDayGeneratorActivity;
use App\Workflows\MealPlanPipeline\FinalizeInitialMealPlanStep;
use App\Workflows\MealPlanPipeline\GenerateDayMealsStep;
use App\Workflows\MealPlanPipeline\MealPlanDayContext;
use App\Workflows\MealPlanPipeline\SaveDayMealsStep;
use App\Workflows\SaveDayMealsActivity;
use Illuminate\Queue\Middleware\WithoutOverlapping;

covers(
    GenerateInitialMealPlanJob::class,
    GenerateDayMealsStep::class,
    SaveDayMealsStep::class,
    FinalizeInitialMealPlanStep::class,
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
});

it('generates and saves day one then marks the plan pending', function (): void {
    MealPlanAgent::fake([$this->mockResponse]);

    $mealPlan = MealPlan::factory()->for($this->user)->weekly()->create([
        'duration_days' => 7,
        'metadata' => [
            'status' => MealPlanGenerationStatus::Generating->value,
            'days_completed' => 0,
        ],
    ]);

    new GenerateInitialMealPlanJob($this->user, $mealPlan, null, DietType::Mediterranean)->handle();

    $fresh = $mealPlan->fresh();

    expect($fresh->meals()->where('day_number', 1)->count())->toBe(1)
        ->and($fresh->meals()->where('day_number', 1)->first()->name)->toBe('Test Breakfast')
        ->and($fresh->metadata['days_completed'])->toBe(1)
        ->and($fresh->metadata['status'])->toBe(MealPlanGenerationStatus::Pending->value);
});

it('keeps a single day plan pending rather than completed', function (): void {
    MealPlanAgent::fake([$this->mockResponse]);

    $mealPlan = MealPlan::factory()->for($this->user)->custom(1)->create([
        'duration_days' => 1,
        'metadata' => [
            'status' => MealPlanGenerationStatus::Generating->value,
            'days_completed' => 0,
        ],
    ]);

    new GenerateInitialMealPlanJob($this->user, $mealPlan)->handle();

    expect($mealPlan->fresh()->metadata['status'])
        ->toBe(MealPlanGenerationStatus::Pending->value);
});

it('marks the plan failed when the job fails', function (): void {
    $mealPlan = MealPlan::factory()->for($this->user)->weekly()->create([
        'metadata' => [
            'status' => MealPlanGenerationStatus::Generating->value,
            'days_completed' => 0,
        ],
    ]);

    new GenerateInitialMealPlanJob($this->user, $mealPlan)->failed(new RuntimeException('boom'));

    expect($mealPlan->fresh()->metadata['status'])
        ->toBe(MealPlanGenerationStatus::Failed->value);
});

it('builds a stable unique id from the meal plan', function (): void {
    $mealPlan = MealPlan::factory()->for($this->user)->weekly()->create();

    expect(new GenerateInitialMealPlanJob($this->user, $mealPlan)->uniqueId())
        ->toBe('meal-plan-init:'.$mealPlan->id);
});

it('prevents overlapping execution', function (): void {
    $mealPlan = MealPlan::factory()->for($this->user)->weekly()->create();

    $middleware = new GenerateInitialMealPlanJob($this->user, $mealPlan)->middleware();

    expect($middleware)->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(WithoutOverlapping::class);
});
