<?php

declare(strict_types=1);

use App\Enums\MealPlanGenerationStatus;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;
use Workflow\WorkflowStub;

it('returns 403 when user does not own meal plan', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->postJson(route('meal-plans.generate-day', $mealPlan), ['day' => 1])
        ->assertForbidden();
});

it('returns 422 for invalid day number', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create(['duration_days' => 7]);

    $this->actingAs($user)
        ->postJson(route('meal-plans.generate-day', $mealPlan), ['day' => 10])
        ->assertUnprocessable()
        ->assertJson(['success' => false, 'message' => 'Invalid day number']);
});

it('returns completed when day already has meals', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create(['duration_days' => 7]);
    Meal::factory()->for($mealPlan)->create(['day_number' => 1]);

    $this->actingAs($user)
        ->postJson(route('meal-plans.generate-day', $mealPlan), ['day' => 1])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'status' => MealPlanGenerationStatus::Completed->value,
        ]);
});

it('returns generating when day is currently being generated', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'duration_days' => 7,
        'metadata' => ['day_1_status' => MealPlanGenerationStatus::Generating->value],
    ]);

    $this->actingAs($user)
        ->postJson(route('meal-plans.generate-day', $mealPlan), ['day' => 1])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'status' => MealPlanGenerationStatus::Generating->value,
            'message' => 'Day is currently being generated',
        ]);
});

it('starts workflow for pending day', function (): void {
    WorkflowStub::fake();

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create(['duration_days' => 7]);

    $this->actingAs($user)
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
