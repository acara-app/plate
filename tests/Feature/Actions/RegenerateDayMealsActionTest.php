<?php

declare(strict_types=1);

use App\Actions\RegenerateDayMealsAction;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;
use Workflow\WorkflowStub;

beforeEach(function (): void {
    WorkflowStub::fake();
});

test('it deletes existing meals for the specified day', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id, 'duration_days' => 7]);

    // Create meals for day 1
    Meal::factory()->count(3)->create([
        'meal_plan_id' => $mealPlan->id,
        'day_number' => 1,
    ]);

    // Create meals for day 2 (should not be deleted)
    Meal::factory()->count(3)->create([
        'meal_plan_id' => $mealPlan->id,
        'day_number' => 2,
    ]);

    $action = resolve(RegenerateDayMealsAction::class);
    $result = $action->handle($mealPlan, 1);

    expect($result['deleted_count'])->toBe(3)
        ->and($result['meal_plan_id'])->toBe($mealPlan->id)
        ->and($result['day_number'])->toBe(1)
        ->and($mealPlan->meals()->where('day_number', 1)->count())->toBe(0)
        ->and($mealPlan->meals()->where('day_number', 2)->count())->toBe(3);
});

test('it returns correct result structure', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id, 'duration_days' => 7]);

    Meal::factory()->count(3)->create([
        'meal_plan_id' => $mealPlan->id,
        'day_number' => 1,
    ]);

    $action = resolve(RegenerateDayMealsAction::class);
    $result = $action->handle($mealPlan, 1);

    expect($result)->toHaveKeys(['meal_plan_id', 'day_number', 'deleted_count'])
        ->and($result['meal_plan_id'])->toBe($mealPlan->id)
        ->and($result['day_number'])->toBe(1)
        ->and($result['deleted_count'])->toBe(3);
});

test('it returns zero deleted count when no meals exist for the day', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id, 'duration_days' => 7]);

    $action = resolve(RegenerateDayMealsAction::class);
    $result = $action->handle($mealPlan, 3);

    expect($result['deleted_count'])->toBe(0)
        ->and($result['day_number'])->toBe(3);
});

test('it can regenerate different days independently', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id, 'duration_days' => 7]);

    Meal::factory()->count(4)->create([
        'meal_plan_id' => $mealPlan->id,
        'day_number' => 1,
    ]);

    Meal::factory()->count(5)->create([
        'meal_plan_id' => $mealPlan->id,
        'day_number' => 2,
    ]);

    $action = resolve(RegenerateDayMealsAction::class);

    $result1 = $action->handle($mealPlan, 1);
    expect($result1['deleted_count'])->toBe(4);

    $result2 = $action->handle($mealPlan, 2);
    expect($result2['deleted_count'])->toBe(5);
});

test('it works with meal plan that has no meals', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id, 'duration_days' => 7]);

    $action = resolve(RegenerateDayMealsAction::class);
    $result = $action->handle($mealPlan, 1);

    expect($result['deleted_count'])->toBe(0)
        ->and($result['meal_plan_id'])->toBe($mealPlan->id);
});
