<?php

declare(strict_types=1);

use App\Enums\GroceryListStatus;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\User;

it('denies access to other users meal plan', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($owner)->create();

    $response = $this->actingAs($otherUser)->get(route('meal-plans.grocery-list.show', $mealPlan));

    $response->assertForbidden();
});

it('toggles grocery item checked status', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();
    $item = GroceryItem::factory()->for($groceryList)->create(['is_checked' => false]);

    expect($item->is_checked)->toBeFalse();

    $this->actingAs($user)->patch(route('grocery-items.toggle', $item));

    $item->refresh();
    expect($item->is_checked)->toBeTrue();
});

it('updates grocery list to completed when all items are checked', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()
        ->for($mealPlan)
        ->for($user)
        ->create(['status' => GroceryListStatus::Active]);

    $item1 = GroceryItem::factory()->for($groceryList)->create(['is_checked' => true]);
    $item2 = GroceryItem::factory()->for($groceryList)->create(['is_checked' => false]);

    expect($groceryList->fresh()->status)->toBe(GroceryListStatus::Active);

    $this->actingAs($user)->patch(route('grocery-items.toggle', $item2));

    expect($groceryList->fresh()->status)->toBe(GroceryListStatus::Completed);
});

it('updates grocery list back to active when unchecking items', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()
        ->for($mealPlan)
        ->for($user)
        ->create(['status' => GroceryListStatus::Completed]);

    $item = GroceryItem::factory()->for($groceryList)->create(['is_checked' => true]);

    expect($groceryList->fresh()->status)->toBe(GroceryListStatus::Completed);

    $this->actingAs($user)->patch(route('grocery-items.toggle', $item));

    expect($groceryList->fresh()->status)->toBe(GroceryListStatus::Active);
});

it('denies toggling items from other users grocery list', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($owner)->create();
    $groceryList = GroceryList::factory()->for($mealPlan)->for($owner)->create();
    $item = GroceryItem::factory()->for($groceryList)->create();

    $response = $this->actingAs($otherUser)->patch(route('grocery-items.toggle', $item));

    $response->assertForbidden();
});
