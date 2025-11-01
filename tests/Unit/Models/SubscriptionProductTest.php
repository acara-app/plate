<?php

declare(strict_types=1);

use App\Models\SubscriptionProduct;

test('to array', function (): void {
    $product = SubscriptionProduct::factory()->create()->refresh();

    expect(array_keys($product->toArray()))
        ->toBe([
            'id',
            'name',
            'price',
            'description',
            'popular',
            'stripe_price_id',
            'billing_interval',
            'product_group',
            'yearly_price',
            'yearly_stripe_price_id',
            'features',
            'coming_soon',
            'created_at',
            'updated_at',
            'formatted_price',
            'formatted_yearly_price',
            'yearly_savings',
            'yearly_savings_percentage',
        ]);
});

test('get stripe price id returns monthly by default', function (): void {
    $product = SubscriptionProduct::factory()->create([
        'stripe_price_id' => 'price_monthly',
        'yearly_stripe_price_id' => 'price_yearly',
    ]);

    expect($product->getStripePriceId())->toBe('price_monthly');
});

test('get stripe price id returns yearly when interval is year', function (): void {
    $product = SubscriptionProduct::factory()->create([
        'stripe_price_id' => 'price_monthly',
        'yearly_stripe_price_id' => 'price_yearly',
    ]);

    expect($product->getStripePriceId('year'))->toBe('price_yearly');
});

test('get price for interval returns monthly price by default', function (): void {
    $product = SubscriptionProduct::factory()->create([
        'price' => 10.00,
        'yearly_price' => 100.00,
    ]);

    expect($product->getPriceForInterval())->toBe(10.00);
});

test('get price for interval returns yearly price when interval is year', function (): void {
    $product = SubscriptionProduct::factory()->create([
        'price' => 10.00,
        'yearly_price' => 100.00,
    ]);

    expect($product->getPriceForInterval('year'))->toBe(100.00);
});

test('get price for interval calculates yearly when yearly price is null', function (): void {
    $product = SubscriptionProduct::factory()->create([
        'price' => 10.00,
        'yearly_price' => null,
    ]);

    expect($product->getPriceForInterval('year'))->toBe(120.00);
});

test('yearly savings returns zero when yearly price is null', function (): void {
    $product = SubscriptionProduct::factory()->create([
        'price' => 10.00,
        'yearly_price' => null,
    ]);

    expect($product->yearly_savings)->toBe(0.0);
});

test('yearly savings calculates correctly when yearly price is set', function (): void {
    $product = SubscriptionProduct::factory()->create([
        'price' => 10.00,
        'yearly_price' => 100.00,
    ]);

    // Monthly total would be 10 * 12 = 120, yearly is 100, so savings is 20
    expect($product->yearly_savings)->toBe(20.0);
});

test('yearly savings percentage returns zero when yearly price is null', function (): void {
    $product = SubscriptionProduct::factory()->create([
        'price' => 10.00,
        'yearly_price' => null,
    ]);

    expect($product->yearly_savings_percentage)->toBe(0);
});

test('yearly savings percentage calculates correctly when yearly price is set', function (): void {
    $product = SubscriptionProduct::factory()->create([
        'price' => 10.00,
        'yearly_price' => 100.00,
    ]);

    // Monthly total would be 10 * 12 = 120, yearly is 100
    // Savings = 20, percentage = (20 / 120) * 100 = 16.67 rounds to 17
    expect($product->yearly_savings_percentage)->toBe(17);
});
