<?php

declare(strict_types=1);

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
