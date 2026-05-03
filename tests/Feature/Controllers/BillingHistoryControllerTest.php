<?php

declare(strict_types=1);

use App\Enums\SubscriptionTier;
use App\Http\Controllers\BillingHistoryController;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Laravel\Cashier\Subscription;

covers(BillingHistoryController::class);

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

it('renders billing history page', function (): void {
    $user = User::factory()->create();

    $this->withoutVite();

    $this->actingAs($user)
        ->get(route('billing.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('billing/index')
            ->where('aiUsage.tier', SubscriptionTier::Free->value)
            ->where('aiUsage.tier_label', 'Free')
            ->where('aiUsage.premium_enforcement_active', true)
            ->where('aiUsage.payment_pending', false));
});

it('handles exception when fetching invoices', function (): void {
    $user = User::factory()->create(['stripe_id' => null]);

    $this->withoutVite();

    $response = $this->actingAs($user)
        ->get(route('billing.index'));

    $response->assertOk();
});

it('shows the resolved tier limits for a Basic subscriber', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_basic_monthly')
        ->create();

    $this->withoutVite();

    $this->actingAs($user)
        ->get(route('billing.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('aiUsage.tier', SubscriptionTier::Basic->value)
            ->where('aiUsage.tier_label', 'Supporter')
            ->where('aiUsage.rolling.limit', 500)
            ->where('aiUsage.weekly.limit', 2000));
});

it("does not leak another user's subscription tier into the response", function (): void {
    $basicUser = User::factory()->create();
    Subscription::factory()
        ->for($basicUser)
        ->active()
        ->withPrice('price_basic_monthly')
        ->create();

    $freeUser = User::factory()->create();

    $this->withoutVite();

    $this->actingAs($freeUser)
        ->get(route('billing.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('aiUsage.tier', SubscriptionTier::Free->value)
            ->where('aiUsage.rolling.limit', 100));
});

it('flags self-host mode by setting premium_enforcement_active to false', function (): void {
    Config::set('plate.enable_premium_upgrades', false);

    $user = User::factory()->create();

    $this->withoutVite();

    $this->actingAs($user)
        ->get(route('billing.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('aiUsage.premium_enforcement_active', false)
            ->where('aiUsage.tier', SubscriptionTier::Free->value));
});
