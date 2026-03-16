<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Models\User;
use Laravel\Cashier\Subscription;

interface StripeServiceContract
{
    public function ensureStripeCustomer(User $user): void;

    public function getBillingPortalUrl(User $user, string $returnUrl): string;

    public function hasIncompletePayment(User $user, string $subscriptionType): bool;

    public function hasActiveSubscription(User $user): bool;

    public function getPriceIdFromLookupKey(string $lookupKey): ?string;

    /**
     * @param  array<string, string>  $metadata
     */
    public function createSubscriptionCheckout(User $user, string $subscriptionType, string $priceId, string $successUrl, string $cancelUrl, array $metadata = [], ?int $trialDays = null): string;

    public function getIncompletePaymentUrl(Subscription $subscription): ?string;
}
