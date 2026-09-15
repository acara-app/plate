<?php

declare(strict_types=1);

use App\Models\SubscriptionProduct;
use App\Contracts\Billing\OffersSubscriptions;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Services\Billing\NullPhotoAnalyses;

it('keeps community photo analysis available without Cloud quota tables', function (): void {
    config()->set('snap.enabled', true);
    $access = resolve(ManagesPhotoAnalyses::class);
    expect($access)->toBeInstanceOf(NullPhotoAnalyses::class)
        ->and($access->enabled())->toBeFalse()
        ->and($access->entitlement(null, null)->limit)->toBeNull();
});

it('does not offer a photo-specific subscription without the photo access integration', function (): void {
    $product = SubscriptionProduct::factory()->create(['name' => 'Snap Pro', 'price' => 9, 'stripe_price_id' => 'price_test', 'purchasable' => true]);
    expect(resolve(OffersSubscriptions::class)->available($product))->toBeFalse();
});
