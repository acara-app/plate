<?php

declare(strict_types=1);

use App\DataObjects\GeneratedMealData;

it('can be instantiated with all properties', function (): void {
    $data = new GeneratedMealData(
        name: 'Chicken Salad',
        mealType: 'lunch',
        calories: 500,
        proteinGrams: 40,
        cuisine: 'Mediterranean',
        description: 'A healthy chicken salad',
        carbsGrams: 10,
        fatGrams: 20,
        fiberGrams: 5,
        ingredients: ['chicken', 'lettuce'],
        instructions: ['chop', 'mix'],
        prepTimeMinutes: 10,
        cookTimeMinutes: 0,
        servings: 2,
        dietaryTags: ['keto', 'gf'],
        glycemicIndexEstimate: 'low',
        glucoseImpactNotes: 'Minimal impact'
    );

    expect($data->name)->toBe('Chicken Salad')
        ->and($data->mealType)->toBe('lunch')
        ->and($data->calories)->toBe(500.0)
        ->and($data->proteinGrams)->toBe(40.0)
        ->and($data->cuisine)->toBe('Mediterranean')
        ->and($data->description)->toBe('A healthy chicken salad')
        ->and($data->carbsGrams)->toBe(10.0)
        ->and($data->fatGrams)->toBe(20.0)
        ->and($data->fiberGrams)->toBe(5.0)
        ->and($data->ingredients)->toBe(['chicken', 'lettuce'])
        ->and($data->instructions)->toBe(['chop', 'mix'])
        ->and($data->prepTimeMinutes)->toBe(10)
        ->and($data->cookTimeMinutes)->toBe(0)
        ->and($data->servings)->toBe(2)
        ->and($data->dietaryTags)->toBe(['keto', 'gf'])
        ->and($data->glycemicIndexEstimate)->toBe('low')
        ->and($data->glucoseImpactNotes)->toBe('Minimal impact');
});

it('can be instantiated with required properties only', function (): void {
    $data = new GeneratedMealData(
        name: 'Simple Egg',
        mealType: 'breakfast',
        calories: 70,
        proteinGrams: 6,
        cuisine: null,
        description: null,
        carbsGrams: 0,
        fatGrams: 5
    );

    expect($data->name)->toBe('Simple Egg')
        ->and($data->mealType)->toBe('breakfast')
        ->and($data->calories)->toBe(70.0)
        ->and($data->proteinGrams)->toBe(6.0)
        ->and($data->cuisine)->toBeNull()
        ->and($data->description)->toBeNull()
        ->and($data->carbsGrams)->toBe(0.0)
        ->and($data->fatGrams)->toBe(5.0)
        ->and($data->fiberGrams)->toBeNull()
        ->and($data->ingredients)->toBeNull()
        ->and($data->instructions)->toBeNull()
        ->and($data->prepTimeMinutes)->toBeNull()
        ->and($data->cookTimeMinutes)->toBeNull()
        ->and($data->servings)->toBe(1) // Default value
        ->and($data->dietaryTags)->toBeNull()
        ->and($data->glycemicIndexEstimate)->toBeNull()
        ->and($data->glucoseImpactNotes)->toBeNull();
});

it('can be created from array', function (): void {
    $array = [
        'name' => 'Tofu Stir Fry',
        'mealType' => 'dinner',
        'calories' => 400,
        'proteinGrams' => 20,
        'cuisine' => 'Asian',
        'description' => 'Spicy tofu',
        'carbsGrams' => 30,
        'fatGrams' => 15,
        'servings' => 4,
    ];

    $data = GeneratedMealData::from($array);

    expect($data->name)->toBe('Tofu Stir Fry')
        ->and($data->mealType)->toBe('dinner')
        ->and($data->calories)->toBe(400.0)
        ->and($data->servings)->toBe(4);
});
