<?php

declare(strict_types=1);

use App\Enums\MealPlanGenerationStatus;
use App\Models\Meal;
use App\Models\MealPlan;
use App\Models\User;
use Workflow\WorkflowStub;

it('requires authentication to regenerate meal plan day', function (): void {
    $mealPlan = MealPlan::factory()->create();

    $response = $this->post(route('meal-plans.regenerate-day', $mealPlan), [
        'day' => 1,
    ]);

    $response->assertRedirectToRoute('login');
});

it('requires email verification to regenerate meal plan day', function (): void {
    $user = User::factory()->unverified()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->post(route('meal-plans.regenerate-day', $mealPlan), [
            'day' => 1,
        ]);

    $response->assertRedirectToRoute('verification.notice');
});

it('forbids access to another users meal plan', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->post(route('meal-plans.regenerate-day', $mealPlan), [
            'day' => 1,
        ]);

    $response->assertForbidden();
});

it('validates day number is within meal plan duration', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->weekly()->create([
        'user_id' => $user->id,
        'duration_days' => 7,
    ]);

    $response = $this->actingAs($user)
        ->post(route('meal-plans.regenerate-day', $mealPlan), [
            'day' => 10,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['day']);
});

it('validates day number is positive', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->weekly()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->post(route('meal-plans.regenerate-day', $mealPlan), [
            'day' => 0,
        ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['day']);
});

it('deletes existing meals for the specified day', function (): void {
    WorkflowStub::fake();

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->weekly()->create([
        'user_id' => $user->id,
        'duration_days' => 7,
    ]);

    Meal::factory()->count(3)->create([
        'meal_plan_id' => $mealPlan->id,
        'day_number' => 1,
    ]);

    Meal::factory()->count(2)->create([
        'meal_plan_id' => $mealPlan->id,
        'day_number' => 2,
    ]);

    expect($mealPlan->meals()->where('day_number', 1)->count())->toBe(3);

    $this->actingAs($user)
        ->post(route('meal-plans.regenerate-day', $mealPlan), [
            'day' => 1,
        ]);

    expect($mealPlan->meals()->where('day_number', 1)->count())->toBe(0);
    expect($mealPlan->meals()->where('day_number', 2)->count())->toBe(2);
});

it('updates metadata with generating status', function (): void {
    WorkflowStub::fake();

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->weekly()->create([
        'user_id' => $user->id,
        'duration_days' => 7,
        'metadata' => [
            'day_1_status' => MealPlanGenerationStatus::Completed->value,
        ],
    ]);

    $this->actingAs($user)
        ->post(route('meal-plans.regenerate-day', $mealPlan), [
            'day' => 1,
        ]);

    $mealPlan->refresh();

    expect($mealPlan->metadata['day_1_status'])
        ->toBe(MealPlanGenerationStatus::Generating->value);
});

it('starts workflow for regenerating day', function (): void {
    WorkflowStub::fake();

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->weekly()->create([
        'user_id' => $user->id,
        'duration_days' => 7,
    ]);

    $response = $this->actingAs($user)
        ->post(route('meal-plans.regenerate-day', $mealPlan), [
            'day' => 2,
        ]);

    $response->assertRedirect();

    expect($mealPlan->fresh()->metadata['day_2_status'])
        ->toBe(MealPlanGenerationStatus::Generating->value);
});

it('redirects back after regeneration', function (): void {
    WorkflowStub::fake();

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->weekly()->create([
        'user_id' => $user->id,
        'duration_days' => 7,
    ]);

    $response = $this->actingAs($user)
        ->from(route('meal-plans.index', ['day' => 1]))
        ->post(route('meal-plans.regenerate-day', $mealPlan), [
            'day' => 1,
        ]);

    $response->assertRedirect(route('meal-plans.index', ['day' => 1]));
});

it('requires day parameter', function (): void {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->weekly()->create([
        'user_id' => $user->id,
        'duration_days' => 7,
    ]);

    $this->actingAs($user)
        ->post(route('meal-plans.regenerate-day', $mealPlan))
        ->assertSessionHasErrors('day');
});
