<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SubscriptionProduct;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;

/**
 * @codeCoverageIgnore
 */
final class BootstrapStripeProductsCommand extends Command
{
    protected $signature = 'billing:bootstrap-stripe-products
        {--dry-run : Print the plan but do not write to Stripe}';

    protected $description = 'Idempotently create the Stripe Products and Prices for each seeded subscription_product (matched by lookup_key).';

    public function handle(): int
    {
        /** @var Collection<int, SubscriptionProduct> $products */
        $products = SubscriptionProduct::query()
            ->where(function (Builder $query): void {
                $query->whereNotNull('stripe_lookup_key')
                    ->orWhereNotNull('yearly_stripe_lookup_key');
            })
            ->orderBy('price')
            ->get();

        if ($products->isEmpty()) {
            $this->error('No subscription_products carry a lookup key. Seed the catalog first with `php artisan db:seed --class=SubscriptionProductSeeder`.');

            return self::FAILURE;
        }

        $secret = config('cashier.secret');
        if (! is_string($secret) || $secret === '') {
            $this->error('STRIPE_SECRET is not configured. Set it in .env before running this command.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $rows = [];
        $hasErrors = false;

        foreach ($products as $product) {
            $monthly = $this->ensurePrice($product, 'monthly', $dryRun);
            $yearly = $this->ensurePrice($product, 'yearly', $dryRun);

            if ($monthly['failed'] || $yearly['failed']) {
                $hasErrors = true;
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
            ['Product', 'Monthly lookup_key', 'Monthly result', 'Yearly lookup_key', 'Yearly result'],
            $rows,
        );

        if ($hasErrors) {
            $this->newLine();
            $this->error('One or more Stripe operations failed. See messages above.');

            return self::FAILURE;
        }

        $this->newLine();
        if ($dryRun) {
            $this->info('Dry run complete — no calls were made to Stripe.');
        } else {
            $this->info('Done. Run `php artisan billing:sync-stripe-prices` to populate stripe_price_id columns.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{display: string, failed: bool}
     */
    private function ensurePrice(SubscriptionProduct $product, string $interval, bool $dryRun): array
    {
        $lookupKey = $interval === 'yearly'
            ? $product->yearly_stripe_lookup_key
            : $product->stripe_lookup_key;

        if (! is_string($lookupKey) || $lookupKey === '') {
            return ['display' => '—', 'failed' => false];
        }

        $unitAmount = $this->unitAmountInCents($product, $interval);

        if ($unitAmount <= 0) {
            return ['display' => '⚠ no price configured', 'failed' => true];
        }

        try {
            $existing = Cashier::stripe()->prices->all([
                'lookup_keys' => [$lookupKey],
                'limit' => 1,
            ]);

            if (! empty($existing->data)) {
                return ['display' => $existing->data[0]->id.' (existing)', 'failed' => false];
            }
        } catch (ApiErrorException $apiErrorException) {
            return ['display' => '⚠ '.$apiErrorException->getMessage(), 'failed' => true];
        }

        if ($dryRun) {
            return ['display' => 'would create '.$lookupKey, 'failed' => false];
        }

        try {
            $stripeProduct = Cashier::stripe()->products->create([
                'name' => sprintf('%s (%s)', $product->name, $interval),
                'metadata' => [
                    'plate_tier' => mb_strtolower($product->name),
                    'plate_interval' => $interval,
                ],
            ]);

            $price = Cashier::stripe()->prices->create([
                'product' => $stripeProduct->id,
                'unit_amount' => $unitAmount,
                'currency' => mb_strtolower((string) (config('cashier.currency', 'usd'))),
                'recurring' => ['interval' => $interval === 'yearly' ? 'year' : 'month'],
                'lookup_key' => $lookupKey,
            ]);

            return ['display' => $price->id.' (created)', 'failed' => false];
        } catch (ApiErrorException $apiErrorException) {
            return ['display' => '⚠ '.$apiErrorException->getMessage(), 'failed' => true];
        }
    }

    private function unitAmountInCents(SubscriptionProduct $product, string $interval): int
    {
        $value = $interval === 'yearly' ? $product->yearly_price : $product->price;

        return (int) round((float) $value * 100);
    }
}
