<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

use App\Models\SubscriptionProduct;

interface OffersSubscriptions
{
    public function present(SubscriptionProduct $product): SubscriptionProduct;

    public function available(SubscriptionProduct $product): bool;
}
