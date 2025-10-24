<?php

declare(strict_types=1);

use App\Enums\MealPlanType;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

it('belongs to a user', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    expect($mealPlan->user)->toBeInstanceOf(User::class);
    expect($mealPlan->user_id)->toBe($user->id);
});

it('has many meals', function (): void {
    $mealPlan = MealPlan::factory()->create();
    Meal::factory()->count(5)->for($mealPlan)->create();

    expect($mealPlan->meals)->toHaveCount(5);
    expect($mealPlan->meals->first())->toBeInstanceOf(Meal::class);
});

it('casts type to MealPlanType enum', function (): void {
    $mealPlan = MealPlan::factory()->create(['type' => 'weekly']);

    expect($mealPlan->type)->toBeInstanceOf(MealPlanType::class);
    expect($mealPlan->type)->toBe(MealPlanType::Weekly);
});

it('casts macronutrient_ratios to array', function (): void {
    $ratios = ['protein' => 30, 'carbs' => 40, 'fat' => 30];
    $mealPlan = MealPlan::factory()->create(['macronutrient_ratios' => $ratios]);

    expect($mealPlan->macronutrient_ratios)->toBe($ratios);
    expect($mealPlan->macronutrient_ratios)->toBeArray();
});

it('casts metadata to array', function (): void {
    $metadata = ['bmi' => 22.5, 'bmr' => 1600];
    $mealPlan = MealPlan::factory()->create(['metadata' => $metadata]);

    expect($mealPlan->metadata)->toBe($metadata);
    expect($mealPlan->metadata)->toBeArray();
});

it('can get meals for a specific day', function (): void {
    $mealPlan = MealPlan::factory()->weekly()->create();

    Meal::factory()->for($mealPlan)->forDay(1)->count(3)->create();
    Meal::factory()->for($mealPlan)->forDay(2)->count(3)->create();
    Meal::factory()->for($mealPlan)->forDay(3)->count(3)->create();

    $day1Meals = $mealPlan->mealsForDay(1);
    $day2Meals = $mealPlan->mealsForDay(2);

    expect($day1Meals)->toHaveCount(3);
    expect($day2Meals)->toHaveCount(3);
    expect($day1Meals->first()->day_number)->toBe(1);
    expect($day2Meals->first()->day_number)->toBe(2);
});

it('can calculate total calories for a specific day', function (): void {
    $mealPlan = MealPlan::factory()->create();

    Meal::factory()->for($mealPlan)->forDay(1)->create(['calories' => 300]);
    Meal::factory()->for($mealPlan)->forDay(1)->create(['calories' => 500]);
    Meal::factory()->for($mealPlan)->forDay(1)->create(['calories' => 400]);
    Meal::factory()->for($mealPlan)->forDay(2)->create(['calories' => 600]);

    $day1Total = $mealPlan->totalCaloriesForDay(1);
    $day2Total = $mealPlan->totalCaloriesForDay(2);

    expect($day1Total)->toBe(1200.0);
    expect($day2Total)->toBe(600.0);
});

it('can calculate average daily calories', function (): void {
    $mealPlan = MealPlan::factory()->create(['duration_days' => 7]);

    // Create meals totaling 14000 calories across all days
    foreach (range(1, 7) as $day) {
        Meal::factory()->for($mealPlan)->forDay($day)->create(['calories' => 2000]);
    }

    $average = $mealPlan->averageDailyCalories();

    expect($average)->toBe(2000.0);
});

it('returns zero average when no meals exist', function (): void {
    $mealPlan = MealPlan::factory()->create(['duration_days' => 7]);

    expect($mealPlan->averageDailyCalories())->toBe(0.0);
});

it('can calculate macros for a specific day', function (): void {
    $mealPlan = MealPlan::factory()->create();

    Meal::factory()->for($mealPlan)->forDay(1)->create([
        'protein_grams' => 30,
        'carbs_grams' => 50,
        'fat_grams' => 20,
    ]);
    Meal::factory()->for($mealPlan)->forDay(1)->create([
        'protein_grams' => 25,
        'carbs_grams' => 40,
        'fat_grams' => 15,
    ]);

    $macros = $mealPlan->macrosForDay(1);

    expect($macros)->toBe([
        'protein' => 55.0,
        'carbs' => 90.0,
        'fat' => 35.0,
    ]);
});

it('cascades delete to meals when deleted', function (): void {
    $mealPlan = MealPlan::factory()->create();
    $meal = Meal::factory()->for($mealPlan)->create();

    assertDatabaseHas('meals', ['id' => $meal->id]);

    $mealPlan->delete();

    expect(Meal::query()->find($meal->id))->toBeNull();
});

it('can create weekly meal plan using factory state', function (): void {
    $mealPlan = MealPlan::factory()->weekly()->create();

    expect($mealPlan->type)->toBe(MealPlanType::Weekly);
    expect($mealPlan->duration_days)->toBe(7);
});

it('can create monthly meal plan using factory state', function (): void {
    $mealPlan = MealPlan::factory()->monthly()->create();

    expect($mealPlan->type)->toBe(MealPlanType::Monthly);
    expect($mealPlan->duration_days)->toBe(30);
});

it('can create custom meal plan using factory state', function (): void {
    $mealPlan = MealPlan::factory()->custom(14)->create();

    expect($mealPlan->type)->toBe(MealPlanType::Custom);
    expect($mealPlan->duration_days)->toBe(14);
});

it('orders meals by day_number and sort_order', function (): void {
    $mealPlan = MealPlan::factory()->create();

    Meal::factory()->for($mealPlan)->create(['day_number' => 2, 'sort_order' => 1]);
    Meal::factory()->for($mealPlan)->create(['day_number' => 1, 'sort_order' => 2]);
    Meal::factory()->for($mealPlan)->create(['day_number' => 1, 'sort_order' => 1]);

    $meals = $mealPlan->meals;

    expect($meals[0]->day_number)->toBe(1);
    expect($meals[0]->sort_order)->toBe(1);
    expect($meals[1]->day_number)->toBe(1);
    expect($meals[1]->sort_order)->toBe(2);
    expect($meals[2]->day_number)->toBe(2);
});
