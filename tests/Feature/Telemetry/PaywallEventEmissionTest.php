<?php

declare(strict_types=1);

use App\Actions\Billing\AuthorizeGatedFeature;
use App\Actions\Billing\BuildCreditWarning;
use App\Actions\Billing\EnforceAiUsageLimit;
use App\Contracts\Telemetry\EmitsPaywallEvents;
use App\Enums\GatedFeature;
use App\Enums\ModelName;
use App\Enums\Telemetry\PaywallEvent;
use App\Models\AiUsage;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
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

it('emits gated_feature_attempt when AuthorizeGatedFeature blocks', function (): void {
    $user = User::factory()->create();

    try {
        resolve(AuthorizeGatedFeature::class)->handle($user, GatedFeature::MealPlanner);
    } catch (Throwable) {
        //
    }

    $events = $this->fake->eventsOfType(PaywallEvent::GatedFeatureAttempt);

    expect($events)->toHaveCount(1)
        ->and($events[0]['user_id'])->toBe($user->id)
        ->and($events[0]['payload'])->toMatchArray([
            'tier_current' => 'free',
            'tier_required' => 'basic',
            'feature' => 'meal_planner',
        ]);
});

it('does not emit gated_feature_attempt when the user clears the gate', function (): void {
    Config::set('plate.enable_premium_upgrades', false);

    $user = User::factory()->create();

    resolve(AuthorizeGatedFeature::class)->handle($user, GatedFeature::MealPlanner);

    expect($this->fake->emitted)->toBeEmpty();
});

it('emits gated_feature_attempt when a Free user lands on the meal planner page', function (): void {
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('meal-plans.index'))->assertSuccessful();

    $events = $this->fake->eventsOfType(PaywallEvent::GatedFeatureAttempt);

    expect($events)->toHaveCount(1)
        ->and($events[0]['payload'])->toMatchArray([
            'tier_current' => 'free',
            'feature' => 'meal_planner',
            'surface' => 'meal_plans_page',
        ]);
});

it('debounces the meal-planner page-landing event to once per day per user', function (): void {
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('meal-plans.index'));
    $this->actingAs($user)->get(route('meal-plans.index'));
    $this->actingAs($user)->get(route('meal-plans.index'));

    expect($this->fake->eventsOfType(PaywallEvent::GatedFeatureAttempt))
        ->toHaveCount(1);
});

it('does not emit page-landing event for users who already have access', function (): void {
    Cache::flush();
    Config::set('plate.enable_premium_upgrades', false);
    Queue::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('meal-plans.index'));

    expect($this->fake->emitted)->toBeEmpty();
});

it('separates the cap funnel from the feature-gate funnel', function (): void {
    $user = User::factory()->create();
    AiUsage::factory()->create(['user_id' => $user->id, 'cost' => 0.099]);

    try {
        resolve(EnforceAiUsageLimit::class)->handle($user, ModelName::GPT_5_4_MINI);
    } catch (Throwable) {
        //
    }

    try {
        resolve(AuthorizeGatedFeature::class)->handle($user, GatedFeature::MealPlanner);
    } catch (Throwable) {
        //
    }

    expect($this->fake->eventsOfType(PaywallEvent::UsageLimitExceeded))
        ->toHaveCount(1)
        ->and($this->fake->eventsOfType(PaywallEvent::GatedFeatureAttempt))
        ->toHaveCount(1);
});
