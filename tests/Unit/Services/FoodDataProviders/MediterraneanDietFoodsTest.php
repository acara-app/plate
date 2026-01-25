<?php

declare(strict_types=1);

use App\DataObjects\MediterraneanDietFoodData;
use App\Services\FoodDataProviders\MediterraneanDietFoods;

it('returns an array of DTO foods', function (): void {
    $foods = MediterraneanDietFoods::all();

    expect($foods)->toBeArray();
    expect($foods[0])->toBeInstanceOf(MediterraneanDietFoodData::class);
});

it('contains the correct number of foods', function (): void {
    $foods = MediterraneanDietFoods::all();

    expect($foods)->toHaveCount(64);
});

it('each food item is a DTO', function (): void {
    $foods = MediterraneanDietFoods::all();

    foreach ($foods as $food) {
        expect($food)->toBeInstanceOf(MediterraneanDietFoodData::class);
    }
});

it('contains vegetables', function (): void {
    $foods = MediterraneanDietFoods::all();

    $vegetableNames = collect($foods)
        ->filter(fn (MediterraneanDietFoodData $food): bool => str_contains($food->name, 'Artichoke') || str_contains($food->name, 'Broccoli') || str_contains($food->name, 'Spinach'))
        ->map(fn (MediterraneanDietFoodData $food): string => $food->name)
        ->all();

    expect($vegetableNames)
        ->toContain('Artichoke, boiled, 1 medium')
        ->toContain('Broccoli, boiled, 1/2 cup')
        ->toContain('Spinach, raw, 1/2 cup, chopped');
});

it('contains beans and legumes', function (): void {
    $foods = MediterraneanDietFoods::all();

    $beanNames = collect($foods)
        ->filter(fn (MediterraneanDietFoodData $food): bool => str_contains($food->name, 'Chickpea') || str_contains($food->name, 'Lentil') || str_contains($food->name, 'Hummus'))
        ->map(fn (MediterraneanDietFoodData $food): string => $food->name)
        ->all();

    expect($beanNames)
        ->toContain('Chickpeas (garbanzo beans), boiled, 1 cup')
        ->toContain('Lentils, boiled, 1/2 cup')
        ->toContain('Hummus, 1/2 cup');
});

it('contains fruits', function (): void {
    $foods = MediterraneanDietFoods::all();

    $fruitNames = collect($foods)
        ->filter(fn (MediterraneanDietFoodData $food): bool => str_contains($food->name, 'Apple') || str_contains($food->name, 'Orange') || str_contains($food->name, 'Pear'))
        ->map(fn (MediterraneanDietFoodData $food): string => $food->name)
        ->all();

    expect($fruitNames)
        ->toContain('Apple, raw with skin, 1 medium')
        ->toContain('Orange, navel, raw, 1')
        ->toContain('Pear, raw, 1 medium');
});

it('contains nuts and seeds', function (): void {
    $foods = MediterraneanDietFoods::all();

    $nutSeedNames = collect($foods)
        ->filter(fn (MediterraneanDietFoodData $food): bool => str_contains($food->name, 'Almonds') || str_contains($food->name, 'Cashews') || str_contains($food->name, 'Sesame'))
        ->map(fn (MediterraneanDietFoodData $food): string => $food->name)
        ->all();

    expect($nutSeedNames)
        ->toContain('Almonds, dried, 1/2 oz (12 nuts)')
        ->toContain('Cashews, dry roasted, 1 oz (9 nuts)')
        ->toContain('Sesame seeds, whole, dried, 1 tablespoon');
});

it('contains fish and seafood', function (): void {
    $foods = MediterraneanDietFoods::all();

    $fishNames = collect($foods)
        ->filter(fn (MediterraneanDietFoodData $food): bool => str_contains($food->name, 'Salmon') || str_contains($food->name, 'Scallops') || str_contains($food->name, 'Halibut'))
        ->map(fn (MediterraneanDietFoodData $food): string => $food->name)
        ->all();

    expect($fishNames)
        ->toContain('Salmon, Atlantic, wild, dry heat cooked, 3 oz')
        ->toContain('Scallops, sea, raw, 3.5 oz')
        ->toContain('Halibut, dry heat cooked, 3 oz');
});

it('food data has correct types', function (): void {
    $foods = MediterraneanDietFoods::all();

    $firstFood = $foods[0];

    expect($firstFood->name)->toBeString();
    expect($firstFood->calories)->toBeInt();
    expect($firstFood->protein)->toBeFloat();
    expect($firstFood->fat)->toBeFloat();
    expect($firstFood->saturatedFat)->toBeFloat();
    expect($firstFood->fiber)->toBeFloat();
});

it('has foods with high fiber content', function (): void {
    $foods = MediterraneanDietFoods::all();

    $highFiberFoods = collect($foods)
        ->filter(fn (MediterraneanDietFoodData $food): bool => $food->fiber >= 10)
        ->map(fn (MediterraneanDietFoodData $food): string => $food->name)
        ->all();

    expect($highFiberFoods)->toContain('Artichoke, boiled, 1 medium');
});
