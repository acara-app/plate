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

it('searches for products successfully using v2 API', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/search*' => Http::response([
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

    // Verify it uses v2 endpoint
    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/api/v2/search')
    );
});

it('extracts nutrition data per 100g', function (): void {
    $service = app(OpenFoodFactsService::class);

    $product = OpenFoodFactsProductData::from([
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

    $product = OpenFoodFactsProductData::from([]);

    $nutrition = $service->extractNutritionPer100g($product);

    expect($nutrition)->toBeNull();
});

it('caches search results for 30 days', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/search*' => Http::response([
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
    $searchResults = OpenFoodFactsSearchResultData::from([
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
        'world.openfoodfacts.org/api/v2/search*' => Http::response(null, 500),
    ]);

    $service = app(OpenFoodFactsService::class);
    $result = $service->searchProduct('test');

    expect($result)->toBeNull();
});

it('returns empty result when search has no products', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/search*' => Http::response(['products' => []], 200),
    ]);

    $service = app(OpenFoodFactsService::class);
    $result = $service->searchProduct('test');

    expect($result)
        ->toBeInstanceOf(OpenFoodFactsSearchResultData::class)
        ->and($result->isEmpty())->toBeTrue();
});

it('returns non-empty result when search has products', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/search*' => Http::response([
            'products' => [
                ['product_name' => 'Test Product', 'code' => '123'],
            ],
        ], 200),
    ]);

    $service = app(OpenFoodFactsService::class);
    $result = $service->searchProduct('test');

    expect($result)
        ->toBeInstanceOf(OpenFoodFactsSearchResultData::class)
        ->and($result->isEmpty())->toBeFalse();
});

it('constructs with array and converts to DataCollection', function (): void {
    $searchResults = OpenFoodFactsSearchResultData::from([
        'count' => 2,
        'page' => 1,
        'page_size' => 24,
        'products' => [
            ['product_name' => 'Product 1', 'code' => '111'],
            ['product_name' => 'Product 2', 'code' => '222'],
        ],
    ]);

    expect($searchResults->count)->toBe(2)
        ->and($searchResults->page)->toBe(1)
        ->and($searchResults->pageSize)->toBe(24)
        ->and($searchResults->products)->toHaveCount(2)
        ->and($searchResults->products[0])->toBeInstanceOf(OpenFoodFactsProductData::class)
        ->and($searchResults->products[1])->toBeInstanceOf(OpenFoodFactsProductData::class);
});

it('constructs with DataCollection directly', function (): void {
    $products = OpenFoodFactsProductData::collect([
        ['product_name' => 'Product 1', 'code' => '111'],
        ['product_name' => 'Product 2', 'code' => '222'],
    ], Spatie\LaravelData\DataCollection::class);

    $searchResults = new OpenFoodFactsSearchResultData(
        count: 2,
        page: 1,
        pageSize: 24,
        products: $products
    );

    expect($searchResults->count)->toBe(2)
        ->and($searchResults->products)->toHaveCount(2)
        ->and($searchResults->products[0])->toBeInstanceOf(OpenFoodFactsProductData::class);
});

it('returns null for best match when products array is empty', function (): void {
    $searchResults = OpenFoodFactsSearchResultData::from(['products' => []]);
    $result = $searchResults->getBestMatch();

    expect($result)->toBeNull();
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

    $product = OpenFoodFactsProductData::from([
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

    $product = OpenFoodFactsProductData::from([
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

it('infers correct category from ingredient name', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/search*' => Http::response([
            'products' => [['product_name' => 'Test']],
        ], 200),
    ]);

    $service = app(OpenFoodFactsService::class);
    $service->searchProduct('chicken breast');

    Http::assertSent(function ($request): bool {
        $url = $request->url();

        // Should use chicken-breasts category
        return str_contains($url, 'categories_tags_en=chicken-breasts');
    });
});

it('uses popularity sorting for search results', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/search*' => Http::response([
            'products' => [['product_name' => 'Test']],
        ], 200),
    ]);

    $service = app(OpenFoodFactsService::class);
    $service->searchProduct('rice');

    Http::assertSent(function ($request): bool {
        $url = $request->url();

        // Should sort by unique scans (popularity)
        return str_contains($url, 'sort_by=unique_scans_n');
    });
});

it('respects rate limit for search requests', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/search*' => Http::response([
            'products' => [['product_name' => 'Test']],
        ], 200),
    ]);

    $service = app(OpenFoodFactsService::class);

    // Make 8 requests (at the limit)
    for ($i = 0; $i < 8; $i++) {
        $service->searchProduct("query{$i}");
    }

    // 9th request should be blocked by rate limit
    $result = $service->searchProduct('query9');

    // Should be null due to rate limit
    expect($result)->toBeNull();

    // Should have made exactly 8 API calls
    Http::assertSentCount(8);
});

it('cleans ingredient names for better matching', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/search*' => Http::response([
            'products' => [['product_name' => 'Test']],
        ], 200),
    ]);

    $service = app(OpenFoodFactsService::class);
    // Should clean "fresh organic grilled" and still find chicken-breasts category
    $service->searchProduct('fresh organic grilled chicken breast (boneless)');

    Http::assertSent(function ($request): bool {
        $url = $request->url();

        return str_contains($url, 'categories_tags_en=chicken-breasts');
    });
});
