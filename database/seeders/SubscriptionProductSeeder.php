<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SubscriptionProduct;
use Illuminate\Database\Seeder;

final class SubscriptionProductSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionProduct::query()->whereIn('name', ['Personal', 'Basic', 'Plus'])->delete();

        $products = [
            [
                'name' => 'Free',
                'description' => 'Use every open Acara Cloud feature with a limited included AI credit budget.',
                'features' => [
                    'All open Acara Cloud features',
                    '1,000 monthly AI credits',
                    '100 rolling daily AI credits',
                    'No credit card required',
                ],
                'price' => 0.00,
                'yearly_price' => null,
                'stripe_price_id' => null,
                'stripe_lookup_key' => null,
                'yearly_stripe_price_id' => null,
                'yearly_stripe_lookup_key' => null,
                'billing_interval' => null,
                'product_group' => 'free',
                'popular' => false,
                'coming_soon' => false,
            ],
            [
                'name' => 'Supporter',
                'description' => 'More Cloud AI credits for regular use while supporting open-source Plate.',
                'features' => [
                    'All open Acara Cloud features',
                    '6,000 monthly AI credits',
                    '500 rolling daily AI credits',
                    'Higher weekly AI credit limit',
                    'Supports open-source Acara Cloud',
                ],
                'price' => 9.00,
                'yearly_price' => 89.00,
                'stripe_price_id' => null,
                'stripe_lookup_key' => 'acara-plate-supporter-monthly-v1',
                'yearly_stripe_price_id' => null,
                'yearly_stripe_lookup_key' => 'acara-plate-supporter-yearly-v1',
                'billing_interval' => 'monthly',
                'product_group' => 'subscription',
                'popular' => true,
                'coming_soon' => false,
            ],
            [
                'name' => 'Pro',
                'description' => 'Highest Cloud AI limits and premium model access for daily planning and analysis.',
                'features' => [
                    'All open Acara Cloud features',
                    '10,000 monthly AI credits',
                    '1,000 rolling daily AI credits',
                    'Highest weekly AI credit limit',
                    'Pro model access when available',
                    'Priority Cloud capacity',
                ],
                'price' => 19.00,
                'yearly_price' => 190.00,
                'stripe_price_id' => null,
                'stripe_lookup_key' => 'acara-plate-pro-monthly-v1',
                'yearly_stripe_price_id' => null,
                'yearly_stripe_lookup_key' => 'acara-plate-pro-yearly-v1',
                'billing_interval' => 'monthly',
                'product_group' => 'subscription',
                'popular' => false,
                'coming_soon' => false,
            ],
        ];

        foreach ($products as $product) {
            SubscriptionProduct::query()->updateOrCreate(
                ['name' => $product['name']],
                $product,
            );
        }
    }
}
