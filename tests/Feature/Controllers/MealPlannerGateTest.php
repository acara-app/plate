<?php

declare(strict_types=1);

use App\Enums\DietType;
use App\Http\Controllers\GenerateMealDayController;
use App\Http\Controllers\RegenerateMealPlanController;
use App\Http\Controllers\RegenerateMealPlanDayController;
use App\Http\Controllers\ShowMealPlansController;
use App\Http\Controllers\StoreMealPlanController;
use App\Models\MealPlan;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Laravel\Cashier\Subscription;

covers(
    StoreMealPlanController::class,
    RegenerateMealPlanController::class,
    RegenerateMealPlanDayController::class,
    GenerateMealDayController::class,
    ShowMealPlansController::class,
);

beforeEach(function (): void {
    Config::set('plate.enable_premium_upgrades', true);

    SubscriptionProduct::factory()->create([
        'name' => 'Basic',
        'stripe_price_id' => 'price_basic_monthly',
        'yearly_stripe_price_id' => 'price_basic_yearly',
    ]);

    SubscriptionProduct::factory()->create([
        'name' => 'Plus',
        'stripe_price_id' => 'price_plus_monthly',
        'yearly_stripe_price_id' => 'price_plus_yearly',
    ]);
});

it('rejects free users posting directly to meal-plans.store with 402 and creates no row', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('meal-plans.store'), [
            'duration_days' => 3,
        ]);

    $response->assertStatus(402)
        ->assertJsonPath('error', 'feature_gated')
        ->assertJsonPath('feature', 'meal_planner')
        ->assertJsonPath('required_tier', 'basic')
        ->assertJsonPath('current_tier', 'free');

    expect($user->mealPlans()->count())->toBe(0);
});

it('allows Basic users to post to meal-plans.store', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_basic_monthly')
        ->create();

    $response = $this->actingAs($user)
        ->post(route('meal-plans.store'), [
            'duration_days' => 3,
            'diet_type' => DietType::Mediterranean->value,
        ]);

    $response->assertRedirect();

    expect($user->mealPlans()->count())->toBe(1);
});

it('allows Plus users to post to meal-plans.store', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_plus_monthly')
        ->create();

    $response = $this->actingAs($user)
        ->post(route('meal-plans.store'), [
            'duration_days' => 3,
        ]);

    $response->assertRedirect();

    expect($user->mealPlans()->count())->toBe(1);
});

it('allows free users to post when premium enforcement is off', function (): void {
    Config::set('plate.enable_premium_upgrades', false);
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('meal-plans.store'), [
            'duration_days' => 3,
        ]);

    $response->assertRedirect();

    expect($user->mealPlans()->count())->toBe(1);
});

it('rejects free users posting to meal-plans.regenerate with 402', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('meal-plans.regenerate'));

    $response->assertStatus(402)
        ->assertJsonPath('feature', 'meal_planner');

    expect($user->mealPlans()->count())->toBe(0);
});

it('rejects free users posting to meal-plans.regenerate-day with 402', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'duration_days' => 3,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('meal-plans.regenerate-day', ['mealPlan' => $mealPlan->id]), [
            'day' => 1,
        ]);

    $response->assertStatus(402)
        ->assertJsonPath('feature', 'meal_planner');
});

it('rejects free users posting to meal-plans.generate-day with 402', function (): void {
    Queue::fake();

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'duration_days' => 3,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('meal-plans.generate-day', ['mealPlan' => $mealPlan->id]), [
            'day' => 1,
        ]);

    $response->assertStatus(402)
        ->assertJsonPath('feature', 'meal_planner');
});

it('passes mealPlannerLocked=true and requiredTier=basic to free user UI', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('meal-plans.index'));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('mealPlannerLocked', true)
            ->where('requiredTier', 'basic')
        );
});

it('passes mealPlannerLocked=false to Basic user UI', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_basic_monthly')
        ->create();

    $response = $this->actingAs($user)->get(route('meal-plans.index'));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('mealPlannerLocked', false)
        );
});

it('passes mealPlannerLocked=false when premium enforcement is off', function (): void {
    Config::set('plate.enable_premium_upgrades', false);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('meal-plans.index'));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('mealPlannerLocked', false)
        );
});
