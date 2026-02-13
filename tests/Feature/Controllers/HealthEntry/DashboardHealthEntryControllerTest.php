<?php

declare(strict_types=1);

use App\Models\HealthEntry;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;

it('renders diabetes log tracking dashboard', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('health-entries.dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('logs')
            ->has('timePeriod')
            ->has('summary')
            ->has('glucoseReadingTypes')
            ->has('insulinTypes'));
});

it('displays user diabetes logs filtered by time period', function (): void {
    $user = User::factory()->create();

    // Create 3 logs within 30 days
    HealthEntry::factory()->count(3)->create([
        'user_id' => $user->id,
        'measured_at' => now()->subDays(10),
    ]);
    // Create 2 logs older than 30 days
    HealthEntry::factory()->count(2)->create([
        'user_id' => $user->id,
        'measured_at' => now()->subDays(40),
    ]);

    $response = $this->actingAs($user)
        ->get(route('health-entries.dashboard'));

    // Default 30d period should only show 3 logs
    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('logs', 3)
            ->where('timePeriod', '30d'));
});

it('filters logs by query parameter period', function (): void {
    $user = User::factory()->create();

    // Create logs at different times
    HealthEntry::factory()->count(2)->create([
        'user_id' => $user->id,
        'measured_at' => now()->subDays(5),
    ]);
    HealthEntry::factory()->count(3)->create([
        'user_id' => $user->id,
        'measured_at' => now()->subDays(20),
    ]);

    // 7d period should only show 2 logs
    $response = $this->actingAs($user)
        ->get(route('health-entries.dashboard', ['period' => '7d']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('logs', 2)
            ->where('timePeriod', '7d'));
});

it('includes summary statistics in response', function (): void {
    $user = User::factory()->create();
    HealthEntry::factory()->create([
        'user_id' => $user->id,
        'glucose_value' => 120,
        'measured_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('health-entries.dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('summary.glucoseStats')
            ->has('summary.insulinStats')
            ->has('summary.carbStats')
            ->has('summary.exerciseStats')
            ->has('summary.weightStats')
            ->has('summary.bpStats')
            ->has('summary.medicationStats')
            ->has('summary.a1cStats')
            ->has('summary.streakStats')
            ->has('summary.dataTypes'));
});

it('includes todays meals from meal plan on dashboard', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);
    Meal::factory()->count(3)->create(['meal_plan_id' => $mealPlan->id, 'day_number' => 1]);

    $response = $this->actingAs($user)
        ->get(route('health-entries.dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->has('todaysMeals'));
});
