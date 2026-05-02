<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SubscriptionProduct;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

final class ValidateStripePricesCommand extends Command
{
    protected $signature = 'billing:validate-stripe-prices
        {--strict : Treat any unconfigured paid product as a failure}';

    protected $description = 'Verify subscription_products carry production-shaped Stripe price IDs (price_…) before launch';

    public function handle(): int
    {
        /** @var Collection<int, SubscriptionProduct> $products */
        $products = SubscriptionProduct::query()->orderBy('price')->get();

        if ($products->isEmpty()) {
            $this->error('No subscription_products are seeded. Run `php artisan db:seed --class=SubscriptionProductSeeder` first.');

            return self::FAILURE;
        }

        $rows = [];
        $hasErrors = false;
        $strict = (bool) $this->option('strict');

        foreach ($products as $product) {
            $tierIsPaid = $product->price > 0;
            $monthly = $this->classifyPrice((string) ($product->stripe_price_id ?? ''), $tierIsPaid, $strict);
            $yearly = $this->classifyPrice((string) ($product->yearly_stripe_price_id ?? ''), $tierIsPaid, $strict);

            if ($monthly['failed'] || $yearly['failed']) {
                $hasErrors = true;
            }

            $rows[] = [
                $product->name,
                $tierIsPaid ? '$'.number_format((float) $product->price, 2).' / mo' : 'free',
                $monthly['display'],
                $yearly['display'],
            ];
        }

        $this->table(['Product', 'Price', 'Monthly Stripe ID', 'Yearly Stripe ID'], $rows);

        if ($hasErrors) {
            $this->newLine();
            $this->error('One or more Stripe price IDs are missing or look like placeholders.');
            $this->line('Real Stripe price IDs start with "price_". Run `php artisan billing:sync-stripe-prices` to resolve each lookup key from Stripe and populate stripe_price_id before enabling premium upgrades.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('All paid products carry production-shaped Stripe price IDs.');

        return self::SUCCESS;
    }

    /**
     * @return array{display: string, failed: bool}
     */
    private function classifyPrice(string $value, bool $tierIsPaid, bool $strict): array
    {
        if ($value === '') {
            return [
                'display' => $tierIsPaid ? '⚠ missing' : '—',
                'failed' => $tierIsPaid && $strict,
            ];
        }

        if (str_starts_with($value, 'price_')) {
            return ['display' => $value, 'failed' => false];
        }

        if (! $tierIsPaid) {
            return ['display' => $value, 'failed' => false];
        }

        return [
            'display' => '⚠ '.$value.' (placeholder)',
            'failed' => true,
        ];
    }
}
