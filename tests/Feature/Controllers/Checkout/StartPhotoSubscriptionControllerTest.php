<?php

declare(strict_types=1);

use App\Contracts\Billing\OffersSubscriptions;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('preserves the chosen offer through account creation without starting payment', function (bool $authenticated): void {
    offerAllSubscriptions();
    $product = SubscriptionProduct::factory()->create(['price' => 9, 'stripe_price_id' => 'price_test']);
    if ($authenticated) {
        $this->actingAs(User::factory()->create());
    }

    $this->get(route('checkout.start', $product))
        ->assertRedirect(route($authenticated ? 'checkout.subscription' : 'register'))
        ->assertSessionHas('checkout.selected_product', $product->id);

    expect(session('url.intended'))->toBe($authenticated ? null : route('checkout.subscription', absolute: false));
})->with([false, true]);

it('brings the chosen plan and billing interval back after sign-up', function (): void {
    offerAllSubscriptions();
    $product = SubscriptionProduct::factory()->create(['price' => 9, 'stripe_price_id' => 'price_monthly', 'yearly_stripe_lookup_key' => 'lookup_yearly']);

    $this->get(route('checkout.start', ['product' => $product, 'interval' => 'yearly']))->assertRedirect(route('register'));
    $this->post(route('register.store'), ['name' => 'Yearly Buyer', 'email' => 'yearly-buyer@example.com', 'password' => 'password1234', 'accepted_disclaimer' => '1', 'health_data_consent' => '1'])
        ->assertRedirect(route('checkout.subscription'));

    $this->get(route('checkout.subscription'))
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->where('selectedProductId', $product->id)
            ->where('selectedInterval', 'yearly'));
});

it('sends a returning chat user to log in rather than sign up', function (): void {
    offerAllSubscriptions();
    $product = SubscriptionProduct::factory()->create(['price' => 9, 'stripe_price_id' => 'price_test']);

    $this->get(route('checkout.start', ['product' => $product, 'returning' => 1]))->assertRedirect(route('login'));
});

it('refuses to start checkout for a plan that is not on offer', function (): void {
    $snapPro = SubscriptionProduct::query()->where('name', 'Snap Pro')->sole();
    $snapPro->update(['stripe_price_id' => 'price_snap_pro_monthly']);

    $this->app->instance(OffersSubscriptions::class, new readonly class implements OffersSubscriptions
    {
        public function present(SubscriptionProduct $product): SubscriptionProduct
        {
            return $product;
        }

        public function available(SubscriptionProduct $product): bool
        {
            return false;
        }
    });

    $this->get(route('checkout.start', $snapPro))->assertNotFound();
});
