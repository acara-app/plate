<?php

declare(strict_types=1);

use App\DataObjects\MealPlanContext\MacronutrientRatiosData;
use App\DataObjects\MealPlanContext\MealPlanContextData;
use App\Enums\BloodType;
use App\Enums\DietType;
use App\Enums\Sex;
use Carbon\Carbon;

function buildMealPlanContextData(array $overrides = []): MealPlanContextData
{
    return MealPlanContextData::from([
        'age' => 40,
        'height' => 175.0,
        'weight' => 70.0,
        'sex' => Sex::Male->value,
        'blood_type' => BloodType::APositive->value,
        'bmi' => 22.9,
        'bmr' => 1650.0,
        'tdee' => 2145.0,
        'goal' => 'Weight Loss',
        'target_weight' => 65.0,
        'additional_goals' => null,
        'dietary_preferences' => [],
        'health_conditions' => [],
        'medications' => [],
        'daily_calorie_target' => 1645.0,
        'macronutrient_ratios' => new MacronutrientRatiosData(
            protein: 30,
            carbs: 40,
            fat: 30,
        ),
        'diet_type' => DietType::Balanced->value,
        'diet_type_label' => 'Balanced',
        'diet_type_focus' => 'General wellness',
        'glucose_analysis' => null,
        ...$overrides,
    ]);
}

test('it casts date_of_birth with various ISO 8601 formats', function (string $dateString): void {
    $data = buildMealPlanContextData(['date_of_birth' => $dateString]);

    expect($data->dateOfBirth)->not->toBeNull()
        ->and($data->dateOfBirth)->toBeInstanceOf(Carbon::class)
        ->and($data->dateOfBirth->year)->toBe(1984)
        ->and($data->dateOfBirth->month)->toBe(10)
        ->and($data->dateOfBirth->day)->toBe(8);
})->with([
    'ISO 8601 with microseconds and Z' => '1984-10-08T00:00:00.000000Z',
    'ISO 8601 without microseconds and Z' => '1984-10-08T00:00:00Z',
    'ISO 8601 with offset and microseconds' => '1984-10-08T00:00:00.000000+00:00',
    'ISO 8601 with offset no microseconds' => '1984-10-08T00:00:00+00:00',
]);

test('it builds MealPlanContextData from array with date_of_birth', function (): void {
    $data = buildMealPlanContextData(['date_of_birth' => '1984-10-08T00:00:00.000000Z']);

    expect($data->dateOfBirth)->not->toBeNull()
        ->and($data->dateOfBirth)->toBeInstanceOf(Carbon::class)
        ->and($data->dateOfBirth->year)->toBe(1984)
        ->and($data->dateOfBirth->month)->toBe(10)
        ->and($data->dateOfBirth->day)->toBe(8);
});
