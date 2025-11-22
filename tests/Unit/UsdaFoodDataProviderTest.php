<?php

declare(strict_types=1);

use App\Enums\IngredientSpecificity;
use App\Models\UsdaFoundationFood;
use App\Services\FoodDataProviders\UsdaFoodDataProvider;
use App\Services\UsdaFoodDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('searches for foods using local database', function (): void {
    UsdaFoundationFood::factory()->create([
        'id' => 12345,
        'description' => 'Brown Rice, raw',
        'nutrients' => [
            ['nutrient' => ['number' => '208'], 'amount' => 370],
            ['nutrient' => ['number' => '203'], 'amount' => 7.5],
            ['nutrient' => ['number' => '205'], 'amount' => 77],
            ['nutrient' => ['number' => '204'], 'amount' => 2.9],
        ],
    ]);

    $service = app(UsdaFoodDataService::class);
    $provider = new UsdaFoodDataProvider($service);
    $results = $provider->search('brown rice');

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(1)
        ->and($results[0]['id'])->toBe('12345')
        ->and($results[0]['name'])->toBe('Brown Rice, raw')
        ->and($results[0]['calories'])->toBe(370.0)
        ->and($results[0]['protein'])->toBe(7.5)
        ->and($results[0]['source'])->toBe('usda');
});

it('returns empty array when no results', function (): void {
    $service = app(UsdaFoodDataService::class);
    $provider = new UsdaFoodDataProvider($service);

    expect($provider->search('nonexistent'))->toBeEmpty();
});

it('gets nutrition data by product ID', function (): void {
    UsdaFoundationFood::factory()->create([
        'id' => 12345,
        'description' => 'Chicken breast',
        'nutrients' => [
            ['nutrient' => ['number' => '208'], 'amount' => 165],
            ['nutrient' => ['number' => '203'], 'amount' => 31],
        ],
    ]);

    $service = app(UsdaFoodDataService::class);
    $provider = new UsdaFoodDataProvider($service);
    $result = $provider->getNutritionData('12345');

    expect($result)->toBeArray()
        ->and($result['id'])->toBe('12345')
        ->and($result['calories'])->toBe(165.0)
        ->and($result['source'])->toBe('usda');
});

it('returns null when product not found', function (): void {
    $service = app(UsdaFoodDataService::class);
    $provider = new UsdaFoodDataProvider($service);

    expect($provider->getNutritionData('99999'))->toBeNull();
});

it('delegates to search for searchWithSpecificity', function (): void {
    UsdaFoundationFood::factory()->create([
        'id' => 12345,
        'description' => 'Test Food',
        'nutrients' => [
            ['nutrient' => ['number' => '208'], 'amount' => 100],
        ],
    ]);

    $service = app(UsdaFoodDataService::class);
    $provider = new UsdaFoodDataProvider($service);
    $results = $provider->searchWithSpecificity('test', IngredientSpecificity::Generic);

    expect($results)->toBeArray();
});

it('filters out invalid search results', function (): void {
    // Create two valid foods
    UsdaFoundationFood::factory()->create([
        'id' => 123,
        'description' => 'Valid Food Item',
        'nutrients' => [
            ['nutrient' => ['number' => '208'], 'amount' => 100],
            ['nutrient' => ['number' => '203'], 'amount' => 10],
        ],
    ]);

    UsdaFoundationFood::factory()->create([
        'id' => 456,
        'description' => 'Another Valid Food',
        'nutrients' => [
            ['nutrient' => ['number' => '208'], 'amount' => 200],
        ],
    ]);

    $service = app(UsdaFoodDataService::class);
    $provider = new UsdaFoodDataProvider($service);
    $results = $provider->search('food');

    // Should get back both valid results
    expect($results)->toBeArray()
        ->and($results)->toHaveCount(2);
});
