<?php

declare(strict_types=1);

use App\Ai\Agents\GroceryListGeneratorAgent;
use App\DataObjects\GroceryListData;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;

it('returns empty grocery list when meal plan has no meals', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    $agent = resolve(GroceryListGeneratorAgent::class);
    $result = $agent->generate($mealPlan);

    expect($result)
        ->toBeInstanceOf(GroceryListData::class)
        ->and($result->items)->toHaveCount(0);
});

it('returns empty grocery list when meals have no ingredients', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    Meal::factory()
        ->for($mealPlan)
        ->create([
            'ingredients' => null,
        ]);

    Meal::factory()
        ->for($mealPlan)
        ->create([
            'ingredients' => [],
        ]);

    $agent = resolve(GroceryListGeneratorAgent::class);
    $result = $agent->generate($mealPlan);

    expect($result)
        ->toBeInstanceOf(GroceryListData::class)
        ->and($result->items)->toHaveCount(0);
});
