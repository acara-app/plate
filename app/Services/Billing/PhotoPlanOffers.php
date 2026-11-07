<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\OffersSubscriptions;
use App\Enums\PhotoPlan;
use App\Models\SubscriptionProduct;

final readonly class PhotoPlanOffers implements OffersSubscriptions
{
    public function __construct(private PhotoPlanCatalog $catalog) {}

    public function present(SubscriptionProduct $product): SubscriptionProduct
    {
        if ($product->name !== 'Free' || ! $this->catalog->isSelling()) {
            return $product;
        }

        $offer = clone $product;
        $scans = config()->integer('snap.free_trial_scans', 2);
        $offer->setAttribute('name', 'Free trial');
        $offer->setAttribute('description', "Try {$scans} photo ".($scans === 1 ? 'scan' : 'scans').' free. Choose a plan when you are ready.');
        $offer->setAttribute('features', ["{$scans} free photo ".($scans === 1 ? 'scan' : 'scans').', with no daily reset', 'Save scans to your food log', 'Small monthly allowance for other AI tools', 'No credit card required']);

        return $offer;
    }

    public function available(SubscriptionProduct $product): bool
    {
        return $product->name === 'Free' || (PhotoPlan::fromProductName($product->name) instanceof PhotoPlan
            && $this->catalog->isSelling() && $this->catalog->product($product->name)?->id === $product->id);
    }
}
