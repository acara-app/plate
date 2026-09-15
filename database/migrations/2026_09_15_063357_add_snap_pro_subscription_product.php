<?php

declare(strict_types=1);

use App\Models\SubscriptionProduct;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        SubscriptionProduct::query()->firstOrCreate(
            ['name' => 'Snap Pro'],
            [
                'name' => 'Snap Pro',
                'description' => 'More photo scans with our tested premium nutrition model.',
                'features' => [
                    '100 premium photo scans per billing month',
                    'Tested premium nutrition model',
                    'Save scans to your food log',
                    'Scans reset monthly; no rollover or overage charges',
                ],
                'price' => 9.00,
                'yearly_price' => null,
                'stripe_price_id' => null,
                'stripe_lookup_key' => 'acara-plate-snap-pro-monthly-v1',
                'yearly_stripe_price_id' => null,
                'yearly_stripe_lookup_key' => null,
                'billing_interval' => 'monthly',
                'product_group' => 'subscription',
                'popular' => false,
                'coming_soon' => false,
            ],
        );
    }
};
