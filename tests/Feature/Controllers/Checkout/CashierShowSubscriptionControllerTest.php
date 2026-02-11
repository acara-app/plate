<?php

declare(strict_types=1);

use App\Contracts\Services\StripeServiceContract;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\mock;

it('calls stripe service for user without stripe id', function (): void {
    $user = User::factory()->create(['stripe_id' => null]);
    SubscriptionProduct::factory()->count(3)->create();

    $stripeMock = mock(StripeServiceContract::class);
    $stripeMock->shouldReceive('ensureStripeCustomer')
        ->once()
        ->with(Mockery::type(User::class));
    $stripeMock->shouldReceive('getBillingPortalUrl')
        ->once()
        ->andReturn('https://billing.stripe.com/session/test');

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();
});

it('renders subscription with active subscription', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_test123']);
    $product = SubscriptionProduct::factory()->create([
        'name' => 'Premium Plan',
        'stripe_price_id' => 'price_monthly_test',
    ]);

    DB::table('subscriptions')->insert([
        'user_id' => $user->id,
        'type' => 'premium-plan',
        'stripe_id' => 'sub_test123',
        'stripe_status' => 'active',
        'stripe_price' => 'price_monthly_test',
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $subscription = $user->subscriptions()->first();

    DB::table('subscription_items')->insert([
        'subscription_id' => $subscription->id,
        'stripe_id' => 'si_test123',
        'stripe_product' => 'prod_test123',
        'stripe_price' => 'price_monthly_test',
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $stripeMock = mock(StripeServiceContract::class);
    $stripeMock->shouldReceive('ensureStripeCustomer')->once();
    $stripeMock->shouldReceive('hasIncompletePayment')
        ->once()
        ->andReturn(false);
    $stripeMock->shouldReceive('getBillingPortalUrl')
        ->once()
        ->andReturn('https://billing.stripe.com/session/test');

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();
});

it('detects yearly subscription correctly', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_test123']);
    $product = SubscriptionProduct::factory()->create([
        'name' => 'Pro Plan',
        'yearly_stripe_price_id' => 'price_yearly_test',
    ]);

    DB::table('subscriptions')->insert([
        'user_id' => $user->id,
        'type' => 'pro-plan',
        'stripe_id' => 'sub_test456',
        'stripe_status' => 'active',
        'stripe_price' => 'price_yearly_test',
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $subscription = $user->subscriptions()->first();

    DB::table('subscription_items')->insert([
        'subscription_id' => $subscription->id,
        'stripe_id' => 'si_test456',
        'stripe_product' => 'prod_test456',
        'stripe_price' => 'price_yearly_test',
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $stripeMock = mock(StripeServiceContract::class);
    $stripeMock->shouldReceive('ensureStripeCustomer')->once();
    $stripeMock->shouldReceive('hasIncompletePayment')->once()->andReturn(false);
    $stripeMock->shouldReceive('getBillingPortalUrl')->once()->andReturn('https://billing.stripe.com/session/test');

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();
});

it('renders page when user has no active subscription', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_test123']);
    SubscriptionProduct::factory()->count(3)->create();

    $stripeMock = mock(StripeServiceContract::class);
    $stripeMock->shouldReceive('ensureStripeCustomer')->once();
    $stripeMock->shouldReceive('getBillingPortalUrl')->once()->andReturn('https://billing.stripe.com/session/test');

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();
});

it('requires authentication', function (): void {
    $response = $this->get(route('checkout.subscription'));

    $response->assertRedirect(route('login'));
});

it('renders subscription when no subscription items exist', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_test123']);
    SubscriptionProduct::factory()->count(2)->create();

    DB::table('subscriptions')->insert([
        'user_id' => $user->id,
        'type' => 'premium-plan',
        'stripe_id' => 'sub_test123',
        'stripe_status' => 'active',
        'stripe_price' => 'price_monthly_test',
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $stripeMock = mock(StripeServiceContract::class);
    $stripeMock->shouldReceive('ensureStripeCustomer')->once();
    $stripeMock->shouldReceive('hasIncompletePayment')->once()->andReturn(false);
    $stripeMock->shouldReceive('getBillingPortalUrl')->once()->andReturn('https://billing.stripe.com/session/test');

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();
});

it('renders subscription when product does not match price id', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_test123']);
    SubscriptionProduct::factory()->create([
        'name' => 'Premium Plan',
        'stripe_price_id' => 'price_different_monthly',
        'yearly_stripe_price_id' => 'price_different_yearly',
    ]);

    DB::table('subscriptions')->insert([
        'user_id' => $user->id,
        'type' => 'premium-plan',
        'stripe_id' => 'sub_test123',
        'stripe_status' => 'active',
        'stripe_price' => 'price_unmatched_test',
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $subscription = $user->subscriptions()->first();

    DB::table('subscription_items')->insert([
        'subscription_id' => $subscription->id,
        'stripe_id' => 'si_test123',
        'stripe_product' => 'prod_test123',
        'stripe_price' => 'price_unmatched_test',
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $stripeMock = mock(StripeServiceContract::class);
    $stripeMock->shouldReceive('ensureStripeCustomer')->once();
    $stripeMock->shouldReceive('hasIncompletePayment')->once()->andReturn(false);
    $stripeMock->shouldReceive('getBillingPortalUrl')->once()->andReturn('https://billing.stripe.com/session/test');

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();
});

it('returns null for incomplete payment url when has incomplete payment is false', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_test123']);
    SubscriptionProduct::factory()->create();

    DB::table('subscriptions')->insert([
        'user_id' => $user->id,
        'type' => 'default',
        'stripe_id' => 'sub_test123',
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $stripeMock = mock(StripeServiceContract::class);
    $stripeMock->shouldReceive('ensureStripeCustomer')->once();
    $stripeMock->shouldReceive('hasIncompletePayment')->once()->andReturn(false);
    $stripeMock->shouldReceive('getBillingPortalUrl')->once()->andReturn('https://billing.stripe.com');

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();
});

it('returns incomplete payment url when payment is incomplete', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_test123']);
    SubscriptionProduct::factory()->create();

    DB::table('subscriptions')->insert([
        'user_id' => $user->id,
        'type' => 'default',
        'stripe_id' => 'sub_test123',
        'stripe_status' => 'active',
        'stripe_price' => 'price_test',
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $subscription = $user->subscriptions()->first();

    $stripeMock = mock(StripeServiceContract::class);
    $stripeMock->shouldReceive('ensureStripeCustomer')->once();
    $stripeMock->shouldReceive('hasIncompletePayment')->once()->andReturn(true);
    $stripeMock->shouldReceive('getBillingPortalUrl')->once()->andReturn('https://billing.stripe.com');
    $stripeMock->shouldReceive('getIncompletePaymentUrl')
        ->once()
        ->with(Mockery::on(fn ($sub): bool => $sub->id === $subscription->id))
        ->andReturn('https://invoice.stripe.com/test_invoice');

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();
});

it('renders subscription page with trialing subscription', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_test123']);
    $product = SubscriptionProduct::factory()->create([
        'name' => 'Premium Plan',
        'stripe_price_id' => 'price_monthly_test',
    ]);

    DB::table('subscriptions')->insert([
        'user_id' => $user->id,
        'type' => 'premium-plan',
        'stripe_id' => 'sub_trial123',
        'stripe_status' => 'trialing',
        'stripe_price' => 'price_monthly_test',
        'quantity' => 1,
        'trial_ends_at' => now()->addDays(7),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $subscription = $user->subscriptions()->first();

    DB::table('subscription_items')->insert([
        'subscription_id' => $subscription->id,
        'stripe_id' => 'si_trial123',
        'stripe_product' => 'prod_test123',
        'stripe_price' => 'price_monthly_test',
        'quantity' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $stripeMock = mock(StripeServiceContract::class);
    $stripeMock->shouldReceive('ensureStripeCustomer')->once();
    $stripeMock->shouldReceive('hasIncompletePayment')->once()->andReturn(false);
    $stripeMock->shouldReceive('getBillingPortalUrl')->once()->andReturn('https://billing.stripe.com/session/test');

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();
});
