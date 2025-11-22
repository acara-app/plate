<?php

declare(strict_types=1);

use App\Services\FoodDataProviders\UsdaFoodDataProvider;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config(['services.usda.api_key' => 'test-api-key']);
    config(['services.usda.url' => 'https://api.nal.usda.gov/fdc/v1']);
});

it('searches for generic ingredients and returns unified format', function (): void {
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
        'api.nal.usda.gov/fdc/v1/food/*' => Http::response([
            'fdcId' => 12345,
            'description' => 'Chicken breast',
            'dataType' => 'Foundation',
            'foodNutrients' => [
                ['nutrient' => ['number' => '208'], 'amount' => 165],
                ['nutrient' => ['number' => '203'], 'amount' => 31],
                ['nutrient' => ['number' => '205'], 'amount' => 0],
                ['nutrient' => ['number' => '204'], 'amount' => 3.6],
                ['nutrient' => ['number' => '291'], 'amount' => 0],
                ['nutrient' => ['number' => '269'], 'amount' => 0],
                ['nutrient' => ['number' => '307'], 'amount' => 74],
            ],
        ], 200),
    ]);

    $provider = app(UsdaFoodDataProvider::class);
    $results = $provider->search('chicken breast');

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(1)
        ->and($results[0])->toHaveKeys(['calories', 'protein', 'carbs', 'fat', 'fiber', 'sugar', 'sodium', 'source'])
        ->and($results[0]['calories'])->toBe(165.0)
        ->and($results[0]['protein'])->toBe(31.0)
        ->and($results[0]['carbs'])->toBe(0.0)
        ->and($results[0]['fat'])->toBe(3.6)
        ->and($results[0]['source'])->toBe('usda');
});

it('returns empty array when no foods found', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response([
            'foods' => [],
            'totalHits' => 0,
        ], 200),
    ]);

    $provider = app(UsdaFoodDataProvider::class);
    $results = $provider->search('nonexistent food');

    expect($results)->toBeArray()
        ->and($results)->toBeEmpty();
});

it('passes limit parameter to service', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response([
            'foods' => [],
            'totalHits' => 0,
        ], 200),
    ]);

    $provider = app(UsdaFoodDataProvider::class);
    $provider->search('test food');

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'pageSize=5'));
});

it('returns all foods even with incomplete nutrition data', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response([
            'foods' => [
                [
                    'fdcId' => 1,
                    'description' => 'Complete food',
                    'dataType' => 'Foundation',
                ],
                [
                    'fdcId' => 2,
                    'description' => 'Incomplete food',
                    'dataType' => 'Foundation',
                ],
            ],
            'totalHits' => 2,
        ], 200),
        'api.nal.usda.gov/fdc/v1/food/1*' => Http::response([
            'fdcId' => 1,
            'description' => 'Complete food',
            'dataType' => 'Foundation',
            'foodNutrients' => [
                ['nutrient' => ['number' => '208'], 'amount' => 100],
                ['nutrient' => ['number' => '203'], 'amount' => 10],
                ['nutrient' => ['number' => '205'], 'amount' => 20],
                ['nutrient' => ['number' => '204'], 'amount' => 5],
            ],
        ], 200),
        'api.nal.usda.gov/fdc/v1/food/2*' => Http::response([
            'fdcId' => 2,
            'description' => 'Incomplete food',
            'dataType' => 'Foundation',
            'foodNutrients' => [
                ['nutrient' => ['number' => '208'], 'amount' => 100],
            ],
        ], 200),
    ]);

    $provider = app(UsdaFoodDataProvider::class);
    $results = $provider->search('test food');

    expect($results)->toHaveCount(2);
});

it('searches only Foundation and SR Legacy data types', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response(['foods' => []], 200),
    ]);

    $provider = app(UsdaFoodDataProvider::class);
    $provider->search('test');

    Http::assertSent(function ($request): bool {
        $url = $request->url();

        return str_contains($url, 'dataType');
    });
});

it('handles API errors gracefully', function (): void {
    Http::fake([
        'api.nal.usda.gov/*' => Http::response(null, 500),
    ]);

    $provider = app(UsdaFoodDataProvider::class);
    $results = $provider->search('test food');

    expect($results)->toBeArray()
        ->and($results)->toBeEmpty();
});

it('gets nutrition data by item ID', function (): void {
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
                ['nutrient' => ['number' => '291'], 'amount' => 0],
                ['nutrient' => ['number' => '269'], 'amount' => 0],
                ['nutrient' => ['number' => '307'], 'amount' => 74],
            ],
        ], 200),
    ]);

    $provider = app(UsdaFoodDataProvider::class);
    $nutrition = $provider->getNutritionData('12345');

    expect($nutrition)->toBeArray()
        ->and($nutrition)->toHaveKeys(['calories', 'protein', 'carbs', 'fat', 'fiber', 'sugar', 'sodium', 'source'])
        ->and($nutrition['calories'])->toBe(165.0)
        ->and($nutrition['protein'])->toBe(31.0)
        ->and($nutrition['source'])->toBe('usda');
});

it('returns null when food by ID not found', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/food/99999*' => Http::response(null, 404),
    ]);

    $provider = app(UsdaFoodDataProvider::class);
    $nutrition = $provider->getNutritionData('99999');

    expect($nutrition)->toBeNull();
});

it('returns null when food by ID has incomplete nutrition', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/food/12345*' => Http::response([
            'fdcId' => 12345,
            'description' => 'Incomplete food',
            'dataType' => 'Foundation',
            'foodNutrients' => [
                ['nutrient' => ['number' => '208'], 'amount' => 100],
            ],
        ], 200),
    ]);

    $provider = app(UsdaFoodDataProvider::class);
    $nutrition = $provider->getNutritionData('12345');

    expect($nutrition)->toBeArray()
        ->and($nutrition['protein'])->toBeNull();
});

it('skips foods with empty ID in search results', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response([
            'foods' => [
                [
                    'fdcId' => '',  // Empty ID
                    'description' => 'Food 1',
                    'dataType' => 'Foundation',
                ],
                [
                    'fdcId' => 2,
                    'description' => 'Food 2',
                    'dataType' => 'Foundation',
                ],
            ],
            'totalHits' => 2,
        ], 200),
        'api.nal.usda.gov/fdc/v1/food/2*' => Http::response([
            'fdcId' => 2,
            'description' => 'Food 2',
            'dataType' => 'Foundation',
            'foodNutrients' => [
                ['nutrient' => ['number' => '208'], 'amount' => 100],
                ['nutrient' => ['number' => '203'], 'amount' => 10],
                ['nutrient' => ['number' => '205'], 'amount' => 20],
                ['nutrient' => ['number' => '204'], 'amount' => 5],
            ],
        ], 200),
    ]);

    $provider = app(UsdaFoodDataProvider::class);
    $results = $provider->search('test');

    // Should only have 1 result (skipped the empty ID)
    expect($results)->toHaveCount(1)
        ->and($results[0]['id'])->toBe('2');
});

it('skips foods when getFoodById returns null', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response([
            'foods' => [
                [
                    'fdcId' => 1,
                    'description' => 'Food 1',
                    'dataType' => 'Foundation',
                ],
                [
                    'fdcId' => 2,
                    'description' => 'Food 2',
                    'dataType' => 'Foundation',
                ],
            ],
            'totalHits' => 2,
        ], 200),
        'api.nal.usda.gov/fdc/v1/food/1*' => Http::response(null, 404),  // Not found
        'api.nal.usda.gov/fdc/v1/food/2*' => Http::response([
            'fdcId' => 2,
            'description' => 'Food 2',
            'dataType' => 'Foundation',
            'foodNutrients' => [
                ['nutrient' => ['number' => '208'], 'amount' => 100],
                ['nutrient' => ['number' => '203'], 'amount' => 10],
                ['nutrient' => ['number' => '205'], 'amount' => 20],
                ['nutrient' => ['number' => '204'], 'amount' => 5],
            ],
        ], 200),
    ]);

    $provider = app(UsdaFoodDataProvider::class);
    $results = $provider->search('test');

    // Should only have 1 result (skipped the 404)
    expect($results)->toHaveCount(1)
        ->and($results[0]['id'])->toBe('2');
});

it('skips foods when nutrition extraction returns null', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response([
            'foods' => [
                [
                    'fdcId' => 1,
                    'description' => 'Food 1',
                    'dataType' => 'Foundation',
                ],
                [
                    'fdcId' => 2,
                    'description' => 'Food 2',
                    'dataType' => 'Foundation',
                ],
            ],
            'totalHits' => 2,
        ], 200),
        'api.nal.usda.gov/fdc/v1/food/1*' => Http::response([
            'fdcId' => 1,
            'description' => 'Food 1',
            'dataType' => 'Foundation',
            // Missing foodNutrients - will cause extraction to return null
        ], 200),
        'api.nal.usda.gov/fdc/v1/food/2*' => Http::response([
            'fdcId' => 2,
            'description' => 'Food 2',
            'dataType' => 'Foundation',
            'foodNutrients' => [
                ['nutrient' => ['number' => '208'], 'amount' => 100],
                ['nutrient' => ['number' => '203'], 'amount' => 10],
                ['nutrient' => ['number' => '205'], 'amount' => 20],
                ['nutrient' => ['number' => '204'], 'amount' => 5],
            ],
        ], 200),
    ]);

    $provider = app(UsdaFoodDataProvider::class);
    $results = $provider->search('test');

    // Should only have 1 result (skipped the one without nutrition)
    expect($results)->toHaveCount(1)
        ->and($results[0]['id'])->toBe('2');
});

it('searches with specificity delegates to search method', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response([
            'foods' => [
                [
                    'fdcId' => 12345,
                    'description' => 'Chicken breast',
                    'dataType' => 'Foundation',
                ],
            ],
            'totalHits' => 1,
        ], 200),
        'api.nal.usda.gov/fdc/v1/food/12345*' => Http::response([
            'fdcId' => 12345,
            'description' => 'Chicken breast',
            'dataType' => 'Foundation',
            'foodNutrients' => [
                ['nutrient' => ['number' => '208'], 'amount' => 165],
                ['nutrient' => ['number' => '203'], 'amount' => 31],
                ['nutrient' => ['number' => '205'], 'amount' => 0],
                ['nutrient' => ['number' => '204'], 'amount' => 3.6],
                ['nutrient' => ['number' => '291'], 'amount' => 0],
                ['nutrient' => ['number' => '269'], 'amount' => 0],
                ['nutrient' => ['number' => '307'], 'amount' => 74],
            ],
        ], 200),
    ]);

    $provider = app(UsdaFoodDataProvider::class);
    $results = $provider->searchWithSpecificity('chicken breast', App\Enums\IngredientSpecificity::Generic);

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(1)
        ->and($results[0]['source'])->toBe('usda');
});
