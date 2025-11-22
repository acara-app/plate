<?php

declare(strict_types=1);

use App\Services\FoodDataProviders\OpenFoodFactsProvider;
use Illuminate\Support\Facades\Http;

it('searches for food items and returns formatted results', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/search*' => Http::response([
            'products' => [
                [
                    'code' => '123456',
                    'product_name' => 'Test Food',
                    'brands' => 'Test Brand',
                    'nutriments' => [
                        'energy-kcal_100g' => 150,
                        'proteins_100g' => 20,
                        'carbohydrates_100g' => 25,
                        'fat_100g' => 3,
                    ],
                ],
            ],
        ], 200),
    ]);

    $provider = app(OpenFoodFactsProvider::class);
    $results = $provider->search('test food');

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(1)
        ->and($results[0])->toHaveKeys(['calories', 'protein', 'carbs', 'fat', 'fiber', 'sugar', 'sodium', 'source'])
        ->and($results[0]['calories'])->toBe(150.0)
        ->and($results[0]['protein'])->toBe(20.0)
        ->and($results[0]['carbs'])->toBe(25.0)
        ->and($results[0]['fat'])->toBe(3.0)
        ->and($results[0]['source'])->toBe('openfoodfacts');
});

it('returns empty array when no products found', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/search*' => Http::response([
            'products' => [],
        ], 200),
    ]);

    $provider = app(OpenFoodFactsProvider::class);
    $results = $provider->search('nonexistent food');

    expect($results)->toBeArray()
        ->and($results)->toBeEmpty();
});

it('handles products without nutriments', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/search*' => Http::response([
            'products' => [
                [
                    'code' => '123456',
                    'product_name' => 'Test Food',
                    'brands' => 'Test Brand',
                ],
            ],
        ], 200),
    ]);

    $provider = app(OpenFoodFactsProvider::class);
    $results = $provider->search('test food');

    expect($results)->toBeArray()
        ->and($results)->toBeEmpty();
});

it('gets nutrition data by item id', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/product/*' => Http::response([
            'status' => 1,
            'product' => [
                'code' => '123456',
                'product_name' => 'Test Food',
                'nutriments' => [
                    'energy-kcal_100g' => 150,
                    'proteins_100g' => 20,
                    'carbohydrates_100g' => 25,
                    'fat_100g' => 3,
                ],
            ],
        ], 200),
    ]);

    $provider = app(OpenFoodFactsProvider::class);
    $nutrition = $provider->getNutritionData('123456');

    expect($nutrition)->toBeArray()
        ->and($nutrition)->toHaveKeys(['calories', 'protein', 'carbs', 'fat', 'fiber', 'sugar', 'sodium', 'source'])
        ->and($nutrition['calories'])->toBe(150.0)
        ->and($nutrition['protein'])->toBe(20.0)
        ->and($nutrition['carbs'])->toBe(25.0)
        ->and($nutrition['fat'])->toBe(3.0)
        ->and($nutrition['source'])->toBe('openfoodfacts');
});

it('cleans ingredient names correctly', function (): void {
    $provider = app(OpenFoodFactsProvider::class);

    $cleaned = $provider->cleanIngredientName('Fresh organic grilled chicken breast (boneless)');

    expect($cleaned)->not->toContain('fresh')
        ->and($cleaned)->not->toContain('organic')
        ->and($cleaned)->not->toContain('grilled')
        ->and($cleaned)->not->toContain('(boneless)');
});

it('returns null when product has no nutriments', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/product/*' => Http::response([
            'status' => 1,
            'product' => [
                'code' => '123456',
                'product_name' => 'Test Food',
                // Missing nutriments field
            ],
        ], 200),
    ]);

    $provider = app(OpenFoodFactsProvider::class);
    $nutrition = $provider->getNutritionData('123456');

    expect($nutrition)->toBeNull();
});

it('searches with specificity delegates to search method', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/search*' => Http::response([
            'products' => [
                [
                    'code' => '123456',
                    'product_name' => 'Test Food',
                    'brands' => 'Test Brand',
                    'nutriments' => [
                        'energy-kcal_100g' => 150,
                        'proteins_100g' => 20,
                        'carbohydrates_100g' => 25,
                        'fat_100g' => 3,
                    ],
                ],
            ],
        ], 200),
    ]);

    $provider = app(OpenFoodFactsProvider::class);
    $results = $provider->searchWithSpecificity('test food', App\Enums\IngredientSpecificity::Specific);

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(1)
        ->and($results[0]['source'])->toBe('openfoodfacts');
});
