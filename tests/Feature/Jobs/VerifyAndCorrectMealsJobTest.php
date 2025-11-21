<?php

declare(strict_types=1);

use App\Actions\CorrectMealNutrition;
use App\Actions\VerifyIngredientNutrition;
use App\Enums\MealPlanType;
use App\Enums\MealType;
use App\Jobs\VerifyAndCorrectMealsJob;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

it('dispatches verify and correct meals job', function (): void {
    Queue::fake();

    $mealPlan = MealPlan::factory()->create();

    dispatch(new VerifyAndCorrectMealsJob($mealPlan->id));

    Queue::assertPushed(VerifyAndCorrectMealsJob::class, fn ($job): bool => $job->mealPlanId === $mealPlan->id);
});

it('verifies and corrects meals when job is processed', function (): void {
    Http::fake([
        'world.openfoodfacts.org/api/v2/search*' => Http::response([
            'products' => [
                [
                    'code' => '123456',
                    'product_name' => 'Test Food',
                    'nutriments' => [
                        'energy-kcal_100g' => 150,
                        'proteins_100g' => 20,
                        'carbohydrates_100g' => 25,
                        'fat_100g' => 3,
                    ],
                ],
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create([
        'user_id' => $user->id,
        'type' => MealPlanType::Weekly,
    ]);

    Meal::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'type' => MealType::Breakfast,
        'ingredients' => [
            ['name' => 'Test ingredient', 'quantity' => '150g'],
        ],
        'calories' => 300.0,
        'protein_grams' => 30.0,
        'carbs_grams' => 40.0,
        'fat_grams' => 5.0,
        'openfoodfacts_verification' => null,
    ]);

    $job = new VerifyAndCorrectMealsJob($mealPlan->id);
    $job->handle(
        app(VerifyIngredientNutrition::class),
        app(CorrectMealNutrition::class)
    );

    $meal = $mealPlan->meals()->first();
    expect($meal->openfoodfacts_verification)->not->toBeNull();
});

it('skips meals that are already verified', function (): void {
    Http::fake();

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create([
        'user_id' => $user->id,
        'type' => MealPlanType::Weekly,
    ]);

    $meal = Meal::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'type' => MealType::Breakfast,
        'ingredients' => [
            ['name' => 'Chicken breast', 'quantity' => '150g'],
        ],
        'calories' => 300.0,
        'protein_grams' => 30.0,
        'carbs_grams' => 40.0,
        'fat_grams' => 5.0,
        'openfoodfacts_verification' => [
            'verified' => true,
            'verification_rate' => 1.0,
        ],
    ]);

    $originalCalories = $meal->calories;

    $job = new VerifyAndCorrectMealsJob($mealPlan->id);
    $job->handle(
        app(VerifyIngredientNutrition::class),
        app(CorrectMealNutrition::class)
    );

    $meal->refresh();
    expect($meal->calories)->toBe($originalCalories);
});

it('skips meals without ingredients', function (): void {
    Http::fake();

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create([
        'user_id' => $user->id,
        'type' => MealPlanType::Weekly,
    ]);

    $meal = Meal::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'type' => MealType::Breakfast,
        'ingredients' => [],
        'calories' => 300.0,
        'protein_grams' => 30.0,
        'carbs_grams' => 40.0,
        'fat_grams' => 5.0,
        'openfoodfacts_verification' => null,
    ]);

    $job = new VerifyAndCorrectMealsJob($mealPlan->id);
    $job->handle(
        app(VerifyIngredientNutrition::class),
        app(CorrectMealNutrition::class)
    );

    $meal->refresh();
    expect($meal->openfoodfacts_verification)->toBeNull();
});

it('handles missing meal plan gracefully', function (): void {
    $job = new VerifyAndCorrectMealsJob(99999);
    $job->handle(
        app(VerifyIngredientNutrition::class),
        app(CorrectMealNutrition::class)
    );

    // Should not throw an exception
    expect(true)->toBeTrue();
});

it('handles empty meal plan gracefully', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create([
        'user_id' => $user->id,
        'type' => MealPlanType::Weekly,
    ]);

    $job = new VerifyAndCorrectMealsJob($mealPlan->id);
    $job->handle(
        app(VerifyIngredientNutrition::class),
        app(CorrectMealNutrition::class)
    );

    // Should not throw an exception
    expect(true)->toBeTrue();
});

it('has correct timeout and retry configuration', function (): void {
    $job = new VerifyAndCorrectMealsJob(1);

    expect($job->timeout)->toBe(300)
        ->and($job->tries)->toBe(3);
});
