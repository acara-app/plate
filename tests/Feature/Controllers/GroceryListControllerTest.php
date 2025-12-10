<?php

declare(strict_types=1);

use App\Enums\GroceryListStatus;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\User;

it('creates placeholder when grocery list does not exist', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    expect($mealPlan->groceryList)->toBeNull();

    $this->actingAs($user)->get(route('meal-plans.grocery-list.show', $mealPlan));

    $mealPlan->refresh();
    expect($mealPlan->groceryList)->not->toBeNull()
        ->and($mealPlan->groceryList->status)->toBe(GroceryListStatus::Generating);
});

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

it('retries generation when grocery list status is generating with no items', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()
        ->for($mealPlan)
        ->for($user)
        ->create(['status' => GroceryListStatus::Generating]);

    // No items exist, so it should retry generation
    expect($groceryList->items()->count())->toBe(0);

    $this->actingAs($user)->get(route('meal-plans.grocery-list.show', $mealPlan));

    // The endpoint should handle this case
    expect($groceryList->fresh()->status)->toBe(GroceryListStatus::Generating);
});

it('retries generation when grocery list status is failed', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $oldGroceryList = GroceryList::factory()
        ->for($mealPlan)
        ->for($user)
        ->create(['status' => GroceryListStatus::Failed]);

    $this->actingAs($user)->get(route('meal-plans.grocery-list.show', $mealPlan));

    // A new grocery list should be created
    $mealPlan->refresh();
    $newGroceryList = $mealPlan->groceryList;

    expect($newGroceryList)->not->toBeNull()
        ->and($newGroceryList->id)->not->toBe($oldGroceryList->id)
        ->and($newGroceryList->status)->toBe(GroceryListStatus::Generating);
});

it('regenerates grocery list via store endpoint', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $oldGroceryList = GroceryList::factory()
        ->for($mealPlan)
        ->for($user)
        ->create(['status' => GroceryListStatus::Active]);

    GroceryItem::factory()->for($oldGroceryList)->count(3)->create();

    expect($mealPlan->groceryList->items)->toHaveCount(3);

    $this->actingAs($user)->post(route('meal-plans.grocery-list.store', $mealPlan));

    $mealPlan->refresh();
    $newGroceryList = $mealPlan->groceryList;

    expect($newGroceryList)->not->toBeNull()
        ->and($newGroceryList->id)->not->toBe($oldGroceryList->id)
        ->and($newGroceryList->status)->toBe(GroceryListStatus::Generating);
});

it('denies regenerating grocery list for other users', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($owner)->create();

    $response = $this->actingAs($otherUser)->post(route('meal-plans.grocery-list.store', $mealPlan));

    $response->assertForbidden();
});

it('returns formatted grocery list when active with items', function (): void {
    $this->withoutVite();

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()
        ->for($mealPlan)
        ->for($user)
        ->create(['status' => GroceryListStatus::Active]);

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

    $response = $this->actingAs($user)->get(route('meal-plans.grocery-list.show', $mealPlan));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('grocery-list/show')
            ->has('groceryList', fn ($list) => $list
                ->where('id', $groceryList->id)
                ->where('name', $groceryList->name)
                ->where('status', 'active')
                ->where('total_items', 2)
                ->where('checked_items', 1)
                ->has('items_by_category', fn ($categories) => $categories
                    ->has('Produce')
                    ->has('Dairy')
                )
                ->etc()
            )
        );
});
