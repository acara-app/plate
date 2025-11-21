<?php

declare(strict_types=1);

use App\Actions\CorrectMealNutrition;
use App\DataObjects\MealData;
use App\Enums\MealType;

it('keeps AI estimates when verification fails', function (): void {
    $mealData = new MealData(
        dayNumber: 1,
        type: MealType::Breakfast,
        name: 'Test Meal',
        description: 'Test description',
        preparationInstructions: 'Test instructions',
        ingredients: [['name' => 'Test ingredient', 'quantity' => '100g']],
        portionSize: '1 serving',
        calories: 500.0,
        proteinGrams: 30.0,
        carbsGrams: 50.0,
        fatGrams: 15.0,
        preparationTimeMinutes: 10,
        sortOrder: 1,
    );

    $verificationData = [
        'verified_ingredients' => [],
        'total_verified' => null,
        'verification_success' => false,
        'verification_rate' => 0.0,
    ];

    $action = app(CorrectMealNutrition::class);
    $result = $action->handle($mealData, $verificationData);

    expect($result)
        ->calories->toBe(500.0)
        ->proteinGrams->toBe(30.0)
        ->carbsGrams->toBe(50.0)
        ->fatGrams->toBe(15.0)
        ->verificationMetadata->toBeArray();

    expect($result->verificationMetadata['verified'])->toBeFalse();
    expect($result->verificationMetadata['confidence'])->toBe('low');
});

it('applies corrections when discrepancy exceeds threshold', function (): void {
    $mealData = new MealData(
        dayNumber: 1,
        type: MealType::Lunch,
        name: 'Test Meal',
        description: 'Test description',
        preparationInstructions: 'Test instructions',
        ingredients: [['name' => 'Chicken breast', 'quantity' => '150g']],
        portionSize: '1 serving',
        calories: 400.0,
        proteinGrams: 25.0,
        carbsGrams: 40.0,
        fatGrams: 10.0,
        preparationTimeMinutes: 15,
        sortOrder: 2,
    );

    $verificationData = [
        'verified_ingredients' => [
            [
                'name' => 'Chicken breast',
                'quantity' => '150g',
                'nutrition_per_100g' => [
                    'calories' => 165.0,
                    'protein' => 31.0,
                    'carbs' => 0.0,
                    'fat' => 3.6,
                ],
                'matched' => true,
            ],
        ],
        'total_verified' => null,
        'verification_success' => true,
        'verification_rate' => 1.0,
    ];

    $action = app(CorrectMealNutrition::class);
    $result = $action->handle($mealData, $verificationData);

    expect($result)
        ->verificationMetadata->toBeArray();

    expect($result->verificationMetadata['verified'])->toBeTrue();
    expect($result->verificationMetadata['confidence'])->toBe('high');
    expect($result->verificationMetadata['source'])->toBe('openfoodfacts_verified');
    expect($result->verificationMetadata)->toHaveKey('original_ai_values');
    expect($result->verificationMetadata)->toHaveKey('verified_values');

    // Calories should be adjusted (not exactly AI or verified, but weighted)
    expect($result->calories)->not->toBe(400.0);
});

it('keeps AI estimates when discrepancy is below threshold', function (): void {
    $mealData = new MealData(
        dayNumber: 1,
        type: MealType::Dinner,
        name: 'Test Meal',
        description: 'Test description',
        preparationInstructions: 'Test instructions',
        ingredients: [['name' => 'Salmon', 'quantity' => '150g']],
        portionSize: '1 serving',
        calories: 300.0,
        proteinGrams: 30.0,
        carbsGrams: 0.0,
        fatGrams: 15.0,
        preparationTimeMinutes: 20,
        sortOrder: 3,
    );

    $verificationData = [
        'verified_ingredients' => [
            [
                'name' => 'Salmon',
                'quantity' => '150g',
                'nutrition_per_100g' => [
                    'calories' => 206.0, // Close to AI estimate (300/1.5 = 200)
                    'protein' => 22.0,
                    'carbs' => 0.0,
                    'fat' => 13.0,
                ],
                'matched' => true,
            ],
        ],
        'total_verified' => null,
        'verification_success' => true,
        'verification_rate' => 1.0,
    ];

    $action = app(CorrectMealNutrition::class);
    $result = $action->handle($mealData, $verificationData);

    expect($result)
        ->verificationMetadata->toBeArray();

    expect($result->verificationMetadata['verified'])->toBeTrue();
});

it('handles verification with no matched ingredients', function (): void {
    $mealData = new MealData(
        dayNumber: 1,
        type: MealType::Snack,
        name: 'Test Meal',
        description: 'Test description',
        preparationInstructions: 'Test instructions',
        ingredients: [['name' => 'Unknown ingredient', 'quantity' => '100g']],
        portionSize: '1 serving',
        calories: 200.0,
        proteinGrams: 10.0,
        carbsGrams: 20.0,
        fatGrams: 5.0,
        preparationTimeMinutes: 5,
        sortOrder: 4,
    );

    $verificationData = [
        'verified_ingredients' => [
            [
                'name' => 'Unknown ingredient',
                'quantity' => null,
                'nutrition_per_100g' => null,
                'matched' => false,
            ],
        ],
        'total_verified' => null,
        'verification_success' => false,
        'verification_rate' => 0.0,
    ];

    $action = app(CorrectMealNutrition::class);
    $result = $action->handle($mealData, $verificationData);

    expect($result)
        ->calories->toBe(200.0)
        ->proteinGrams->toBe(10.0)
        ->carbsGrams->toBe(20.0)
        ->fatGrams->toBe(5.0)
        ->verificationMetadata->toBeArray();

    expect($result->verificationMetadata['verified'])->toBeFalse();
});

it('handles medium confidence verification', function (): void {
    $mealData = new MealData(
        dayNumber: 1,
        type: MealType::Lunch,
        name: 'Test Meal',
        description: 'Test description',
        preparationInstructions: 'Test instructions',
        ingredients: [['name' => 'Mixed ingredients', 'quantity' => '200g']],
        portionSize: '1 serving',
        calories: 350.0,
        proteinGrams: 20.0,
        carbsGrams: 30.0,
        fatGrams: 12.0,
        preparationTimeMinutes: 15,
        sortOrder: 2,
    );

    $verificationData = [
        'verified_ingredients' => [
            [
                'name' => 'Mixed ingredients',
                'quantity' => '200g',
                'nutrition_per_100g' => [
                    'calories' => 150.0,
                    'protein' => 15.0,
                    'carbs' => 20.0,
                    'fat' => 8.0,
                ],
                'matched' => true,
            ],
        ],
        'total_verified' => null,
        'verification_success' => true,
        'verification_rate' => 0.6,
    ];

    $action = app(CorrectMealNutrition::class);
    $result = $action->handle($mealData, $verificationData);

    expect($result->verificationMetadata)
        ->toBeArray()
        ->toHaveKey('confidence');
});

it('handles matched ingredients with null nutrition data', function (): void {
    $mealData = new MealData(
        dayNumber: 1,
        type: MealType::Snack,
        name: 'Test Meal',
        description: 'Test description',
        preparationInstructions: 'Test instructions',
        ingredients: [['name' => 'Unknown branded item', 'quantity' => '1 pack']],
        portionSize: '1 serving',
        calories: 150.0,
        proteinGrams: 5.0,
        carbsGrams: 20.0,
        fatGrams: 6.0,
        preparationTimeMinutes: 5,
        sortOrder: 4,
    );

    $verificationData = [
        'verified_ingredients' => [
            [
                'name' => 'Unknown branded item',
                'quantity' => '1 pack',
                'nutrition_per_100g' => null, // Matched but no nutrition data
                'matched' => true,
            ],
        ],
        'total_verified' => null,
        'verification_success' => true,
        'verification_rate' => 1.0,
    ];

    $action = app(CorrectMealNutrition::class);
    $result = $action->handle($mealData, $verificationData);

    expect($result)
        ->calories->toBe(150.0)
        ->proteinGrams->toBe(5.0)
        ->verificationMetadata->toBeArray();

    expect($result->verificationMetadata['verified'])->toBeFalse();
    expect($result->verificationMetadata['confidence'])->toBe('medium');
    expect($result->verificationMetadata['note'])->toBe('Ingredients matched but nutrition data incomplete');
});
