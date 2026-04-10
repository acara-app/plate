<?php

declare(strict_types=1);

use App\Models\GroceryList;
use App\Models\User;
use App\Policies\GroceryListPolicy;

covers(GroceryListPolicy::class);

it('allows the owner to view the grocery list', function (): void {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create();

    expect((new GroceryListPolicy)->view($user, $groceryList))->toBeTrue();
});

it('denies a non-owner from viewing the grocery list', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $groceryList = GroceryList::factory()->for($owner)->create();

    expect((new GroceryListPolicy)->view($other, $groceryList))->toBeFalse();
});

it('allows the owner to update the grocery list', function (): void {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create();

    expect((new GroceryListPolicy)->update($user, $groceryList))->toBeTrue();
});

it('denies a non-owner from updating the grocery list', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $groceryList = GroceryList::factory()->for($owner)->create();

    expect((new GroceryListPolicy)->update($other, $groceryList))->toBeFalse();
});
