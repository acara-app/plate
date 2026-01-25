<?php

declare(strict_types=1);

use App\DataObjects\MediterraneanDietFoodData;

it('can be created directly with constructor', function (): void {
    $foodData = new MediterraneanDietFoodData(
        name: 'Artichoke, boiled, 1 medium',
        calories: 150,
        protein: 10.0,
        fat: 5.0,
        saturatedFat: 0.0,
        fiber: 16.0,
    );

    expect($foodData)
        ->name->toBe('Artichoke, boiled, 1 medium')
        ->calories->toBe(150)
        ->protein->toBe(10.0)
        ->fat->toBe(5.0)
        ->saturatedFat->toBe(0.0)
        ->fiber->toBe(16.0);
});

it('can be created from array using from method', function (): void {
    $data = [
        'name' => 'Asparagus, boiled, 6 spears',
        'calories' => 22,
        'protein' => 2.3,
        'fat' => 0.0,
        'saturatedFat' => 0.0,
        'fiber' => 1.5,
    ];

    $foodData = MediterraneanDietFoodData::from($data);

    expect($foodData)
        ->name->toBe('Asparagus, boiled, 6 spears')
        ->calories->toBe(22)
        ->protein->toBe(2.3)
        ->fat->toBe(0.0)
        ->saturatedFat->toBe(0.0)
        ->fiber->toBe(1.5);
});

it('can be converted to array', function (): void {
    $foodData = new MediterraneanDietFoodData(
        name: 'Chickpeas (garbanzo beans), boiled, 1 cup',
        calories: 270,
        protein: 14.5,
        fat: 0.0,
        saturatedFat: 0.0,
        fiber: 12.5,
    );

    $array = $foodData->toArray();

    expect($array)
        ->toBeArray()
        ->toHaveKeys(['name', 'calories', 'protein', 'fat', 'saturated_fat', 'fiber'])
        ->name->toBe('Chickpeas (garbanzo beans), boiled, 1 cup')
        ->calories->toBe(270)
        ->protein->toBe(14.5)
        ->fat->toBe(0.0)
        ->saturated_fat->toBe(0.0)
        ->fiber->toBe(12.5);
});

it('handles zero values correctly', function (): void {
    $foodData = new MediterraneanDietFoodData(
        name: 'Broccoli, boiled, 1/2 cup',
        calories: 22,
        protein: 2.5,
        fat: 0.0,
        saturatedFat: 0.0,
        fiber: 2.5,
    );

    expect($foodData)
        ->fat->toBe(0.0)
        ->saturatedFat->toBe(0.0);
});

it('handles decimal values correctly', function (): void {
    $foodData = new MediterraneanDietFoodData(
        name: 'Lentils, boiled, 1/2 cup',
        calories: 115,
        protein: 9.0,
        fat: 0.0,
        saturatedFat: 0.0,
        fiber: 7.5,
    );

    expect($foodData)
        ->protein->toBe(9.0)
        ->fiber->toBe(7.5);
});
