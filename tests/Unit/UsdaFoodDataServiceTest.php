<?php

declare(strict_types=1);

use App\DataObjects\FoodSearchResultData;
use App\DataObjects\NutritionData;
use App\DataObjects\UsdaFoodData;
use App\Models\UsdaFoundationFood;
use App\Models\UsdaSrLegacyFood;
use App\Services\UsdaFoodDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();
});

it('searches for foods and returns foundation results', function (): void {
    UsdaFoundationFood::factory()->create([
        'id' => 12345,
        'description' => 'Chicken, broilers or fryers, breast, meat only, raw',
        'nutrients' => [
            ['nutrient' => ['number' => '208'], 'amount' => 165],
            ['nutrient' => ['number' => '203'], 'amount' => 31],
        ],
    ]);

    $service = resolve(UsdaFoodDataService::class);
    $results = $service->searchFoods('chicken');

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(1)
        ->and($results[0])->toBeInstanceOf(FoodSearchResultData::class)
        ->and($results[0]->id)->toBe('12345')
        ->and($results[0]->name)->toBe('Chicken, broilers or fryers, breast, meat only, raw')
        ->and($results[0]->dataType)->toBe('Foundation');
});

it('searches sr legacy foods', function (): void {
    UsdaSrLegacyFood::factory()->create([
        'id' => 67890,
        'description' => 'Rice, white, long-grain, regular, raw',
    ]);

    $service = resolve(UsdaFoodDataService::class);
    $results = $service->searchFoods('rice');

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(1)
        ->and($results[0]->dataType)->toBe('SR Legacy');
});

it('limits search results', function (): void {
    UsdaFoundationFood::factory(10)->create([
        'description' => 'Test Food',
    ]);

    $service = resolve(UsdaFoodDataService::class);
    $results = $service->searchFoods('Test Food', pageSize: 3);

    expect($results)->toHaveCount(3);
});

it('caches search results', function (): void {
    UsdaFoundationFood::factory()->create([
        'description' => 'Chicken breast',
    ]);

    $service = resolve(UsdaFoodDataService::class);
    $service->searchFoods('chicken breast');

    UsdaFoundationFood::query()->delete();
    $results = $service->searchFoods('chicken breast');

    expect($results)->toHaveCount(1);
});

it('returns null when no results found', function (): void {
    $service = resolve(UsdaFoodDataService::class);
    $results = $service->searchFoods('nonexistent food');

    expect($results)->toBeNull();
});

it('fetches food by ID from foundation table', function (): void {
    UsdaFoundationFood::factory()->create([
        'id' => 12345,
        'description' => 'Chicken breast',
        'nutrients' => [
            ['nutrient' => ['number' => '208'], 'amount' => 165],
        ],
    ]);

    $service = resolve(UsdaFoodDataService::class);
    $result = $service->getFoodById('12345');

    expect($result)->toBeInstanceOf(UsdaFoodData::class)
        ->and($result->fdcId)->toBe('12345')
        ->and($result->dataType)->toBe('Foundation');
});

it('fetches food by ID from sr legacy table', function (): void {
    UsdaSrLegacyFood::factory()->create([
        'id' => 67890,
        'description' => 'White rice',
    ]);

    $service = resolve(UsdaFoodDataService::class);
    $result = $service->getFoodById('67890');

    expect($result)->toBeInstanceOf(UsdaFoodData::class)
        ->and($result->dataType)->toBe('SR Legacy');
});

it('returns null when food not found', function (): void {
    $service = resolve(UsdaFoodDataService::class);

    expect($service->getFoodById('99999'))->toBeNull();
});

it('extracts nutrition per 100g', function (): void {
    $foodData = new UsdaFoodData(
        fdcId: '12345',
        description: 'Chicken breast',
        brandOwner: null,
        dataType: 'Foundation',
        foodNutrients: [
            ['nutrient' => ['number' => '208'], 'amount' => 165],
            ['nutrient' => ['number' => '203'], 'amount' => 31],
            ['nutrient' => ['number' => '205'], 'amount' => 25],
            ['nutrient' => ['number' => '204'], 'amount' => 3.6],
            ['nutrient' => ['number' => '291'], 'amount' => 2.5],
            ['nutrient' => ['number' => '269'], 'amount' => 1.2],
            ['nutrient' => ['number' => '307'], 'amount' => 74],
        ]
    );

    $service = resolve(UsdaFoodDataService::class);
    $nutrition = $service->extractNutritionPer100g($foodData);

    expect($nutrition)->toBeInstanceOf(NutritionData::class)
        ->and($nutrition->calories)->toBe(165.0)
        ->and($nutrition->protein)->toBe(31.0)
        ->and($nutrition->carbs)->toBe(25.0)
        ->and($nutrition->fat)->toBe(3.6)
        ->and($nutrition->fiber)->toBe(2.5)
        ->and($nutrition->sugar)->toBe(1.2)
        ->and($nutrition->sodium)->toBe(74.0);
});

it('handles missing nutrients', function (): void {
    $foodData = new UsdaFoodData(
        fdcId: '12345',
        description: 'Incomplete food',
        brandOwner: null,
        dataType: 'Foundation',
        foodNutrients: [
            ['nutrient' => ['number' => '208'], 'amount' => 100],
        ]
    );

    $service = resolve(UsdaFoodDataService::class);
    $nutrition = $service->extractNutritionPer100g($foodData);

    expect($nutrition->calories)->toBe(100.0)
        ->and($nutrition->protein)->toBeNull();
});

it('cleans ingredient names', function (): void {
    $service = resolve(UsdaFoodDataService::class);

    expect($service->cleanIngredientName('fresh organic chicken breast'))
        ->toBe('chicken breast');
});
