<?php

declare(strict_types=1);

use App\Contracts\Services\StripeServiceContract;
use App\Http\Controllers\Checkout\CashierSubscriptionController;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use Mockery\MockInterface;
use Tests\Helpers\FakeGuestCheckout;

covers(CashierSubscriptionController::class);

beforeEach(function (): void {
    offerAllSubscriptions();
});

it('starts monthly checkout for a member without a billing interval', function (): void {
    $product = SubscriptionProduct::factory()->create(['stripe_lookup_key' => 'lookup_monthly']);
    $this->mock(StripeServiceContract::class, function (MockInterface $mock): void {
        $mock->shouldReceive('hasActiveSubscription')->andReturnFalse();
        $mock->shouldReceive('getPriceIdFromLookupKey')->with('lookup_monthly')->andReturn('price_monthly');
        $mock->shouldReceive('createSubscriptionCheckout')->once()->andReturn('https://checkout.stripe.com/session_123');
    });

    $this->actingAs(User::factory()->create())->post(route('checkout.subscription.store'), ['product_id' => $product->id])
        ->assertRedirect('https://checkout.stripe.com/session_123');
});

it('rejects yearly checkout for a member even when the product has a yearly price', function (): void {
    $product = SubscriptionProduct::factory()->withYearlyPricing()->create();
    $this->mock(StripeServiceContract::class);

    $this->actingAs(User::factory()->create())->postJson(route('checkout.subscription.store'), [
        'product_id' => $product->id,
        'billing_interval' => 'yearly',
    ])->assertUnprocessable()->assertJsonValidationErrors('billing_interval');
});

it('redirects when user already has active subscription', function (): void {
    $user = User::factory()->create();
    $product = SubscriptionProduct::factory()->create();

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

        public function getIncompletePaymentUrlForUser(User $user): ?string
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
    $response->assertInertiaFlash('toast.type', 'error')
        ->assertInertiaFlash('toast.message', 'You already have an active subscription. Use the billing portal to manage it.');
});

it('redirects when price lookup key not found', function (): void {
    $user = User::factory()->create();
    $product = SubscriptionProduct::factory()->create([
        'name' => 'Mystery Plan',
        'stripe_price_id' => 'price_invalid',
        'stripe_lookup_key' => 'lookup_mystery_monthly',
    ]);

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

        public function getIncompletePaymentUrlForUser(User $user): ?string
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
    $response->assertInertiaFlash('toast.type', 'error')
        ->assertInertiaFlash('toast.message', 'Failed to initiate subscription. Please try again.');
});

it('validates required fields', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('checkout.subscription.store'), []);

    $response->assertSessionHasErrors('product_id')->assertSessionDoesntHaveErrors('billing_interval');
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

it('sends a guest directly to Stripe for the selected monthly plan', function (string $plan): void {
    $product = SubscriptionProduct::factory()->create([
        'name' => $plan,
        'coming_soon' => false,
        'stripe_price_id' => 'price_monthly',
    ]);
    $checkout = FakeGuestCheckout::install();

    $this->withHeader('X-Inertia', 'true')->post(route('checkout.subscription.store'), ['product_id' => $product->id])
        ->assertConflict()->assertHeader('X-Inertia-Location', 'https://checkout.stripe.com/c/pay/cs_test_guest_1');

    expect($checkout->sessions[0]['price'])->toBe('price_monthly')
        ->and($checkout->sessions[0]['metadata']['product_name'])->toBe($plan);
})->with(['Starter', 'Snap Pro']);

it('closes the old Stripe checkout when a guest changes plans', function (): void {
    $starter = sellPhotoPlans();
    $pro = SubscriptionProduct::query()->where('name', 'Snap Pro')->sole();
    $pro->update(['purchasable' => true, 'stripe_price_id' => 'price_pro']);
    $checkout = FakeGuestCheckout::install();
    $this->withCookie('photo_guest', (string) Str::uuid());

    $this->post(route('checkout.subscription.store'), ['product_id' => $starter->id])
        ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_guest_1');
    $this->post(route('checkout.subscription.store'), ['product_id' => $pro->id])
        ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_guest_2');

    expect($checkout->expiredSessions)->toBe(['cs_test_guest_1']);
});

it('lets unverified users pay and sends repeat clicks back to the same Stripe checkout', function (): void {
    $user = User::factory()->unverified()->create();
    $product = SubscriptionProduct::factory()->create([
        'name' => 'Starter',
        'stripe_price_id' => 'price_monthly_test',
        'stripe_lookup_key' => 'lookup_starter_monthly',
    ]);

    $this->mock(StripeServiceContract::class, function (MockInterface $mock) use ($user): void {
        $mock->shouldReceive('hasActiveSubscription')->andReturnFalse();
        $mock->shouldReceive('getPriceIdFromLookupKey')->with('lookup_starter_monthly')->andReturn('price_actual_id_123');
        $mock->shouldReceive('createSubscriptionCheckout')
            ->once()
            ->withArgs(fn (User $checkoutUser): bool => $checkoutUser->is($user))
            ->andReturn('https://checkout.stripe.com/session_unverified');
    });

    $subscribe = fn () => $this->actingAs($user)->post(route('checkout.subscription.store'), [
        'product_id' => $product->id,
        'billing_interval' => 'monthly',
    ]);

    $subscribe()->assertRedirect('https://checkout.stripe.com/session_unverified');
    $subscribe()->assertRedirect('https://checkout.stripe.com/session_unverified');
});

it('rejects a yearly guest checkout before opening Stripe', function (): void {
    $product = sellPhotoPlans();
    $checkout = FakeGuestCheckout::install();

    $this->postJson(route('checkout.subscription.store'), [
        'product_id' => $product->id,
        'billing_interval' => 'yearly',
    ])->assertUnprocessable()->assertJsonValidationErrors('billing_interval');

    expect($checkout->sessions)->toBeEmpty();
});
