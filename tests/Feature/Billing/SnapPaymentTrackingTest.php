<?php

declare(strict_types=1);

use App\Enums\SnapFunnelStage;
use App\Models\SnapAnalyticsEvent;
use App\Models\SnapJourney;
use Illuminate\Support\Str;

beforeEach(function (): void {
    enableBilling();
});

function snapInvoiceWebhook(array $invoice, string $type = 'invoice.paid'): array
{
    return ['id' => 'evt_'.Str::random(12), 'type' => $type, 'livemode' => true, 'data' => ['object' => [
        'customer' => 'cus_first_payment',
        'status' => 'paid',
        'amount_paid' => 299,
        'currency' => 'usd',
        'billing_reason' => 'subscription_create',
        'parent' => ['subscription_details' => ['subscription' => 'sub_first_payment', 'metadata' => []]],
        ...$invoice,
    ]]];
}

function firstPayments(): array
{
    return SnapAnalyticsEvent::query()->where('name', SnapFunnelStage::FirstPayment->value)->orderBy('id')->pluck('journey_id', 'event_key')->all();
}

it('counts the first paid invoice of each subscription once', function (): void {
    $journey = SnapJourney::factory()->create();
    $subscription = ['parent' => ['subscription_details' => [
        'subscription' => 'sub_first_payment',
        'metadata' => ['snap_journey' => $journey->id, 'product_name' => 'Starter'],
    ]]];

    foreach ([
        snapInvoiceWebhook([...$subscription, 'amount_paid' => 0]),
        snapInvoiceWebhook([...$subscription, 'status' => 'open'], 'invoice.payment_failed'),
        snapInvoiceWebhook($subscription),
        snapInvoiceWebhook($subscription, 'invoice.payment_succeeded'),
        snapInvoiceWebhook([...$subscription, 'billing_reason' => 'subscription_cycle']),
        snapInvoiceWebhook(['parent' => ['subscription_details' => ['subscription' => 'sub_elsewhere', 'metadata' => []]]]),
    ] as $webhook) {
        sendSignedStripeWebhook($webhook)->assertSuccessful();
    }

    expect(firstPayments())->toBe([
        'snap_to_track_first_payment:sub_first_payment' => $journey->id,
        'snap_to_track_first_payment:sub_elsewhere' => null,
    ])->and(SnapAnalyticsEvent::query()->where('journey_id', $journey->id)->sole()->properties)
        ->toMatchArray(['plan' => 'starter', 'amount' => 299, 'billing_reason' => 'subscription_create', 'live' => true]);
});

it('rejects forged Stripe callbacks before recording a payment', function (): void {
    $payload = json_encode(snapInvoiceWebhook([]), JSON_THROW_ON_ERROR);

    $this->call('POST', route('cashier.webhook'), server: ['HTTP_STRIPE_SIGNATURE' => 't='.time().',v1=forged', 'CONTENT_TYPE' => 'application/json'], content: $payload)
        ->assertForbidden();

    expect(firstPayments())->toBe([]);
});

it('does not count callbacks while Stripe signature checks are off', function (): void {
    config(['cashier.webhook.secret' => null]);

    $this->postJson(route('cashier.webhook'), snapInvoiceWebhook([]))->assertSuccessful();

    expect(firstPayments())->toBe([]);
});
