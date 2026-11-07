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
        if (! $this->catalog->isSelling()) {
            return $product;
        }

        $plan = PhotoPlan::fromProductName($product->name);
        if ($product->name !== 'Free' && ! $plan instanceof PhotoPlan) {
            return $product;
        }

        $offer = clone $product;

        if ($plan instanceof PhotoPlan) {
            $offer->setAttribute('description', __('common.photo_plans.'.$plan->value.'.description'));
            $offer->setAttribute('features', __('common.photo_plans.'.$plan->value.'.features'));

            return $offer;
        }

        $scans = config()->integer('snap.free_trial_scans', 2);
        $plural = $scans === 1 ? 'one' : 'other';
        $offer->setAttribute('name', __('common.photo_plans.free.name'));
        $offer->setAttribute('description', strtr(__('common.photo_plans.free.description_'.$plural), ['{{count}}' => (string) $scans]));
        $offer->setAttribute('features', __('common.photo_plans.free.features'));

        return $offer;
    }

    public function available(SubscriptionProduct $product): bool
    {
        return $product->name === 'Free' || (PhotoPlan::fromProductName($product->name) instanceof PhotoPlan
            && $this->catalog->isSelling() && $this->catalog->product($product->name)?->id === $product->id);
    }
}
