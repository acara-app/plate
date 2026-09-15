<?php

declare(strict_types=1);

use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Contracts\Billing\OffersSubscriptions;
use App\Data\Billing\PhotoEntitlement;
use App\Data\Billing\PhotoOffer;
use App\Models\SubscriptionProduct;
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

it('serializes the photo offer with the keys the upgrade call to action reads', function (): void {
    $entitlement = new PhotoEntitlement(
        enabled: true,
        limit: 30,
        used: 30,
        resetsAt: '2026-10-01T00:00:00+00:00',
        mode: 'premium',
        canUpgrade: true,
        offer: new PhotoOffer(productId: 7, name: 'Snap Pro', formattedPrice: '$9', scans: 300),
    );

    expect($entitlement->toArray())
        ->toHaveKeys(['can_upgrade', 'resets_at', 'exhausted', 'remaining'])
        ->and($entitlement->toArray()['offer'])
        ->toHaveKeys(['product_id', 'formatted_price', 'name', 'scans']);
});
