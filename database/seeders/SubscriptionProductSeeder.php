<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SubscriptionProduct;
use Illuminate\Database\Seeder;

final class SubscriptionProductSeeder extends Seeder
{
    public function run(): void
    {
        SubscriptionProduct::query()->where('name', 'Personal')->delete();

        $products = [
            [
                'name' => 'Free',
                'description' => 'Start with Altani for everyday health questions with a limited free credit budget.',
                'features' => [
                    'Core chat with Altani',
                    'Guidance shaped by your profile',
                    'Limited free credits each month',
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
                'name' => 'Basic',
                'description' => 'More room to use Plate regularly, plus planning and meal photo analysis.',
                'features' => [
                    'Higher chat limits for ongoing guidance',
                    'AI Meal Planner',
                    'Meal photo analysis',
                    'Answers shaped by your profile and goals',
                ],
                'price' => 9.00,
                'yearly_price' => 89.90,
                'stripe_price_id' => null,
                'stripe_lookup_key' => 'acara-plate-personal-monthly',
                'yearly_stripe_price_id' => null,
                'yearly_stripe_lookup_key' => 'acara-plate-personal-yearly',
                'billing_interval' => 'monthly',
                'product_group' => 'subscription',
                'popular' => true,
                'coming_soon' => false,
            ],
            [
                'name' => 'Plus',
                'description' => 'Plate that remembers your context, syncs health data, and gives you the highest limits.',
                'features' => [
                    'Everything in Basic',
                    'Memory for your preferences, goals, and context',
                    "Syncs with your iPhone's Health app",
                    'Highest chat limits',
                    '7-day free trial',
                ],
                'price' => 18.00,
                'yearly_price' => 179.00,
                'stripe_price_id' => null,
                'stripe_lookup_key' => 'acara-plate-plus-monthly',
                'yearly_stripe_price_id' => null,
                'yearly_stripe_lookup_key' => 'acara-plate-plus-yearly',
                'billing_interval' => 'monthly',
                'product_group' => 'trial',
                'popular' => false,
                'coming_soon' => false,
            ],
        ];

        foreach ($products as $product) {
            SubscriptionProduct::query()->updateOrCreate(
                ['name' => $product['name']],
                $product
            );
        }
    }
}
