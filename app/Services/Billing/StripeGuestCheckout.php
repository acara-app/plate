<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\ManagesGuestCheckout;
use App\Data\Billing\GuestCheckoutSession;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Laravel\Cashier\Cashier;
use Stripe\Checkout\Session;
use Stripe\Exception\InvalidRequestException;
use Stripe\Subscription;

/**
 * @codeCoverageIgnore
 */
final readonly class StripeGuestCheckout implements ManagesGuestCheckout
{
    public function open(string $idempotencyKey, string $priceId, string $reference, string $successUrl, string $cancelUrl, array $metadata, string $submitMessage): GuestCheckoutSession
    {
        return $this->toSession(Cashier::stripe()->checkout->sessions->create([
            'mode' => 'subscription',
            'line_items' => [['price' => $priceId, 'quantity' => 1]],
            'client_reference_id' => $reference,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
            'subscription_data' => ['metadata' => $metadata],
            'custom_text' => ['submit' => ['message' => $submitMessage]],
        ], ['idempotency_key' => $idempotencyKey]));
    }

    public function findOpenSession(string $reference, CarbonInterface $createdSince): ?GuestCheckoutSession
    {
        $sessions = Cashier::stripe()->checkout->sessions->all([
            'created' => ['gte' => $createdSince->toImmutable()->subMinutes(5)->getTimestamp()],
            'status' => Session::STATUS_OPEN,
            'limit' => 100,
        ]);

        foreach ($sessions->autoPagingIterator() as $session) {
            if ($session->client_reference_id === $reference) {
                return $this->toSession($session);
            }
        }

        return null;
    }

    public function subscription(string $subscriptionId): Subscription
    {
        return Cashier::stripe()->subscriptions->retrieve($subscriptionId);
    }

    public function expireIfOpen(string $sessionId): bool
    {
        $sessions = Cashier::stripe()->checkout->sessions;

        try {
            $sessions->expire($sessionId);

            return true;
        } catch (InvalidRequestException $exception) {
            return match ($sessions->retrieve($sessionId)->status) {
                Session::STATUS_EXPIRED => true,
                Session::STATUS_COMPLETE => false,
                default => throw $exception,
            };
        }
    }

    private function toSession(Session $session): GuestCheckoutSession
    {
        return new GuestCheckoutSession($session->id, (string) $session->url, CarbonImmutable::createFromTimestamp($session->expires_at));
    }
}
