<?php

declare(strict_types=1);

use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;

it('requires authentication to view printable meal plan', function (): void {
    $mealPlan = MealPlan::factory()->create();

    $response = $this->get(route('meal-plans.print', $mealPlan));

    $response->assertRedirectToRoute('login');
});

it('requires email verification to view printable meal plan', function (): void {
    $user = User::factory()->unverified()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.print', $mealPlan));

    $response->assertRedirectToRoute('verification.notice');
});

it('forbids access to another users meal plan', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.print', $mealPlan));

    $response->assertForbidden();
});

it('renders printable meal plan for owner', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create([
        'user_id' => $user->id,
        'name' => 'My Test Meal Plan',
    ]);

    Meal::factory()->count(3)->create([
        'meal_plan_id' => $mealPlan->id,
        'day_number' => 1,
        'ingredients' => null,
    ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.print', $mealPlan));

    $response->assertOk()
        ->assertViewIs('meal-plans.print')
        ->assertSee('My Test Meal Plan')
        ->assertSee('Acara Plate');
});

it('displays all meals grouped by day', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->weekly()->create([
        'user_id' => $user->id,
    ]);

    Meal::factory()->breakfast()->create([
        'meal_plan_id' => $mealPlan->id,
        'day_number' => 1,
        'name' => 'Day 1 Breakfast',
        'ingredients' => null,
    ]);

    Meal::factory()->lunch()->create([
        'meal_plan_id' => $mealPlan->id,
        'day_number' => 1,
        'name' => 'Day 1 Lunch',
        'ingredients' => null,
    ]);

    Meal::factory()->dinner()->create([
        'meal_plan_id' => $mealPlan->id,
        'day_number' => 2,
        'name' => 'Day 2 Dinner',
        'ingredients' => null,
    ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.print', $mealPlan));

    $response->assertOk()
        ->assertSee('Day 1 Breakfast')
        ->assertSee('Day 1 Lunch')
        ->assertSee('Day 2 Dinner')
        ->assertSee('Monday')
        ->assertSee('Tuesday');
});

it('includes semantic html for reading mode support', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create([
        'user_id' => $user->id,
        'name' => 'Semantic Test Plan',
        'description' => 'A plan to test semantic HTML',
    ]);

    Meal::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'day_number' => 1,
        'name' => 'Test Meal',
        'ingredients' => [
            ['name' => 'Chicken', 'quantity' => '200g'],
            ['name' => 'Rice', 'quantity' => '1 cup'],
        ],
    ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.print', $mealPlan));

    $response->assertOk()
        ->assertSee('itemscope')
        ->assertSee('itemtype="https://schema.org/Diet"', escape: false)
        ->assertSee('itemtype="https://schema.org/Recipe"', escape: false)
        ->assertSee('itemprop="name"', escape: false)
        ->assertSee('itemprop="recipeIngredient"', escape: false);
});

it('shows nutrition information for each meal', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);

    Meal::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'day_number' => 1,
        'calories' => 450,
        'protein_grams' => 30,
        'carbs_grams' => 45,
        'fat_grams' => 15,
        'ingredients' => null,
    ]);

    $response = $this->actingAs($user)
        ->get(route('meal-plans.print', $mealPlan));

    $response->assertOk()
        ->assertSee('450 kcal')
        ->assertSee('Protein:')
        ->assertSee('30g')
        ->assertSee('Carbs:')
        ->assertSee('45g')
        ->assertSee('Fat:')
        ->assertSee('15g');
});
