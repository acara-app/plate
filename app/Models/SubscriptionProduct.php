<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\SubscriptionProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read float $price
 * @property-read string|null $description
 * @property-read bool $popular
 * @property-read string|null $stripe_price_id
 * @property-read string|null $billing_interval
 * @property-read string|null $product_group
 * @property-read float|null $yearly_price
 * @property-read string|null $yearly_stripe_price_id
 * @property-read array<int, string>|null $features
 * @property-read bool $coming_soon
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class SubscriptionProduct extends Model
{
    /** @use HasFactory<SubscriptionProductFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * Append computed attributes to JSON output
     */
    protected $appends = [
        'formatted_price',
        'formatted_yearly_price',
        'yearly_savings',
        'yearly_savings_percentage',
        'coming_soon',
    ];

    /**
     * Get the Stripe price ID for the specified interval
     */
    public function getStripePriceId(string $interval = 'month'): ?string
    {
        return $interval === 'year' ? $this->yearly_stripe_price_id : $this->stripe_price_id;
    }

    /**
     * Get the price for the specified interval
     */
    public function getPriceForInterval(string $interval = 'month'): float
    {
        return $interval === 'year' ? ($this->yearly_price ?? $this->price * 12) : $this->price;
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'name' => 'string',
            'price' => 'float',
            'description' => 'string',
            'popular' => 'boolean',
            'stripe_price_id' => 'string',
            'billing_interval' => 'string',
            'product_group' => 'string',
            'yearly_price' => 'float',
            'yearly_stripe_price_id' => 'string',
            'features' => 'array',
            'coming_soon' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the formatted price as currency
     */
    protected function getFormattedPriceAttribute(): string
    {
        return '$'.number_format($this->price, 2);
    }

    /**
     * Get the formatted yearly price as currency
     */
    protected function getFormattedYearlyPriceAttribute(): string
    {
        return $this->yearly_price ? '$'.number_format($this->yearly_price, 2) : '$'.number_format($this->price * 12, 2);
    }

    /**
     * Calculate savings when paying yearly vs monthly
     */
    protected function getYearlySavingsAttribute(): float
    {
        if (! $this->yearly_price) {
            return 0;
        }

        $monthlyTotal = $this->price * 12;

        return $monthlyTotal - $this->yearly_price;
    }

    /**
     * Get savings percentage when paying yearly
     */
    protected function getYearlySavingsPercentageAttribute(): int
    {
        if (! $this->yearly_price) {
            return 0;
        }

        $monthlyTotal = $this->price * 12;

        return (int) round((($monthlyTotal - $this->yearly_price) / $monthlyTotal) * 100);
    }

    /**
     * Check if this product is coming soon
     */
    protected function getComingSoonAttribute(): bool
    {
        return (bool) ($this->attributes['coming_soon'] ?? false);
    }
}
