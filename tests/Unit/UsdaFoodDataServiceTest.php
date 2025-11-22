<?php

declare(strict_types=1);

use App\DataObjects\FoodSearchResultData;
use App\DataObjects\NutritionData;
use App\DataObjects\UsdaFoodData;
use App\Services\UsdaFoodDataService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::flush();
    config(['services.usda.api_key' => 'test-api-key']);
    config(['services.usda.url' => 'https://api.nal.usda.gov/fdc/v1']);
});

it('searches for foods and returns results', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response([
            'foods' => [
                [
                    'fdcId' => 12345,
                    'description' => 'Chicken, broilers or fryers, breast, meat only, raw',
                    'dataType' => 'Foundation',
                    'foodNutrients' => [
                        ['nutrientId' => 1008, 'nutrientNumber' => '208', 'value' => 165],
                        ['nutrientId' => 1003, 'nutrientNumber' => '203', 'value' => 31],
                        ['nutrientId' => 1005, 'nutrientNumber' => '205', 'value' => 0],
                        ['nutrientId' => 1004, 'nutrientNumber' => '204', 'value' => 3.6],
                        ['nutrientId' => 1079, 'nutrientNumber' => '291', 'value' => 0],
                        ['nutrientId' => 2000, 'nutrientNumber' => '269', 'value' => 0],
                        ['nutrientId' => 1093, 'nutrientNumber' => '307', 'value' => 74],
                    ],
                ],
            ],
            'totalHits' => 1,
        ], 200),
    ]);

    $service = app(UsdaFoodDataService::class);
    $results = $service->searchFoods('chicken breast');

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(1)
        ->and($results[0])->toBeInstanceOf(FoodSearchResultData::class)
        ->and($results[0]->id)->toBe('12345')
        ->and($results[0]->name)->toBe('Chicken, broilers or fryers, breast, meat only, raw');
});

it('searches with default page size', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response([
            'foods' => [],
            'totalHits' => 0,
        ], 200),
    ]);

    $service = app(UsdaFoodDataService::class);
    $service->searchFoods('test food');

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'pageSize=5'));
});

it('limits search results', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response([
            'foods' => [],
            'totalHits' => 0,
        ], 200),
    ]);

    $service = app(UsdaFoodDataService::class);
    $service->searchFoods('test food', pageSize: 10);

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'pageSize=10'));
});

it('caches search results', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response([
            'foods' => [
                [
                    'fdcId' => 12345,
                    'description' => 'Test Food',
                    'dataType' => 'Foundation',
                    'foodNutrients' => [],
                ],
            ],
            'totalHits' => 1,
        ], 200),
    ]);

    $service = app(UsdaFoodDataService::class);

    $service->searchFoods('chicken breast');
    $service->searchFoods('chicken breast');

    Http::assertSentCount(1);
});

it('returns empty array when no foods found', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response([
            'foods' => [],
            'totalHits' => 0,
        ], 200),
    ]);

    $service = app(UsdaFoodDataService::class);
    $results = $service->searchFoods('nonexistent food');

    expect($results)->toBeArray()
        ->and($results)->toBeEmpty();
});

it('handles API errors gracefully', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response(null, 500),
    ]);

    $service = app(UsdaFoodDataService::class);
    $results = $service->searchFoods('test food');

    expect($results)->toBeNull();
});

it('fetches food by ID', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/food/12345*' => Http::response([
            'fdcId' => 12345,
            'description' => 'Chicken breast',
            'dataType' => 'Foundation',
            'foodNutrients' => [
                ['nutrient' => ['number' => '208'], 'amount' => 165],
                ['nutrient' => ['number' => '203'], 'amount' => 31],
                ['nutrient' => ['number' => '205'], 'amount' => 0],
                ['nutrient' => ['number' => '204'], 'amount' => 3.6],
            ],
        ], 200),
    ]);

    $service = app(UsdaFoodDataService::class);
    $result = $service->getFoodById('12345');

    expect($result)->toBeInstanceOf(UsdaFoodData::class)
        ->and($result->fdcId)->toBe('12345')
        ->and($result->description)->toBe('Chicken breast');
});

it('caches food by ID results', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/food/12345*' => Http::response([
            'fdcId' => 12345,
            'description' => 'Test Food',
            'dataType' => 'Foundation',
            'foodNutrients' => [],
        ], 200),
    ]);

    $service = app(UsdaFoodDataService::class);

    $service->getFoodById('12345');
    $service->getFoodById('12345');

    Http::assertSentCount(1);
});

it('returns null when food by ID not found', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/food/99999*' => Http::response(null, 404),
    ]);

    $service = app(UsdaFoodDataService::class);
    $result = $service->getFoodById('99999');

    expect($result)->toBeNull();
});

it('extracts nutrition per 100g from food data', function (): void {
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

    $service = app(UsdaFoodDataService::class);
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

it('handles missing nutrients in food data', function (): void {
    $foodData = new UsdaFoodData(
        fdcId: '12345',
        description: 'Incomplete food',
        brandOwner: null,
        dataType: 'Foundation',
        foodNutrients: [
            ['nutrient' => ['number' => '208'], 'amount' => 100],
            ['nutrient' => ['number' => '203'], 'amount' => 10],
        ]
    );

    $service = app(UsdaFoodDataService::class);
    $nutrition = $service->extractNutritionPer100g($foodData);

    expect($nutrition->calories)->toBe(100.0)
        ->and($nutrition->protein)->toBe(10.0)
        ->and($nutrition->carbs)->toBeNull()
        ->and($nutrition->fat)->toBeNull()
        ->and($nutrition->fiber)->toBeNull()
        ->and($nutrition->sugar)->toBeNull()
        ->and($nutrition->sodium)->toBeNull();
});

it('returns null for nutrition when food nutrients missing', function (): void {
    $foodData = new UsdaFoodData(
        fdcId: '12345',
        description: 'Food without nutrients',
        brandOwner: null,
        dataType: 'Foundation',
        foodNutrients: []
    );

    $service = app(UsdaFoodDataService::class);
    $nutrition = $service->extractNutritionPer100g($foodData);

    expect($nutrition)->toBeInstanceOf(NutritionData::class);
});

it('includes API key in requests', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response([
            'foods' => [],
            'totalHits' => 0,
        ], 200),
    ]);

    $service = app(UsdaFoodDataService::class);
    $service->searchFoods('test');

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'api_key=test-api-key'));
});

it('uses correct base URL from config', function (): void {
    Http::fake([
        'api.nal.usda.gov/*' => Http::response(['foods' => []], 200),
    ]);

    $service = app(UsdaFoodDataService::class);
    $service->searchFoods('test');

    Http::assertSent(fn ($request): bool => str_starts_with((string) $request->url(), 'https://api.nal.usda.gov/fdc/v1'));
});

it('handles connection exceptions for search', function (): void {
    Http::fake([
        'api.nal.usda.gov/*' => function (): void {
            throw new Illuminate\Http\Client\ConnectionException('Connection failed');
        },
    ]);

    $service = app(UsdaFoodDataService::class);
    $result = $service->searchFoods('test');

    expect($result)->toBeNull();
});

it('handles connection exceptions for getFoodById', function (): void {
    Http::fake([
        'api.nal.usda.gov/*' => function (): void {
            throw new Illuminate\Http\Client\ConnectionException('Connection failed');
        },
    ]);

    $service = app(UsdaFoodDataService::class);
    $result = $service->getFoodById('12345');

    expect($result)->toBeNull();
});

it('returns null when API key is not configured for getFoodById', function (): void {
    config(['services.usda.api_key' => null]);

    $service = new UsdaFoodDataService(
        config('services.usda.api_key'),
        config('services.usda.base_url')
    );

    $result = $service->getFoodById('12345');

    expect($result)->toBeNull();
});
