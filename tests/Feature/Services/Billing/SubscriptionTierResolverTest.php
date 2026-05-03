<?php

declare(strict_types=1);

use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\TierEntitlement;
use App\Enums\SubscriptionTier;
use App\Models\SubscriptionProduct;
use App\Models\User;
use App\Services\Billing\SubscriptionTierResolver;
use Illuminate\Support\Facades\Config;
use Laravel\Cashier\Subscription;

covers(SubscriptionTierResolver::class, TierEntitlement::class, SubscriptionTier::class);

beforeEach(function (): void {
    Config::set('plate.enable_premium_upgrades', true);

    SubscriptionProduct::factory()->create([
        'name' => 'Free',
        'stripe_price_id' => null,
        'yearly_stripe_price_id' => null,
    ]);

    SubscriptionProduct::factory()->create([
        'name' => 'Basic',
        'stripe_price_id' => 'price_basic_monthly',
        'yearly_stripe_price_id' => 'price_basic_yearly',
    ]);

    SubscriptionProduct::factory()->create([
        'name' => 'Plus',
        'stripe_price_id' => 'price_plus_monthly',
        'yearly_stripe_price_id' => 'price_plus_yearly',
    ]);
});

function resolver(): SubscriptionTierResolver
{
    return resolve(SubscriptionTierResolver::class);
}

it('binds the resolver via the contract', function (): void {
    expect(resolve(ResolvesUserTier::class))->toBeInstanceOf(SubscriptionTierResolver::class);
});

it('returns Free for users with no Cashier subscription', function (): void {
    $user = User::factory()->create();

    $entitlement = resolver()->resolve($user);

    expect($entitlement->tier)->toBe(SubscriptionTier::Free)
        ->and($entitlement->isPaymentPending())->toBeFalse()
        ->and($entitlement->inGracePeriod())->toBeFalse()
        ->and($entitlement->isUnrestricted())->toBeFalse();
});

it('returns Basic for an active Basic monthly subscription', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_basic_monthly')
        ->create();

    $entitlement = resolver()->resolve($user);

    expect($entitlement->tier)->toBe(SubscriptionTier::Basic)
        ->and($entitlement->inGracePeriod())->toBeFalse()
        ->and($entitlement->isPaymentPending())->toBeFalse();
});

it('returns Basic for an active Basic yearly subscription', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_basic_yearly')
        ->create();

    expect(resolver()->resolve($user)->tier)->toBe(SubscriptionTier::Basic);
});

it('returns Plus for an active Plus subscription', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_plus_monthly')
        ->create();

    expect(resolver()->resolve($user)->tier)->toBe(SubscriptionTier::Plus);
});

it('keeps the paid tier while a canceled subscription is on grace', function (): void {
    $user = User::factory()->create();
    $endsAt = now()->addDays(5)->startOfSecond();

    Subscription::factory()
        ->for($user)
        ->withPrice('price_plus_monthly')
        ->canceled()
        ->state(['ends_at' => $endsAt])
        ->create();

    $entitlement = resolver()->resolve($user);

    expect($entitlement->tier)->toBe(SubscriptionTier::Plus)
        ->and($entitlement->inGracePeriod())->toBeTrue()
        ->and($entitlement->gracePeriodEndsAt?->format('Y-m-d H:i:s'))->toBe($endsAt->format('Y-m-d H:i:s'));
});

it('drops to Free once the grace period has lapsed', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->withPrice('price_plus_monthly')
        ->canceled()
        ->state(['ends_at' => now()->subDay()])
        ->create();

    $entitlement = resolver()->resolve($user);

    expect($entitlement->tier)->toBe(SubscriptionTier::Free)
        ->and($entitlement->inGracePeriod())->toBeFalse();
});

it('returns Free with payment_pending for an incomplete subscription', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->incomplete()
        ->withPrice('price_basic_monthly')
        ->create();

    $entitlement = resolver()->resolve($user);

    expect($entitlement->tier)->toBe(SubscriptionTier::Free)
        ->and($entitlement->isPaymentPending())->toBeTrue()
        ->and($entitlement->inGracePeriod())->toBeFalse();
});

it('short-circuits to unrestricted Free when premium upgrades are disabled', function (): void {
    Config::set('plate.enable_premium_upgrades', false);

    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->incomplete()
        ->withPrice('price_basic_monthly')
        ->create();

    $entitlement = resolver()->resolve($user);

    expect($entitlement->tier)->toBe(SubscriptionTier::Free)
        ->and($entitlement->isUnrestricted())->toBeTrue()
        ->and($entitlement->premiumEnforcementActive)->toBeFalse()
        ->and($entitlement->isPaymentPending())->toBeFalse();
});

it('does not consult the is_verified column when resolving the tier', function (): void {
    $user = User::factory()->verified()->create();

    expect(resolver()->resolve($user)->tier)->toBe(SubscriptionTier::Free);
});

it('returns Free when the subscription price does not match any seeded product', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_unknown_xyz')
        ->create();

    expect(resolver()->resolve($user)->tier)->toBe(SubscriptionTier::Free);
});

it('prefers the active subscription over a canceled-and-ended one', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->withPrice('price_basic_monthly')
        ->canceled()
        ->state(['ends_at' => now()->subWeek()])
        ->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_plus_monthly')
        ->create();

    expect(resolver()->resolve($user)->tier)->toBe(SubscriptionTier::Plus);
});
