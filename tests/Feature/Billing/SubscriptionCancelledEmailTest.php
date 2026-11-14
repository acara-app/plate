<?php

declare(strict_types=1);

use App\Listeners\SendSubscriptionCancelledEmail;
use App\Models\User;
use App\Notifications\SubscriptionCancelledNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Laravel\Cashier\Subscription;

covers(SendSubscriptionCancelledEmail::class);

beforeEach(function (): void {
    enableBilling();
    Notification::fake();
});

function starterSubscriber(string $locale = 'en'): User
{
    return User::factory()
        ->has(Subscription::factory()->state(['type' => 'starter', 'stripe_id' => 'sub_starter', 'stripe_price' => 'price_starter']), 'subscriptions')
        ->create(['stripe_id' => 'cus_starter', 'locale' => $locale]);
}

function subscriptionCancellationWebhook(string $type, array $subscription, array $previousAttributes = []): array
{
    return ['id' => 'evt_'.md5($type.serialize($subscription)), 'type' => $type, 'data' => array_filter([
        'object' => [
            'id' => 'sub_starter',
            'customer' => 'cus_starter',
            'status' => 'active',
            'metadata' => ['type' => 'starter'],
            'items' => ['data' => [['id' => 'si_starter', 'price' => ['id' => 'price_starter', 'product' => 'prod_starter'], 'quantity' => 1]]],
            'cancel_at' => null,
            'cancel_at_period_end' => false,
            'canceled_at' => null,
            'ended_at' => null,
            'cancellation_details' => ['reason' => 'cancellation_requested'],
            ...$subscription,
        ],
        'previous_attributes' => $previousAttributes,
    ])];
}

it("confirms a requested cancellation by email once, in the subscriber's language", function (string $type, array $subscription, array $previousAttributes, bool $endedNow): void {
    $user = starterSubscriber('fr');
    $webhook = subscriptionCancellationWebhook($type, $subscription, $previousAttributes);

    sendSignedStripeWebhook($webhook)->assertSuccessful();
    sendSignedStripeWebhook($webhook)->assertSuccessful();

    Notification::assertSentToTimes($user, SubscriptionCancelledNotification::class, 1);
    Notification::assertSentTo($user, fn (SubscriptionCancelledNotification $notification): bool => $notification->plan === 'Starter'
        && $notification->endedNow === $endedNow
        && $notification->endsAt->toDateString() === '2026-12-14'
        && $notification->locale === 'fr');
})->with([
    'scheduled to end on a date' => ['customer.subscription.updated', ['cancel_at' => CarbonImmutable::parse('2026-12-14')->timestamp], ['cancel_at' => null], false],
    'cancelled right away' => ['customer.subscription.deleted', ['status' => 'canceled', 'canceled_at' => CarbonImmutable::parse('2026-12-14')->timestamp, 'ended_at' => CarbonImmutable::parse('2026-12-14')->timestamp], [], true],
]);

it('sends nothing for subscription changes the subscriber did not request as a cancellation', function (string $type, array $subscription, array $previousAttributes = []): void {
    starterSubscriber();

    sendSignedStripeWebhook(subscriptionCancellationWebhook($type, $subscription, $previousAttributes))->assertSuccessful();

    Notification::assertNothingSent();
})->with([
    'cancellation undone' => ['customer.subscription.updated', [], ['cancel_at' => CarbonImmutable::parse('2026-12-14')->timestamp]],
    'card changed while a cancellation is pending' => ['customer.subscription.updated', ['cancel_at' => CarbonImmutable::parse('2026-12-14')->timestamp], ['default_payment_method' => 'pm_old_card']],
    'ended after failed payments' => ['customer.subscription.deleted', ['status' => 'canceled', 'ended_at' => CarbonImmutable::parse('2026-12-14')->timestamp, 'cancellation_details' => ['reason' => 'payment_failed']]],
    'scheduled cancellation reaching its end' => ['customer.subscription.deleted', ['status' => 'canceled', 'cancel_at' => CarbonImmutable::parse('2026-12-14')->timestamp, 'ended_at' => CarbonImmutable::parse('2026-12-14')->timestamp]],
    "another app's Stripe customer" => ['customer.subscription.deleted', ['customer' => 'cus_another_app', 'status' => 'canceled', 'ended_at' => CarbonImmutable::parse('2026-12-14')->timestamp]],
]);
