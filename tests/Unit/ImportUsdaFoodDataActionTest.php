<?php

declare(strict_types=1);

use App\Actions\ImportUsdaFoodDataAction;
use App\DataObjects\UsdaFoodImportRowData;
use App\Services\JsonStreamReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('transforms raw json to database row format', function (): void {
    $streamReader = new JsonStreamReader;
    $action = new ImportUsdaFoodDataAction($streamReader);

    $rawData = [
        'fdcId' => 12345,
        'description' => 'Test Food',
        'foodCategory' => ['description' => 'Vegetables'],
        'publicationDate' => '11/22/2025',
        'foodNutrients' => [
            ['nutrient' => ['number' => '203'], 'amount' => 10.5],
        ],
    ];

    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('transform');
    $result = $method->invoke($action, $rawData);

    expect($result)->toBeInstanceOf(UsdaFoodImportRowData::class)
        ->and($result->id)->toBe(12345)
        ->and($result->description)->toBe('Test Food')
        ->and($result->food_category)->toBe('Vegetables')
        ->and($result->publication_date)->toBe('2025-11-22')
        ->and($result->nutrients)->toBeJson();
});

it('handles missing optional fields gracefully', function (): void {
    $streamReader = new JsonStreamReader;
    $action = new ImportUsdaFoodDataAction($streamReader);

    $rawData = [
        'fdcId' => 12345,
        'description' => 'Test Food',
        'foodNutrients' => [],
    ];

    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('transform');
    $result = $method->invoke($action, $rawData);

    expect($result->food_category)->toBeNull()
        ->and($result->publication_date)->toBeNull();
});

it('parses dates correctly', function (): void {
    $streamReader = new JsonStreamReader;
    $action = new ImportUsdaFoodDataAction($streamReader);

    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('parseDate');

    expect($method->invoke($action, '1/5/2025'))->toBe('2025-01-05')
        ->and($method->invoke($action, '12/25/2024'))->toBe('2024-12-25')
        ->and($method->invoke($action, null))->toBeNull();
});

it('imports data from file to database', function (): void {
    $testFile = storage_path('app/test-import-usda.json');

    file_put_contents($testFile, <<<'JSON'
{"FoundationFoods": [
{"fdcId": 1001, "description": "Test Food 1", "foodCategory": {"description": "Fruits"}, "publicationDate": "1/1/2025", "foodNutrients": []},
{"fdcId": 1002, "description": "Test Food 2", "foodCategory": {"description": "Vegetables"}, "publicationDate": "2/1/2025", "foodNutrients": []}
]}
JSON
    );

    $streamReader = new JsonStreamReader;
    $action = new ImportUsdaFoodDataAction($streamReader);

    $action->handle($testFile, 'usda_foundation_foods', 100);

    $count = DB::table('usda_foundation_foods')->whereIn('id', [1001, 1002])->count();
    expect($count)->toBe(2);

    $food1 = DB::table('usda_foundation_foods')->where('id', 1001)->first();
    expect($food1->description)->toBe('Test Food 1')
        ->and($food1->food_category)->toBe('Fruits')
        ->and($food1->publication_date)->toBe('2025-01-01');

    unlink($testFile);
});
