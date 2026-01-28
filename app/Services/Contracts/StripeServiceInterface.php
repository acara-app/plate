<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\User;

interface StripeServiceInterface
{
    /**
     * Ensure the user has a Stripe customer ID.
     */
    public function ensureStripeCustomer(User $user): void;

    /**
     * Get the billing portal URL for the user.
     */
    public function getBillingPortalUrl(User $user, string $returnUrl): string;

    /**
     * Check if user has incomplete payment for subscription.
     */
    public function hasIncompletePayment(User $user, string $subscriptionType): bool;

    /**
     * Check if user has an active subscription.
     */
    public function hasActiveSubscription(User $user): bool;

    /**
     * Get Stripe price ID from lookup key.
     */
    public function getPriceIdFromLookupKey(string $lookupKey): ?string;

    /**
     * Create a new subscription checkout session.
     *
     * @param  array<string, string>  $metadata
     */
    public function createSubscriptionCheckout(User $user, string $subscriptionType, string $priceId, string $successUrl, string $cancelUrl, array $metadata = [], ?int $trialDays = null): string;

    /**
     * Get the hosted invoice URL for an incomplete payment.
     */
    public function getIncompletePaymentUrl(\Laravel\Cashier\Subscription $subscription): ?string;
}
