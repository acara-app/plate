<?php

declare(strict_types=1);

use App\Enums\IngredientSpecificity;
use App\Models\UsdaFoundationFood;
use App\Services\FoodDataProviders\FoodDataProviderResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('routes generic ingredients to USDA provider', function (): void {
    UsdaFoundationFood::factory()->create([
        'id' => 12345,
        'description' => 'Chicken breast',
        'nutrients' => [
            ['nutrient' => ['number' => '208'], 'amount' => 165],
            ['nutrient' => ['number' => '203'], 'amount' => 31],
        ],
    ]);

    $resolver = app(FoodDataProviderResolver::class);
    $results = $resolver->searchWithSpecificity('chicken breast', IngredientSpecificity::Generic);

    expect($results)->toBeArray()
        ->and($results)->toHaveCount(1)
        ->and($results[0]['source'])->toBe('usda');
});

it('routes specific ingredients to USDA provider', function (): void {
    UsdaFoundationFood::factory()->create([
        'id' => 12345,
        'description' => 'Hazelnut spread',
        'nutrients' => [
            ['nutrient' => ['number' => '208'], 'amount' => 539],
            ['nutrient' => ['number' => '203'], 'amount' => 6.3],
            ['nutrient' => ['number' => '205'], 'amount' => 57.5],
            ['nutrient' => ['number' => '204'], 'amount' => 30.9],
        ],
    ]);

    $resolver = app(FoodDataProviderResolver::class);
    $results = $resolver->searchWithSpecificity('hazelnut spread', IngredientSpecificity::Specific);

    expect($results)->toBeArray()
        ->and($results[0]['source'])->toBe('usda');
});

it('returns results from USDA search', function (): void {
    UsdaFoundationFood::factory()->create([
        'id' => 12345,
        'description' => 'Test product',
        'nutrients' => [
            ['nutrient' => ['number' => '208'], 'amount' => 200],
            ['nutrient' => ['number' => '203'], 'amount' => 10],
            ['nutrient' => ['number' => '205'], 'amount' => 30],
            ['nutrient' => ['number' => '204'], 'amount' => 5],
        ],
    ]);

    $resolver = app(FoodDataProviderResolver::class);
    $results = $resolver->searchWithSpecificity('product', IngredientSpecificity::Specific);

    expect($results)->toBeArray()
        ->and($results[0]['source'])->toBe('usda');
});

it('routes all ingredients to USDA provider', function (): void {
    UsdaFoundationFood::factory()->create([
        'id' => 12345,
        'description' => 'Brand product',
        'nutrients' => [
            ['nutrient' => ['number' => '208'], 'amount' => 200],
            ['nutrient' => ['number' => '203'], 'amount' => 10],
            ['nutrient' => ['number' => '205'], 'amount' => 30],
            ['nutrient' => ['number' => '204'], 'amount' => 5],
        ],
    ]);

    $resolver = app(FoodDataProviderResolver::class);
    $results = $resolver->searchWithSpecificity('brand product', IngredientSpecificity::Specific);

    expect($results)->toBeArray()
        ->and($results[0]['source'])->toBe('usda');
});

it('delegates search without specificity to USDA provider', function (): void {
    UsdaFoundationFood::factory()->create([
        'id' => 12345,
        'description' => 'Test food',
        'nutrients' => [
            ['nutrient' => ['number' => '208'], 'amount' => 150],
            ['nutrient' => ['number' => '203'], 'amount' => 8],
            ['nutrient' => ['number' => '205'], 'amount' => 20],
            ['nutrient' => ['number' => '204'], 'amount' => 4],
        ],
    ]);

    $resolver = app(FoodDataProviderResolver::class);
    $results = $resolver->search('test food');

    expect($results)->toBeArray()
        ->and($results[0]['source'])->toBe('usda');
});

it('returns null for non-numeric IDs', function (): void {
    $resolver = app(FoodDataProviderResolver::class);
    $nutrition = $resolver->getNutritionData('123456abc');

    expect($nutrition)->toBeNull();
});

it('returns empty array when USDA returns no results', function (): void {
    $resolver = app(FoodDataProviderResolver::class);
    $results = $resolver->searchWithSpecificity('nonexistent', IngredientSpecificity::Generic);

    expect($results)->toBeArray()
        ->and($results)->toBeEmpty();
});

it('delegates to correct provider based on specificity', function (): void {
    UsdaFoundationFood::factory()->create([
        'id' => 12345,
        'description' => 'Test Food',
        'nutrients' => [
            ['nutrient' => ['number' => '208'], 'amount' => 100],
            ['nutrient' => ['number' => '203'], 'amount' => 10],
            ['nutrient' => ['number' => '205'], 'amount' => 20],
            ['nutrient' => ['number' => '204'], 'amount' => 5],
        ],
    ]);

    $resolver = app(FoodDataProviderResolver::class);

    // Generic should use USDA
    $results = $resolver->searchWithSpecificity('test', IngredientSpecificity::Generic);
    expect($results)->toBeArray();
});

it('gets nutrition data from USDA for numeric IDs', function (): void {
    UsdaFoundationFood::factory()->create([
        'id' => 12345,
        'description' => 'Chicken breast',
        'nutrients' => [
            ['nutrient' => ['number' => '208'], 'amount' => 165],
            ['nutrient' => ['number' => '203'], 'amount' => 31],
            ['nutrient' => ['number' => '205'], 'amount' => 0],
            ['nutrient' => ['number' => '204'], 'amount' => 3.6],
        ],
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
