<?php

declare(strict_types=1);

use App\Actions\GetAiUsageForBillingAction;
use App\Enums\SubscriptionTier;
use App\Models\AiUsage;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Laravel\Cashier\Subscription;

covers(GetAiUsageForBillingAction::class);

function billingUsageAction(): GetAiUsageForBillingAction
{
    return resolve(GetAiUsageForBillingAction::class);
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

it('returns usage data for user with no usage', function (): void {
    $user = User::factory()->create();

    $result = billingUsageAction()->handle($user);

    expect($result)->toBeArray()
        ->toHaveKeys([
            'tier',
            'tier_label',
            'payment_pending',
            'premium_enforcement_active',
            'rolling',
            'weekly',
        ])
        ->and($result['tier'])->toBe(SubscriptionTier::Free->value)
        ->and($result['tier_label'])->toBe('Free')
        ->and($result['payment_pending'])->toBeFalse()
        ->and($result['premium_enforcement_active'])->toBeTrue()
        ->and($result['rolling']['current'])->toBe(0)
        ->and($result['weekly']['current'])->toBe(0)
        ->and($result['rolling']['percentage'])->toBe(0)
        ->and($result['rolling']['over_limit'])->toBeFalse()
        ->and($result['rolling']['resets_in'])->toBeString();
});

it('uses Free-tier limits for users without an active subscription', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.05,
    ]);

    $result = billingUsageAction()->handle($user);

    expect($result['tier'])->toBe(SubscriptionTier::Free->value)
        ->and($result['rolling']['limit'])->toBe(100)
        ->and($result['weekly']['limit'])->toBe(350)
        ->and($result['rolling']['current'])->toBe(50)
        ->and($result['rolling']['percentage'])->toBe(50);
});

it('uses Basic-tier limits for users with an active Basic subscription', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_basic_monthly')
        ->create();

    $result = billingUsageAction()->handle($user);

    expect($result['tier'])->toBe(SubscriptionTier::Basic->value)
        ->and($result['tier_label'])->toBe('Supporter')
        ->and($result['rolling']['limit'])->toBe(500)
        ->and($result['weekly']['limit'])->toBe(2000);
});

it('uses Plus-tier limits for users with an active Plus subscription', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_plus_monthly')
        ->create();

    $result = billingUsageAction()->handle($user);

    expect($result['tier'])->toBe(SubscriptionTier::Plus->value)
        ->and($result['tier_label'])->toBe('Pro')
        ->and($result['rolling']['limit'])->toBe(1000)
        ->and($result['weekly']['limit'])->toBe(4000);
});

it('caps the displayed percentage at 100 but preserves the raw credit count', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 1.50,
    ]);

    $result = billingUsageAction()->handle($user);

    expect($result['rolling']['percentage'])->toBe(100)
        ->and($result['rolling']['current'])->toBe(1500)
        ->and($result['rolling']['limit'])->toBe(100)
        ->and($result['rolling']['over_limit'])->toBeTrue();
});

it('marks under-limit buckets as not over_limit', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.005,
    ]);

    $result = billingUsageAction()->handle($user);

    expect($result['weekly']['percentage'])->toBe(1)
        ->and($result['weekly']['over_limit'])->toBeFalse();
});

it('flags payment_pending when the user has only an incomplete subscription', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->incomplete()
        ->withPrice('price_plus_monthly')
        ->create();

    $result = billingUsageAction()->handle($user);

    expect($result['tier'])->toBe(SubscriptionTier::Free->value)
        ->and($result['payment_pending'])->toBeTrue()
        ->and($result['rolling']['limit'])->toBe(100);
});

it('returns unrestricted free state when premium upgrades are disabled', function (): void {
    Config::set('plate.enable_premium_upgrades', false);

    $user = User::factory()->create();

    $result = billingUsageAction()->handle($user);

    expect($result['tier'])->toBe(SubscriptionTier::Free->value)
        ->and($result['premium_enforcement_active'])->toBeFalse()
        ->and($result['rolling']['limit'])->toBe(100);
});

it('returns credits as integers using configured multiplier', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.007,
    ]);

    $result = billingUsageAction()->handle($user);

    expect($result['rolling']['current'])->toBe(7)
        ->and($result['rolling']['current'])->toBeInt()
        ->and($result['rolling']['limit'])->toBeInt()
        ->and($result['rolling']['limit'])->toBe(100)
        ->and($result['weekly']['limit'])->toBe(350);
});

it('returns usage data for guest user with default limits', function (): void {
    $result = billingUsageAction()->handle(new User);

    expect($result)->toBeArray()
        ->toHaveKeys(['tier', 'rolling', 'weekly'])
        ->and($result['tier'])->toBe(SubscriptionTier::Free->value)
        ->and($result['rolling']['current'])->toBe(0);
});
