<?php

declare(strict_types=1);

use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\User;

it('shows print view for grocery list', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    $response = $this->actingAs($user)->get(route('meal-plans.grocery-list.print', $mealPlan));

    $response->assertSuccessful();
    $response->assertViewIs('grocery-list.print');
    $response->assertViewHas('mealPlan', $mealPlan);
    $response->assertViewHas('groceryList');
});

it('denies access to other users grocery list', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($owner)->create();
    GroceryList::factory()->for($mealPlan)->for($owner)->create();

    $response = $this->actingAs($otherUser)->get(route('meal-plans.grocery-list.print', $mealPlan));

    $response->assertForbidden();
});

it('returns 404 when grocery list does not exist', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    $response = $this->actingAs($user)->get(route('meal-plans.grocery-list.print', $mealPlan));

    $response->assertNotFound();
});
