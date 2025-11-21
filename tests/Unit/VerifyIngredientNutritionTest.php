<?php

declare(strict_types=1);

use App\Actions\VerifyIngredientNutrition;
use Illuminate\Support\Facades\Http;

it('parses ingredients and verifies nutrition', function (): void {
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

    $ingredients = [
        ['name' => 'Chicken breast', 'quantity' => '150g'],
        ['name' => 'Brown rice', 'quantity' => '1 cup (185g)'],
        ['name' => 'Olive oil', 'quantity' => '1 tablespoon (15ml)'],
    ];

    $action = app(VerifyIngredientNutrition::class);
    $result = $action->handle($ingredients);

    expect($result)
        ->toBeArray()
        ->toHaveKeys(['verified_ingredients', 'total_verified', 'verification_success', 'verification_rate'])
        ->and($result['verified_ingredients'])->toHaveCount(3)
        ->and($result['verification_rate'])->toBeGreaterThan(0);
});

it('handles ingredients without quantities', function (): void {
    Http::fake([
        '*' => Http::response(['products' => []], 200),
    ]);

    $ingredients = [
        ['name' => 'Chicken breast', 'quantity' => 'some'],
        ['name' => 'Brown rice', 'quantity' => 'a handful'],
        ['name' => 'Olive oil', 'quantity' => 'drizzle'],
    ];

    $action = app(VerifyIngredientNutrition::class);
    $result = $action->handle($ingredients);

    expect($result)
        ->toBeArray()
        ->and($result['verified_ingredients'])->toHaveCount(3)
        ->and($result['verified_ingredients'][0])->toHaveKeys(['name', 'quantity', 'nutrition_per_100g', 'matched']);
});

it('marks verification as unsuccessful when no ingredients match', function (): void {
    Http::fake([
        '*' => Http::response(['products' => []], 200),
    ]);

    $ingredients = [
        ['name' => 'Ingredient 1', 'quantity' => '100g'],
        ['name' => 'Ingredient 2', 'quantity' => '200g'],
    ];

    $action = app(VerifyIngredientNutrition::class);
    $result = $action->handle($ingredients);

    expect($result['verification_success'])->toBeFalse()
        ->and($result['verification_rate'])->toBe(0);
});

it('cleans ingredient names before searching', function (): void {
    Http::fake([
        '*' => Http::response([
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

    $ingredients = [
        ['name' => 'Fresh organic grilled chicken breast (boneless)', 'quantity' => '150g'],
    ];

    $action = app(VerifyIngredientNutrition::class);
    $result = $action->handle($ingredients);

    expect($result['verified_ingredients'][0]['matched'])->toBeTrue();
});

it('handles empty ingredients text', function (): void {
    $action = app(VerifyIngredientNutrition::class);
    $result = $action->handle([]);

    expect($result)
        ->toBeArray()
        ->and($result['verified_ingredients'])->toBeEmpty()
        ->and($result['verification_success'])->toBeFalse()
        ->and($result['verification_rate'])->toBe(0.0);
});

it('handles API errors gracefully', function (): void {
    Http::fake([
        '*' => Http::response(null, 500),
    ]);

    $ingredients = [
        ['name' => 'Chicken breast', 'quantity' => '150g'],
    ];

    $action = app(VerifyIngredientNutrition::class);
    $result = $action->handle($ingredients);

    expect($result)
        ->toBeArray()
        ->and($result['verified_ingredients'])->toHaveCount(1)
        ->and($result['verified_ingredients'][0]['matched'])->toBeFalse();
});
