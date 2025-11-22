<?php

declare(strict_types=1);

use App\DataObjects\NutritionData;

it('creates nutrition data with all fields', function (): void {
    $data = new NutritionData(
        calories: 165.0,
        protein: 31.0,
        carbs: 0.0,
        fat: 3.6,
        fiber: 0.0,
        sugar: 0.0,
        sodium: 74.0,
    );

    expect($data->calories)->toBe(165.0)
        ->and($data->protein)->toBe(31.0)
        ->and($data->carbs)->toBe(0.0)
        ->and($data->fat)->toBe(3.6)
        ->and($data->fiber)->toBe(0.0)
        ->and($data->sugar)->toBe(0.0)
        ->and($data->sodium)->toBe(74.0);
});

it('creates nutrition data with null values', function (): void {
    $data = new NutritionData(
        calories: 100.0,
        protein: 10.0,
        carbs: null,
        fat: null,
        fiber: null,
        sugar: null,
        sodium: null,
    );

    expect($data->calories)->toBe(100.0)
        ->and($data->protein)->toBe(10.0)
        ->and($data->carbs)->toBeNull()
        ->and($data->fat)->toBeNull()
        ->and($data->fiber)->toBeNull()
        ->and($data->sugar)->toBeNull()
        ->and($data->sodium)->toBeNull();
});

it('creates nutrition data with mixed null and non-null values', function (): void {
    $data = new NutritionData(
        calories: 200.0,
        protein: 15.0,
        carbs: 30.0,
        fat: null,
        fiber: 5.0,
        sugar: null,
        sodium: 150.0,
    );

    expect($data->calories)->toBe(200.0)
        ->and($data->protein)->toBe(15.0)
        ->and($data->carbs)->toBe(30.0)
        ->and($data->fat)->toBeNull()
        ->and($data->fiber)->toBe(5.0)
        ->and($data->sugar)->toBeNull()
        ->and($data->sodium)->toBe(150.0);
});

it('handles zero values correctly', function (): void {
    $data = new NutritionData(
        calories: 0.0,
        protein: 0.0,
        carbs: 0.0,
        fat: 0.0,
        fiber: 0.0,
        sugar: 0.0,
        sodium: 0.0,
    );

    expect($data->calories)->toBe(0.0)
        ->and($data->protein)->toBe(0.0)
        ->and($data->carbs)->toBe(0.0)
        ->and($data->fat)->toBe(0.0)
        ->and($data->fiber)->toBe(0.0)
        ->and($data->sugar)->toBe(0.0)
        ->and($data->sodium)->toBe(0.0);
});

it('handles floating point precision', function (): void {
    $data = new NutritionData(
        calories: 165.123,
        protein: 31.456,
        carbs: 0.789,
        fat: 3.654,
        fiber: 2.321,
        sugar: 1.987,
        sodium: 74.555,
    );

    expect($data->calories)->toBe(165.123)
        ->and($data->protein)->toBe(31.456)
        ->and($data->carbs)->toBe(0.789)
        ->and($data->fat)->toBe(3.654)
        ->and($data->fiber)->toBe(2.321)
        ->and($data->sugar)->toBe(1.987)
        ->and($data->sodium)->toBe(74.555);
});

it('can be modified after creation', function (): void {
    $data = new NutritionData(
        calories: 100.0,
        protein: 10.0,
        carbs: 20.0,
        fat: 5.0,
        fiber: 3.0,
        sugar: 2.0,
        sodium: 50.0,
    );

    // Laravel Data objects are mutable by default
    $data->calories = 200.0;

    expect($data->calories)->toBe(200.0);
});

it('creates nutrition data from array with all fields', function (): void {
    $array = [
        'calories' => 165.0,
        'protein' => 31.0,
        'carbs' => 0.0,
        'fat' => 3.6,
        'fiber' => 0.0,
        'sugar' => 0.0,
        'sodium' => 74.0,
    ];

    $data = NutritionData::from($array);

    expect($data->calories)->toBe(165.0)
        ->and($data->protein)->toBe(31.0)
        ->and($data->carbs)->toBe(0.0)
        ->and($data->fat)->toBe(3.6)
        ->and($data->fiber)->toBe(0.0)
        ->and($data->sugar)->toBe(0.0)
        ->and($data->sodium)->toBe(74.0);
});

it('creates nutrition data from array with null values', function (): void {
    $array = [
        'calories' => 100.0,
        'protein' => 10.0,
        'carbs' => null,
        'fat' => null,
        'fiber' => null,
        'sugar' => null,
        'sodium' => null,
    ];

    $data = NutritionData::from($array);

    expect($data->calories)->toBe(100.0)
        ->and($data->protein)->toBe(10.0)
        ->and($data->carbs)->toBeNull()
        ->and($data->fat)->toBeNull()
        ->and($data->fiber)->toBeNull()
        ->and($data->sugar)->toBeNull()
        ->and($data->sodium)->toBeNull();
});

it('creates nutrition data from array with mixed values', function (): void {
    $array = [
        'calories' => 200.0,
        'protein' => 15.0,
        'carbs' => 30.0,
        'fat' => null,
        'fiber' => 5.0,
        'sugar' => null,
        'sodium' => 150.0,
    ];

    $data = NutritionData::from($array);

    expect($data->calories)->toBe(200.0)
        ->and($data->protein)->toBe(15.0)
        ->and($data->carbs)->toBe(30.0)
        ->and($data->fat)->toBeNull()
        ->and($data->fiber)->toBe(5.0)
        ->and($data->sugar)->toBeNull()
        ->and($data->sodium)->toBe(150.0);
});

it('converts nutrition data to array', function (): void {
    $data = new NutritionData(
        calories: 165.0,
        protein: 31.0,
        carbs: 0.0,
        fat: 3.6,
        fiber: 0.0,
        sugar: 0.0,
        sodium: 74.0,
    );

    $array = $data->toArray();

    expect($array)->toBe([
        'calories' => 165.0,
        'protein' => 31.0,
        'carbs' => 0.0,
        'fat' => 3.6,
        'fiber' => 0.0,
        'sugar' => 0.0,
        'sodium' => 74.0,
    ]);
});

it('converts nutrition data with nulls to array', function (): void {
    $data = new NutritionData(
        calories: 100.0,
        protein: 10.0,
        carbs: null,
        fat: null,
        fiber: null,
        sugar: null,
        sodium: null,
    );

    $array = $data->toArray();

    expect($array)->toBe([
        'calories' => 100.0,
        'protein' => 10.0,
        'carbs' => null,
        'fat' => null,
        'fiber' => null,
        'sugar' => null,
        'sodium' => null,
    ]);
});

it('round-trips through array conversion', function (): void {
    $original = new NutritionData(
        calories: 165.123,
        protein: 31.456,
        carbs: 0.789,
        fat: 3.654,
        fiber: 2.321,
        sugar: 1.987,
        sodium: 74.555,
    );

    $array = $original->toArray();
    $recreated = NutritionData::from($array);

    expect($recreated->calories)->toBe($original->calories)
        ->and($recreated->protein)->toBe($original->protein)
        ->and($recreated->carbs)->toBe($original->carbs)
        ->and($recreated->fat)->toBe($original->fat)
        ->and($recreated->fiber)->toBe($original->fiber)
        ->and($recreated->sugar)->toBe($original->sugar)
        ->and($recreated->sodium)->toBe($original->sodium);
});
