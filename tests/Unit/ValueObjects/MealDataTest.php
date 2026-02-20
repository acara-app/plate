<?php

declare(strict_types=1);

use Spatie\LaravelData\DataCollection;
use App\DataObjects\MealData;
use App\Enums\MealType;

it('creates meal data from array with all fields', function (): void {
    $data = [
        'day_number' => 1,
        'type' => 'breakfast',
        'name' => 'Oatmeal',
        'description' => 'Healthy breakfast',
        'preparation_instructions' => 'Cook oats',
        'ingredients' => [['name' => 'Oats', 'quantity' => '50g'], ['name' => 'Milk', 'quantity' => '200ml']],
        'portion_size' => '1 cup',
        'calories' => 300.5,
        'protein_grams' => 10.5,
        'carbs_grams' => 50.0,
        'fat_grams' => 5.5,
        'preparation_time_minutes' => 10,
        'sort_order' => 1,
        'metadata' => ['key' => 'value'],
    ];

    $mealData = MealData::from($data);

    expect($mealData->dayNumber)->toBe(1)
        ->and($mealData->type)->toBe(MealType::Breakfast)
        ->and($mealData->name)->toBe('Oatmeal')
        ->and($mealData->description)->toBe('Healthy breakfast')
        ->and($mealData->preparationInstructions)->toBe('Cook oats')
        ->and($mealData->ingredients)->toBeInstanceOf(DataCollection::class)
        ->and($mealData->ingredients->toArray())->toHaveCount(2)
        ->and($mealData->portionSize)->toBe('1 cup')
        ->and($mealData->calories)->toBe(300.5)
        ->and($mealData->proteinGrams)->toBe(10.5)
        ->and($mealData->carbsGrams)->toBe(50.0)
        ->and($mealData->fatGrams)->toBe(5.5)
        ->and($mealData->preparationTimeMinutes)->toBe(10)
        ->and($mealData->sortOrder)->toBe(1)
        ->and($mealData->metadata)->toBe(['key' => 'value']);
});

it('creates meal data from array with minimal fields', function (): void {
    $data = [
        'day_number' => 1,
        'type' => 'lunch',
        'name' => 'Salad',
        'calories' => 250.0,
        'sort_order' => 2,
    ];

    $mealData = MealData::from($data);

    expect($mealData->dayNumber)->toBe(1)
        ->and($mealData->type)->toBe(MealType::Lunch)
        ->and($mealData->name)->toBe('Salad')
        ->and($mealData->description)->toBeNull()
        ->and($mealData->preparationInstructions)->toBeNull()
        ->and($mealData->ingredients)->toBeNull()
        ->and($mealData->portionSize)->toBeNull()
        ->and($mealData->calories)->toBe(250.0)
        ->and($mealData->proteinGrams)->toBeNull()
        ->and($mealData->carbsGrams)->toBeNull()
        ->and($mealData->fatGrams)->toBeNull()
        ->and($mealData->preparationTimeMinutes)->toBeNull()
        ->and($mealData->sortOrder)->toBe(2)
        ->and($mealData->metadata)->toBeNull();
});

it('converts meal data to array', function (): void {
    $mealData = MealData::from([
        'day_number' => 1,
        'type' => 'dinner',
        'name' => 'Chicken',
        'description' => 'Grilled chicken',
        'preparation_instructions' => 'Grill it',
        'ingredients' => [['name' => 'Chicken', 'quantity' => '150g'], ['name' => 'Spices', 'quantity' => '5g']],
        'portion_size' => '200g',
        'calories' => 400.0,
        'protein_grams' => 30.0,
        'carbs_grams' => 10.0,
        'fat_grams' => 20.0,
        'preparation_time_minutes' => 30,
        'sort_order' => 3,
        'metadata' => ['test' => 'data'],
    ]);

    $array = $mealData->toArray();

    expect($array['day_number'])->toBe(1)
        ->and($array['type'])->toBe('dinner')
        ->and($array['name'])->toBe('Chicken')
        ->and($array['description'])->toBe('Grilled chicken')
        ->and($array['preparation_instructions'])->toBe('Grill it')
        ->and($array['ingredients'])->toBeArray()
        ->and($array['ingredients'])->toHaveCount(2)
        ->and($array['portion_size'])->toBe('200g')
        ->and($array['calories'])->toBe(400.0)
        ->and($array['protein_grams'])->toBe(30.0)
        ->and($array['carbs_grams'])->toBe(10.0)
        ->and($array['fat_grams'])->toBe(20.0)
        ->and($array['preparation_time_minutes'])->toBe(30)
        ->and($array['sort_order'])->toBe(3)
        ->and($array['metadata'])->toBe(['test' => 'data']);
});

it('handles float day_number by converting to int', function (): void {
    $data = [
        'day_number' => 1.5,
        'type' => 'snack',
        'name' => 'Apple',
        'calories' => 100.0,
        'sort_order' => 1,
    ];

    $mealData = MealData::from($data);

    expect($mealData->dayNumber)->toBe(1);
});

it('handles string day_number by converting to int', function (): void {
    $data = [
        'day_number' => '2',
        'type' => 'breakfast',
        'name' => 'Toast',
        'calories' => 150.0,
        'sort_order' => 1,
    ];

    $mealData = MealData::from($data);

    expect($mealData->dayNumber)->toBe(2);
});

it('handles int calories by converting to float', function (): void {
    $data = [
        'day_number' => 1,
        'type' => 'lunch',
        'name' => 'Pasta',
        'calories' => 500,
        'sort_order' => 1,
    ];

    $mealData = MealData::from($data);

    expect($mealData->calories)->toBe(500.0);
});

it('handles string calories by converting to float', function (): void {
    $data = [
        'day_number' => 1,
        'type' => 'dinner',
        'name' => 'Fish',
        'calories' => '350.5',
        'sort_order' => 1,
    ];

    $mealData = MealData::from($data);

    expect($mealData->calories)->toBe(350.5);
});

it('handles numeric string fields by converting to string', function (): void {
    $data = [
        'day_number' => 1,
        'type' => 'breakfast',
        'name' => 123,
        'description' => 456,
        'calories' => 200.0,
        'sort_order' => 1,
    ];

    $mealData = MealData::from($data);

    expect($mealData->name)->toBe('123')
        ->and($mealData->description)->toBe('456');
});

it('throws exception for invalid day_number', function (): void {
    $data = [
        'day_number' => 'invalid',
        'type' => 'breakfast',
        'name' => 'Test',
        'calories' => 100.0,
        'sort_order' => 1,
    ];

    MealData::from($data);
})->throws(TypeError::class);

it('throws exception for invalid calories', function (): void {
    $data = [
        'day_number' => 1,
        'type' => 'lunch',
        'name' => 'Test',
        'calories' => 'not-a-number',
        'sort_order' => 1,
    ];

    MealData::from($data);
})->throws(TypeError::class);

it('throws exception for invalid name type', function (): void {
    $data = [
        'day_number' => 1,
        'type' => 'dinner',
        'name' => ['invalid'],
        'calories' => 100.0,
        'sort_order' => 1,
    ];

    MealData::from($data);
})->throws(TypeError::class);

it('handles optional float fields with string values', function (): void {
    $data = [
        'day_number' => 1,
        'type' => 'snack',
        'name' => 'Nuts',
        'calories' => 200.0,
        'protein_grams' => '15.5',
        'carbs_grams' => '10',
        'fat_grams' => 20,
        'sort_order' => 1,
    ];

    $mealData = MealData::from($data);

    expect($mealData->proteinGrams)->toBe(15.5)
        ->and($mealData->carbsGrams)->toBe(10.0)
        ->and($mealData->fatGrams)->toBe(20.0);
});

it('handles optional int field with string value', function (): void {
    $data = [
        'day_number' => 1,
        'type' => 'breakfast',
        'name' => 'Eggs',
        'calories' => 150.0,
        'preparation_time_minutes' => '15',
        'sort_order' => 1,
    ];

    $mealData = MealData::from($data);

    expect($mealData->preparationTimeMinutes)->toBe(15);
});

it('handles metadata as null when not provided', function (): void {
    $data = [
        'day_number' => 1,
        'type' => 'lunch',
        'name' => 'Soup',
        'calories' => 100.0,
        'sort_order' => 1,
    ];

    $mealData = MealData::from($data);

    expect($mealData->metadata)->toBeNull();
});
