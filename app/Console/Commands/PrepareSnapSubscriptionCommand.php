<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\PhotoPlan;
use App\Models\SubscriptionProduct;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;

#[Signature('snap:prepare-subscription {--plan=snap-pro : starter or snap-pro} {--price-id= : Existing USD monthly Stripe price ID}')]
#[Description('Prepare a photo subscription catalog entry without creating or modifying Stripe resources')]
final class PrepareSnapSubscriptionCommand extends Command
{
    public function handle(): int
    {
        $option = $this->option('plan');
        $plan = is_string($option) ? PhotoPlan::tryFrom($option) : null;
        if (! $plan instanceof PhotoPlan) {
            $this->error('Choose --plan=starter or --plan=snap-pro.');

            return self::FAILURE;
        }

        $priceId = $this->option('price-id');
        $priceId = is_string($priceId) && $priceId !== '' ? $priceId : null;
        if ($priceId !== null && ! $this->priceMatches($plan, $priceId)) {
            return self::FAILURE;
        }

        $product = SubscriptionProduct::query()->updateOrCreate(['name' => $plan->productName()], [
            'price' => $plan->monthlyPriceCents() / 100,
            'description' => __('common.photo_plans.'.$plan->value.'.description', [], 'en'),
            'features' => __('common.photo_plans.'.$plan->value.'.features', [], 'en'),
            'stripe_lookup_key' => $plan->lookupKey(), 'billing_interval' => 'monthly',
            'product_group' => 'subscription', 'popular' => $plan === PhotoPlan::Starter, 'purchasable' => true,
            'yearly_price' => null, 'yearly_stripe_price_id' => null, 'yearly_stripe_lookup_key' => null,
        ]);
        if ($priceId !== null) {
            $product->update(['stripe_price_id' => $priceId]);
        }

        $this->info("{$plan->productName()} prepared. Sales require Stripe configuration and a purchasable product.");

        return self::SUCCESS;
    }

    private function priceMatches(PhotoPlan $plan, string $priceId): bool
    {
        if (! str_starts_with($priceId, 'price_')) {
            $this->error('Supply a Stripe price ID beginning with price_.');

            return false;
        }

        try {
            $price = Cashier::stripe()->prices->retrieve($priceId);
        } catch (ApiErrorException $apiErrorException) {
            $this->error("Stripe could not load {$priceId}: {$apiErrorException->getMessage()}");

            return false;
        }

        if (! $price->active || $price->currency !== 'usd' || $price->unit_amount !== $plan->monthlyPriceCents()
            || $price->recurring === null || $price->recurring->interval !== 'month' || $price->recurring->interval_count !== 1
            || $price->lookup_key !== $plan->lookupKey()) {
            $this->error(sprintf('The price must be active, USD $%.2f/month, and use the %s lookup key.', $plan->monthlyPriceCents() / 100, $plan->productName()));

            return false;
        }

        return true;
    }
}
