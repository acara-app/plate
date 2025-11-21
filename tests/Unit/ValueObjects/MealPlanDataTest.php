<?php

declare(strict_types=1);

use App\DataObjects\MealData;
use App\DataObjects\MealPlanData;
use App\Enums\MealPlanType;
use App\Enums\MealType;

it('creates meal plan data from array with all fields', function (): void {
    $data = [
        'type' => 'weekly',
        'name' => 'Weight Loss Plan',
        'description' => 'A plan for weight loss',
        'duration_days' => 7,
        'target_daily_calories' => 2000.5,
        'macronutrient_ratios' => [
            'protein' => 30,
            'carbs' => 40,
            'fat' => 30,
        ],
        'meals' => [
            [
                'day_number' => 1,
                'type' => 'breakfast',
                'name' => 'Oatmeal',
                'calories' => 300.0,
                'sort_order' => 1,
            ],
        ],
        'metadata' => ['key' => 'value'],
    ];

    $mealPlanData = MealPlanData::fromArray($data);

    expect($mealPlanData->type)->toBe(MealPlanType::Weekly)
        ->and($mealPlanData->name)->toBe('Weight Loss Plan')
        ->and($mealPlanData->description)->toBe('A plan for weight loss')
        ->and($mealPlanData->durationDays)->toBe(7)
        ->and($mealPlanData->targetDailyCalories)->toBe(2000.5)
        ->and($mealPlanData->macronutrientRatios)->toBe([
            'protein' => 30,
            'carbs' => 40,
            'fat' => 30,
        ])
        ->and($mealPlanData->meals)->toHaveCount(1)
        ->and($mealPlanData->meals[0])->toBeInstanceOf(MealData::class)
        ->and($mealPlanData->metadata)->toBe(['key' => 'value']);
});

it('creates meal plan data from array with minimal fields', function (): void {
    $data = [
        'type' => 'monthly',
        'duration_days' => 30,
        'meals' => [],
    ];

    $mealPlanData = MealPlanData::fromArray($data);

    expect($mealPlanData->type)->toBe(MealPlanType::Monthly)
        ->and($mealPlanData->name)->toBeNull()
        ->and($mealPlanData->description)->toBeNull()
        ->and($mealPlanData->durationDays)->toBe(30)
        ->and($mealPlanData->targetDailyCalories)->toBeNull()
        ->and($mealPlanData->macronutrientRatios)->toBeNull()
        ->and($mealPlanData->meals)->toBe([])
        ->and($mealPlanData->metadata)->toBeNull();
});

it('converts meal plan data to array', function (): void {
    $mealData = new MealData(
        dayNumber: 1,
        type: MealType::Breakfast,
        name: 'Eggs',
        description: null,
        preparationInstructions: null,
        ingredients: null,
        portionSize: null,
        calories: 200.0,
        proteinGrams: null,
        carbsGrams: null,
        fatGrams: null,
        preparationTimeMinutes: null,
        sortOrder: 1,
        metadata: null,
    );

    $mealPlanData = new MealPlanData(
        type: MealPlanType::Custom,
        name: 'My Plan',
        description: 'Custom plan',
        durationDays: 14,
        targetDailyCalories: 1800.0,
        macronutrientRatios: ['protein' => 40, 'carbs' => 30, 'fat' => 30],
        meals: [$mealData],
        metadata: ['test' => 'data'],
    );

    $array = $mealPlanData->toArray();

    expect($array)->toBe([
        'type' => 'custom',
        'name' => 'My Plan',
        'description' => 'Custom plan',
        'duration_days' => 14,
        'target_daily_calories' => 1800.0,
        'macronutrient_ratios' => ['protein' => 40, 'carbs' => 30, 'fat' => 30],
        'meals' => [
            [
                'day_number' => 1,
                'type' => 'breakfast',
                'name' => 'Eggs',
                'description' => null,
                'preparation_instructions' => null,
                'ingredients' => null,
                'portion_size' => null,
                'calories' => 200.0,
                'protein_grams' => null,
                'carbs_grams' => null,
                'fat_grams' => null,
                'preparation_time_minutes' => null,
                'sort_order' => 1,
                'metadata' => null,
                'verification_metadata' => null,
            ],
        ],
        'metadata' => ['test' => 'data'],
    ]);
});

it('handles float duration_days by converting to int', function (): void {
    $data = [
        'type' => 'weekly',
        'duration_days' => 7.5,
        'meals' => [],
    ];

    $mealPlanData = MealPlanData::fromArray($data);

    expect($mealPlanData->durationDays)->toBe(7);
});

it('handles string duration_days by converting to int', function (): void {
    $data = [
        'type' => 'monthly',
        'duration_days' => '30',
        'meals' => [],
    ];

    $mealPlanData = MealPlanData::fromArray($data);

    expect($mealPlanData->durationDays)->toBe(30);
});

it('handles int target_daily_calories by converting to float', function (): void {
    $data = [
        'type' => 'weekly',
        'duration_days' => 7,
        'target_daily_calories' => 2000,
        'meals' => [],
    ];

    $mealPlanData = MealPlanData::fromArray($data);

    expect($mealPlanData->targetDailyCalories)->toBe(2000.0);
});

it('handles string target_daily_calories by converting to float', function (): void {
    $data = [
        'type' => 'custom',
        'duration_days' => 10,
        'target_daily_calories' => '1850.5',
        'meals' => [],
    ];

    $mealPlanData = MealPlanData::fromArray($data);

    expect($mealPlanData->targetDailyCalories)->toBe(1850.5);
});

it('handles numeric name and description by converting to string', function (): void {
    $data = [
        'type' => 'weekly',
        'name' => 456,
        'description' => 789.5,
        'duration_days' => 7,
        'meals' => [],
    ];

    $mealPlanData = MealPlanData::fromArray($data);

    expect($mealPlanData->name)->toBe('456')
        ->and($mealPlanData->description)->toBe('789.5');
});

it('throws exception for invalid duration_days', function (): void {
    $data = [
        'type' => 'weekly',
        'duration_days' => 'invalid',
        'meals' => [],
    ];

    MealPlanData::fromArray($data);
})->throws(InvalidArgumentException::class, 'Value must be convertible to int');

it('throws exception for invalid target_daily_calories', function (): void {
    $data = [
        'type' => 'monthly',
        'duration_days' => 30,
        'target_daily_calories' => 'not-a-number',
        'meals' => [],
    ];

    MealPlanData::fromArray($data);
})->throws(InvalidArgumentException::class, 'Value must be convertible to float');

it('throws exception for invalid name type', function (): void {
    $data = [
        'type' => 'custom',
        'name' => ['invalid'],
        'duration_days' => 14,
        'meals' => [],
    ];

    MealPlanData::fromArray($data);
})->throws(InvalidArgumentException::class, 'Value must be convertible to string');

it('handles macronutrient_ratios as null when not array', function (): void {
    $data = [
        'type' => 'weekly',
        'duration_days' => 7,
        'macronutrient_ratios' => 'not-an-array',
        'meals' => [],
    ];

    $mealPlanData = MealPlanData::fromArray($data);

    expect($mealPlanData->macronutrientRatios)->toBeNull();
});

it('handles metadata as null when not array', function (): void {
    $data = [
        'type' => 'monthly',
        'duration_days' => 30,
        'metadata' => 'not-an-array',
        'meals' => [],
    ];

    $mealPlanData = MealPlanData::fromArray($data);

    expect($mealPlanData->metadata)->toBeNull();
});

it('creates meal plan with multiple meals', function (): void {
    $data = [
        'type' => 'weekly',
        'duration_days' => 7,
        'meals' => [
            [
                'day_number' => 1,
                'type' => 'breakfast',
                'name' => 'Meal 1',
                'calories' => 300.0,
                'sort_order' => 1,
            ],
            [
                'day_number' => 1,
                'type' => 'lunch',
                'name' => 'Meal 2',
                'calories' => 500.0,
                'sort_order' => 2,
            ],
            [
                'day_number' => 1,
                'type' => 'dinner',
                'name' => 'Meal 3',
                'calories' => 600.0,
                'sort_order' => 3,
            ],
        ],
    ];

    $mealPlanData = MealPlanData::fromArray($data);

    expect($mealPlanData->meals)->toHaveCount(3)
        ->and($mealPlanData->meals[0]->name)->toBe('Meal 1')
        ->and($mealPlanData->meals[1]->name)->toBe('Meal 2')
        ->and($mealPlanData->meals[2]->name)->toBe('Meal 3');
});

it('handles missing meals array', function (): void {
    $data = [
        'type' => 'custom',
        'duration_days' => 10,
    ];

    $mealPlanData = MealPlanData::fromArray($data);

    expect($mealPlanData->meals)->toBe([]);
});
