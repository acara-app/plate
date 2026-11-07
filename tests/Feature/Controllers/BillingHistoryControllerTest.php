<?php

declare(strict_types=1);

use App\Http\Controllers\BillingHistoryController;
use App\Models\AiUsage;
use App\Models\User;
use Laravel\Cashier\Subscription;

covers(BillingHistoryController::class);

beforeEach(function (): void {
    $this->withoutVite();
});

it("shows a paying member today's AI allowance", function (): void {
    sellPhotoPlans();
    $user = User::factory()->create(['stripe_id' => 'cus_test_latest']);
    Subscription::factory()->for($user)->active()->withPrice('price_starter')->create();
    AiUsage::factory()->create(['user_id' => $user->id, 'cost' => 0.25]);

    $this->actingAs($user)
        ->get(route('billing.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('billing/index')
            ->where('hasStripeCustomer', true)
            ->where('aiAllowance.tier', 'starter')
            ->where('aiAllowance.resets', 'daily')
            ->where('aiAllowance.percentage', 50));
});

it('has no allowance on a self-hosted install without billing', function (): void {
    disableBilling();

    $this->actingAs(User::factory()->create(['stripe_id' => null]))
        ->get(route('billing.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('hasStripeCustomer', false)
            ->where('aiAllowance', null));
});
