<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Contracts\Services\StripeServiceContract;
use App\Models\SubscriptionProduct;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

final class SyncStripePricesCommand extends Command
{
    protected $signature = 'billing:sync-stripe-prices
        {--dry-run : Resolve lookup keys but do not write to the database}';

    protected $description = 'Resolve each subscription_product lookup key into a real price_… via the Stripe API and write it to stripe_price_id';

    public function handle(StripeServiceContract $stripe): int
    {
        /** @var Collection<int, SubscriptionProduct> $products */
        $products = SubscriptionProduct::query()
            ->where(function ($query): void {
                $query->whereNotNull('stripe_lookup_key')
                    ->orWhereNotNull('yearly_stripe_lookup_key');
            })
            ->orderBy('price')
            ->get();

        if ($products->isEmpty()) {
            $this->error('No subscription_products carry a lookup key. Seed the catalog first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $rows = [];
        $hasErrors = false;

        foreach ($products as $product) {
            $monthly = $this->resolve($stripe, (string) ($product->stripe_lookup_key ?? ''));
            $yearly = $this->resolve($stripe, (string) ($product->yearly_stripe_lookup_key ?? ''));

            if ($monthly['failed'] || $yearly['failed']) {
                $hasErrors = true;
            }

            if (! $dryRun) {
                $product->update([
                    'stripe_price_id' => $monthly['price_id'] ?? $product->stripe_price_id,
                    'yearly_stripe_price_id' => $yearly['price_id'] ?? $product->yearly_stripe_price_id,
                ]);
            }

            $rows[] = [
                $product->name,
                $product->stripe_lookup_key ?? '—',
                $monthly['display'],
                $product->yearly_stripe_lookup_key ?? '—',
                $yearly['display'],
            ];
        }

        $this->table(
            ['Product', 'Monthly lookup_key', 'Monthly price_id', 'Yearly lookup_key', 'Yearly price_id'],
            $rows,
        );

        if ($hasErrors) {
            $this->newLine();
            $this->error('One or more lookup keys could not be resolved. Check that the keys exist in your Stripe account.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info($dryRun ? 'Dry run complete — no changes written.' : 'Stripe price IDs synced into subscription_products.');

        return self::SUCCESS;
    }

    /**
     * @return array{display: string, price_id: ?string, failed: bool}
     */
    private function resolve(StripeServiceContract $stripe, string $lookupKey): array
    {
        if ($lookupKey === '') {
            return ['display' => '—', 'price_id' => null, 'failed' => false];
        }

        $priceId = $stripe->getPriceIdFromLookupKey($lookupKey);

        if (! is_string($priceId) || ! str_starts_with($priceId, 'price_')) {
            return ['display' => '⚠ '.$lookupKey.' (not found)', 'price_id' => null, 'failed' => true];
        }

        return ['display' => $priceId, 'price_id' => $priceId, 'failed' => false];
    }
}
