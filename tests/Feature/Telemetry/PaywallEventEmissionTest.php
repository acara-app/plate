<?php

declare(strict_types=1);

use App\Actions\Billing\BuildCreditWarning;
use App\Actions\Billing\EnforceAiUsageLimit;
use App\Contracts\Telemetry\EmitsPaywallEvents;
use App\Enums\ModelName;
use App\Enums\Telemetry\PaywallEvent;
use App\Models\AiUsage;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Tests\Helpers\FakePaywallTelemetry;

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

    $this->fake = new FakePaywallTelemetry();
    $this->app->instance(EmitsPaywallEvents::class, $this->fake);
});

it('emits credit_warning_shown when BuildCreditWarning fires', function (): void {
    $user = User::factory()->create();
    AiUsage::factory()->create(['user_id' => $user->id, 'cost' => 0.085]);

    resolve(BuildCreditWarning::class)->handle($user);

    $events = $this->fake->eventsOfType(PaywallEvent::CreditWarningShown);

    expect($events)->toHaveCount(1)
        ->and($events[0]['user_id'])->toBe($user->id)
        ->and($events[0]['payload'])->toMatchArray([
            'tier_current' => 'free',
            'limit_type' => 'rolling',
            'percentage' => 85,
        ])
        ->and($events[0]['payload']['period_resets_at'])->toBeString();
});

it('does not emit credit_warning_shown twice in the same period', function (): void {
    $user = User::factory()->create();
    AiUsage::factory()->create(['user_id' => $user->id, 'cost' => 0.085]);

    resolve(BuildCreditWarning::class)->handle($user);
    resolve(BuildCreditWarning::class)->handle($user);

    expect($this->fake->eventsOfType(PaywallEvent::CreditWarningShown))
        ->toHaveCount(1);
});

it('emits usage_limit_exceeded when EnforceAiUsageLimit blocks the call', function (): void {
    $user = User::factory()->create();
    AiUsage::factory()->create(['user_id' => $user->id, 'cost' => 0.099]);

    try {
        resolve(EnforceAiUsageLimit::class)->handle($user, ModelName::GPT_5_4_MINI);
    } catch (Throwable) {
        //
    }

    $events = $this->fake->eventsOfType(PaywallEvent::UsageLimitExceeded);

    expect($events)->toHaveCount(1)
        ->and($events[0]['user_id'])->toBe($user->id)
        ->and($events[0]['payload'])->toMatchArray([
            'tier_current' => 'free',
            'limit_type' => 'rolling',
        ]);
});

it('does not emit usage_limit_exceeded when the user is under cap', function (): void {
    $user = User::factory()->create();
    AiUsage::factory()->create(['user_id' => $user->id, 'cost' => 0.005]);

    resolve(EnforceAiUsageLimit::class)->handle($user, ModelName::GPT_5_4_MINI);

    expect($this->fake->emitted)->toBeEmpty();
});
