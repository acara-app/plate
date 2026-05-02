<?php

declare(strict_types=1);

use App\Actions\Billing\AuthorizeGatedFeature;
use App\Enums\GatedFeature;
use App\Enums\SubscriptionTier;
use App\Exceptions\Billing\FeatureGateException;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Laravel\Cashier\Subscription;

covers(AuthorizeGatedFeature::class);

function gate(): AuthorizeGatedFeature
{
    return resolve(AuthorizeGatedFeature::class);
}

beforeEach(function (): void {
    Config::set('plate.enable_premium_upgrades', true);

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

it('allows free users when premium enforcement is off', function (): void {
    Config::set('plate.enable_premium_upgrades', false);

    $user = User::factory()->create();

    gate()->handle($user, GatedFeature::MealPlanner);

    expect(gate()->check($user, GatedFeature::MealPlanner))->toBeTrue();
});

it('throws for free users on a Basic-tier feature', function (): void {
    $user = User::factory()->create();

    expect(fn (): mixed => gate()->handle($user, GatedFeature::MealPlanner))
        ->toThrow(FeatureGateException::class);
});

it('returns false in check() for free users on a gated feature', function (): void {
    $user = User::factory()->create();

    expect(gate()->check($user, GatedFeature::MealPlanner))->toBeFalse();
});

it('allows Basic users on a Basic-tier feature', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_basic_monthly')
        ->create();

    gate()->handle($user, GatedFeature::MealPlanner);

    expect(gate()->check($user, GatedFeature::MealPlanner))->toBeTrue();
});

it('allows Plus users on a Basic-tier feature', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_plus_monthly')
        ->create();

    gate()->handle($user, GatedFeature::MealPlanner);

    expect(gate()->check($user, GatedFeature::MealPlanner))->toBeTrue();
});

it('throws for Basic users on a Plus-tier feature', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_basic_monthly')
        ->create();

    expect(fn (): mixed => gate()->handle($user, GatedFeature::Memory))
        ->toThrow(FeatureGateException::class);
});

it('allows Plus users on a Plus-tier feature', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_plus_monthly')
        ->create();

    gate()->handle($user, GatedFeature::Memory);

    expect(gate()->check($user, GatedFeature::Memory))->toBeTrue();
});

it('exposes the current and required tiers on the exception', function (): void {
    $user = User::factory()->create();

    try {
        gate()->handle($user, GatedFeature::MealPlanner);
        expect(true)->toBeFalse('expected exception was not thrown');
    } catch (FeatureGateException $featureGateException) {
        expect($featureGateException->feature)->toBe(GatedFeature::MealPlanner)
            ->and($featureGateException->currentTier)->toBe(SubscriptionTier::Free)
            ->and($featureGateException->requiredTier)->toBe(SubscriptionTier::Basic);
    }
});

it('image analysis is gated to Basic, memory and health sync to Plus', function (): void {
    expect(GatedFeature::MealPlanner->requiredTier())->toBe(SubscriptionTier::Basic)
        ->and(GatedFeature::ImageAnalysis->requiredTier())->toBe(SubscriptionTier::Basic)
        ->and(GatedFeature::Memory->requiredTier())->toBe(SubscriptionTier::Plus)
        ->and(GatedFeature::HealthSync->requiredTier())->toBe(SubscriptionTier::Plus);
});
