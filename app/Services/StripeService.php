<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Services\Contracts\StripeServiceInterface;
use RuntimeException;

final readonly class StripeService implements StripeServiceInterface
{
    public function ensureStripeCustomer(User $user): void
    {
        if (! $user->stripe_id) {
            $user->createAsStripeCustomer([
                'name' => $user->name,
                'email' => $user->email,
            ]);
        }
    }

    public function getBillingPortalUrl(User $user, string $returnUrl): string
    {
        return $user->billingPortalUrl($returnUrl);
    }

    public function hasIncompletePayment(User $user, string $subscriptionType): bool
    {
        return $user->hasIncompletePayment($subscriptionType);
    }

    public function hasActiveSubscription(User $user): bool
    {
        return $user->subscribed();
    }

    public function getPriceIdFromLookupKey(string $lookupKey): ?string
    {
        $apiKey = config('cashier.secret');

        throw_unless(is_string($apiKey), RuntimeException::class, 'Stripe API key is not configured properly');

        \Stripe\Stripe::setApiKey($apiKey); // @codeCoverageIgnore

        $prices = \Stripe\Price::all([ // @codeCoverageIgnore
            'lookup_keys' => [$lookupKey], // @codeCoverageIgnore
            'limit' => 1, // @codeCoverageIgnore
        ]); // @codeCoverageIgnore

        return empty($prices->data) ? null : $prices->data[0]->id; // @codeCoverageIgnore
    }

    /**
     * @param  array<string, string>  $metadata
     */
    public function createSubscriptionCheckout(User $user, string $subscriptionType, string $priceId, string $successUrl, string $cancelUrl, array $metadata = [], ?int $trialDays = null): string
    {
        $subscription = $user->newSubscription($subscriptionType, $priceId);

        // @codeCoverageIgnoreStart
        if ($trialDays !== null && $trialDays > 0) {
            $subscription->trialDays($trialDays);
        }
        // @codeCoverageIgnoreEnd

        $checkout = $subscription->checkout([
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => $metadata,
        ]);

        $url = $checkout->url ?? null; // @codeCoverageIgnore

        if (! is_string($url)) { // @codeCoverageIgnore
            throw new RuntimeException('Checkout URL is not available'); // @codeCoverageIgnore
        } // @codeCoverageIgnore

        return $url; // @codeCoverageIgnore
    }

    public function getIncompletePaymentUrl(\Laravel\Cashier\Subscription $subscription): ?string
    {
        $latestPayment = $subscription->latestPayment();

        if (! $latestPayment instanceof \Laravel\Cashier\Payment) {
            return null;
        }

        $url = $latestPayment->hosted_invoice_url ?? null;

        return is_string($url) ? $url : null;
    }
}
