<?php

declare(strict_types=1);

use App\Actions\VerifyIngredientNutrition;
use App\DataObjects\IngredientData;
use App\Models\UsdaFoundationFood;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelData\DataCollection;

uses(RefreshDatabase::class);

it('parses ingredients and verifies nutrition', function (): void {
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

    UsdaFoundationFood::factory()->create([
        'id' => 67890,
        'description' => 'Brown rice',
        'nutrients' => [
            ['nutrient' => ['number' => '208'], 'amount' => 112],
            ['nutrient' => ['number' => '203'], 'amount' => 2.6],
            ['nutrient' => ['number' => '205'], 'amount' => 23.5],
            ['nutrient' => ['number' => '204'], 'amount' => 0.9],
        ],
    ]);

    $ingredients = IngredientData::collect([
        ['name' => 'Chicken breast', 'quantity' => '150g', 'specificity' => 'generic'],
        ['name' => 'Brown rice', 'quantity' => '1 cup (185g)', 'specificity' => 'generic'],
        ['name' => 'Olive oil', 'quantity' => '1 tablespoon (15ml)', 'specificity' => 'generic'],
    ], DataCollection::class);

    $action = app(VerifyIngredientNutrition::class);
    $result = $action->handle($ingredients);

    expect($result->verifiedIngredients)->toHaveCount(3)
        ->and($result->totalVerified)->toBeInt()
        ->and($result->verificationSuccess)->toBeBool()
        ->and($result->verificationRate)->toBeFloat();
});

it('handles ingredients without quantities', function (): void {
    $ingredients = IngredientData::collect([
        ['name' => 'Chicken breast', 'quantity' => 'some', 'specificity' => 'generic'],
        ['name' => 'Brown rice', 'quantity' => 'a handful', 'specificity' => 'generic'],
        ['name' => 'Olive oil', 'quantity' => 'drizzle', 'specificity' => 'generic'],
    ], DataCollection::class);

    $action = app(VerifyIngredientNutrition::class);
    $result = $action->handle($ingredients);

    expect($result->verifiedIngredients)->toHaveCount(3)
        ->and($result->verifiedIngredients[0])->toHaveProperties(['name', 'quantity', 'nutritionPer100g', 'matched']);
});

it('marks verification as unsuccessful when no ingredients match', function (): void {
    $ingredients = IngredientData::collect([
        ['name' => 'Ingredient 1', 'quantity' => '100g', 'specificity' => 'generic'],
        ['name' => 'Ingredient 2', 'quantity' => '200g', 'specificity' => 'generic'],
    ], DataCollection::class);

    $action = app(VerifyIngredientNutrition::class);
    $result = $action->handle($ingredients);

    expect($result->verificationSuccess)->toBeFalse()
        ->and($result->verificationRate)->toBe(0.0);
});

it('cleans ingredient names before searching', function (): void {
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

    $ingredients = IngredientData::collect([
        ['name' => 'Fresh organic grilled chicken breast (boneless)', 'quantity' => '150g', 'specificity' => 'generic'],
    ], DataCollection::class);

    $action = app(VerifyIngredientNutrition::class);
    $result = $action->handle($ingredients);

    expect($result->verifiedIngredients[0]->matched)->toBeTrue();
});

it('handles empty ingredients text', function (): void {
    $action = app(VerifyIngredientNutrition::class);
    $result = $action->handle(IngredientData::collect([], DataCollection::class));

    expect($result->verifiedIngredients)->toBeEmpty()
        ->and($result->verificationSuccess)->toBeFalse()
        ->and($result->verificationRate)->toBe(0.0);
});

it('handles errors gracefully', function (): void {
    $ingredients = IngredientData::collect([
        ['name' => 'Chicken breast', 'quantity' => '150g', 'specificity' => 'generic'],
    ], DataCollection::class);

    $action = app(VerifyIngredientNutrition::class);
    $result = $action->handle($ingredients);

    expect($result->verifiedIngredients)->toHaveCount(1);
});
