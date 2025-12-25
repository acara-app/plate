<?php

declare(strict_types=1);

use App\Models\GlucoseReading;
use App\Models\MealPlan;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('requires authentication', function (): void {
    $response = get(route('glucose-action.show'));

    $response->assertRedirectToRoute('login');
});

it('requires verified email', function (): void {
    $user = User::factory()->unverified()->create();

    $response = actingAs($user)->get(route('glucose-action.show'));

    $response->assertRedirectToRoute('verification.notice');
});

it('renders page for authenticated user', function (): void {
    $user = User::factory()->create();

    $response = actingAs($user)->get(route('glucose-action.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('glucose/action')
            ->has('glucoseAnalysis')
            ->has('concerns')
            ->has('hasMealPlan')
            ->where('hasMealPlan', false)
            ->where('mealPlan', null));
});

it('shows correct state when user has no glucose data', function (): void {
    $user = User::factory()->create();

    $response = actingAs($user)->get(route('glucose-action.show'));

    $response->assertInertia(fn ($page) => $page
        ->has('glucoseAnalysis')
        ->where('concerns', []));
});

it('shows correct state when user has glucose data but no concerns', function (): void {
    $user = User::factory()->create();

    // Create glucose readings within normal range
    GlucoseReading::factory()
        ->count(10)
        ->for($user)
        ->create([
            'reading_value' => fake()->numberBetween(80, 120),
            'measured_at' => now()->subDays(random_int(1, 7)),
        ]);

    $response = actingAs($user)->get(route('glucose-action.show'));

    $response->assertInertia(fn ($page) => $page
        ->has('glucoseAnalysis')
        ->where('concerns', []));
});

it('shows concerns when glucose readings are problematic', function (): void {
    $user = User::factory()->create([
        'settings' => [
            'glucose_notifications_enabled' => true,
        ],
    ]);

    // Create high glucose readings (above threshold)
    GlucoseReading::factory()
        ->count(10)
        ->for($user)
        ->create([
            'reading_value' => fake()->numberBetween(180, 250),
            'measured_at' => now()->subDays(random_int(1, 7)),
        ]);

    $response = actingAs($user)->get(route('glucose-action.show'));

    $response->assertInertia(fn ($page) => $page
        ->has('glucoseAnalysis')
        ->has('concerns')
        ->where('concerns', fn ($concerns): bool => count($concerns) > 0));
});

it('includes meal plan data when user has a meal plan', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    GlucoseReading::factory()
        ->count(5)
        ->for($user)
        ->create();

    $response = actingAs($user)->get(route('glucose-action.show'));

    $response->assertInertia(fn ($page) => $page
        ->where('hasMealPlan', true)
        ->where('mealPlan.id', $mealPlan->id)
        ->where('mealPlan.duration_days', $mealPlan->duration_days));
});

it('shows has_meal_plan false when user has no meal plan', function (): void {
    $user = User::factory()->create();

    $response = actingAs($user)->get(route('glucose-action.show'));

    $response->assertInertia(fn ($page) => $page
        ->where('hasMealPlan', false)
        ->where('mealPlan', null));
});

it('returns fresh glucose analysis data', function (): void {
    $user = User::factory()->create();

    // Create some glucose readings
    GlucoseReading::factory()
        ->count(5)
        ->for($user)
        ->create([
            'reading_value' => fake()->numberBetween(90, 130),
            'measured_at' => now()->subDays(random_int(1, 7)),
        ]);

    $response = actingAs($user)->get(route('glucose-action.show'));

    $response->assertInertia(fn ($page) => $page
        ->has('glucoseAnalysis')
        ->has('concerns')
        ->has('hasMealPlan')
        ->has('mealPlan'));
});
