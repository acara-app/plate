<?php

declare(strict_types=1);

use App\Models\SubscriptionProduct;
use App\Models\User;
use App\Services\Contracts\StripeServiceInterface;

use function Pest\Laravel\mock;

it('creates monthly subscription checkout successfully', function (): void {
    $user = User::factory()->create();
    $product = SubscriptionProduct::factory()->create([
        'name' => 'Premium Plan',
        'stripe_price_id' => 'price_monthly_test',
    ]);

    $stripeMock = mock(StripeServiceInterface::class);
    $stripeMock->shouldReceive('hasActiveSubscription')
        ->once()
        ->with(Mockery::type(User::class))
        ->andReturn(false);
    $stripeMock->shouldReceive('getPriceIdFromLookupKey')
        ->once()
        ->with('price_monthly_test')
        ->andReturn('price_actual_id_123');
    $stripeMock->shouldReceive('createSubscriptionCheckout')
        ->once()
        ->with(
            Mockery::type(User::class),
            'premium-plan',
            'price_actual_id_123',
            Mockery::type('string'),
            Mockery::type('string'),
            Mockery::type('array')
        )
        ->andReturn('https://checkout.stripe.com/session_123');

    $response = $this->actingAs($user)->post(route('checkout.subscription.store'), [
        'product_id' => $product->id,
        'billing_interval' => 'monthly',
    ]);

    $response->assertRedirect('https://checkout.stripe.com/session_123');
});

it('creates yearly subscription checkout successfully', function (): void {
    $user = User::factory()->create();
    $product = SubscriptionProduct::factory()->create([
        'name' => 'Pro Plan',
        'yearly_stripe_price_id' => 'price_yearly_test',
    ]);

    $stripeMock = mock(StripeServiceInterface::class);
    $stripeMock->shouldReceive('hasActiveSubscription')
        ->once()
        ->andReturn(false);
    $stripeMock->shouldReceive('getPriceIdFromLookupKey')
        ->once()
        ->with('price_yearly_test')
        ->andReturn('price_yearly_actual_456');
    $stripeMock->shouldReceive('createSubscriptionCheckout')
        ->once()
        ->andReturn('https://checkout.stripe.com/session_456');

    $response = $this->actingAs($user)->post(route('checkout.subscription.store'), [
        'product_id' => $product->id,
        'billing_interval' => 'yearly',
    ]);

    $response->assertRedirect('https://checkout.stripe.com/session_456');
});

it('redirects when user already has active subscription', function (): void {
    $user = User::factory()->create();
    $product = SubscriptionProduct::factory()->create();

    $stripeMock = mock(StripeServiceInterface::class);
    $stripeMock->shouldReceive('hasActiveSubscription')
        ->once()
        ->andReturn(true);

    $response = $this->actingAs($user)->post(route('checkout.subscription.store'), [
        'product_id' => $product->id,
        'billing_interval' => 'monthly',
    ]);

    $response->assertRedirect(route('checkout.subscription'));
    $response->assertSessionHas('error', 'You already have an active subscription. Use the billing portal to manage it.');
});

it('redirects when price lookup key not found', function (): void {
    $user = User::factory()->create();
    $product = SubscriptionProduct::factory()->create([
        'stripe_price_id' => 'price_invalid',
    ]);

    $stripeMock = mock(StripeServiceInterface::class);
    $stripeMock->shouldReceive('hasActiveSubscription')
        ->once()
        ->andReturn(false);
    $stripeMock->shouldReceive('getPriceIdFromLookupKey')
        ->once()
        ->with('price_invalid')
        ->andReturn(null);

    $response = $this->actingAs($user)->post(route('checkout.subscription.store'), [
        'product_id' => $product->id,
        'billing_interval' => 'monthly',
    ]);

    $response->assertRedirect(route('checkout.subscription'));
    $response->assertSessionHas('error', 'Failed to initiate subscription. Please try again.');
});

it('validates required fields', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('checkout.subscription.store'), []);

    $response->assertSessionHasErrors(['product_id', 'billing_interval']);
});

it('validates product exists', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('checkout.subscription.store'), [
        'product_id' => 99999,
        'billing_interval' => 'monthly',
    ]);

    $response->assertSessionHasErrors(['product_id']);
});

it('validates billing interval values', function (): void {
    $user = User::factory()->create();
    $product = SubscriptionProduct::factory()->create();

    $response = $this->actingAs($user)->post(route('checkout.subscription.store'), [
        'product_id' => $product->id,
        'billing_interval' => 'invalid',
    ]);

    $response->assertSessionHasErrors(['billing_interval']);
});

it('requires authentication', function (): void {
    $product = SubscriptionProduct::factory()->create();

    $response = $this->post(route('checkout.subscription.store'), [
        'product_id' => $product->id,
        'billing_interval' => 'monthly',
    ]);

    $response->assertRedirect(route('login'));
});

it('handles missing yearly price id gracefully', function (): void {
    $user = User::factory()->create();
    $product = SubscriptionProduct::factory()->create([
        'stripe_price_id' => 'price_monthly',
        'yearly_stripe_price_id' => null,
    ]);

    $stripeMock = mock(StripeServiceInterface::class);
    $stripeMock->shouldReceive('hasActiveSubscription')
        ->once()
        ->andReturn(false);

    $response = $this->actingAs($user)->post(route('checkout.subscription.store'), [
        'product_id' => $product->id,
        'billing_interval' => 'yearly',
    ]);

    $response->assertRedirect(route('checkout.subscription'));
    $response->assertSessionHas('error', 'Failed to initiate subscription. Please try again.');
});
