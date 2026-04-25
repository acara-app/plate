<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Spatie\LaravelData\Exceptions\CannotCreateData;

covers(FoodPhotoAnalyzerAgent::class);

beforeEach(function (): void {
    $this->agent = new FoodPhotoAnalyzerAgent;
});

it('returns instructions with food analysis guidance', function (): void {
    $instructions = $this->agent->instructions();

    expect($instructions)
        ->toContain('nutritionist')
        ->toContain('food recognition')
        ->toContain('calories')
        ->toContain('protein')
        ->toContain('carbs')
        ->toContain('fat')
        ->toContain('portion');
});

it('omits language directive when language is not set', function (): void {
    $instructions = $this->agent->instructions();

    expect($instructions)
        ->not->toContain('Return all `name` and `portion` values in')
        ->not->toContain('language code:');
});

it('appends language directive to instructions when language is set', function (): void {
    $this->agent->withLanguage('English', 'en');

    $instructions = $this->agent->instructions();

    expect($instructions)
        ->toContain('Return all `name` and `portion` values in English')
        ->toContain('language code: `en`')
        ->toContain('Numeric fields and JSON keys stay as-is')
        ->toContain('do not transliterate from English');
});

it('has correct attributes configured', function (): void {
    $reflection = new ReflectionClass($this->agent);

    $maxTokens = $reflection->getAttributes(MaxTokens::class);
    $timeout = $reflection->getAttributes(Timeout::class);

    expect($maxTokens)->toHaveCount(1)
        ->and($maxTokens[0]->newInstance()->value)->toBe(35000)
        ->and($timeout)->toHaveCount(1)
        ->and($timeout[0]->newInstance()->value)->toBe(120);
});

it('analyzes food photo and returns analysis data', function (): void {
    $mockResponse = [
        'items' => [
            ['name' => 'Grilled Chicken', 'calories' => 165.0, 'protein' => 31.0, 'carbs' => 0.0, 'fat' => 3.6, 'portion' => '100g'],
        ],
        'total_calories' => 165.0,
        'total_protein' => 31.0,
        'total_carbs' => 0.0,
        'total_fat' => 3.6,
        'confidence' => 85.0,
    ];

    FoodPhotoAnalyzerAgent::fake([$mockResponse]);

    $imageBase64 = base64_encode('fake-image-data');
    $result = $this->agent->analyze($imageBase64, 'image/jpeg');

    expect($result->totalCalories)->toBe(165.0)
        ->and($result->totalProtein)->toBe(31.0)
        ->and($result->totalCarbs)->toBe(0.0)
        ->and($result->totalFat)->toBe(3.6)
        ->and($result->confidence)->toBe(85)
        ->and($result->items)->toHaveCount(1)
        ->and($result->items->first()->name)->toBe('Grilled Chicken');
});

it('analyzes food photo with multiple items', function (): void {
    $mockResponse = [
        'items' => [
            ['name' => 'Rice', 'calories' => 130.0, 'protein' => 2.7, 'carbs' => 28.0, 'fat' => 0.3, 'portion' => '100g'],
            ['name' => 'Chicken', 'calories' => 165.0, 'protein' => 31.0, 'carbs' => 0.0, 'fat' => 3.6, 'portion' => '100g'],
        ],
        'total_calories' => 295.0,
        'total_protein' => 33.7,
        'total_carbs' => 28.0,
        'total_fat' => 3.9,
        'confidence' => 90.0,
    ];

    FoodPhotoAnalyzerAgent::fake([$mockResponse]);

    $imageBase64 = base64_encode('fake-image-data');
    $result = $this->agent->analyze($imageBase64, 'image/png');

    expect($result->totalCalories)->toBe(295.0)
        ->and($result->items)->toHaveCount(2)
        ->and($result->items->first()->name)->toBe('Rice')
        ->and($result->items->last()->name)->toBe('Chicken');
});

it('handles empty food detection', function (): void {
    $mockResponse = [
        'items' => [],
        'total_calories' => 0,
        'total_protein' => 0,
        'total_carbs' => 0,
        'total_fat' => 0,
        'confidence' => 0.0,
    ];

    FoodPhotoAnalyzerAgent::fake([$mockResponse]);

    $imageBase64 = base64_encode('fake-image-data');
    $result = $this->agent->analyze($imageBase64, 'image/jpeg');

    expect($result->totalCalories)->toBe(0.0)
        ->and($result->confidence)->toBe(0)
        ->and($result->items)->toHaveCount(0);
});

it('throws exception when structured data is empty', function (): void {
    FoodPhotoAnalyzerAgent::fake([[]]);

    $imageBase64 = base64_encode('fake-image-data');

    $this->agent->analyze($imageBase64, 'image/jpeg');
})->throws(CannotCreateData::class);
