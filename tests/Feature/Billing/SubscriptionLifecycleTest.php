<?php

declare(strict_types=1);

use App\Contracts\Billing\ResolvesUserTier;
use App\Enums\GatedFeature;
use App\Enums\SubscriptionTier;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Laravel\Cashier\Subscription;

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

it('refreshes entitlements on the next request after a checkout activates a subscription', function (): void {
    $user = User::factory()->create();
    $resolver = resolve(ResolvesUserTier::class);

    expect($resolver->resolve($user)->tier)->toBe(SubscriptionTier::Free);

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_basic_monthly')
        ->create();

    expect($resolver->resolve($user->fresh())->tier)->toBe(SubscriptionTier::Basic);
});

it('lifts the meal-planner gate after a Stripe webhook activates the subscription', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $beforeResponse = $this->actingAs($user)
        ->postJson(route('meal-plans.store'), ['duration_days' => 3]);
    $beforeResponse->assertStatus(402);

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_basic_monthly')
        ->create();

    $afterResponse = $this->actingAs($user)
        ->post(route('meal-plans.store'), ['duration_days' => 3]);
    $afterResponse->assertRedirect();

    expect($user->mealPlans()->count())->toBe(1);
});

it('keeps entitlement Free while the subscription is incomplete and exposes payment_pending', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->incomplete()
        ->withPrice('price_basic_monthly')
        ->create();

    $response = $this->actingAs($user)->get(route('meal-plans.index'));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('mealPlannerLocked', true)
            ->where('entitlement.tier', 'free')
            ->where('entitlement.payment_pending', true)
            ->where('entitlement.premium_enforcement_active', true)
        );
});

it('preserves paid access during the cancellation grace period', function (): void {
    $endsAt = now()->addDays(5);
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->withPrice('price_plus_monthly')
        ->canceled()
        ->state(['ends_at' => $endsAt])
        ->create();

    $response = $this->actingAs($user)->get(route('meal-plans.index'));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('mealPlannerLocked', false)
            ->where('entitlement.tier', 'plus')
            ->where('entitlement.on_grace_period', true)
            ->where('entitlement.payment_pending', false)
            ->where('entitlement.grace_period_ends_at', $endsAt->copy()->startOfSecond()->toIso8601String())
        );
});

it('drops to Free after the grace period ends', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->withPrice('price_plus_monthly')
        ->canceled()
        ->state(['ends_at' => now()->subDay()])
        ->create();

    $response = $this->actingAs($user)->get(route('meal-plans.index'));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('mealPlannerLocked', true)
            ->where('entitlement.tier', 'free')
            ->where('entitlement.on_grace_period', false)
        );

    Queue::fake();
    $directPost = $this->actingAs($user)
        ->postJson(route('meal-plans.store'), ['duration_days' => 3]);
    $directPost->assertStatus(402);
});

it('exposes premium_enforcement_active=false when the flag is off', function (): void {
    Config::set('plate.enable_premium_upgrades', false);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('meal-plans.index'));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('entitlement.premium_enforcement_active', false)
            ->where('entitlement.tier', 'free')
            ->where('entitlement.payment_pending', false)
        );
});

it('serves no entitlement prop for unauthenticated requests', function (): void {
    $response = $this->get(route('login'));

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('entitlement', null)
        );
});

it('lifts the gate when an incomplete subscription transitions to active', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $subscription = Subscription::factory()
        ->for($user)
        ->incomplete()
        ->withPrice('price_basic_monthly')
        ->create();

    $beforeResponse = $this->actingAs($user)
        ->postJson(route('meal-plans.store'), ['duration_days' => 3]);
    $beforeResponse->assertStatus(402);

    $subscription->update(['stripe_status' => 'active']);

    $afterResponse = $this->actingAs($user)
        ->post(route('meal-plans.store'), ['duration_days' => 3]);
    $afterResponse->assertRedirect();

    expect($user->mealPlans()->count())->toBe(1);
});

it('blocks access immediately after a subscription is fully canceled with ends_at in the past', function (): void {
    Queue::fake();

    $user = User::factory()->create();

    $subscription = Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_plus_monthly')
        ->create();

    $beforeResponse = $this->actingAs($user)
        ->post(route('meal-plans.store'), ['duration_days' => 3]);
    $beforeResponse->assertRedirect();

    $subscription->update([
        'stripe_status' => 'canceled',
        'ends_at' => now()->subMinute(),
    ]);

    $afterResponse = $this->actingAs($user)
        ->postJson(route('meal-plans.regenerate'));
    $afterResponse->assertStatus(402)
        ->assertJsonPath('feature', GatedFeature::MealPlanner->value);
});
