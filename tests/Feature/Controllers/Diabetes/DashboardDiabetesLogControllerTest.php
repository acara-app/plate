<?php

declare(strict_types=1);

use App\Models\DiabetesLog;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;

it('renders diabetes log tracking dashboard', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('diabetes-log.dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('logs')
            ->has('glucoseReadingTypes')
            ->has('insulinTypes'));
});

it('displays all user diabetes logs on tracking dashboard', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    DiabetesLog::factory()->count(5)->create(['user_id' => $user->id]);
    DiabetesLog::factory()->count(3)->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->get(route('diabetes-log.dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->has('logs', 5));
});

it('includes todays meals from meal plan on dashboard', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);
    Meal::factory()->count(3)->create(['meal_plan_id' => $mealPlan->id, 'day_number' => 1]);

    $response = $this->actingAs($user)
        ->get(route('diabetes-log.dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->has('todaysMeals'));
});
