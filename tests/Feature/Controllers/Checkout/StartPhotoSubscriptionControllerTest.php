<?php

declare(strict_types=1);

use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoEntitlement;
use App\Data\Billing\PhotoModel;
use App\Data\FoodAnalysisData;
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

it('opens the photo plan for purchase once the scan quota is live', function (): void {
    $snapPro = SubscriptionProduct::query()->where('name', 'Snap Pro')->sole();
    $snapPro->update(['stripe_price_id' => 'price_snap_pro_monthly']);

    $this->get(route('checkout.start', $snapPro))->assertNotFound();

    $this->app->instance(ManagesPhotoAnalyses::class, new readonly class implements ManagesPhotoAnalyses
    {
        public function enabled(): bool
        {
            return true;
        }

        public function entitlement(?User $user, ?string $guestId): PhotoEntitlement
        {
            return new PhotoEntitlement(enabled: true, limit: 100);
        }

        public function analyze(PhotoAnalysisContext $context, Closure $analyze): FoodAnalysisData
        {
            return $analyze(PhotoModel::standard());
        }
    });

    $this->get(route('checkout.start', $snapPro))
        ->assertRedirect(route('register'))
        ->assertSessionHas('checkout.selected_product', $snapPro->id);
});
