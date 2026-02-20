<?php

declare(strict_types=1);

use App\Actions\GenerateGroceryListAction;
use App\Ai\Agents\GroceryListGeneratorAgent;
use App\Enums\GroceryListStatus;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\User;

it('creates a placeholder grocery list with generating status', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'name' => 'Weekly Meal Plan',
        'duration_days' => 7,
    ]);

    $agent = resolve(GroceryListGeneratorAgent::class);
    $action = new GenerateGroceryListAction($agent);

    $groceryList = $action->createPlaceholder($mealPlan);

    expect($groceryList)
        ->toBeInstanceOf(GroceryList::class)
        ->user_id->toBe($user->id)
        ->name->toBe('Grocery List for '.$mealPlan->name)
        ->status->toBe(GroceryListStatus::Generating)
        ->metadata->toBeArray()
        ->and($groceryList->metadata)
        ->toHaveKey('started_at')
        ->toHaveKey('meal_plan_duration_days', 7);
});

it('deletes existing grocery list before creating placeholder', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $existingGroceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    $agent = resolve(GroceryListGeneratorAgent::class);
    $action = new GenerateGroceryListAction($agent);

    expect(GroceryList::query()->count())->toBe(1);

    $newGroceryList = $action->createPlaceholder($mealPlan);

    expect(GroceryList::query()->count())->toBe(1)
        ->and($newGroceryList->id)->not->toBe($existingGroceryList->id);
});

it('preserves metadata when creating placeholder', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'duration_days' => 14,
    ]);

    $agent = resolve(GroceryListGeneratorAgent::class);
    $action = new GenerateGroceryListAction($agent);

    $groceryList = $action->createPlaceholder($mealPlan);

    expect($groceryList->metadata)
        ->toHaveKey('meal_plan_duration_days', 14)
        ->toHaveKey('started_at');
});
