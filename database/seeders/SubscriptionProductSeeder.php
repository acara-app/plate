<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SubscriptionProduct;
use Illuminate\Database\Seeder;

final class SubscriptionProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Personal',
                'description' => 'AI-powered meal plans personalized to your nutrition goals, preferences, and lifestyle.',
                'features' => [
                    '7-day free trial',
                    'Personalized weekly meal plans',
                    'Tailored to your dietary preferences',
                    'Recipes that match your goals',
                ],
                'price' => 9.00,
                'yearly_price' => 89.90, // 17% discount (~2 months free)
                'stripe_price_id' => 'acara-plate-personal-monthly',
                'yearly_stripe_price_id' => 'acara-plate-personal-yearly',
                'billing_interval' => 'monthly',
                'product_group' => 'trial', // Enables 7-day trial
                'popular' => true,
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
