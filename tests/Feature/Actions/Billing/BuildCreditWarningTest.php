<?php

declare(strict_types=1);

use App\Actions\Billing\BuildCreditWarning;
use App\Data\Billing\CreditWarning;
use App\Enums\SubscriptionTier;
use App\Models\AiUsage;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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

    expect(buildWarning()->handle($user))->toBeNull();
});

it('returns a warning when usage crosses 80% of the rolling cap', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.085,
    ]);

    $warning = buildWarning()->handle($user);

    expect($warning)->toBeInstanceOf(CreditWarning::class)
        ->and($warning->limitType)->toBe('rolling')
        ->and($warning->tier)->toBe(SubscriptionTier::Free)
        ->and($warning->currentCredits)->toBe(85)
        ->and($warning->limitCredits)->toBe(100)
        ->and($warning->percentage)->toBe(85);
});

it('returns null on the second call within the same period (dedup)', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.085,
    ]);

    expect(buildWarning()->handle($user))->toBeInstanceOf(CreditWarning::class)
        ->and(buildWarning()->handle($user))->toBeNull();
});

it('returns a warning capped at 100% when the user is already over the cap', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.5,
    ]);

    $warning = buildWarning()->handle($user);

    expect($warning)->toBeInstanceOf(CreditWarning::class)
        ->and($warning->limitType)->toBe('rolling')
        ->and($warning->currentCredits)->toBe(500)
        ->and($warning->limitCredits)->toBe(100)
        ->and($warning->percentage)->toBe(100);
});

it('exposes a pure currentState read that ignores the dedup cache', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.085,
    ]);

    $first = buildWarning()->currentState($user);
    $second = buildWarning()->currentState($user);

    expect($first)->toBeInstanceOf(CreditWarning::class)
        ->and($second)->toBeInstanceOf(CreditWarning::class)
        ->and($first->percentage)->toBe(85)
        ->and($second->percentage)->toBe(85);
});

it('does not emit telemetry from currentState', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.085,
    ]);

    buildWarning()->currentState($user);

    expect(Cache::has(sprintf('credit_warning_shown:%d:rolling', $user->id)))->toBeFalse();
});

it('returns null when premium enforcement is off', function (): void {
    Config::set('plate.enable_premium_upgrades', false);

    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.085,
    ]);

    expect(buildWarning()->handle($user))->toBeNull();
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
        'cost' => 0.85,
    ]);

    $warning = buildWarning()->handle($user);

    expect($warning)->toBeInstanceOf(CreditWarning::class)
        ->and($warning->tier)->toBe(SubscriptionTier::Plus)
        ->and($warning->currentCredits)->toBe(850)
        ->and($warning->limitCredits)->toBe(1000)
        ->and($warning->percentage)->toBe(85);
});

it('keeps the dedup cache key isolated per user', function (): void {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $alice->id,
        'cost' => 0.085,
    ]);

    AiUsage::factory()->create([
        'user_id' => $bob->id,
        'cost' => 0.085,
    ]);

    expect(buildWarning()->handle($alice))->toBeInstanceOf(CreditWarning::class)
        ->and(buildWarning()->handle($bob))->toBeInstanceOf(CreditWarning::class);
});

it('picks the most-restrictive window when multiple are over 80%', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.315,
        'created_at' => now()->subDays(2),
    ]);

    Cache::flush();

    $warning = buildWarning()->handle($user);

    expect($warning)->toBeInstanceOf(CreditWarning::class)
        ->and($warning->limitType)->toBe('weekly')
        ->and($warning->percentage)->toBe(90);
});

it('picks the monthly window when only monthly is over 80%', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.81,
        'created_at' => now()->subDays(20),
    ]);

    Cache::flush();

    $warning = buildWarning()->handle($user);

    expect($warning)->toBeInstanceOf(CreditWarning::class)
        ->and($warning->limitType)->toBe('monthly');
});

it('produces a human-readable resets_in string', function (): void {
    $user = User::factory()->create();

    AiUsage::factory()->create([
        'user_id' => $user->id,
        'cost' => 0.085,
    ]);

    $warning = buildWarning()->handle($user);

    expect($warning?->resetsIn)->toBeString()->not->toBeEmpty();
});
