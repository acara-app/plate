<?php

declare(strict_types=1);

use App\Models\SubscriptionProduct;
use Database\Seeders\SubscriptionProductSeeder;

covers(SubscriptionProductSeeder::class);

it('seeds the open-feature Cloud subscription catalog', function (): void {
    SubscriptionProduct::factory()->create(['name' => 'Personal']);
    SubscriptionProduct::factory()->create(['name' => 'Basic']);
    SubscriptionProduct::factory()->create(['name' => 'Plus']);

    $this->seed(SubscriptionProductSeeder::class);

    $products = SubscriptionProduct::query()
        ->orderBy('price')
        ->get()
        ->keyBy('name');

    expect($products->keys()->all())->toBe(['Free', 'Supporter', 'Pro'])
        ->and(SubscriptionProduct::query()->whereIn('name', ['Personal', 'Basic', 'Plus'])->exists())->toBeFalse();

    expect($products['Free']->features)
        ->toContain('All open Acara Cloud features', '1,000 monthly AI credits');

    expect($products['Supporter'])
        ->price->toBe(9.00)
        ->yearly_price->toBe(89.00)
        ->stripe_lookup_key->toBe('acara-plate-supporter-monthly-v1')
        ->yearly_stripe_lookup_key->toBe('acara-plate-supporter-yearly-v1')
        ->popular->toBeTrue()
        ->product_group->toBe('subscription');

    expect($products['Pro'])
        ->price->toBe(19.00)
        ->yearly_price->toBe(190.00)
        ->stripe_lookup_key->toBe('acara-plate-pro-monthly-v1')
        ->yearly_stripe_lookup_key->toBe('acara-plate-pro-yearly-v1')
        ->popular->toBeFalse()
        ->product_group->toBe('subscription');
});

it('does not sell open product features as paid-only gates', function (): void {
    $this->seed(SubscriptionProductSeeder::class);

    $paidFeatureCopy = SubscriptionProduct::query()
        ->whereIn('name', ['Supporter', 'Pro'])
        ->get()
        ->flatMap(fn (SubscriptionProduct $product): array => $product->features ?? []);

    expect($paidFeatureCopy)
        ->toContain('All open Acara Cloud features')
        ->not->toContain('AI Meal Planner')
        ->not->toContain('Meal photo analysis')
        ->not->toContain("Syncs with your iPhone's Health app");
});
