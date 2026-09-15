<?php

declare(strict_types=1);

use Acara\AcaraCore\Services\Billing\CloudPhotoAnalyses;
use App\Contracts\Billing\ManagesPhotoAnalyses;

it('binds the Cloud extension without activating an unconfigured photo offer', function (): void {
    config()->set(['snap.enabled' => true, 'snap.model_approved' => false]);
    $access = resolve(ManagesPhotoAnalyses::class);
    expect($access)->toBeInstanceOf(CloudPhotoAnalyses::class)
        ->and($access->enabled())->toBeFalse()
        ->and($access->entitlement(null, null)->limit)->toBeNull();
});

it('does not offer a photo-specific subscription without the photo access integration', function (): void {
    $product = App\Models\SubscriptionProduct::factory()->create(['name' => 'Snap Pro', 'price' => 9, 'stripe_price_id' => 'price_test', 'purchasable' => true]);
    expect(resolve(App\Contracts\Billing\OffersSubscriptions::class)->available($product))->toBeFalse();
});
