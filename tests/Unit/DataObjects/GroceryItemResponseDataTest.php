<?php

declare(strict_types=1);

use App\DataObjects\GroceryItemResponseData;

it('can be created from array using from method', function (): void {
    $data = [
        'id' => 1,
        'name' => 'Chicken Breast',
        'quantity' => '2 lbs',
        'category' => 'Meat & Seafood',
        'is_checked' => false,
    ];

    $responseData = GroceryItemResponseData::from($data);

    expect($responseData)
        ->id->toBe(1)
        ->name->toBe('Chicken Breast')
        ->quantity->toBe('2 lbs')
        ->category->toBe('Meat & Seafood')
        ->is_checked->toBeFalse();
});

it('can be created directly with constructor', function (): void {
    $responseData = new GroceryItemResponseData(
        id: 42,
        name: 'Olive Oil',
        quantity: '1 bottle',
        category: 'Condiments & Sauces',
        is_checked: true,
    );

    expect($responseData)
        ->id->toBe(42)
        ->name->toBe('Olive Oil')
        ->quantity->toBe('1 bottle')
        ->category->toBe('Condiments & Sauces')
        ->is_checked->toBeTrue();
});

it('can be converted to array', function (): void {
    $responseData = new GroceryItemResponseData(
        id: 1,
        name: 'Apples',
        quantity: '3 lbs',
        category: 'Produce',
        is_checked: false,
    );

    $array = $responseData->toArray();

    expect($array)->toBeArray()
        ->toHaveKeys(['id', 'name', 'quantity', 'category', 'is_checked'])
        ->and($array['id'])->toBe(1)
        ->and($array['name'])->toBe('Apples')
        ->and($array['quantity'])->toBe('3 lbs')
        ->and($array['category'])->toBe('Produce')
        ->and($array['is_checked'])->toBeFalse();
});
