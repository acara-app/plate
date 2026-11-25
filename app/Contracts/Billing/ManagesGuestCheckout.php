<?php

declare(strict_types=1);

namespace App\Contracts\Billing;

use App\Data\Billing\GuestCheckoutSession;
use Carbon\CarbonInterface;
use Stripe\Subscription;

interface ManagesGuestCheckout
{
    /** @param array<string, string> $metadata */
    public function open(string $idempotencyKey, string $priceId, string $reference, string $successUrl, string $cancelUrl, array $metadata, string $submitMessage): GuestCheckoutSession;

    public function findOpenSession(string $reference, CarbonInterface $createdSince): ?GuestCheckoutSession;

    public function expireIfOpen(string $sessionId): bool;

    public function subscription(string $subscriptionId): Subscription;
}
