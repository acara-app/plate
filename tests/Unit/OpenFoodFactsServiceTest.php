<?php

declare(strict_types=1);

use App\DataObjects\NutritionData;
use App\DataObjects\OpenFoodFactsProductData;
use App\DataObjects\OpenFoodFactsSearchResultData;
use App\Services\OpenFoodFactsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::flush();
});

it('searches for products successfully', function (): void {
    Http::fake([
        'world.openfoodfacts.org/cgi/search.pl*' => Http::response([
            'products' => [
                [
                    'product_name' => 'Chicken Breast',
                    'nutriments' => [
                        'energy-kcal_100g' => 165,
                        'proteins_100g' => 31,
                        'carbohydrates_100g' => 0,
                        'fat_100g' => 3.6,
                    ],
                ],
            ],
        ], 200),
    ]);

    $service = app(OpenFoodFactsService::class);
    $result = $service->searchProduct('chicken breast');

    expect($result)
        ->toBeInstanceOf(OpenFoodFactsSearchResultData::class)
        ->and($result->products)->toHaveCount(1)
        ->and($result->products[0])->toBeInstanceOf(OpenFoodFactsProductData::class);
});

it('extracts nutrition data per 100g', function (): void {
    $service = app(OpenFoodFactsService::class);

    $product = OpenFoodFactsProductData::fromArray([
        'nutriments' => [
            'energy-kcal_100g' => 165,
            'proteins_100g' => 31,
            'carbohydrates_100g' => 0,
            'fat_100g' => 3.6,
            'fiber_100g' => 0,
            'sugars_100g' => 0,
            'sodium_100g' => 0.074,
        ],
    ]);

    $nutrition = $service->extractNutritionPer100g($product);

    expect($nutrition)
        ->toBeInstanceOf(NutritionData::class)
        ->and($nutrition->calories)->toBe(165.0)
        ->and($nutrition->protein)->toBe(31.0)
        ->and($nutrition->carbs)->toBe(0.0)
        ->and($nutrition->fat)->toBe(3.6);
});

it('returns null for missing nutrition data', function (): void {
    $service = app(OpenFoodFactsService::class);

    $product = OpenFoodFactsProductData::fromArray([]);

    $nutrition = $service->extractNutritionPer100g($product);

    expect($nutrition)->toBeNull();
});

it('caches search results', function (): void {
    Http::fake([
        'world.openfoodfacts.org/cgi/search.pl*' => Http::response([
            'products' => [['product_name' => 'Test Product']],
        ], 200),
    ]);

    $service = app(OpenFoodFactsService::class);

    // First call should hit the API
    $result1 = $service->searchProduct('test');
    expect($result1)->toBeInstanceOf(OpenFoodFactsSearchResultData::class);

    // Second call should use cache
    Http::assertSentCount(1);
    $result2 = $service->searchProduct('test');
    expect($result2)->toEqual($result1);

    // Should still be only 1 API call
    Http::assertSentCount(1);
});

it('gets best match from search results', function (): void {
    $searchResults = OpenFoodFactsSearchResultData::fromArray([
        'products' => [
            ['product_name' => 'First Match', 'code' => '123'],
            ['product_name' => 'Second Match', 'code' => '456'],
        ],
    ]);

    $bestMatch = $searchResults->getBestMatch();

    expect($bestMatch)
        ->toBeInstanceOf(OpenFoodFactsProductData::class)
        ->and($bestMatch->productName)->toBe('First Match')
        ->and($bestMatch->code)->toBe('123');
});

it('returns null when API request fails', function (): void {
    Http::fake([
        'world.openfoodfacts.org/cgi/search.pl*' => Http::response(null, 500),
    ]);

    $service = app(OpenFoodFactsService::class);
    $result = $service->searchProduct('test');

    expect($result)->toBeNull();
});

it('returns empty result when search has no products', function (): void {
    Http::fake([
        'world.openfoodfacts.org/cgi/search.pl*' => Http::response(['products' => []], 200),
    ]);

    $service = app(OpenFoodFactsService::class);
    $result = $service->searchProduct('test');

    expect($result)
        ->toBeInstanceOf(OpenFoodFactsSearchResultData::class)
        ->and($result->isEmpty())->toBeTrue();
});

it('returns null for best match when products array is empty', function (): void {
    $searchResults = OpenFoodFactsSearchResultData::fromArray(['products' => []]);
    $result = $searchResults->getBestMatch();

    expect($result)->toBeNull();
});

it('returns null for best match when products is not an array', function (): void {
    $searchResults = OpenFoodFactsSearchResultData::fromArray(['products' => 'invalid']);
    $result = $searchResults->getBestMatch();

    expect($result)->toBeNull();
});

it('handles invalid product data gracefully', function (): void {
    $searchResults = OpenFoodFactsSearchResultData::fromArray(['products' => ['invalid']]);

    expect($searchResults->products)->toBeArray()->toBeEmpty();
    expect($searchResults->getBestMatch())->toBeNull();
});

it('gets product by barcode successfully', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/product/*' => Http::response([
            'status' => 1,
            'product' => [
                'product_name' => 'Test Product',
                'code' => '1234567890',
            ],
        ], 200),
    ]);

    $service = app(OpenFoodFactsService::class);
    $result = $service->getProductByBarcode('1234567890');

    expect($result)
        ->toBeInstanceOf(OpenFoodFactsProductData::class)
        ->and($result->productName)->toBe('Test Product')
        ->and($result->code)->toBe('1234567890');
});

it('returns null when barcode product has status 0', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/product/*' => Http::response([
            'status' => 0,
        ], 200),
    ]);

    $service = app(OpenFoodFactsService::class);
    $result = $service->getProductByBarcode('invalid');

    expect($result)->toBeNull();
});

it('returns null when barcode request fails', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/product/*' => Http::response(null, 404),
    ]);

    $service = app(OpenFoodFactsService::class);
    $result = $service->getProductByBarcode('invalid');

    expect($result)->toBeNull();
});

it('extracts nutrition from direct nutriments field', function (): void {
    $service = app(OpenFoodFactsService::class);

    $product = OpenFoodFactsProductData::fromArray([
        'nutriments' => [
            'energy-kcal_100g' => 200,
            'proteins_100g' => 25,
            'carbohydrates_100g' => 5,
            'fat_100g' => 10,
        ],
    ]);

    $nutrition = $service->extractNutritionPer100g($product);

    expect($nutrition)
        ->toBeInstanceOf(NutritionData::class)
        ->and($nutrition->calories)->toBe(200.0)
        ->and($nutrition->protein)->toBe(25.0);
});

it('handles missing nutriment values gracefully', function (): void {
    $service = app(OpenFoodFactsService::class);

    $product = OpenFoodFactsProductData::fromArray([
        'nutriments' => [
            'energy-kcal_100g' => 165,
        ],
    ]);

    $nutrition = $service->extractNutritionPer100g($product);

    expect($nutrition)
        ->toBeInstanceOf(NutritionData::class)
        ->and($nutrition->calories)->toBe(165.0)
        ->and($nutrition->protein)->toBeNull()
        ->and($nutrition->carbs)->toBeNull();
});

it('handles connection exceptions for search', function (): void {
    Http::fake(function (): void {
        throw new Illuminate\Http\Client\ConnectionException('Connection timeout');
    });

    $service = app(OpenFoodFactsService::class);
    $result = $service->searchProduct('test');

    expect($result)->toBeNull();
});

it('handles connection exceptions for barcode lookup', function (): void {
    Http::fake(function (): void {
        throw new Illuminate\Http\Client\ConnectionException('Connection timeout');
    });

    $service = app(OpenFoodFactsService::class);
    $result = $service->getProductByBarcode('1234567890');

    expect($result)->toBeNull();
});

it('converts product data to array', function (): void {
    $rawData = [
        'product_name' => 'Test Product',
        'code' => '123',
        'brands' => 'Test Brand',
        'nutriments' => ['energy-kcal_100g' => 100],
    ];

    $product = OpenFoodFactsProductData::fromArray($rawData);
    $array = $product->toArray();

    expect($array)->toBe($rawData);
});
