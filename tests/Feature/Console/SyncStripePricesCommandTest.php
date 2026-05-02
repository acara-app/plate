<?php

declare(strict_types=1);

use App\Console\Commands\SyncStripePricesCommand;
use App\Contracts\Services\StripeServiceContract;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Laravel\Cashier\Subscription;

covers(SyncStripePricesCommand::class);

function fakeStripeWithPrices(array $map): StripeServiceContract
{
    return new class($map) implements StripeServiceContract
    {
        /** @param  array<string, ?string>  $map */
        public function __construct(private array $map) {}

        public function ensureStripeCustomer(User $user): void {}

        public function getBillingPortalUrl(User $user, string $returnUrl): string
        {
            return '';
        }

        public function hasIncompletePayment(User $user, string $subscriptionType): bool
        {
            return false;
        }

        public function hasActiveSubscription(User $user): bool
        {
            return false;
        }

        public function getPriceIdFromLookupKey(string $lookupKey): ?string
        {
            return $this->map[$lookupKey] ?? null;
        }

        public function createSubscriptionCheckout(User $user, string $subscriptionType, string $priceId, string $successUrl, string $cancelUrl, array $metadata = [], ?int $trialDays = null): string
        {
            return '';
        }

        public function getIncompletePaymentUrl(Subscription $subscription): ?string
        {
            return null;
        }

        public function getIncompletePaymentUrlForUser(User $user): ?string
        {
            return null;
        }
    };
}

it('writes resolved price_ids onto subscription_products', function (): void {
    SubscriptionProduct::factory()->create([
        'name' => 'Basic',
        'price' => 9.00,
        'stripe_price_id' => null,
        'stripe_lookup_key' => 'acara-plate-personal-monthly',
        'yearly_stripe_price_id' => null,
        'yearly_stripe_lookup_key' => 'acara-plate-personal-yearly',
    ]);

    SubscriptionProduct::factory()->create([
        'name' => 'Plus',
        'price' => 18.00,
        'stripe_price_id' => null,
        'stripe_lookup_key' => 'acara-plate-plus-monthly',
        'yearly_stripe_price_id' => null,
        'yearly_stripe_lookup_key' => 'acara-plate-plus-yearly',
    ]);

    app()->instance(StripeServiceContract::class, fakeStripeWithPrices([
        'acara-plate-personal-monthly' => 'price_basic_monthly_real',
        'acara-plate-personal-yearly' => 'price_basic_yearly_real',
        'acara-plate-plus-monthly' => 'price_plus_monthly_real',
        'acara-plate-plus-yearly' => 'price_plus_yearly_real',
    ]));

    $this->artisan('billing:sync-stripe-prices')
        ->assertSuccessful();

    expect(SubscriptionProduct::query()->where('name', 'Basic')->first())
        ->stripe_price_id->toBe('price_basic_monthly_real')
        ->yearly_stripe_price_id->toBe('price_basic_yearly_real');

    expect(SubscriptionProduct::query()->where('name', 'Plus')->first())
        ->stripe_price_id->toBe('price_plus_monthly_real')
        ->yearly_stripe_price_id->toBe('price_plus_yearly_real');
});

it('does not write when --dry-run is set', function (): void {
    SubscriptionProduct::factory()->create([
        'name' => 'Basic',
        'stripe_price_id' => null,
        'stripe_lookup_key' => 'acara-plate-personal-monthly',
        'yearly_stripe_price_id' => null,
        'yearly_stripe_lookup_key' => 'acara-plate-personal-yearly',
    ]);

    app()->instance(StripeServiceContract::class, fakeStripeWithPrices([
        'acara-plate-personal-monthly' => 'price_basic_monthly_real',
        'acara-plate-personal-yearly' => 'price_basic_yearly_real',
    ]));

    $this->artisan('billing:sync-stripe-prices', ['--dry-run' => true])
        ->expectsOutputToContain('Dry run complete')
        ->assertSuccessful();

    expect(SubscriptionProduct::query()->where('name', 'Basic')->first()->stripe_price_id)->toBeNull();
});

it('fails when a lookup key cannot be resolved', function (): void {
    SubscriptionProduct::factory()->create([
        'name' => 'Basic',
        'stripe_price_id' => null,
        'stripe_lookup_key' => 'acara-plate-personal-monthly',
        'yearly_stripe_price_id' => null,
        'yearly_stripe_lookup_key' => 'acara-plate-personal-yearly',
    ]);
    SubscriptionProduct::factory()->create([
        'name' => 'Plus',
        'stripe_price_id' => null,
        'stripe_lookup_key' => 'acara-plate-plus-monthly',
        'yearly_stripe_price_id' => null,
        'yearly_stripe_lookup_key' => 'acara-plate-plus-yearly',
    ]);

    app()->instance(StripeServiceContract::class, fakeStripeWithPrices([
        'acara-plate-personal-monthly' => 'price_basic_monthly_real',
        'acara-plate-personal-yearly' => 'price_basic_yearly_real',
    ]));

    $this->artisan('billing:sync-stripe-prices')
        ->expectsOutputToContain('not found')
        ->assertExitCode(1);
});

it('fails when no products carry a lookup key', function (): void {
    SubscriptionProduct::factory()->create([
        'name' => 'Free',
        'stripe_price_id' => null,
        'stripe_lookup_key' => null,
        'yearly_stripe_price_id' => null,
        'yearly_stripe_lookup_key' => null,
    ]);

    $this->artisan('billing:sync-stripe-prices')
        ->assertExitCode(1);
});

it('skips products without a lookup key without aborting the run', function (): void {
    SubscriptionProduct::factory()->create([
        'name' => 'Free',
        'stripe_price_id' => null,
        'stripe_lookup_key' => null,
        'yearly_stripe_price_id' => null,
        'yearly_stripe_lookup_key' => null,
    ]);
    SubscriptionProduct::factory()->create([
        'name' => 'Basic',
        'stripe_price_id' => null,
        'stripe_lookup_key' => 'acara-plate-personal-monthly',
        'yearly_stripe_price_id' => null,
        'yearly_stripe_lookup_key' => 'acara-plate-personal-yearly',
    ]);

    app()->instance(StripeServiceContract::class, fakeStripeWithPrices([
        'acara-plate-personal-monthly' => 'price_basic_monthly_real',
        'acara-plate-personal-yearly' => 'price_basic_yearly_real',
    ]));

    $this->artisan('billing:sync-stripe-prices')
        ->assertSuccessful();

    expect(SubscriptionProduct::query()->where('name', 'Basic')->first())
        ->stripe_price_id->toBe('price_basic_monthly_real');
});
