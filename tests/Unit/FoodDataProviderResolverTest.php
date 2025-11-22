<?php

declare(strict_types=1);

use App\Enums\IngredientSpecificity;
use App\Services\FoodDataProviders\FoodDataProviderResolver;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config(['services.usda.api_key' => 'test-api-key']);
    config(['services.usda.url' => 'https://api.nal.usda.gov/fdc/v1']);
});

it('routes generic ingredients to USDA provider', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response([
            'foods' => [
                [
                    'fdcId' => 12345,
                    'description' => 'Chicken breast',
                    'dataType' => 'Foundation',
                    'foodNutrients' => [
                        ['nutrientId' => 1008, 'nutrientNumber' => '208', 'value' => 165],
                        ['nutrientId' => 1003, 'nutrientNumber' => '203', 'value' => 31],
                        ['nutrientId' => 1005, 'nutrientNumber' => '205', 'value' => 0],
                        ['nutrientId' => 1004, 'nutrientNumber' => '204', 'value' => 3.6],
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
            ],
        ], 200),
        'world.openfoodfacts.org/*' => Http::response(['products' => []], 200),
    ]);

    $resolver = app(FoodDataProviderResolver::class);
    $results = $resolver->searchWithSpecificity('chicken breast', IngredientSpecificity::Generic);

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(1)
        ->and($results[0]['source'])->toBe('usda');

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'api.nal.usda.gov'));
});

it('routes specific ingredients with barcode to OpenFoodFacts', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/product/3017620422003*' => Http::response([
            'status' => 1,
            'product' => [
                'code' => '3017620422003',
                'product_name' => 'Nutella',
                'nutriments' => [
                    'energy-kcal_100g' => 539,
                    'proteins_100g' => 6.3,
                    'carbohydrates_100g' => 57.5,
                    'fat_100g' => 30.9,
                ],
            ],
        ], 200),
        'api.nal.usda.gov/*' => Http::response(['foods' => []], 200),
    ]);

    $resolver = app(FoodDataProviderResolver::class);
    $results = $resolver->searchWithSpecificity('nutella', IngredientSpecificity::Specific, '3017620422003');

    expect($results)->toBeArray()
        ->and($results[0]['source'])->toBe('openfoodfacts');

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'world.openfoodfacts.org'));
});

it('routes specific ingredients without barcode to OpenFoodFacts', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/search*' => Http::response([
            'products' => [
                [
                    'code' => '123456',
                    'product_name' => 'Specific Brand Product',
                    'nutriments' => [
                        'energy-kcal_100g' => 200,
                        'proteins_100g' => 10,
                        'carbohydrates_100g' => 30,
                        'fat_100g' => 5,
                    ],
                ],
            ],
        ], 200),
        'api.nal.usda.gov/*' => Http::response(['foods' => []], 200),
    ]);

    $resolver = app(FoodDataProviderResolver::class);
    $results = $resolver->searchWithSpecificity('brand product', IngredientSpecificity::Specific);

    expect($results)->toBeArray()
        ->and($results[0]['source'])->toBe('openfoodfacts');
});

it('delegates search without specificity to OpenFoodFacts', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/search*' => Http::response([
            'products' => [
                [
                    'code' => '123456',
                    'product_name' => 'Test Product',
                    'nutriments' => [
                        'energy-kcal_100g' => 150,
                        'proteins_100g' => 8,
                        'carbohydrates_100g' => 20,
                        'fat_100g' => 4,
                    ],
                ],
            ],
        ], 200),
        'api.nal.usda.gov/*' => Http::response(['foods' => []], 200),
    ]);

    $resolver = app(FoodDataProviderResolver::class);
    $results = $resolver->search('test food');

    expect($results)->toBeArray();
});

it('delegates getNutritionData to OpenFoodFacts', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/product/123456*' => Http::response([
            'status' => 1,
            'product' => [
                'code' => '123456',
                'product_name' => 'Test Product',
                'nutriments' => [
                    'energy-kcal_100g' => 150,
                    'proteins_100g' => 8,
                    'carbohydrates_100g' => 20,
                    'fat_100g' => 4,
                ],
            ],
        ], 200),
        'api.nal.usda.gov/*' => Http::response(['foods' => []], 200),
    ]);

    $resolver = app(FoodDataProviderResolver::class);
    $nutrition = $resolver->getNutritionData('123456');

    expect($nutrition)->toBeArray()
        ->and($nutrition['source'])->toBe('usda');
});

it('returns empty array when both providers return no results', function (): void {
    Http::fake([
        'api.nal.usda.gov/*' => Http::response(['foods' => []], 200),
        'world.openfoodfacts.org/*' => Http::response(['products' => []], 200),
    ]);

    $resolver = app(FoodDataProviderResolver::class);
    $results = $resolver->searchWithSpecificity('nonexistent', IngredientSpecificity::Generic);

    expect($results)->toBeArray()
        ->and($results)->toBeEmpty();
});

it('delegates to correct provider based on specificity', function (): void {
    Http::fake([
        'api.nal.usda.gov/fdc/v1/foods/search*' => Http::response([
            'foods' => [
                [
                    'fdcId' => 12345,
                    'description' => 'Test Food',
                    'dataType' => 'Foundation',
                ],
            ],
            'totalHits' => 1,
        ], 200),
        'api.nal.usda.gov/fdc/v1/food/*' => Http::response([
            'fdcId' => 12345,
            'description' => 'Test Food',
            'dataType' => 'Foundation',
            'foodNutrients' => [
                ['nutrient' => ['number' => '208'], 'amount' => 100],
                ['nutrient' => ['number' => '203'], 'amount' => 10],
                ['nutrient' => ['number' => '205'], 'amount' => 20],
                ['nutrient' => ['number' => '204'], 'amount' => 5],
            ],
        ], 200),
    ]);

    $resolver = app(FoodDataProviderResolver::class);

    // Generic should use USDA
    $results = $resolver->searchWithSpecificity('test', IngredientSpecificity::Generic);
    expect($results)->toBeArray();
});

it('gets nutrition data from USDA for numeric IDs', function (): void {
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

    $resolver = app(FoodDataProviderResolver::class);
    $nutrition = $resolver->getNutritionData('12345');

    expect($nutrition)->toBeArray()
        ->and($nutrition['source'])->toBe('usda');
});

it('delegates cleanIngredientName to USDA provider', function (): void {
    $resolver = app(FoodDataProviderResolver::class);
    $cleaned = $resolver->cleanIngredientName('chicken breast (boneless)');

    expect($cleaned)->toBe('chicken breast');
});
