<?php

declare(strict_types=1);

use App\Enums\MealType;
use App\Models\Meal;
use App\Models\MealPlan;

covers(Meal::class);

it('belongs to a meal plan', function (): void {
    $mealPlan = MealPlan::factory()->create();
    $meal = Meal::factory()->for($mealPlan)->create();

    expect($meal->mealPlan)->toBeInstanceOf(MealPlan::class)
        ->and($meal->meal_plan_id)->toBe($mealPlan->id);
});

it('casts type to MealType enum', function (): void {
    $meal = Meal::factory()->create(['type' => 'breakfast']);

    expect($meal->type)->toBeInstanceOf(MealType::class)
        ->and($meal->type)->toBe(MealType::Breakfast);
});

it('casts metadata to array', function (): void {
    $metadata = ['fiber_grams' => 5, 'sugar_grams' => 10];
    $meal = Meal::factory()->create(['metadata' => $metadata]);

    expect($meal->metadata)->toBe($metadata)
        ->and($meal->metadata)->toBeArray();
});

it('can calculate macro percentages', function (): void {
    $meal = Meal::factory()->create([
        'calories' => 400,
        'protein_grams' => 30,
        'carbs_grams' => 40,
        'fat_grams' => 10,
    ]);

    $percentages = $meal->macroPercentages();

    expect($percentages['protein'])->toBeFloat()
        ->and($percentages['carbs'])->toBeFloat()
        ->and($percentages['fat'])->toBeFloat()
        ->and($percentages['protein'])->toBeGreaterThan(0);
});

it('returns zero percentages when calories are zero', function (): void {
    $meal = Meal::factory()->create([
        'calories' => 0,
        'protein_grams' => 0,
        'carbs_grams' => 0,
        'fat_grams' => 0,
    ]);

    $percentages = $meal->macroPercentages();

    expect($percentages)->toBe(['protein' => 0, 'carbs' => 0, 'fat' => 0]);
});

it('returns zero percentages when macro cals are zero', function (): void {
    $meal = Meal::factory()->create([
        'calories' => 500,
        'protein_grams' => 0,
        'carbs_grams' => 0,
        'fat_grams' => 0,
    ]);

    $percentages = $meal->macroPercentages();

    expect($percentages)->toBe(['protein' => 0, 'carbs' => 0, 'fat' => 0]);
});

it('can check if meal meets protein requirement', function (): void {
    $highProtein = Meal::factory()->create(['protein_grams' => 30]);
    $lowProtein = Meal::factory()->create(['protein_grams' => 10]);

    expect($highProtein->meetsProteinRequirement(20))->toBeTrue()
        ->and($lowProtein->meetsProteinRequirement(20))->toBeFalse();
});

it('can get day name for weekly plans', function (): void {
    $mealPlan = MealPlan::factory()->weekly()->create();

    $mondayMeal = Meal::factory()->for($mealPlan)->forDay(1)->create();
    $tuesdayMeal = Meal::factory()->for($mealPlan)->forDay(2)->create();
    $sundayMeal = Meal::factory()->for($mealPlan)->forDay(7)->create();

    expect($mondayMeal->getDayName())->toBe('Monday')
        ->and($tuesdayMeal->getDayName())->toBe('Tuesday')
        ->and($sundayMeal->getDayName())->toBe('Sunday');
});

it('can get day name for non-weekly plans', function (): void {
    $mealPlan = MealPlan::factory()->monthly()->create();
    $meal = Meal::factory()->for($mealPlan)->forDay(15)->create();

    expect($meal->getDayName())->toBe('Day 15');
});

it('can create breakfast using factory state', function (): void {
    $meal = Meal::factory()->breakfast()->create();

    expect($meal->type)->toBe(MealType::Breakfast)
        ->and($meal->sort_order)->toBe(0);
});

it('can create lunch using factory state', function (): void {
    $meal = Meal::factory()->lunch()->create();

    expect($meal->type)->toBe(MealType::Lunch)
        ->and($meal->sort_order)->toBe(1);
});

it('can create dinner using factory state', function (): void {
    $meal = Meal::factory()->dinner()->create();

    expect($meal->type)->toBe(MealType::Dinner)
        ->and($meal->sort_order)->toBe(2);
});

it('can create snack using factory state', function (): void {
    $meal = Meal::factory()->snack()->create();

    expect($meal->type)->toBe(MealType::Snack)
        ->and($meal->sort_order)->toBe(3);
});

it('can create meal for specific day using factory state', function (): void {
    $meal = Meal::factory()->forDay(5)->create();

    expect($meal->day_number)->toBe(5);
});

it('can create high protein meal using factory state', function (): void {
    $meal = Meal::factory()->highProtein()->create();

    expect($meal->protein_grams)->toBeGreaterThanOrEqual(40);
});

it('can create low carb meal using factory state', function (): void {
    $meal = Meal::factory()->lowCarb()->create();

    expect($meal->carbs_grams)->toBeLessThanOrEqual(25);
});
