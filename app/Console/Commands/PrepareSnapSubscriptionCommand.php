<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SubscriptionProduct;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Laravel\Cashier\Cashier;

#[Signature('snap:prepare-subscription {--plan=snap-pro : starter or snap-pro} {--price-id= : Existing USD monthly Stripe price ID}')]
#[Description('Prepare a photo subscription catalog entry without creating or modifying Stripe resources')]
final class PrepareSnapSubscriptionCommand extends Command
{
    public function handle(): int
    {
        $plan = $this->option('plan');
        if (! in_array($plan, ['starter', 'snap-pro'], true)) {
            $this->error('Choose --plan=starter or --plan=snap-pro.');

            return self::FAILURE;
        }

        $starter = $plan === 'starter';
        $name = $starter ? 'Starter' : 'Snap Pro';
        $amount = $starter ? 299 : 900;
        $lookupKey = $starter ? 'acara-plate-starter-monthly-v1' : 'acara-plate-snap-pro-monthly-v1';
        $priceId = $this->option('price-id');
        if (is_string($priceId) && $priceId !== '') {
            if (! str_starts_with($priceId, 'price_')) {
                $this->error('Supply a Stripe price ID beginning with price_.');

                return self::FAILURE;
            }

            $price = Cashier::stripe()->prices->retrieve($priceId);
            if (! $price->active || $price->currency !== 'usd' || $price->unit_amount !== $amount
                || $price->recurring === null || $price->recurring->interval !== 'month' || $price->recurring->interval_count !== 1
                || $price->lookup_key !== $lookupKey) {
                $this->error(sprintf('The price must be active, USD $%.2f/month, and use the %s lookup key.', $amount / 100, $name));

                return self::FAILURE;
            }
        }

        $product = SubscriptionProduct::query()->updateOrCreate(['name' => $name], [
            'price' => $amount / 100,
            'description' => $starter ? 'Make food tracking a simple daily habit.' : 'Higher limits for people who track every meal.',
            'features' => $starter
                ? ['Every Acara feature, including photo scans', 'Premium AI models', 'Generous daily and weekly limits', 'Cancel anytime']
                : ['Everything in Starter', 'Higher daily and weekly limits', 'Built for heavy daily tracking', 'Cancel anytime'],
            'stripe_lookup_key' => $lookupKey, 'billing_interval' => 'monthly',
            'product_group' => 'subscription', 'popular' => $starter, 'purchasable' => true,
            'yearly_price' => null, 'yearly_stripe_price_id' => null, 'yearly_stripe_lookup_key' => null,
        ]);
        if (is_string($priceId) && $priceId !== '') {
            $product->update(['stripe_price_id' => $priceId]);
        }

        $this->info($name.' prepared. Sales require a valid paid photo model, Stripe configuration, and a purchasable product.');

        return self::SUCCESS;
    }
}
