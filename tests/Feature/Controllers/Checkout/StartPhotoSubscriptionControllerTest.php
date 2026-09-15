<?php

declare(strict_types=1);

use App\Contracts\Billing\OffersSubscriptions;
use App\Models\SubscriptionProduct;
use App\Models\User;

it('preserves the chosen offer through account creation without starting payment', function (bool $authenticated): void {
    $product = SubscriptionProduct::factory()->create(['price' => 9, 'stripe_price_id' => 'price_test']);
    if ($authenticated) {
        $this->actingAs(User::factory()->create());
    }

    $this->withSession(['snap_to_track.upgrade_draft' => 'saved-result'])
        ->get(route('checkout.start', $product))
        ->assertRedirect(route($authenticated ? 'checkout.subscription' : 'register'))
        ->assertSessionHas('url.intended', route('checkout.subscription', absolute: false))
        ->assertSessionHas('checkout.selected_product', $product->id)
        ->assertSessionHas('snap_to_track.upgrade_draft', 'saved-result');
})->with([false, true]);

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
