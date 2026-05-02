<?php

declare(strict_types=1);

use App\Contracts\Telemetry\EmitsPaywallEvents;
use App\Enums\Telemetry\PaywallEvent;
use App\Listeners\EmitPaywallEventsFromWebhook;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Laravel\Cashier\Events\WebhookHandled;
use Tests\Helpers\FakePaywallTelemetry;

covers(EmitPaywallEventsFromWebhook::class);

beforeEach(function (): void {
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

function dispatchWebhook(string $type, array $object): void
{
    $payload = [
        'type' => $type,
        'data' => ['object' => $object],
    ];

    resolve(EmitPaywallEventsFromWebhook::class)->handle(new WebhookHandled($payload));
}

it('emits checkout_completed for an active subscription created via webhook', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_test_001']);

    dispatchWebhook('customer.subscription.created', [
        'id' => 'sub_test_001',
        'customer' => 'cus_test_001',
        'status' => 'active',
        'items' => [
            'data' => [
                ['price' => ['id' => 'price_basic_monthly', 'product' => 'prod_basic']],
            ],
        ],
    ]);

    $events = $this->fake->eventsOfType(PaywallEvent::CheckoutCompleted);

    expect($events)->toHaveCount(1)
        ->and($events[0]['user_id'])->toBe($user->id)
        ->and($events[0]['payload'])->toMatchArray([
            'tier_target' => 'basic',
            'tier_target_label' => 'Basic',
            'interval' => 'monthly',
            'stripe_status' => 'active',
            'stripe_subscription_id' => 'sub_test_001',
        ]);
});

it('emits checkout_completed for an updated webhook that newly transitions to active', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_test_002']);

    dispatchWebhook('customer.subscription.updated', [
        'id' => 'sub_test_002',
        'customer' => 'cus_test_002',
        'status' => 'active',
        'items' => [
            'data' => [
                ['price' => ['id' => 'price_plus_yearly', 'product' => 'prod_plus']],
            ],
        ],
    ]);

    $events = $this->fake->eventsOfType(PaywallEvent::CheckoutCompleted);

    expect($events)->toHaveCount(1)
        ->and($events[0]['user_id'])->toBe($user->id)
        ->and($events[0]['payload'])->toMatchArray([
            'tier_target' => 'plus',
            'interval' => 'yearly',
        ]);
});

it('does not emit checkout_completed for incomplete subscription webhooks', function (): void {
    User::factory()->create(['stripe_id' => 'cus_test_003']);

    dispatchWebhook('customer.subscription.created', [
        'id' => 'sub_test_003',
        'customer' => 'cus_test_003',
        'status' => 'incomplete',
        'items' => [
            'data' => [
                ['price' => ['id' => 'price_basic_monthly', 'product' => 'prod_basic']],
            ],
        ],
    ]);

    expect($this->fake->emitted)->toBeEmpty();
});

it('emits subscription_canceled for a deleted subscription webhook', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_test_004']);

    dispatchWebhook('customer.subscription.deleted', [
        'id' => 'sub_test_004',
        'customer' => 'cus_test_004',
        'items' => [
            'data' => [
                ['price' => ['id' => 'price_plus_monthly', 'product' => 'prod_plus']],
            ],
        ],
    ]);

    $events = $this->fake->eventsOfType(PaywallEvent::SubscriptionCanceled);

    expect($events)->toHaveCount(1)
        ->and($events[0]['user_id'])->toBe($user->id)
        ->and($events[0]['payload'])->toMatchArray([
            'tier' => 'plus',
            'tier_label' => 'Plus',
            'stripe_subscription_id' => 'sub_test_004',
        ]);
});

it('ignores webhook event types it does not care about', function (): void {
    dispatchWebhook('invoice.paid', ['id' => 'in_test', 'customer' => 'cus_test_005']);

    expect($this->fake->emitted)->toBeEmpty();
});

it('emits with a null user_id when the customer is unknown', function (): void {
    dispatchWebhook('customer.subscription.created', [
        'id' => 'sub_orphan',
        'customer' => 'cus_unknown',
        'status' => 'active',
        'items' => [
            'data' => [
                ['price' => ['id' => 'price_basic_monthly', 'product' => 'prod_basic']],
            ],
        ],
    ]);

    $events = $this->fake->eventsOfType(PaywallEvent::CheckoutCompleted);

    expect($events)->toHaveCount(1)
        ->and($events[0]['user_id'])->toBeNull();
});
