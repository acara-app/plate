<?php

declare(strict_types=1);

use App\DataObjects\GroceryItemResponseData;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\User;

it('has correct casts', function (): void {
    $groceryList = GroceryList::factory()->create();

    expect($groceryList->casts())->toBeArray()
        ->toHaveKeys(['id', 'user_id', 'meal_plan_id', 'name', 'status', 'metadata']);
});

it('belongs to a user', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    expect($groceryList->user)->toBeInstanceOf(User::class)
        ->and($groceryList->user->id)->toBe($user->id);
});

it('belongs to a meal plan', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    expect($groceryList->mealPlan)->toBeInstanceOf(MealPlan::class)
        ->and($groceryList->mealPlan->id)->toBe($mealPlan->id);
});

it('has many grocery items', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    GroceryItem::factory()->for($groceryList)->count(3)->create();

    expect($groceryList->items)->toHaveCount(3)
        ->and($groceryList->items->first())->toBeInstanceOf(GroceryItem::class);
});

it('groups items by category in correct order', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create(['category' => 'Pantry', 'name' => 'Rice']);
    GroceryItem::factory()->for($groceryList)->create(['category' => 'Produce', 'name' => 'Apples']);
    GroceryItem::factory()->for($groceryList)->create(['category' => 'Dairy', 'name' => 'Milk']);

    $itemsByCategory = $groceryList->itemsByCategory();

    expect($itemsByCategory->keys()->first())->toBe('Produce')
        ->and($itemsByCategory->keys()->last())->toBe('Pantry');
});

it('places unknown categories at the end', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create(['category' => 'Produce', 'name' => 'Apples']);
    GroceryItem::factory()->for($groceryList)->create(['category' => 'Unknown Category', 'name' => 'Special Item']);

    $itemsByCategory = $groceryList->itemsByCategory();

    expect($itemsByCategory->keys()->first())->toBe('Produce')
        ->and($itemsByCategory->keys()->last())->toBe('Unknown Category');
});

it('returns formatted items by category with response data', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Apples',
        'quantity' => '2 lbs',
        'category' => 'Produce',
        'is_checked' => false,
    ]);
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Milk',
        'quantity' => '1 gallon',
        'category' => 'Dairy',
        'is_checked' => true,
    ]);

    $groceryList->load('items');
    $formatted = $groceryList->formattedItemsByCategory();

    expect($formatted)->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->and($formatted->keys()->first())->toBe('Produce')
        ->and($formatted->keys()->last())->toBe('Dairy')
        ->and($formatted['Produce'])->toBeArray()
        ->and($formatted['Produce'][0])->toBeInstanceOf(GroceryItemResponseData::class)
        ->and($formatted['Produce'][0]->name)->toBe('Apples')
        ->and($formatted['Dairy'][0]->name)->toBe('Milk')
        ->and($formatted['Dairy'][0]->is_checked)->toBeTrue();
});
