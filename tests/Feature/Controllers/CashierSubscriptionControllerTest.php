<?php

declare(strict_types=1);

use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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

it('validates billing_interval is required', function (): void {
    $user = User::factory()->create();
    $product = SubscriptionProduct::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('checkout.subscription.store'), [
            'product_id' => $product->id,
        ]);

    $response->assertSessionHasErrors('billing_interval');
});

it('validates billing_interval is monthly or yearly', function (): void {
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

    // Create an active subscription
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
        ->assertSessionHas('error');

    expect(session('error'))->toContain('subscription');
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
        ->assertSessionHas('error');
});
