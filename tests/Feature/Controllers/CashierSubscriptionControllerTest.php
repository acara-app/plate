<?php

declare(strict_types=1);

use App\Http\Controllers\Checkout\CashierSubscriptionController;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\FakeGuestCheckout;

covers(CashierSubscriptionController::class);

beforeEach(function (): void {
    offerAllSubscriptions();
});

it('validates product_id is required', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('checkout.subscription.store'), [
            'billing_interval' => 'monthly',
        ]);

    $response->assertSessionHasErrors('product_id');
});

it('validates product_id exists', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('checkout.subscription.store'), [
            'product_id' => 99999,
            'billing_interval' => 'monthly',
        ]);

    $response->assertSessionHasErrors('product_id');
});

it('accepts an explicit monthly interval from older checkout clients', function (): void {
    $product = sellPhotoPlans();
    FakeGuestCheckout::install();

    $this->post(route('checkout.subscription.store'), [
        'product_id' => $product->id,
        'billing_interval' => 'monthly',
    ])->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_guest_1');
});

it('rejects an invalid billing interval', function (): void {
    $user = User::factory()->create();
    $product = SubscriptionProduct::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('checkout.subscription.store'), [
            'product_id' => $product->id,
            'billing_interval' => 'invalid',
        ]);

    $response->assertSessionHasErrors('billing_interval');
});

it('redirects with error if user already has subscription', function (): void {
    $user = User::factory()->create();
    $product = SubscriptionProduct::factory()->create();

    DB::table('subscriptions')->insert([
        'user_id' => $user->id,
        'type' => 'existing-plan',
        'stripe_id' => 'sub_existing',
        'stripe_status' => 'active',
        'stripe_price' => 'price_existing',
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->post(route('checkout.subscription.store'), [
            'product_id' => $product->id,
            'billing_interval' => 'monthly',
        ]);

    $response->assertRedirect(route('checkout.subscription'))
        ->assertInertiaFlash('toast.type', 'error')
        ->assertInertiaFlash('toast.message', 'You already have an active subscription. Use the billing portal to manage it.');
});

it('handles exceptions gracefully', function (): void {
    $user = User::factory()->create();
    $product = SubscriptionProduct::factory()->create([
        'stripe_price_id' => null,
    ]);

    $response = $this->actingAs($user)
        ->post(route('checkout.subscription.store'), [
            'product_id' => $product->id,
            'billing_interval' => 'monthly',
        ]);

    $response->assertRedirect(route('checkout.subscription'))
        ->assertInertiaFlash('toast.type', 'error')
        ->assertInertiaFlash('toast.message', 'Failed to initiate subscription. Please try again.');
});
