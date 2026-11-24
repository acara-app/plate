<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\ManagesGuestCheckout;
use App\Data\Billing\GuestCheckoutSession;
use Carbon\CarbonImmutable;
use Laravel\Cashier\Cashier;
use Stripe\Subscription;

/**
 * @codeCoverageIgnore
 */
final readonly class StripeGuestCheckout implements ManagesGuestCheckout
{
    public function open(string $priceId, string $reference, string $successUrl, string $cancelUrl, array $metadata, string $submitMessage): GuestCheckoutSession
    {
        $checkout = Cashier::stripe()->checkout->sessions->create([
            'mode' => 'subscription',
            'line_items' => [['price' => $priceId, 'quantity' => 1]],
            'client_reference_id' => $reference,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
            'subscription_data' => ['metadata' => $metadata],
            'custom_text' => ['submit' => ['message' => $submitMessage]],
        ], ['idempotency_key' => 'plan-purchase:'.$reference]);

        return new GuestCheckoutSession($checkout->id, (string) $checkout->url, CarbonImmutable::createFromTimestamp($checkout->expires_at));
    }

    public function subscription(string $subscriptionId): Subscription
    {
        return Cashier::stripe()->subscriptions->retrieve($subscriptionId);
    }
}
