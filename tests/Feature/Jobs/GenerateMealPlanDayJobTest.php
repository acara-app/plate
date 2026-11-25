<?php

declare(strict_types=1);

use App\Ai\Agents\MealPlanAgent;
use App\Enums\DietType;
use App\Enums\GoalChoice;
use App\Enums\MealPlanGenerationStatus;
use App\Enums\Sex;
use App\Exceptions\EmptyMealPlanDayException;
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
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Laravel\Ai\Responses\Data\ToolCall;

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
    MealPlanAgent::fake([
        new ToolCall('guide', 'LoadSkill', ['name' => 'ketogenic-diet']),
        $this->mockResponse,
    ])->preventStrayPrompts();

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

    new GenerateMealPlanDayJob($mealPlan, 2)->handle();

    $fresh = $mealPlan->fresh();

    expect($fresh->meals()->where('day_number', 2)->count())->toBe(1)
        ->and($fresh->metadata['days_completed'])->toBe(2)
        ->and($fresh->metadata['status'])->toBe(MealPlanGenerationStatus::Pending->value)
        ->and($fresh->metadata)->toHaveKey('day_2_generated_at')
        ->and($fresh->metadata)->not->toHaveKey('day_2_status');
});

it('marks the plan completed when the final day is generated', function (): void {
    MealPlanAgent::fake([
        new ToolCall('guide', 'LoadSkill', ['name' => 'balanced-diet']),
        $this->mockResponse,
    ])->preventStrayPrompts();

    $mealPlan = MealPlan::factory()->for($this->user)->custom(2)->create([
        'duration_days' => 2,
        'metadata' => [
            'status' => MealPlanGenerationStatus::Pending->value,
            'days_completed' => 1,
            'diet_type' => DietType::Balanced->value,
        ],
    ]);

    Meal::factory()->for($mealPlan)->forDay(1)->create();

    new GenerateMealPlanDayJob($mealPlan, 2)->handle();

    expect($mealPlan->fresh()->metadata['status'])
        ->toBe(MealPlanGenerationStatus::Completed->value);
});

it('generates the first day with no prior context and no diet metadata', function (): void {
    MealPlanAgent::fake([
        new ToolCall('guide', 'LoadSkill', ['name' => 'balanced-diet']),
        $this->mockResponse,
    ])->preventStrayPrompts();

    $mealPlan = MealPlan::factory()->for($this->user)->weekly()->create([
        'duration_days' => 7,
        'metadata' => [
            'status' => MealPlanGenerationStatus::Pending->value,
            'days_completed' => 0,
        ],
    ]);

    new GenerateMealPlanDayJob($mealPlan, 1)->handle();

    expect($mealPlan->fresh()->meals()->where('day_number', 1)->count())->toBe(1);
});

it('leaves a day unsaved when the planner skips a required diet reference', function (array $toolCalls, string $missing): void {
    MealPlanAgent::fake([...$toolCalls, $this->mockResponse])->preventStrayPrompts();
    $mealPlan = MealPlan::factory()->for($this->user)->weekly()->create([
        'metadata' => [
            'diet_type' => DietType::Mediterranean->value,
            'days_completed' => 0,
            'day_1_status' => MealPlanGenerationStatus::Generating->value,
        ],
    ]);

    expect(fn () => dispatch_sync(new GenerateMealPlanDayJob($mealPlan, 1)))
        ->toThrow(RuntimeException::class, $missing);

    expect($mealPlan->fresh()->metadata)->toMatchArray([
        'days_completed' => 0,
        'day_1_status' => MealPlanGenerationStatus::Failed->value,
    ]);
    expect($mealPlan->meals()->exists())->toBeFalse();
})->with([
    'no diet guide' => [[], 'mediterranean-diet'],
    'guide without its score card' => [
        [new ToolCall('guide', 'LoadSkill', ['name' => 'mediterranean-diet'])],
        'score card',
    ],
]);

it('marks the day failed when the job fails', function (): void {
    $mealPlan = MealPlan::factory()->for($this->user)->weekly()->create([
        'metadata' => [
            'status' => MealPlanGenerationStatus::Pending->value,
            'days_completed' => 0,
        ],
    ]);

    new GenerateMealPlanDayJob($mealPlan, 2)->failed(new RuntimeException('boom'));

    expect($mealPlan->fresh()->metadata['day_2_status'])
        ->toBe(MealPlanGenerationStatus::Failed->value);
});

it('fails the day instead of finishing it when no meals come back', function (): void {
    MealPlanAgent::fake([
        new ToolCall('guide', 'LoadSkill', ['name' => 'balanced-diet']),
        ['meals' => []],
    ])->preventStrayPrompts();

    $mealPlan = MealPlan::factory()->for($this->user)->weekly()->create([
        'metadata' => [
            'status' => MealPlanGenerationStatus::Pending->value,
            'days_completed' => 1,
            'day_2_status' => MealPlanGenerationStatus::Generating->value,
        ],
    ]);

    expect(function () use ($mealPlan): void {
        dispatch_sync(new GenerateMealPlanDayJob($mealPlan, 2));
    })->toThrow(EmptyMealPlanDayException::class);

    expect($mealPlan->fresh()->metadata)
        ->toMatchArray([
            'days_completed' => 1,
            'day_2_status' => MealPlanGenerationStatus::Failed->value,
        ])
        ->not->toHaveKey('day_2_generated_at');
});

it('builds a stable unique id from the meal plan and day', function (): void {
    $mealPlan = MealPlan::factory()->for($this->user)->weekly()->create();

    expect(new GenerateMealPlanDayJob($mealPlan, 3)->uniqueId())
        ->toBe('meal-plan-day:'.$mealPlan->id.':3');
});

it('prevents overlapping execution', function (): void {
    $mealPlan = MealPlan::factory()->for($this->user)->weekly()->create();

    $middleware = new GenerateMealPlanDayJob($mealPlan, 2)->middleware();

    expect($middleware)->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(WithoutOverlapping::class);
});
