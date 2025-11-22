<?php

declare(strict_types=1);

use App\Enums\AiModel;
use App\Enums\MealPlanType;
use App\Enums\Sex;
use App\Jobs\ProcessMealPlanJob;
use App\Models\Goal;
use App\Models\Lifestyle;
use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

it('dispatches meal plan generation job', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    dispatch(new ProcessMealPlanJob($user->id, AiModel::Gemini25Flash));

    Queue::assertPushed(ProcessMealPlanJob::class, fn ($job): bool => $job->userId === $user->id
        && $job->model === AiModel::Gemini25Flash);
});

it('generates and stores meal plan when job is processed', function (): void {
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
        'name' => 'Test Weekly Plan',
        'description' => 'Test plan from job',
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
                'name' => 'Test Meal',
                'description' => 'Test',
                'preparation_instructions' => 'Test instructions',
                'ingredients' => [['name' => 'Test ingredient', 'quantity' => '100g']],
                'portion_size' => '1 serving',
                'calories' => 350.0,
                'protein_grams' => 25.0,
                'carbs_grams' => 30.0,
                'fat_grams' => 10.0,
                'preparation_time_minutes' => 10,
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

    $job = new ProcessMealPlanJob($user->id, AiModel::Gemini25Flash);
    $job->handle(
        app(App\Actions\AiAgents\GenerateMealPlan::class),
        app(App\Actions\StoreMealPlan::class)
    );

    expect(MealPlan::query()->where('user_id', $user->id)->count())->toBe(1);

    $mealPlan = MealPlan::query()->where('user_id', $user->id)->first();
    expect($mealPlan)
        ->type->toBe(MealPlanType::Weekly)
        ->name->toBe('Test Weekly Plan')
        ->meals->toHaveCount(1);
});

it('handles missing user gracefully', function (): void {
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

    $job = new ProcessMealPlanJob(99999, AiModel::Gemini25Flash);
    $job->handle(
        app(App\Actions\AiAgents\GenerateMealPlan::class),
        app(App\Actions\StoreMealPlan::class)
    );

    // Should not throw an exception and should not create any meal plans
    expect(MealPlan::query()->count())->toBe(0);
});

it('has correct timeout configuration', function (): void {
    $job = new ProcessMealPlanJob(1, AiModel::Gemini25Flash);

    expect($job->timeout)->toBe(300);
});

it('handles exceptions and marks tracking as failed', function (): void {
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

    // Fake a failure by not setting up any Prism fake response
    // This will cause an exception when trying to generate the meal plan
    Prism::fake([]);

    $job = new ProcessMealPlanJob($user->id, AiModel::Gemini25Flash);

    try {
        $job->handle(
            app(App\Actions\AiAgents\GenerateMealPlan::class),
            app(App\Actions\StoreMealPlan::class)
        );
        $this->fail('Expected an exception to be thrown');
    } catch (Throwable $e) {
        // Exception was thrown as expected
        expect($e)->toBeInstanceOf(Throwable::class);
    }

    // Verify that the tracking was marked as failed
    $user->refresh();
    $tracking = $user->jobTrackings()->where('job_type', ProcessMealPlanJob::JOB_TYPE)->first();

    expect($tracking)
        ->not->toBeNull()
        ->status->toBe(App\Enums\JobStatus::Failed)
        ->and($tracking->message)->toContain('Failed to generate meal plan');
});
