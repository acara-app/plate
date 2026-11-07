<?php

declare(strict_types=1);

use App\Console\Commands\PrepareSnapSubscriptionCommand;
use App\Models\SubscriptionProduct;
use Stripe\Exception\InvalidRequestException;
use Stripe\Price;
use Stripe\StripeClient;

covers(PrepareSnapSubscriptionCommand::class);

/**
 * @param  array<string, mixed>  $attributes
 */
function fakeStripePrice(array $attributes = []): void
{
    $price = Price::constructFrom([
        'id' => 'price_starter_usd', 'object' => 'price', 'active' => true, 'currency' => 'usd', 'unit_amount' => 299,
        'recurring' => ['interval' => 'month', 'interval_count' => 1], 'lookup_key' => 'acara-plate-starter-monthly-v1',
        ...$attributes,
    ]);

    fakeStripePriceLookup(fn (): Price => $price);
}

/**
 * @param  Closure(string): Price  $retrieve
 */
function fakeStripePriceLookup(Closure $retrieve): void
{
    app()->bind(StripeClient::class, fn (): StripeClient => new class($retrieve) extends StripeClient
    {
        public function __construct(private readonly Closure $retrieve)
        {
            parent::__construct('sk_test_fake');
        }

        public function getService(mixed $name): object
        {
            return new readonly class($this->retrieve)
            {
                public function __construct(private Closure $retrieve) {}

                public function retrieve(string $id): Price
                {
                    return ($this->retrieve)($id);
                }
            };
        }
    });
}

it('prepares a photo plan catalog entry without touching Stripe', function (string $plan, string $name, float $price, string $lookupKey, bool $popular): void {
    SubscriptionProduct::query()->where('name', $name)->delete();
    SubscriptionProduct::factory()->create([
        'name' => $name, 'purchasable' => false, 'stripe_price_id' => 'price_existing',
        'yearly_price' => 99, 'yearly_stripe_price_id' => 'price_yearly', 'yearly_stripe_lookup_key' => 'yearly',
    ]);

    $this->artisan('snap:prepare-subscription', ['--plan' => $plan])
        ->expectsOutputToContain("{$name} prepared.")
        ->assertSuccessful();

    $product = SubscriptionProduct::query()->where('name', $name)->sole();
    expect($product->price)->toEqual($price)
        ->and($product->stripe_lookup_key)->toBe($lookupKey)
        ->and($product->stripe_price_id)->toBe('price_existing')
        ->and($product->purchasable)->toBeTrue()
        ->and($product->popular)->toBe($popular)
        ->and($product->billing_interval)->toBe('monthly')
        ->and($product->yearly_price)->toBeNull()
        ->and($product->yearly_stripe_price_id)->toBeNull()
        ->and($product->features)->toContain('Cancel anytime through Billing settings');
})->with([
    'Starter' => ['starter', 'Starter', 2.99, 'acara-plate-starter-monthly-v1', true],
    'Snap Pro' => ['snap-pro', 'Snap Pro', 9.0, 'acara-plate-snap-pro-monthly-v1', false],
]);

it('links a Stripe price that matches the plan', function (): void {
    fakeStripePrice();

    $this->artisan('snap:prepare-subscription', ['--plan' => 'starter', '--price-id' => 'price_starter_usd'])
        ->assertSuccessful();

    expect(SubscriptionProduct::query()->where('name', 'Starter')->sole()->stripe_price_id)->toBe('price_starter_usd');
});

it('refuses a Stripe price that does not match the plan', function (array $attributes): void {
    fakeStripePrice($attributes);

    $this->artisan('snap:prepare-subscription', ['--plan' => 'starter', '--price-id' => 'price_starter_usd'])
        ->expectsOutputToContain('The price must be active, USD $2.99/month, and use the Starter lookup key.')
        ->assertFailed();

    expect(SubscriptionProduct::query()->where('name', 'Starter')->exists())->toBeFalse();
})->with([
    'archived' => [['active' => false]],
    'charged in another currency' => [['currency' => 'cad']],
    'a different amount' => [['unit_amount' => 399]],
    'billed yearly' => [['recurring' => ['interval' => 'year', 'interval_count' => 1]]],
    'billed every two months' => [['recurring' => ['interval' => 'month', 'interval_count' => 2]]],
    'a one-time price' => [['recurring' => null]],
    'another plan lookup key' => [['lookup_key' => 'acara-plate-snap-pro-monthly-v1']],
]);

it('rejects input that does not name a photo plan or Stripe price', function (array $options, string $message): void {
    $this->artisan('snap:prepare-subscription', $options)
        ->expectsOutputToContain($message)
        ->assertFailed();

    expect(SubscriptionProduct::query()->where('name', 'Starter')->exists())->toBeFalse();
})->with([
    'unknown plan' => [['--plan' => 'gold'], 'Choose --plan=starter or --plan=snap-pro.'],
    'not a price ID' => [['--plan' => 'starter', '--price-id' => 'prod_starter'], 'Supply a Stripe price ID beginning with price_.'],
]);

it('explains a Stripe price that cannot be loaded instead of crashing', function (): void {
    fakeStripePriceLookup(fn (string $id): Price => throw InvalidRequestException::factory("No such price: '{$id}'"));

    $this->artisan('snap:prepare-subscription', ['--plan' => 'starter', '--price-id' => 'price_missing'])
        ->expectsOutputToContain("Stripe could not load price_missing: No such price: 'price_missing'")
        ->assertFailed();

    expect(SubscriptionProduct::query()->where('name', 'Starter')->exists())->toBeFalse();
});
