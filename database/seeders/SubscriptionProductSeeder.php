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
                'name' => 'Weekly Meal Plan',
                'description' => 'AI-powered meal plans personalized to your nutrition goals, preferences, and lifestyle.',
                'features' => [
                    'Personalized weekly meal plans',
                    'Tailored to your dietary preferences',
                    'Recipes that match your goals',
                ],
                'price' => 9,
                'yearly_price' => 97.2,
                'stripe_price_id' => 'acara-plate-monthly',
                'yearly_stripe_price_id' => 'acare-plate-yearly',
                'billing_interval' => 'monthly',
                'product_group' => 'weekly-meal-plan',
                'popular' => true,
                'coming_soon' => false,
            ],
        ];

        foreach ($products as $product) {
            SubscriptionProduct::query()->updateOrCreate(
                ['name' => $product['name']],
                // Find by name
                $product
            );
        }
    }
}
