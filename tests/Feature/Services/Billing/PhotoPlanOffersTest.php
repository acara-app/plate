<?php

declare(strict_types=1);

use App\Contracts\Billing\OffersSubscriptions;
use App\Models\SubscriptionProduct;
use App\Services\Billing\PhotoPlanCatalog;
use App\Services\Billing\PhotoPlanOffers;

covers(PhotoPlanOffers::class, PhotoPlanCatalog::class);

beforeEach(function (): void {
    config([
        'cashier.key' => 'pk_test_fake',
        'cashier.secret' => 'sk_test_fake',
        'cashier.webhook.secret' => 'whsec_test',
    ]);

    SubscriptionProduct::factory()->create(['name' => 'Free', 'price' => 0, 'stripe_price_id' => null, 'purchasable' => true]);
    SubscriptionProduct::factory()->create(['name' => 'Supporter', 'price' => 9, 'stripe_price_id' => 'price_supporter', 'purchasable' => true]);
    SubscriptionProduct::factory()->create(['name' => 'Pro', 'price' => 19, 'stripe_price_id' => 'price_pro', 'purchasable' => true]);
    SubscriptionProduct::query()->where('name', 'Snap Pro')->update(['stripe_price_id' => 'price_snap_pro']);
});

/**
 * @return list<string>
 */
function offeredPlanNames(): array
{
    $offers = resolve(OffersSubscriptions::class);

    return SubscriptionProduct::query()->orderBy('price')->orderBy('id')->get()
        ->filter($offers->available(...))
        ->pluck('name')
        ->values()
        ->all();
}

function createStarterPlan(float $price = 2.99): SubscriptionProduct
{
    return SubscriptionProduct::factory()->create([
        'name' => 'Starter', 'price' => $price, 'stripe_price_id' => 'price_starter',
        'stripe_lookup_key' => 'acara-plate-starter-monthly-v1', 'purchasable' => true,
    ]);
}

it('offers the free trial, Starter and Snap Pro once photo plans can be sold', function (): void {
    createStarterPlan();

    expect(offeredPlanNames())->toBe(['Free', 'Starter', 'Snap Pro']);
});

it('only offers the free plan while paid photo plans cannot be sold', function (array $settings): void {
    createStarterPlan();
    config($settings);

    expect(offeredPlanNames())->toBe(['Free']);
})->with([
    'Stripe is not configured' => [['cashier.secret' => null]],
    'Stripe webhooks are not configured' => [['cashier.webhook.secret' => null]],
]);

it('does not offer a photo plan whose catalog entry was not prepared for sale', function (): void {
    createStarterPlan(price: 3.99);

    expect(offeredPlanNames())->toBe(['Free', 'Snap Pro']);
});

it('presents the configured total free scans without a timed trial', function (int $scans, string $description, string $feature): void {
    config(['snap.free_trial_scans' => $scans]);
    $free = SubscriptionProduct::query()->where('name', 'Free')->sole();

    $presented = resolve(OffersSubscriptions::class)->present($free);

    expect($presented->name)->toBe('Free scans')
        ->and($presented->description)->toBe($description)
        ->and($presented->features)->toContain($feature)
        ->and($free->name)->toBe('Free');
})->with([
    'one scan' => [1, 'Try photo tracking with 1 successful scan.', 'AI chat and meal plans included'],
    'two scans' => [2, 'Try photo tracking with 2 successful scans.', 'AI chat and meal plans included'],
]);

it('replaces stale plan copy in each language without changing the product terms', function (string $locale, string $name, string $description): void {
    app()->setLocale($locale);
    $product = SubscriptionProduct::factory()->make([
        'name' => $name,
        'price' => 9,
        'stripe_price_id' => 'price_existing',
        'stripe_lookup_key' => 'existing-key',
        'billing_interval' => 'monthly',
        'description' => '100 scans each month',
        'features' => ['100 scans each month'],
    ]);

    $presented = resolve(OffersSubscriptions::class)->present($product);

    expect($presented->description)->toBe($description)
        ->and($presented->features)->not->toContain('100 scans each month')
        ->and($presented->price)->toBe(9.0)
        ->and($presented->stripe_price_id)->toBe('price_existing')
        ->and($presented->stripe_lookup_key)->toBe('existing-key')
        ->and($presented->billing_interval)->toBe('monthly');
    expect($product->description)->toBe('100 scans each month');
})->with([
    'English Starter' => ['en', 'Starter', 'Make photo logging part of your routine.'],
    'French Starter' => ['fr', 'Starter', 'Intégrez le suivi photo à votre routine.'],
    'Mongolian Starter' => ['mn', 'Starter', 'Хоолоо зургаар бүртгэж хэвшээрэй.'],
    'English Snap Pro' => ['en', 'Snap Pro', 'More room for meal tracking and AI help.'],
    'French Snap Pro' => ['fr', 'Snap Pro', 'Plus de capacité pour suivre vos repas et utiliser l’IA.'],
    'Mongolian Snap Pro' => ['mn', 'Snap Pro', 'Хоолоо бүртгэж, AI ашиглах илүү их эрх.'],
]);

it('presents the free plan unchanged while photo plans cannot be sold', function (): void {
    config(['cashier.secret' => null]);
    $free = SubscriptionProduct::query()->where('name', 'Free')->sole();

    expect(resolve(OffersSubscriptions::class)->present($free))->toBe($free);
});
