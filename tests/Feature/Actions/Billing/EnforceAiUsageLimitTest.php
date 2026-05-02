<?php

declare(strict_types=1);

use App\Actions\Billing\EnforceAiUsageLimit;
use App\Enums\ModelName;
use App\Enums\SubscriptionTier;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Models\AiUsage;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Laravel\Cashier\Subscription;

covers(EnforceAiUsageLimit::class);

function enforceUsage(): EnforceAiUsageLimit
{
    return resolve(EnforceAiUsageLimit::class);
}

beforeEach(function (): void {
    Config::set('plate.enable_premium_upgrades', true);
    Config::set('plate.ai_usage_preflight', [
        'token_budget' => ['input' => 2_000, 'output' => 1_000],
        'fallback_estimate' => 0.01,
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

it('allows users who are well under their tier limits', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.005,
    ]);

    enforceUsage()->handle($user, ModelName::GPT_5_4_MINI);

    expect(true)->toBeTrue();
});

it('throws when the rolling window cost plus estimate would exceed the cap', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.099,
    ]);

    enforceUsage()->handle($user, ModelName::GPT_5_4_MINI);
})
    ->throws(UsageLimitExceededException::class)
    ->expectExceptionMessageMatches('/rolling/');

it('throws when the weekly window cost plus estimate would exceed the cap', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.349,
    ]);

    expect(fn (): mixed => enforceUsage()->handle($user, ModelName::GPT_5_4_MINI))
        ->toThrow(UsageLimitExceededException::class);
});

it('throws when the monthly window cost plus estimate would exceed the cap', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.999,
    ]);

    expect(fn (): mixed => enforceUsage()->handle($user, ModelName::GPT_5_4_MINI))
        ->toThrow(UsageLimitExceededException::class);
});

it('throws at the 99% mark when the next call would push the user past 100', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.099,
    ]);

    try {
        enforceUsage()->handle($user, ModelName::GPT_5_4_MINI);
        expect(false)->toBeTrue('expected UsageLimitExceededException to be thrown');
    } catch (UsageLimitExceededException $usageLimitExceededException) {
        expect($usageLimitExceededException->limitType)->toBe('rolling')
            ->and($usageLimitExceededException->tier)->toBe(SubscriptionTier::Free)
            ->and($usageLimitExceededException->limitCredits)->toBe(100)
            ->and($usageLimitExceededException->currentCredits)->toBe(99);
    }
});

it('does not throw when the flag is off, even for users far over their cap', function (): void {
    Config::set('plate.enable_premium_upgrades', false);

    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 5.0,
    ]);

    enforceUsage()->handle($user, ModelName::GPT_5_4_MINI);

    expect(true)->toBeTrue();
});

it('does not write an AiUsage row when it rejects a request', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.099,
    ]);

    $countBefore = AiUsage::query()->count();

    try {
        enforceUsage()->handle($user, ModelName::GPT_5_4_MINI);
    } catch (UsageLimitExceededException) {
        //
    }

    expect(AiUsage::query()->count())->toBe($countBefore);
});

it('uses Plus-tier limits for an active Plus subscriber', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_plus_monthly')
        ->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.5,
    ]);

    enforceUsage()->handle($user, ModelName::GPT_5_4_MINI);

    expect(true)->toBeTrue();
});

it('uses the configured fallback estimate when no model is provided', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.099,
    ]);

    expect(fn (): mixed => enforceUsage()->handle($user))
        ->toThrow(UsageLimitExceededException::class);
});

it('exposes the resets_at timestamp on the exception for the breached window', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.099,
    ]);

    try {
        enforceUsage()->handle($user, ModelName::GPT_5_4_MINI);
        expect(false)->toBeTrue();
    } catch (UsageLimitExceededException $usageLimitExceededException) {
        expect(now()->diffInHours($usageLimitExceededException->resetsAt, true))->toBeGreaterThanOrEqual(23);
    }
});
