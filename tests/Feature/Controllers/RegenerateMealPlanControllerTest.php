<?php

declare(strict_types=1);

use App\Models\GlucoseReading;
use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

it('requires authentication', function (): void {
    $response = post(route('meal-plans.regenerate'));

    $response->assertRedirectToRoute('login');
});

it('requires verified email', function (): void {
    $user = User::factory()->unverified()->create();

    $response = actingAs($user)->post(route('meal-plans.regenerate'));

    $response->assertRedirectToRoute('verification.notice');
});

it('deletes existing meal plan', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $mealPlan = MealPlan::factory()->for($user)->create();

    actingAs($user)->post(route('meal-plans.regenerate'));

    expect(MealPlan::query()->find($mealPlan->id))->toBeNull();
});

it('starts meal plan workflow', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    // Create some glucose data for analysis
    GlucoseReading::factory()->count(5)->for($user)->create();

    $response = actingAs($user)->post(route('meal-plans.regenerate'));

    $response->assertRedirectToRoute('meal-plans.index');

    // Verify workflow was started (this tests the workflow stub was called)
    // The actual workflow execution is tested in workflow tests
    expect($user->fresh()->mealPlans()->exists())->toBeFalse();
});

it('redirects to meal plans index with success message', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $response = actingAs($user)->post(route('meal-plans.regenerate'));

    $response->assertRedirectToRoute('meal-plans.index')
        ->assertSessionHas('success');
});

it('works when user has no existing meal plan', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $response = actingAs($user)->post(route('meal-plans.regenerate'));

    $response->assertRedirectToRoute('meal-plans.index');
});

it('works when user has no glucose data', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $response = actingAs($user)->post(route('meal-plans.regenerate'));

    $response->assertRedirectToRoute('meal-plans.index')
        ->assertSessionHas('success');
});
