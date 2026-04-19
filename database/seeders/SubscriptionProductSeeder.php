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
                'description' => 'Start with Altani for everyday health questions.',
                'features' => [
                    '10 chats a day with Altani',
                    'Plain-language answers',
                    'Guidance shaped by your profile',
                    'No credit card required',
                ],
                'price' => 0.00,
                'yearly_price' => null,
                'stripe_price_id' => null,
                'yearly_stripe_price_id' => null,
                'billing_interval' => null,
                'product_group' => 'free',
                'popular' => false,
                'coming_soon' => false,
            ],
            [
                'name' => 'Basic',
                'description' => 'Unlimited conversations for ongoing personal health guidance.',
                'features' => [
                    'Unlimited chat with Altani',
                    'Answers shaped by your profile and goals',
                    'Support for daily decisions',
                    'Guidance for habits, routines, and progress',
                ],
                'price' => 9.00,
                'yearly_price' => 89.90,
                'stripe_price_id' => 'acara-plate-personal-monthly',
                'yearly_stripe_price_id' => 'acara-plate-personal-yearly',
                'billing_interval' => 'monthly',
                'product_group' => 'subscription',
                'popular' => false,
                'coming_soon' => false,
            ],
            [
                'name' => 'Plus',
                'description' => 'Health AI that remembers your context and adapts over time.',
                'features' => [
                    'Everything in Basic',
                    'Memory for your preferences, goals, and context',
                    'Guidance that evolves with your goals and routines',
                    "Syncs with your iPhone's Health app",
                    'Fastest, smartest AI responses',
                    '7-day free trial',
                ],
                'price' => 18.00,
                'yearly_price' => 179.00,
                'stripe_price_id' => 'acara-plate-plus-monthly',
                'yearly_stripe_price_id' => 'acara-plate-plus-yearly',
                'billing_interval' => 'monthly',
                'product_group' => 'trial',
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
