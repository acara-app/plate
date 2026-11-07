<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\PhotoPlan;
use App\Models\SubscriptionProduct;
use Illuminate\Database\Eloquent\Builder;

final class PhotoPlanCatalog
{
    private ?bool $selling = null;

    /** @var array<string, SubscriptionProduct|null> */
    private array $products = [];

    public function __construct(private readonly StripeBilling $stripe) {}

    public function isSelling(): bool
    {
        return $this->selling ??= $this->stripe->isConfigured() && $this->product() instanceof SubscriptionProduct;
    }

    public function product(?string $name = null): ?SubscriptionProduct
    {
        $key = $name ?? '';

        if (! array_key_exists($key, $this->products)) {
            $this->products[$key] = SubscriptionProduct::query()->where('purchasable', true)
                ->where(function (Builder $catalog): void {
                    foreach (PhotoPlan::cases() as $plan) {
                        $catalog->orWhere(fn (Builder $query): Builder => $query->where('name', $plan->productName())
                            ->where('price', $plan->monthlyPriceCents() / 100)->where('stripe_lookup_key', $plan->lookupKey()));
                    }
                })
                ->when($name !== null, fn (Builder $query): Builder => $query->where('name', $name))
                ->whereNotNull('stripe_price_id')->where('stripe_price_id', '!=', '')->orderBy('price')->first();
        }

        return $this->products[$key];
    }
}
