<?php

declare(strict_types=1);

use App\DataObjects\GroceryItemResponseData;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\User;

it('has correct casts', function (): void {
    $item = GroceryItem::factory()->create();

    expect($item->casts())->toBeArray()
        ->toHaveKeys(['id', 'grocery_list_id', 'name', 'quantity', 'category', 'is_checked', 'sort_order']);
});

it('belongs to a grocery list', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();
    $item = GroceryItem::factory()->for($groceryList)->create();

    expect($item->groceryList)->toBeInstanceOf(GroceryList::class)
        ->and($item->groceryList->id)->toBe($groceryList->id);
});

it('converts to response data', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->for($mealPlan)->for($user)->create();
    $item = GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Apples',
        'quantity' => '2 lbs',
        'category' => 'Produce',
        'is_checked' => false,
    ]);

    $responseData = $item->toResponseData();

    expect($responseData)->toBeInstanceOf(GroceryItemResponseData::class)
        ->and($responseData->id)->toBe($item->id)
        ->and($responseData->name)->toBe('Apples')
        ->and($responseData->quantity)->toBe('2 lbs')
        ->and($responseData->category)->toBe('Produce')
        ->and($responseData->is_checked)->toBeFalse();
});
