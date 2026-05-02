<?php

declare(strict_types=1);

use App\Console\Commands\ValidateStripePricesCommand;
use App\Models\SubscriptionProduct;

covers(ValidateStripePricesCommand::class);

it('fails when no subscription products are seeded', function (): void {
    $this->artisan('billing:validate-stripe-prices')
        ->assertExitCode(1);
});

it('fails when paid tiers carry placeholder Stripe price IDs', function (): void {
    SubscriptionProduct::factory()->create([
        'name' => 'Free',
        'price' => 0.00,
        'stripe_price_id' => null,
        'yearly_stripe_price_id' => null,
    ]);

    SubscriptionProduct::factory()->create([
        'name' => 'Basic',
        'price' => 9.00,
        'stripe_price_id' => 'acara-plate-personal-monthly',
        'yearly_stripe_price_id' => 'acara-plate-personal-yearly',
    ]);

    $this->artisan('billing:validate-stripe-prices')
        ->expectsOutputToContain('placeholder')
        ->assertExitCode(1);
});

it('passes when all paid tiers carry production-shaped price_ ids', function (): void {
    SubscriptionProduct::factory()->create([
        'name' => 'Free',
        'price' => 0.00,
        'stripe_price_id' => null,
        'yearly_stripe_price_id' => null,
    ]);

    SubscriptionProduct::factory()->create([
        'name' => 'Basic',
        'price' => 9.00,
        'stripe_price_id' => 'price_basic_monthly_abc',
        'yearly_stripe_price_id' => 'price_basic_yearly_abc',
    ]);

    SubscriptionProduct::factory()->create([
        'name' => 'Plus',
        'price' => 18.00,
        'stripe_price_id' => 'price_plus_monthly_xyz',
        'yearly_stripe_price_id' => 'price_plus_yearly_xyz',
    ]);

    $this->artisan('billing:validate-stripe-prices')
        ->expectsOutputToContain('production-shaped')
        ->assertSuccessful();
});

it('does not flag the free tier when its Stripe IDs are absent', function (): void {
    SubscriptionProduct::factory()->create([
        'name' => 'Free',
        'price' => 0.00,
        'stripe_price_id' => null,
        'yearly_stripe_price_id' => null,
    ]);

    SubscriptionProduct::factory()->create([
        'name' => 'Basic',
        'price' => 9.00,
        'stripe_price_id' => 'price_basic_monthly_abc',
        'yearly_stripe_price_id' => 'price_basic_yearly_abc',
    ]);

    $this->artisan('billing:validate-stripe-prices')
        ->assertSuccessful();
});

it('flags missing Stripe IDs as a failure under --strict', function (): void {
    SubscriptionProduct::factory()->create([
        'name' => 'Basic',
        'price' => 9.00,
        'stripe_price_id' => null,
        'yearly_stripe_price_id' => null,
    ]);

    $this->artisan('billing:validate-stripe-prices', ['--strict' => true])
        ->expectsOutputToContain('missing')
        ->assertExitCode(1);
});
