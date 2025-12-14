<?php

declare(strict_types=1);

use App\DataObjects\GroceryItemData;

it('can be created from array using from method', function (): void {
    $data = [
        'name' => 'Chicken Breast',
        'quantity' => '2 lbs',
        'category' => 'Meat & Seafood',
    ];

    $itemData = GroceryItemData::from($data);

    expect($itemData)
        ->name->toBe('Chicken Breast')
        ->quantity->toBe('2 lbs')
        ->category->toBe('Meat & Seafood');
});

it('can be created directly with constructor', function (): void {
    $itemData = new GroceryItemData(
        name: 'Olive Oil',
        quantity: '1 bottle',
        category: 'Condiments & Sauces',
    );

    expect($itemData)
        ->name->toBe('Olive Oil')
        ->quantity->toBe('1 bottle')
        ->category->toBe('Condiments & Sauces');
});

it('can be converted to array', function (): void {
    $itemData = new GroceryItemData(
        name: 'Eggs',
        quantity: '12',
        category: 'Dairy',
    );

    $array = $itemData->toArray();

    expect($array)->toBe([
        'name' => 'Eggs',
        'quantity' => '12',
        'category' => 'Dairy',
        'days' => null,
    ]);
});

it('can be created with days array', function (): void {
    $itemData = new GroceryItemData(
        name: 'Eggs',
        quantity: '12',
        category: 'Dairy',
        days: [1, 3, 5],
    );

    expect($itemData)
        ->name->toBe('Eggs')
        ->quantity->toBe('12')
        ->category->toBe('Dairy')
        ->days->toBe([1, 3, 5]);
});

it('can be converted to array with days', function (): void {
    $itemData = new GroceryItemData(
        name: 'Eggs',
        quantity: '12',
        category: 'Dairy',
        days: [1, 2, 3],
    );

    $array = $itemData->toArray();

    expect($array)->toBe([
        'name' => 'Eggs',
        'quantity' => '12',
        'category' => 'Dairy',
        'days' => [1, 2, 3],
    ]);
});
