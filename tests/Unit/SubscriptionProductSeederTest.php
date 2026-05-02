<?php

declare(strict_types=1);

use App\Models\SubscriptionProduct;
use Database\Seeders\SubscriptionProductSeeder;

covers(SubscriptionProductSeeder::class);

it('seeds the subscription catalog for the current monetization model', function (): void {
    $this->seed(SubscriptionProductSeeder::class);

    $products = SubscriptionProduct::query()
        ->orderBy('price')
        ->get()
        ->keyBy('name');

    expect($products->keys()->all())->toBe(['Free', 'Basic', 'Plus']);

    expect($products['Free'])
        ->product_group->toBe('free')
        ->popular->toBeFalse()
        ->features->toContain('Limited free credits each month');

    expect($products['Basic'])
        ->product_group->toBe('subscription')
        ->popular->toBeTrue()
        ->features->toContain('AI Meal Planner', 'Meal photo analysis')
        ->features->not->toContain('Unlimited chat with Altani');

    expect($products['Plus'])
        ->product_group->toBe('trial')
        ->popular->toBeFalse()
        ->features->toContain(
            'Memory for your preferences, goals, and context',
            "Syncs with your iPhone's Health app",
            'Highest chat limits',
        );
});

it('seeds lookup keys but leaves price IDs null for production sync', function (): void {
    $this->seed(SubscriptionProductSeeder::class);

    $basic = SubscriptionProduct::query()->where('name', 'Basic')->firstOrFail();
    $plus = SubscriptionProduct::query()->where('name', 'Plus')->firstOrFail();

    expect($basic)
        ->stripe_lookup_key->toBe('acara-plate-personal-monthly')
        ->yearly_stripe_lookup_key->toBe('acara-plate-personal-yearly')
        ->stripe_price_id->toBeNull()
        ->yearly_stripe_price_id->toBeNull();

    expect($plus)
        ->stripe_lookup_key->toBe('acara-plate-plus-monthly')
        ->yearly_stripe_lookup_key->toBe('acara-plate-plus-yearly')
        ->stripe_price_id->toBeNull()
        ->yearly_stripe_price_id->toBeNull();
});
