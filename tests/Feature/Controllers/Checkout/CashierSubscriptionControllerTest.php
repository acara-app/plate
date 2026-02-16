<?php

declare(strict_types=1);

use App\Contracts\Services\StripeServiceContract;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Laravel\Cashier\Subscription;

it('creates monthly subscription checkout successfully', function (): void {
    $user = User::factory()->create();
    $product = SubscriptionProduct::factory()->create([
        'name' => 'Premium Plan',
        'stripe_price_id' => 'price_monthly_test',
    ]);

    // Create a simple test implementation
    $stripeMock = new class implements StripeServiceContract
    {
        public array $calls = [];

        public function ensureStripeCustomer(User $user): void {}

        public function getBillingPortalUrl(User $user, string $returnUrl): string
        {
            return '';
        }

        public function hasIncompletePayment(User $user, string $subscriptionType): bool
        {
            return false;
        }

        public function hasActiveSubscription(User $user): bool
        {
            $this->calls[] = ['method' => 'hasActiveSubscription', 'user' => $user->id];

            return false;
        }

        public function getPriceIdFromLookupKey(string $lookupKey): string
        {
            $this->calls[] = ['method' => 'getPriceIdFromLookupKey', 'lookupKey' => $lookupKey];

            return 'price_actual_id_123';
        }

        public function createSubscriptionCheckout(User $user, string $subscriptionType, string $priceId, string $successUrl, string $cancelUrl, array $metadata = [], ?int $trialDays = null): string
        {
            $this->calls[] = [
                'method' => 'createSubscriptionCheckout',
                'user' => $user->id,
                'subscriptionType' => $subscriptionType,
                'priceId' => $priceId,
            ];

            return 'https://checkout.stripe.com/session_123';
        }

        public function getIncompletePaymentUrl(Subscription $subscription): ?string
        {
            return null;
        }
    };

    app()->instance(StripeServiceContract::class, $stripeMock);

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

    // Create a simple test implementation
    $stripeMock = new class implements StripeServiceContract
    {
        public function ensureStripeCustomer(User $user): void {}

        public function getBillingPortalUrl(User $user, string $returnUrl): string
        {
            return '';
        }

        public function hasIncompletePayment(User $user, string $subscriptionType): bool
        {
            return false;
        }

        public function hasActiveSubscription(User $user): bool
        {
            return false;
        }

        public function getPriceIdFromLookupKey(string $lookupKey): string
        {
            return 'price_yearly_actual_456';
        }

        public function createSubscriptionCheckout(User $user, string $subscriptionType, string $priceId, string $successUrl, string $cancelUrl, array $metadata = [], ?int $trialDays = null): string
        {
            return 'https://checkout.stripe.com/session_456';
        }

        public function getIncompletePaymentUrl(Subscription $subscription): ?string
        {
            return null;
        }
    };

    app()->instance(StripeServiceContract::class, $stripeMock);

    $response = $this->actingAs($user)->post(route('checkout.subscription.store'), [
        'product_id' => $product->id,
        'billing_interval' => 'yearly',
    ]);

    $response->assertRedirect('https://checkout.stripe.com/session_456');
});

it('redirects when user already has active subscription', function (): void {
    $user = User::factory()->create();
    $product = SubscriptionProduct::factory()->create();

    // Create a simple test implementation
    $stripeMock = new class implements StripeServiceContract
    {
        public function ensureStripeCustomer(User $user): void {}

        public function getBillingPortalUrl(User $user, string $returnUrl): string
        {
            return '';
        }

        public function hasIncompletePayment(User $user, string $subscriptionType): bool
        {
            return false;
        }

        public function hasActiveSubscription(User $user): bool
        {
            return true;
        }

        public function getPriceIdFromLookupKey(string $lookupKey): ?string
        {
            return null;
        }

        public function createSubscriptionCheckout(User $user, string $subscriptionType, string $priceId, string $successUrl, string $cancelUrl, array $metadata = [], ?int $trialDays = null): string
        {
            return '';
        }

        public function getIncompletePaymentUrl(Subscription $subscription): ?string
        {
            return null;
        }
    };

    app()->instance(StripeServiceContract::class, $stripeMock);

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

    // Create a simple test implementation
    $stripeMock = new class implements StripeServiceContract
    {
        public function ensureStripeCustomer(User $user): void {}

        public function getBillingPortalUrl(User $user, string $returnUrl): string
        {
            return '';
        }

        public function hasIncompletePayment(User $user, string $subscriptionType): bool
        {
            return false;
        }

        public function hasActiveSubscription(User $user): bool
        {
            return false;
        }

        public function getPriceIdFromLookupKey(string $lookupKey): ?string
        {
            return null;
        }

        public function createSubscriptionCheckout(User $user, string $subscriptionType, string $priceId, string $successUrl, string $cancelUrl, array $metadata = [], ?int $trialDays = null): string
        {
            return '';
        }

        public function getIncompletePaymentUrl(Subscription $subscription): ?string
        {
            return null;
        }
    };

    app()->instance(StripeServiceContract::class, $stripeMock);

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

    // Create a simple test implementation
    $stripeMock = new class implements StripeServiceContract
    {
        public function ensureStripeCustomer(User $user): void {}

        public function getBillingPortalUrl(User $user, string $returnUrl): string
        {
            return '';
        }

        public function hasIncompletePayment(User $user, string $subscriptionType): bool
        {
            return false;
        }

        public function hasActiveSubscription(User $user): bool
        {
            return false;
        }

        public function getPriceIdFromLookupKey(string $lookupKey): ?string
        {
            return null;
        }

        public function createSubscriptionCheckout(User $user, string $subscriptionType, string $priceId, string $successUrl, string $cancelUrl, array $metadata = [], ?int $trialDays = null): string
        {
            return '';
        }

        public function getIncompletePaymentUrl(Subscription $subscription): ?string
        {
            return null;
        }
    };

    app()->instance(StripeServiceContract::class, $stripeMock);

    $response = $this->actingAs($user)->post(route('checkout.subscription.store'), [
        'product_id' => $product->id,
        'billing_interval' => 'yearly',
    ]);

    $response->assertRedirect(route('checkout.subscription'));
    $response->assertSessionHas('error', 'Failed to initiate subscription. Please try again.');
});
