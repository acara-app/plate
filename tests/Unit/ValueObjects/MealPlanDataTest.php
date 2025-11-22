<?php

declare(strict_types=1);

use App\DataObjects\MealData;
use App\DataObjects\MealPlanData;
use App\Enums\MealPlanType;
use Spatie\LaravelData\DataCollection;

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

    $mealPlanData = MealPlanData::from($data);

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
        ->and($mealPlanData->meals)->toBeInstanceOf(DataCollection::class)
        ->and($mealPlanData->meals[0])->toBeInstanceOf(MealData::class)
        ->and($mealPlanData->metadata)->toBe(['key' => 'value']);
});

it('creates meal plan data from array with minimal fields', function (): void {
    $data = [
        'type' => 'monthly',
        'duration_days' => 30,
        'meals' => [],
    ];

    $mealPlanData = MealPlanData::from($data);

    expect($mealPlanData->type)->toBe(MealPlanType::Monthly)
        ->and($mealPlanData->name)->toBeNull()
        ->and($mealPlanData->description)->toBeNull()
        ->and($mealPlanData->durationDays)->toBe(30)
        ->and($mealPlanData->targetDailyCalories)->toBeNull()
        ->and($mealPlanData->macronutrientRatios)->toBeNull()
        ->and($mealPlanData->meals)->toHaveCount(0)
        ->and($mealPlanData->metadata)->toBeNull();
});

it('converts meal plan data to array', function (): void {
    $mealData = MealData::from([
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
    ]);

    $meals = MealData::collect([$mealData], DataCollection::class);

    $mealPlanData = new MealPlanData(
        type: MealPlanType::Custom,
        name: 'My Plan',
        description: 'Custom plan',
        durationDays: 14,
        targetDailyCalories: 1800.0,
        macronutrientRatios: ['protein' => 40, 'carbs' => 30, 'fat' => 30],
        meals: $meals,
        metadata: ['test' => 'data'],
    );

    $array = $mealPlanData->toArray();

    expect($array)->toHaveKey('type', 'custom')
        ->and($array)->toHaveKey('name', 'My Plan')
        ->and($array)->toHaveKey('description', 'Custom plan')
        ->and($array)->toHaveKey('duration_days', 14)
        ->and($array)->toHaveKey('target_daily_calories', 1800.0)
        ->and($array)->toHaveKey('macronutrient_ratios', ['protein' => 40, 'carbs' => 30, 'fat' => 30])
        ->and($array)->toHaveKey('meals')
        ->and($array['meals'])->toHaveCount(1)
        ->and($array)->toHaveKey('metadata', ['test' => 'data']);
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

    $mealPlanData = MealPlanData::from($data);

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

    $mealPlanData = MealPlanData::from($data);

    expect($mealPlanData->meals)->toHaveCount(0);
});
