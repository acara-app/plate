<?php

declare(strict_types=1);

use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;

it('returns null from macroRatiosForDay when all meals have null protein_grams', function (): void {
    $mealPlan = MealPlan::factory()->for(User::factory())->create([
        'macronutrient_ratios' => null,
    ]);

    $meals = Meal::factory()
        ->count(2)
        ->for($mealPlan)
        ->forDay(1)
        ->create(['protein_grams' => null, 'carbs_grams' => null, 'fat_grams' => null]);

    expect($mealPlan->macroRatiosForDay($meals))->toBeNull();
});

it('returns null from macroRatiosForDay when total macro calories is zero', function (): void {
    $mealPlan = MealPlan::factory()->for(User::factory())->create([
        'macronutrient_ratios' => null,
    ]);

    $meals = Meal::factory()
        ->count(2)
        ->for($mealPlan)
        ->forDay(1)
        ->create(['protein_grams' => 0, 'carbs_grams' => 0, 'fat_grams' => 0]);

    expect($mealPlan->macroRatiosForDay($meals))->toBeNull();
});
