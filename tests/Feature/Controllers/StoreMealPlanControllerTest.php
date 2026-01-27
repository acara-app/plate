<?php

declare(strict_types=1);

use App\Enums\DietType;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

it('requires authentication', function (): void {
    $response = $this->post(route('meal-plans.store'));

    $response->assertRedirectToRoute('login');
});

it('requires verified email', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->post(route('meal-plans.store'));

    $response->assertRedirectToRoute('verification.notice');
});

it('stores meal plan for authenticated user', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('meal-plans.store'), [
            'prompt' => 'Test custom prompt',
        ]);

    $response->assertRedirect();
});

it('stores diet type from request', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('meal-plans.store'), [
            'prompt' => 'Test custom prompt',
            'diet_type' => DietType::Mediterranean->value,
        ]);

    $response->assertRedirect();

    $mealPlan = $user->mealPlans->first();
    expect($mealPlan->metadata['diet_type'])->toBe(DietType::Mediterranean->value);
});

it('uses profile diet type as fallback when not provided', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $user->profile()->create([
        'calculated_diet_type' => DietType::Vegan,
    ]);

    $response = $this->actingAs($user)
        ->post(route('meal-plans.store'), [
            'prompt' => 'Test custom prompt',
        ]);

    $response->assertRedirect();

    $mealPlan = $user->mealPlans->first();
    expect($mealPlan->metadata['diet_type'])->toBe(DietType::Vegan->value);
});

it('uses balanced diet type when no diet type provided and profile has none', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $user->profile()->create([
        'calculated_diet_type' => null,
    ]);

    $response = $this->actingAs($user)
        ->post(route('meal-plans.store'), [
            'prompt' => 'Test custom prompt',
        ]);

    $response->assertRedirect();

    $mealPlan = $user->mealPlans->first();
    expect($mealPlan->metadata['diet_type'])->toBe(DietType::Balanced->value);
});

it('creates meal plan name based on diet type', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('meal-plans.store'), [
            'diet_type' => DietType::Keto->value,
        ]);

    $response->assertRedirect();

    $mealPlan = $user->mealPlans->first();
    expect($mealPlan->name)->toBe('3-Day Keto Plan');
});

it('uses custom duration days from request', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('meal-plans.store'), [
            'duration_days' => 5,
        ]);

    $response->assertRedirect();

    $mealPlan = $user->mealPlans->first();
    expect($mealPlan->duration_days)->toBe(5);
});

it('creates meal plan name with correct duration', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('meal-plans.store'), [
            'duration_days' => 7,
            'diet_type' => DietType::Keto->value,
        ]);

    $response->assertRedirect();

    $mealPlan = $user->mealPlans->first();
    expect($mealPlan->name)->toBe('7-Day Keto Plan');
});
