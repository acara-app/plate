<?php

declare(strict_types=1);

use App\Models\DiabetesLog;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;

it('requires authentication to view diabetes log index', function (): void {
    $response = $this->get(route('diabetes-log.index'));

    $response->assertRedirectToRoute('login');
});

it('requires email verification to view diabetes log index', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->get(route('diabetes-log.index'));

    $response->assertRedirectToRoute('verification.notice');
});

it('renders diabetes log index page for authenticated and verified user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('diabetes-log.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('logs')
            ->has('glucoseReadingTypes')
            ->has('insulinTypes'));
});

it('displays user diabetes logs', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Create logs for both users
    DiabetesLog::factory()->count(3)->create(['user_id' => $user->id]);
    DiabetesLog::factory()->count(2)->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->get(route('diabetes-log.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('diabetes-log/index')
            ->has('logs.data', 3)); // Should only see their own logs
});

it('includes todays meals on index page', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);
    Meal::factory()->count(2)->create(['meal_plan_id' => $mealPlan->id, 'day_number' => 1]);

    $response = $this->actingAs($user)
        ->get(route('diabetes-log.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('todaysMeals'));
});
