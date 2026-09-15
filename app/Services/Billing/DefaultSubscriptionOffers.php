<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\OffersSubscriptions;
use App\Models\SubscriptionProduct;

final readonly class DefaultSubscriptionOffers implements OffersSubscriptions
{
    public function present(SubscriptionProduct $product): SubscriptionProduct
    {
        return $product;
    }

    public function available(SubscriptionProduct $product): bool
    {
        return $product->purchasable;
    }
}
