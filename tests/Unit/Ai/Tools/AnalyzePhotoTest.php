<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Ai\Tools\AnalyzePhoto;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

covers(AnalyzePhoto::class);

it('has the correct name', function (): void {
    $tool = new AnalyzePhoto([]);

    expect($tool->name())->toBe('analyze_photo');
});

it('returns error when no images are provided', function (): void {
    $tool = new AnalyzePhoto([]);
    $request = new Request(['query' => 'Analyze this food']);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('error')
        ->and($result['error'])->toContain('No image');
});

it('analyzes food photo and returns structured data', function (): void {
    FoodPhotoAnalyzerAgent::fake([
        [
            'items' => [
                ['name' => 'Grilled Chicken', 'calories' => 165.0, 'protein' => 31.0, 'carbs' => 0.0, 'fat' => 3.6, 'portion' => '100g'],
            ],
            'total_calories' => 165.0,
            'total_protein' => 31.0,
            'total_carbs' => 0.0,
            'total_fat' => 3.6,
            'confidence' => 85,
        ],
    ]);

    $image = new Base64Image(base64_encode('fake-image-data'), 'image/jpeg');
    $tool = new AnalyzePhoto([$image]);
    $request = new Request(['query' => 'What is this food?']);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('total_calories')
        ->and($result['total_calories'])->toBe(165)
        ->and($result)->toHaveKey('items')
        ->and($result['items'])->toHaveCount(1);
});

it('has a schema with query parameter', function (): void {
    $tool = new AnalyzePhoto([]);
    $schema = new TestJsonSchema();

    $result = $tool->schema($schema);

    expect($result)->toHaveKey('query');
});

it('has a non-empty description', function (): void {
    $tool = new AnalyzePhoto([]);

    expect($tool->description())
        ->toBeString()
        ->not->toBeEmpty()
        ->toContain('food photo')
        ->toContain('log_health_entry');
});
