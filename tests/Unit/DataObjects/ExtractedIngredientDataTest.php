<?php

declare(strict_types=1);

use App\DataObjects\ExtractedIngredientData;

it('can be created from array using from method', function (): void {
    $data = [
        'name' => 'chicken breast',
        'quantity' => '2 lbs',
        'day' => 1,
        'meal' => 'Dinner',
    ];

    $ingredientData = ExtractedIngredientData::from($data);

    expect($ingredientData)
        ->name->toBe('chicken breast')
        ->quantity->toBe('2 lbs')
        ->day->toBe(1)
        ->meal->toBe('Dinner');
});

it('can be created directly with constructor', function (): void {
    $ingredientData = new ExtractedIngredientData(
        name: 'olive oil',
        quantity: '2 tbsp',
        day: 3,
        meal: 'Lunch',
    );

    expect($ingredientData)
        ->name->toBe('olive oil')
        ->quantity->toBe('2 tbsp')
        ->day->toBe(3)
        ->meal->toBe('Lunch');
});

it('can be converted to array', function (): void {
    $ingredientData = new ExtractedIngredientData(
        name: 'eggs',
        quantity: '6',
        day: 2,
        meal: 'Breakfast',
    );

    $array = $ingredientData->toArray();

    expect($array)->toBe([
        'name' => 'eggs',
        'quantity' => '6',
        'day' => 2,
        'meal' => 'Breakfast',
    ]);
});
