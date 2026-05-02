<?php

declare(strict_types=1);

use App\Contracts\Billing\ResolvesUserTier;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    Config::set('plate.enable_premium_upgrades', false);
    Config::set('plate.premium_rollout.allowlist', []);
    Config::set('plate.premium_rollout.percentage', 0);

    SubscriptionProduct::factory()->create([
        'name' => 'Basic',
        'stripe_price_id' => 'price_basic_monthly',
        'yearly_stripe_price_id' => 'price_basic_yearly',
    ]);
});

it('enforces meal planner gating for an allowlisted staff user when the global flag is off', function (): void {
    Queue::fake();

    $staff = User::factory()->create(['email' => 'staff@example.com']);

    Config::set('plate.premium_rollout.allowlist', ['staff@example.com']);

    $response = $this->actingAs($staff)
        ->postJson(route('meal-plans.store'), ['duration_days' => 3]);

    $response->assertStatus(402);

    expect($staff->mealPlans()->count())->toBe(0);
});

it('lets a non-allowlisted user post freely when the global flag is off', function (): void {
    Queue::fake();

    $regular = User::factory()->create(['email' => 'regular@example.com']);

    Config::set('plate.premium_rollout.allowlist', ['staff@example.com']);

    $response = $this->actingAs($regular)
        ->post(route('meal-plans.store'), ['duration_days' => 3]);

    $response->assertRedirect();

    expect($regular->mealPlans()->count())->toBe(1);
});

it('exposes premium_enforcement_active=true for staff via the entitlement shared prop', function (): void {
    $staff = User::factory()->create(['email' => 'staff@example.com']);

    Config::set('plate.premium_rollout.allowlist', ['staff@example.com']);

    $this->actingAs($staff)->get(route('meal-plans.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('entitlement.premium_enforcement_active', true)
            ->where('enablePremiumUpgrades', true)
        );
});

it('exposes premium_enforcement_active=false for non-staff via the entitlement shared prop', function (): void {
    $regular = User::factory()->create();

    Config::set('plate.premium_rollout.allowlist', ['staff@example.com']);

    $this->actingAs($regular)->get(route('meal-plans.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('entitlement.premium_enforcement_active', false)
            ->where('enablePremiumUpgrades', false)
        );
});

it('enforces gating for users in the rollout percentile and lifts it for those outside', function (): void {
    Config::set('plate.premium_rollout.percentage', 100);

    $user = User::factory()->create();

    $entitlement = resolve(ResolvesUserTier::class)->resolve($user);
    expect($entitlement->premiumEnforcementActive)->toBeTrue();

    Config::set('plate.premium_rollout.percentage', 0);

    $entitlementOff = resolve(ResolvesUserTier::class)->resolve($user);
    expect($entitlementOff->premiumEnforcementActive)->toBeFalse();
});

it('the global flag overrides the rollout cohort once it is flipped', function (): void {
    $user = User::factory()->create(['email' => 'random@example.com']);

    Config::set('plate.premium_rollout.allowlist', []);
    Config::set('plate.premium_rollout.percentage', 0);

    expect(resolve(ResolvesUserTier::class)->resolve($user)->premiumEnforcementActive)->toBeFalse();

    Config::set('plate.enable_premium_upgrades', true);

    expect(resolve(ResolvesUserTier::class)->resolve($user)->premiumEnforcementActive)->toBeTrue();
});
