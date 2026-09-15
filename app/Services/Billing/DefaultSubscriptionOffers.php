<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Contracts\Billing\OffersSubscriptions;
use App\Enums\SubscriptionTier;
use App\Models\SubscriptionProduct;

final readonly class DefaultSubscriptionOffers implements OffersSubscriptions
{
    public function __construct(private ManagesPhotoAnalyses $photoAnalyses) {}

    public function present(SubscriptionProduct $product): SubscriptionProduct
    {
        return $product;
    }

    public function available(SubscriptionProduct $product): bool
    {
        if (! $product->purchasable) {
            return false;
        }

        if (SubscriptionTier::fromProductName($product->name) === SubscriptionTier::Snap) {
            return $this->photoAnalyses->enabled();
        }

        return true;
    }
}
