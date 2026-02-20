<?php

declare(strict_types=1);

use App\Contracts\Services\StripeServiceContract;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Subscription;

it('calls stripe service for user without stripe id', function (): void {
    $user = User::factory()->create(['stripe_id' => null]);
    SubscriptionProduct::factory()->count(3)->create();

    $stripeMock = new class implements StripeServiceContract
    {
        public bool $ensureStripeCustomerCalled = false;

        public bool $getBillingPortalUrlCalled = false;

        public function ensureStripeCustomer(User $user): void
        {
            $this->ensureStripeCustomerCalled = true;
            expect($user)->toBeInstanceOf(User::class);
        }

        public function getBillingPortalUrl(User $user, string $returnUrl): string
        {
            $this->getBillingPortalUrlCalled = true;

            return 'https://billing.stripe.com/session/test';
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

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();

    expect($stripeMock->ensureStripeCustomerCalled)->toBeTrue();
    expect($stripeMock->getBillingPortalUrlCalled)->toBeTrue();
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

    $stripeMock = new class implements StripeServiceContract
    {
        public bool $ensureStripeCustomerCalled = false;

        public bool $hasIncompletePaymentCalled = false;

        public bool $getBillingPortalUrlCalled = false;

        public function ensureStripeCustomer(User $user): void
        {
            $this->ensureStripeCustomerCalled = true;
        }

        public function getBillingPortalUrl(User $user, string $returnUrl): string
        {
            $this->getBillingPortalUrlCalled = true;

            return 'https://billing.stripe.com/session/test';
        }

        public function hasIncompletePayment(User $user, string $subscriptionType): bool
        {
            $this->hasIncompletePaymentCalled = true;

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

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();

    expect($stripeMock->ensureStripeCustomerCalled)->toBeTrue();
    expect($stripeMock->hasIncompletePaymentCalled)->toBeTrue();
    expect($stripeMock->getBillingPortalUrlCalled)->toBeTrue();
});

it('detects yearly subscription correctly', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_test123']);
    SubscriptionProduct::factory()->create([
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

    $stripeMock = new class implements StripeServiceContract
    {
        public bool $ensureStripeCustomerCalled = false;

        public bool $hasIncompletePaymentCalled = false;

        public bool $getBillingPortalUrlCalled = false;

        public function ensureStripeCustomer(User $user): void
        {
            $this->ensureStripeCustomerCalled = true;
        }

        public function getBillingPortalUrl(User $user, string $returnUrl): string
        {
            $this->getBillingPortalUrlCalled = true;

            return 'https://billing.stripe.com/session/test';
        }

        public function hasIncompletePayment(User $user, string $subscriptionType): bool
        {
            $this->hasIncompletePaymentCalled = true;

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

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();

    expect($stripeMock->ensureStripeCustomerCalled)->toBeTrue();
    expect($stripeMock->hasIncompletePaymentCalled)->toBeTrue();
    expect($stripeMock->getBillingPortalUrlCalled)->toBeTrue();
});

it('renders page when user has no active subscription', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_test123']);
    SubscriptionProduct::factory()->count(3)->create();

    $stripeMock = new class implements StripeServiceContract
    {
        public bool $ensureStripeCustomerCalled = false;

        public bool $getBillingPortalUrlCalled = false;

        public function ensureStripeCustomer(User $user): void
        {
            $this->ensureStripeCustomerCalled = true;
        }

        public function getBillingPortalUrl(User $user, string $returnUrl): string
        {
            $this->getBillingPortalUrlCalled = true;

            return 'https://billing.stripe.com/session/test';
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

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();

    expect($stripeMock->ensureStripeCustomerCalled)->toBeTrue();
    expect($stripeMock->getBillingPortalUrlCalled)->toBeTrue();
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

    $stripeMock = new class implements StripeServiceContract
    {
        public bool $ensureStripeCustomerCalled = false;

        public bool $hasIncompletePaymentCalled = false;

        public bool $getBillingPortalUrlCalled = false;

        public function ensureStripeCustomer(User $user): void
        {
            $this->ensureStripeCustomerCalled = true;
        }

        public function getBillingPortalUrl(User $user, string $returnUrl): string
        {
            $this->getBillingPortalUrlCalled = true;

            return 'https://billing.stripe.com/session/test';
        }

        public function hasIncompletePayment(User $user, string $subscriptionType): bool
        {
            $this->hasIncompletePaymentCalled = true;

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

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();

    expect($stripeMock->ensureStripeCustomerCalled)->toBeTrue();
    expect($stripeMock->hasIncompletePaymentCalled)->toBeTrue();
    expect($stripeMock->getBillingPortalUrlCalled)->toBeTrue();
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

    $stripeMock = new class implements StripeServiceContract
    {
        public bool $ensureStripeCustomerCalled = false;

        public bool $hasIncompletePaymentCalled = false;

        public bool $getBillingPortalUrlCalled = false;

        public function ensureStripeCustomer(User $user): void
        {
            $this->ensureStripeCustomerCalled = true;
        }

        public function getBillingPortalUrl(User $user, string $returnUrl): string
        {
            $this->getBillingPortalUrlCalled = true;

            return 'https://billing.stripe.com/session/test';
        }

        public function hasIncompletePayment(User $user, string $subscriptionType): bool
        {
            $this->hasIncompletePaymentCalled = true;

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

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();

    expect($stripeMock->ensureStripeCustomerCalled)->toBeTrue();
    expect($stripeMock->hasIncompletePaymentCalled)->toBeTrue();
    expect($stripeMock->getBillingPortalUrlCalled)->toBeTrue();
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

    $stripeMock = new class implements StripeServiceContract
    {
        public bool $ensureStripeCustomerCalled = false;

        public bool $hasIncompletePaymentCalled = false;

        public bool $getBillingPortalUrlCalled = false;

        public function ensureStripeCustomer(User $user): void
        {
            $this->ensureStripeCustomerCalled = true;
        }

        public function getBillingPortalUrl(User $user, string $returnUrl): string
        {
            $this->getBillingPortalUrlCalled = true;

            return 'https://billing.stripe.com';
        }

        public function hasIncompletePayment(User $user, string $subscriptionType): bool
        {
            $this->hasIncompletePaymentCalled = true;

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

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();

    expect($stripeMock->ensureStripeCustomerCalled)->toBeTrue();
    expect($stripeMock->hasIncompletePaymentCalled)->toBeTrue();
    expect($stripeMock->getBillingPortalUrlCalled)->toBeTrue();
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

    $stripeMock = new class($subscription->id) implements StripeServiceContract
    {
        public bool $ensureStripeCustomerCalled = false;

        public bool $hasIncompletePaymentCalled = false;

        public bool $getBillingPortalUrlCalled = false;

        public bool $getIncompletePaymentUrlCalled = false;

        public ?int $capturedSubscriptionId = null;

        public function __construct()
        {
            //
        }

        public function ensureStripeCustomer(User $user): void
        {
            $this->ensureStripeCustomerCalled = true;
        }

        public function getBillingPortalUrl(User $user, string $returnUrl): string
        {
            $this->getBillingPortalUrlCalled = true;

            return 'https://billing.stripe.com';
        }

        public function hasIncompletePayment(User $user, string $subscriptionType): bool
        {
            $this->hasIncompletePaymentCalled = true;

            return true;
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

        public function getIncompletePaymentUrl(Subscription $subscription): string
        {
            $this->getIncompletePaymentUrlCalled = true;
            $this->capturedSubscriptionId = $subscription->id;

            return 'https://invoice.stripe.com/test_invoice';
        }
    };

    app()->instance(StripeServiceContract::class, $stripeMock);

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();

    expect($stripeMock->ensureStripeCustomerCalled)->toBeTrue();
    expect($stripeMock->hasIncompletePaymentCalled)->toBeTrue();
    expect($stripeMock->getBillingPortalUrlCalled)->toBeTrue();
    expect($stripeMock->getIncompletePaymentUrlCalled)->toBeTrue();
    expect($stripeMock->capturedSubscriptionId)->toBe($subscription->id);
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

    $stripeMock = new class implements StripeServiceContract
    {
        public bool $ensureStripeCustomerCalled = false;

        public bool $hasIncompletePaymentCalled = false;

        public bool $getBillingPortalUrlCalled = false;

        public function ensureStripeCustomer(User $user): void
        {
            $this->ensureStripeCustomerCalled = true;
        }

        public function getBillingPortalUrl(User $user, string $returnUrl): string
        {
            $this->getBillingPortalUrlCalled = true;

            return 'https://billing.stripe.com/session/test';
        }

        public function hasIncompletePayment(User $user, string $subscriptionType): bool
        {
            $this->hasIncompletePaymentCalled = true;

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

    $response = $this->actingAs($user)->get(route('checkout.subscription'));

    $response->assertOk();

    expect($stripeMock->ensureStripeCustomerCalled)->toBeTrue();
    expect($stripeMock->hasIncompletePaymentCalled)->toBeTrue();
    expect($stripeMock->getBillingPortalUrlCalled)->toBeTrue();
});
