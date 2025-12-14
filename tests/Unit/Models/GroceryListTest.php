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

it('groups items by day', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Eggs',
        'category' => 'Dairy',
        'days' => [1, 2],
    ]);
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Chicken',
        'category' => 'Meat & Seafood',
        'days' => [1],
    ]);
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Rice',
        'category' => 'Pantry',
        'days' => [2, 3],
    ]);

    $groceryList->load('items');
    $itemsByDay = $groceryList->itemsByDay();

    expect($itemsByDay)->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->and($itemsByDay->keys()->all())->toBe([1, 2, 3])
        ->and($itemsByDay[1])->toHaveCount(2)
        ->and($itemsByDay[2])->toHaveCount(2)
        ->and($itemsByDay[3])->toHaveCount(1);
});

it('returns formatted items by day with response data', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Apples',
        'quantity' => '2 lbs',
        'category' => 'Produce',
        'days' => [1],
        'is_checked' => false,
    ]);
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Milk',
        'quantity' => '1 gallon',
        'category' => 'Dairy',
        'days' => [1, 2],
        'is_checked' => true,
    ]);

    $groceryList->load('items');
    $formatted = $groceryList->formattedItemsByDay();

    expect($formatted)->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->and($formatted->keys()->all())->toBe([1, 2])
        ->and($formatted[1])->toBeArray()
        ->and($formatted[1])->toHaveCount(2)
        ->and($formatted[1][0])->toBeInstanceOf(GroceryItemResponseData::class)
        ->and($formatted[2])->toHaveCount(1);
});

it('derives days from meal plan for items without day data', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    // Create meals with ingredients
    App\Models\Meal::factory()->for($mealPlan)->create([
        'day_number' => 1,
        'name' => 'Breakfast',
        'ingredients' => [
            ['name' => 'eggs', 'quantity' => '2'],
            ['name' => 'bacon', 'quantity' => '3 strips'],
        ],
    ]);
    App\Models\Meal::factory()->for($mealPlan)->create([
        'day_number' => 2,
        'name' => 'Lunch',
        'ingredients' => [
            ['name' => 'chicken breast', 'quantity' => '1 lb'],
        ],
    ]);
    App\Models\Meal::factory()->for($mealPlan)->create([
        'day_number' => 3,
        'name' => 'Dinner',
        'ingredients' => [
            ['name' => 'eggs', 'quantity' => '3'],
        ],
    ]);

    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    // Create items without days
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Eggs',
        'category' => 'Dairy',
        'days' => null,
    ]);
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Chicken Breast',
        'category' => 'Meat & Seafood',
        'days' => null,
    ]);
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Bacon',
        'category' => 'Meat & Seafood',
        'days' => null,
    ]);

    $groceryList->load('items', 'mealPlan.meals');
    $itemsByDay = $groceryList->itemsByDay();

    // Verify that days were derived
    expect($itemsByDay)->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->and($itemsByDay->keys()->all())->toContain(1, 2, 3);
});

it('uses fuzzy matching when exact match is not found', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    // Create meals with ingredients
    App\Models\Meal::factory()->for($mealPlan)->create([
        'day_number' => 1,
        'name' => 'Breakfast',
        'ingredients' => [
            ['name' => 'boneless chicken breast', 'quantity' => '1 lb'],
        ],
    ]);

    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    // Create item with similar but not exact name
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Chicken',
        'category' => 'Meat & Seafood',
        'days' => null,
    ]);

    $groceryList->load('items', 'mealPlan.meals');
    $itemsByDay = $groceryList->itemsByDay();

    // Verify that fuzzy matching worked
    expect($itemsByDay)->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->and($itemsByDay->has(1))->toBeTrue();
});

it('handles items with empty days array when deriving', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    App\Models\Meal::factory()->for($mealPlan)->create([
        'day_number' => 1,
        'name' => 'Breakfast',
        'ingredients' => [
            ['name' => 'eggs', 'quantity' => '2'],
        ],
    ]);

    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Eggs',
        'category' => 'Dairy',
        'days' => [],
    ]);

    $groceryList->load('items', 'mealPlan.meals');
    $itemsByDay = $groceryList->itemsByDay();

    expect($itemsByDay)->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->and($itemsByDay->has(1))->toBeTrue();
});

it('skips derivation when all items have days', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Eggs',
        'category' => 'Dairy',
        'days' => [1, 2],
    ]);

    $groceryList->load('items');
    $itemsByDay = $groceryList->itemsByDay();

    // Should not trigger derivation since item already has days
    expect($itemsByDay)->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->and($itemsByDay->keys()->all())->toBe([1, 2]);
});

it('handles meals with null ingredients when deriving days', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    App\Models\Meal::factory()->for($mealPlan)->create([
        'day_number' => 1,
        'name' => 'Breakfast',
        'ingredients' => null,
    ]);

    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Eggs',
        'category' => 'Dairy',
        'days' => null,
    ]);

    $groceryList->load('items', 'mealPlan.meals');
    $itemsByDay = $groceryList->itemsByDay();

    // Should handle null ingredients gracefully
    expect($itemsByDay)->toBeInstanceOf(Illuminate\Support\Collection::class);
});

it('handles meals with empty ingredients array when deriving days', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    App\Models\Meal::factory()->for($mealPlan)->create([
        'day_number' => 1,
        'name' => 'Breakfast',
        'ingredients' => [],
    ]);

    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Eggs',
        'category' => 'Dairy',
        'days' => null,
    ]);

    $groceryList->load('items', 'mealPlan.meals');
    $itemsByDay = $groceryList->itemsByDay();

    // Should handle empty ingredients array gracefully
    expect($itemsByDay)->toBeInstanceOf(Illuminate\Support\Collection::class);
});

it('handles ingredients with empty name when deriving days', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    App\Models\Meal::factory()->for($mealPlan)->create([
        'day_number' => 1,
        'name' => 'Breakfast',
        'ingredients' => [
            ['name' => '', 'quantity' => '2'],
        ],
    ]);

    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Unknown Item',
        'category' => 'Other',
        'days' => null,
    ]);

    $groceryList->load('items', 'mealPlan.meals');
    $itemsByDay = $groceryList->itemsByDay();

    // Should handle empty ingredient name gracefully
    expect($itemsByDay)->toBeInstanceOf(Illuminate\Support\Collection::class);
});

it('normalizes ingredient names correctly when deriving days', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    App\Models\Meal::factory()->for($mealPlan)->create([
        'day_number' => 1,
        'name' => 'Breakfast',
        'ingredients' => [
            ['name' => '  EGGS (Large)  ', 'quantity' => '2'],
        ],
    ]);

    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Eggs',
        'category' => 'Dairy',
        'days' => null,
    ]);

    $groceryList->load('items', 'mealPlan.meals');
    $itemsByDay = $groceryList->itemsByDay();

    // Should match despite different formatting
    expect($itemsByDay)->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->and($itemsByDay->has(1))->toBeTrue();
});

it('handles mixed items where some have days and some need derivation', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    App\Models\Meal::factory()->for($mealPlan)->create([
        'day_number' => 1,
        'name' => 'Breakfast',
        'ingredients' => [
            ['name' => 'eggs', 'quantity' => '2'],
        ],
    ]);

    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();

    // Item with days already set
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Milk',
        'category' => 'Dairy',
        'days' => [2, 3],
    ]);

    // Item without days
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Eggs',
        'category' => 'Dairy',
        'days' => null,
    ]);

    $groceryList->load('items', 'mealPlan.meals');
    $itemsByDay = $groceryList->itemsByDay();

    // Should have days from both items
    expect($itemsByDay)->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->and($itemsByDay->has(1))->toBeTrue()
        ->and($itemsByDay->has(2))->toBeTrue()
        ->and($itemsByDay->has(3))->toBeTrue();
});
