<?php

declare(strict_types=1);

use App\DataObjects\FoodAnalysisData;
use App\DataObjects\FoodItemData;
use Spatie\LaravelData\DataCollection;

it('can be created directly with constructor', function (): void {
    $items = FoodItemData::collect([
        new FoodItemData(
            name: 'Grilled Chicken',
            calories: 165.0,
            protein: 31.0,
            carbs: 0.0,
            fat: 3.6,
            portion: '100g',
        ),
    ], DataCollection::class);

    $analysisData = new FoodAnalysisData(
        items: $items,
        totalCalories: 165.0,
        totalProtein: 31.0,
        totalCarbs: 0.0,
        totalFat: 3.6,
        confidence: 85,
    );

    expect($analysisData)
        ->totalCalories->toBe(165.0)
        ->totalProtein->toBe(31.0)
        ->totalCarbs->toBe(0.0)
        ->totalFat->toBe(3.6)
        ->confidence->toBe(85);

    expect($analysisData->items)->toHaveCount(1);
    expect($analysisData->items->first()->name)->toBe('Grilled Chicken');
});

it('can be created from array using fromArray method', function (): void {
    $data = [
        'items' => [
            [
                'name' => 'Rice',
                'calories' => 130.0,
                'protein' => 2.7,
                'carbs' => 28.0,
                'fat' => 0.3,
                'portion' => '100g',
            ],
            [
                'name' => 'Chicken',
                'calories' => 165.0,
                'protein' => 31.0,
                'carbs' => 0.0,
                'fat' => 3.6,
                'portion' => '100g',
            ],
        ],
        'total_calories' => 295.0,
        'total_protein' => 33.7,
        'total_carbs' => 28.0,
        'total_fat' => 3.9,
        'confidence' => 90,
    ];

    $analysisData = FoodAnalysisData::fromArray($data);

    expect($analysisData)
        ->totalCalories->toBe(295.0)
        ->totalProtein->toBe(33.7)
        ->totalCarbs->toBe(28.0)
        ->totalFat->toBe(3.9)
        ->confidence->toBe(90);

    expect($analysisData->items)->toHaveCount(2);
    expect($analysisData->items->first()->name)->toBe('Rice');
    expect($analysisData->items->last()->name)->toBe('Chicken');
});

it('can be converted to array', function (): void {
    $items = FoodItemData::collect([
        new FoodItemData(
            name: 'Apple',
            calories: 95.0,
            protein: 0.5,
            carbs: 25.0,
            fat: 0.3,
            portion: '1 medium',
        ),
    ], DataCollection::class);

    $analysisData = new FoodAnalysisData(
        items: $items,
        totalCalories: 95.0,
        totalProtein: 0.5,
        totalCarbs: 25.0,
        totalFat: 0.3,
        confidence: 95,
    );

    $array = $analysisData->toArray();

    expect($array)
        ->toBeArray()
        ->toHaveKey('items')
        ->toHaveKey('confidence');

    expect($array['confidence'])->toBe(95);
    expect($array['items'])->toHaveCount(1);
});

it('handles empty items array', function (): void {
    $data = [
        'items' => [],
        'total_calories' => 0.0,
        'total_protein' => 0.0,
        'total_carbs' => 0.0,
        'total_fat' => 0.0,
        'confidence' => 0,
    ];

    $analysisData = FoodAnalysisData::fromArray($data);

    expect($analysisData)
        ->totalCalories->toBe(0.0)
        ->confidence->toBe(0);

    expect($analysisData->items)->toHaveCount(0);
});
