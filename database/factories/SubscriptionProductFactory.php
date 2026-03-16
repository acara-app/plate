<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SubscriptionProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubscriptionProduct>
 */
final class SubscriptionProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 9.99, 99.99);
        $yearlyPrice = $price * 10;

        return [
            'name' => fake()->randomElement([
                'Basic Plan',
                'Pro Plan',
                'Premium Plan',
                'Enterprise Plan',
                'Starter Pack',
                'Professional',
            ]),
            'price' => $price,
            'description' => fake()->optional(0.8)->sentence(),
            'popular' => fake()->boolean(20),
            'stripe_price_id' => fake()->optional(0.7)->regexify('price_[a-zA-Z0-9]{24}'),
            'billing_interval' => fake()->randomElement(['month', 'year', 'week']),
            'product_group' => fake()->optional(0.6)->randomElement(['subscription', 'addon', 'premium']),
            'yearly_price' => fake()->optional(0.7)->passthrough($yearlyPrice),
            'yearly_stripe_price_id' => fake()->optional(0.7)->regexify('price_[a-zA-Z0-9]{24}'),
            'features' => fake()->optional(0.8)->passthrough([
                fake()->sentence(3),
                fake()->sentence(3),
                fake()->sentence(3),
            ]),
            'coming_soon' => fake()->boolean(10),
        ];
    }

    public function popular(): static
    {
        return $this->state(fn (array $attributes): array => [
            'popular' => true,
        ]);
    }

    public function comingSoon(): static
    {
        return $this->state(fn (array $attributes): array => [
            'coming_soon' => true,
        ]);
    }

    public function withYearlyPricing(): static
    {
        return $this->state(function (array $attributes): array {
            $price = is_numeric($attributes['price'] ?? null) ? (float) $attributes['price'] : 50.0;

            return [
                'yearly_price' => $price * 10,
                'yearly_stripe_price_id' => fake()->regexify('price_[a-zA-Z0-9]{24}'),
            ];
        });
    }

    public function monthly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'billing_interval' => 'month',
        ]);
    }

    public function yearly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'billing_interval' => 'year',
        ]);
    }
}
