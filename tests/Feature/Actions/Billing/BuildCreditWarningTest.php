<?php

declare(strict_types=1);

use App\Actions\Billing\BuildCreditWarning;
use App\Data\Billing\CreditWarning;
use App\Enums\SubscriptionTier;
use App\Models\AiUsage;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Laravel\Cashier\Subscription;

covers(BuildCreditWarning::class);

function buildWarning(): BuildCreditWarning
{
    return resolve(BuildCreditWarning::class);
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

it('returns null when usage is below the 80% threshold', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.05,
    ]);

    expect(buildWarning()->currentState($user))->toBeNull();
});

it('returns a warning when usage crosses 80% of the rolling cap', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.34,
    ]);

    $warning = buildWarning()->currentState($user);

    expect($warning)->toBeInstanceOf(CreditWarning::class)
        ->and($warning->limitType)->toBe('rolling')
        ->and($warning->tier)->toBe(SubscriptionTier::Free)
        ->and($warning->currentCredits)->toBe(340)
        ->and($warning->limitCredits)->toBe(400)
        ->and($warning->percentage)->toBe(85);
});

it('returns a warning capped at 100% when the user is already over the cap', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.5,
    ]);

    $warning = buildWarning()->currentState($user);

    expect($warning)->toBeInstanceOf(CreditWarning::class)
        ->and($warning->limitType)->toBe('rolling')
        ->and($warning->currentCredits)->toBe(500)
        ->and($warning->limitCredits)->toBe(400)
        ->and($warning->percentage)->toBe(100);
});

it('returns the same derived warning on repeated calls', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.34,
    ]);

    $first = buildWarning()->currentState($user);
    $second = buildWarning()->currentState($user);

    expect($first)->toBeInstanceOf(CreditWarning::class)
        ->and($second)->toBeInstanceOf(CreditWarning::class)
        ->and($first->percentage)->toBe(85)
        ->and($second->percentage)->toBe(85);
});

it('returns null when premium enforcement is off', function (): void {
    Config::set('plate.enable_premium_upgrades', false);

    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.34,
    ]);

    expect(buildWarning()->currentState($user))->toBeNull();
});

it('uses Plus-tier limits for Plus subscribers when computing the warning', function (): void {
    $user = User::factory()->create();

    Subscription::factory()
        ->for($user)
        ->active()
        ->withPrice('price_plus_monthly')
        ->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 2.55,
    ]);

    $warning = buildWarning()->currentState($user);

    expect($warning)->toBeInstanceOf(CreditWarning::class)
        ->and($warning->tier)->toBe(SubscriptionTier::Plus)
        ->and($warning->currentCredits)->toBe(2550)
        ->and($warning->limitCredits)->toBe(3000)
        ->and($warning->percentage)->toBe(85);
});

it('picks the most-restrictive window when multiple are over 80%', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 1.26,
        'created_at' => now()->subDays(2),
    ]);

    $warning = buildWarning()->currentState($user);

    expect($warning)->toBeInstanceOf(CreditWarning::class)
        ->and($warning->limitType)->toBe('weekly')
        ->and($warning->percentage)->toBe(90);
});

it('produces a human-readable resets_in string', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.34,
    ]);

    $warning = buildWarning()->currentState($user);

    expect($warning?->resetsIn)->toBeString()->not->toBeEmpty();
});
