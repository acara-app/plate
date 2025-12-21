<?php

declare(strict_types=1);

use App\DataObjects\FoodItemData;

it('can be created directly with constructor', function (): void {
    $itemData = new FoodItemData(
        name: 'Grilled Chicken',
        calories: 165.0,
        protein: 31.0,
        carbs: 0.0,
        fat: 3.6,
        portion: '100g',
    );

    expect($itemData)
        ->name->toBe('Grilled Chicken')
        ->calories->toBe(165.0)
        ->protein->toBe(31.0)
        ->carbs->toBe(0.0)
        ->fat->toBe(3.6)
        ->portion->toBe('100g');
});

it('can be created from array using from method', function (): void {
    $data = [
        'name' => 'Brown Rice',
        'calories' => 216.0,
        'protein' => 5.0,
        'carbs' => 45.0,
        'fat' => 1.8,
        'portion' => '1 cup cooked',
    ];

    $itemData = FoodItemData::from($data);

    expect($itemData)
        ->name->toBe('Brown Rice')
        ->calories->toBe(216.0)
        ->protein->toBe(5.0)
        ->carbs->toBe(45.0)
        ->fat->toBe(1.8)
        ->portion->toBe('1 cup cooked');
});

it('can be converted to array', function (): void {
    $itemData = new FoodItemData(
        name: 'Apple',
        calories: 95.0,
        protein: 0.5,
        carbs: 25.0,
        fat: 0.3,
        portion: '1 medium',
    );

    $array = $itemData->toArray();

    expect($array)
        ->toBeArray()
        ->toHaveKeys(['name', 'calories', 'protein', 'carbs', 'fat', 'portion'])
        ->name->toBe('Apple')
        ->calories->toBe(95.0)
        ->portion->toBe('1 medium');
});
