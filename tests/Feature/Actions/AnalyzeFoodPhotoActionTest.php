<?php

declare(strict_types=1);

use App\Actions\AnalyzeFoodPhotoAction;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Enums\FoodValueProvenance;
use App\Models\ReferenceFood;

function fakePhotoAnalysis(array $items, array $totals): void
{
    FoodPhotoAnalyzerAgent::fake([[
        'items' => $items,
        'total_calories' => $totals['calories'] ?? 0,
        'total_protein' => $totals['protein'] ?? 0,
        'total_carbs' => $totals['carbs'] ?? 0,
        'total_fat' => $totals['fat'] ?? 0,
        'confidence' => 80,
    ]]);
}

function seedActionReferenceFood(string $description, array $macros): ReferenceFood
{
    return ReferenceFood::factory()->create([
        'description' => $description,
        'match_name' => ReferenceFood::normalizeName($description),
        ...$macros,
    ]);
}

beforeEach(function (): void {
    $this->action = app(AnalyzeFoodPhotoAction::class);
});

it('replaces matched item macros with computed reference values when enabled', function (): void {
    config()->set('plate.food_photo_analyzer.reference_lookup.enabled', true);

    seedActionReferenceFood('Hummus, commercial', [
        'calories_per_100g' => 166,
        'protein_per_100g' => 7.9,
        'carbs_per_100g' => 14.3,
        'fat_per_100g' => 9.6,
        'release' => 'USDA Foundation 2026-04-30',
    ]);

    fakePhotoAnalysis([
        ['name' => 'Hummus', 'calories' => 200.0, 'protein' => 5.0, 'carbs' => 10.0, 'fat' => 15.0, 'portion' => '100g', 'grams' => 100.0, 'match_name' => 'hummus commercial'],
        ['name' => 'Pita', 'calories' => 250.0, 'protein' => 8.0, 'carbs' => 50.0, 'fat' => 2.0, 'portion' => '80g', 'grams' => 80.0, 'match_name' => 'pita bread'],
    ], ['calories' => 450, 'protein' => 13, 'carbs' => 60, 'fat' => 17]);

    $result = $this->action->handle(base64_encode('img'), 'image/jpeg');

    $hummus = $result->items->first();
    $pita = $result->items->last();

    expect($hummus->provenance)->toBe(FoodValueProvenance::Reference)
        ->and($hummus->calories)->toBe(166.0)
        ->and($hummus->carbs)->toBe(14.3)
        ->and($pita->provenance)->toBe(FoodValueProvenance::Model)
        ->and($pita->calories)->toBe(250.0)
        ->and($result->totalCalories)->toBe(416.0)
        ->and($result->totalCarbs)->toBe(64.3)
        ->and($result->referenceRelease)->toBe('USDA Foundation 2026-04-30');
});

it('leaves model values untouched when the lookup is disabled', function (): void {
    config()->set('plate.food_photo_analyzer.reference_lookup.enabled', false);

    seedActionReferenceFood('Hummus, commercial', [
        'calories_per_100g' => 166, 'protein_per_100g' => 7.9, 'carbs_per_100g' => 14.3, 'fat_per_100g' => 9.6,
    ]);

    fakePhotoAnalysis([
        ['name' => 'Hummus', 'calories' => 200.0, 'protein' => 5.0, 'carbs' => 10.0, 'fat' => 15.0, 'portion' => '100g', 'grams' => 100.0, 'match_name' => 'hummus commercial'],
    ], ['calories' => 200, 'protein' => 5, 'carbs' => 10, 'fat' => 15]);

    $result = $this->action->handle(base64_encode('img'), 'image/jpeg');

    expect($result->items->first()->provenance)->toBe(FoodValueProvenance::Model)
        ->and($result->items->first()->calories)->toBe(200.0)
        ->and($result->referenceRelease)->toBeNull()
        ->and($result->totalCalories)->toBe(200.0);
});

it('returns the model analysis untouched when nothing matches', function (): void {
    config()->set('plate.food_photo_analyzer.reference_lookup.enabled', true);

    fakePhotoAnalysis([
        ['name' => 'Hummus', 'calories' => 200.0, 'protein' => 5.0, 'carbs' => 10.0, 'fat' => 15.0, 'portion' => '100g', 'grams' => 100.0, 'match_name' => 'hummus commercial'],
    ], ['calories' => 999, 'protein' => 5, 'carbs' => 10, 'fat' => 15]);

    $result = $this->action->handle(base64_encode('img'), 'image/jpeg');

    expect($result->items->first()->provenance)->toBe(FoodValueProvenance::Model)
        ->and($result->referenceRelease)->toBeNull()
        ->and($result->totalCalories)->toBe(999.0);
});

it('keeps model values when a matched item has no gram weight', function (): void {
    config()->set('plate.food_photo_analyzer.reference_lookup.enabled', true);

    seedActionReferenceFood('Hummus, commercial', [
        'calories_per_100g' => 166, 'protein_per_100g' => 7.9, 'carbs_per_100g' => 14.3, 'fat_per_100g' => 9.6,
    ]);

    fakePhotoAnalysis([
        ['name' => 'Hummus', 'calories' => 200.0, 'protein' => 5.0, 'carbs' => 10.0, 'fat' => 15.0, 'portion' => '100g', 'match_name' => 'hummus commercial'],
    ], ['calories' => 200, 'protein' => 5, 'carbs' => 10, 'fat' => 15]);

    $result = $this->action->handle(base64_encode('img'), 'image/jpeg');

    expect($result->items->first()->provenance)->toBe(FoodValueProvenance::Model)
        ->and($result->items->first()->calories)->toBe(200.0)
        ->and($result->referenceRelease)->toBeNull();
});
