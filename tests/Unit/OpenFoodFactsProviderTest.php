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
    $results = $provider->search('test food', 5);

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(1)
        ->and($results[0])->toHaveKeys(['id', 'name', 'brand', 'nutrition_per_100g'])
        ->and($results[0]['id'])->toBe('123456')
        ->and($results[0]['name'])->toBe('Test Food')
        ->and($results[0]['brand'])->toBe('Test Brand')
        ->and($results[0]['nutrition_per_100g'])->not->toBeNull();
});

it('returns null when no products found', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/search*' => Http::response([
            'products' => [],
        ], 200),
    ]);

    $provider = app(OpenFoodFactsProvider::class);
    $results = $provider->search('nonexistent food');

    expect($results)->toBeNull();
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
        ->and($results)->toHaveCount(1)
        ->and($results[0]['nutrition_per_100g'])->toBeNull();
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

    expect($nutrition)->not->toBeNull()
        ->and($nutrition->calories)->toBe(150.0)
        ->and($nutrition->protein)->toBe(20.0)
        ->and($nutrition->carbs)->toBe(25.0)
        ->and($nutrition->fat)->toBe(3.0);
});

it('cleans ingredient names correctly', function (): void {
    $provider = app(OpenFoodFactsProvider::class);

    $cleaned = $provider->cleanIngredientName('Fresh organic grilled chicken breast (boneless)');

    expect($cleaned)->not->toContain('fresh')
        ->and($cleaned)->not->toContain('organic')
        ->and($cleaned)->not->toContain('grilled')
        ->and($cleaned)->not->toContain('(boneless)');
});
