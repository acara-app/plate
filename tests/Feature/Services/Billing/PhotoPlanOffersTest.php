<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
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
    'the paid photo model is not registered' => [['ai.agent_profiles.'.FoodPhotoAnalyzerAgent::class.'.paid' => 'unregistered-model']],
]);

it('does not offer a photo plan whose catalog entry was not prepared for sale', function (): void {
    createStarterPlan(price: 3.99);

    expect(offeredPlanNames())->toBe(['Free', 'Snap Pro']);
});

it('presents the free plan as a trial with the configured number of scans', function (int $scans, string $description, string $feature): void {
    config(['snap.free_trial_scans' => $scans]);
    $free = SubscriptionProduct::query()->where('name', 'Free')->sole();

    $presented = resolve(OffersSubscriptions::class)->present($free);

    expect($presented->name)->toBe('Free trial')
        ->and($presented->description)->toBe($description)
        ->and($presented->features)->toContain($feature)
        ->and($free->name)->toBe('Free');
})->with([
    'one scan' => [1, 'Try 1 photo scan free. Choose a plan when you are ready.', '1 free photo scan, with no daily reset'],
    'two scans' => [2, 'Try 2 photo scans free. Choose a plan when you are ready.', '2 free photo scans, with no daily reset'],
]);

it('presents paid plans unchanged', function (): void {
    $starter = createStarterPlan();

    expect(resolve(OffersSubscriptions::class)->present($starter))->toBe($starter);
});

it('presents the free plan unchanged while photo plans cannot be sold', function (): void {
    config(['cashier.secret' => null]);
    $free = SubscriptionProduct::query()->where('name', 'Free')->sole();

    expect(resolve(OffersSubscriptions::class)->present($free))->toBe($free);
});
