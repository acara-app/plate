<?php

declare(strict_types=1);

use App\Actions\AnalyzeFoodPhotoAction;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\DataObjects\FoodAnalysisData;

beforeEach(function (): void {
    $this->action = resolve(AnalyzeFoodPhotoAction::class);
});

it('calls the agent with image data and returns analysis result', function (): void {
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

    $imageBase64 = base64_encode('fake-image-data');
    $mimeType = 'image/jpeg';

    $result = $this->action->handle($imageBase64, $mimeType);

    expect($result)->toBeInstanceOf(FoodAnalysisData::class);
    expect($result->totalCalories)->toBe(165.0);
    expect($result->totalProtein)->toBe(31.0);
    expect($result->confidence)->toBe(85);
    expect($result->items)->toHaveCount(1);
});

it('handles multiple food items in analysis', function (): void {
    FoodPhotoAnalyzerAgent::fake([
        [
            'items' => [
                ['name' => 'Rice', 'calories' => 130.0, 'protein' => 2.7, 'carbs' => 28.0, 'fat' => 0.3, 'portion' => '100g'],
                ['name' => 'Chicken', 'calories' => 165.0, 'protein' => 31.0, 'carbs' => 0.0, 'fat' => 3.6, 'portion' => '100g'],
            ],
            'total_calories' => 295.0,
            'total_protein' => 33.7,
            'total_carbs' => 28.0,
            'total_fat' => 3.9,
            'confidence' => 90,
        ],
    ]);

    $imageBase64 = base64_encode('fake-image-data');
    $mimeType = 'image/png';

    $result = $this->action->handle($imageBase64, $mimeType);

    expect($result->items)->toHaveCount(2);
    expect($result->totalCalories)->toBe(295.0);
});
