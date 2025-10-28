<?php

declare(strict_types=1);

namespace App\Http\Controllers\Checkout;

use App\Models\SubscriptionProduct;
use App\Services\Contracts\StripeServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Cashier-based subscription display controller
 */
final readonly class CashierShowSubscriptionController
{
    public function __construct(private StripeServiceInterface $stripeService)
    {
        //
    }

    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401); // @codeCoverageIgnore
        }

        $this->stripeService->ensureStripeCustomer($user);

        $products = SubscriptionProduct::all();

        /** @var \Laravel\Cashier\Subscription|null $currentSubscription */
        $currentSubscription = $user->subscriptions()->whereStripeStatus('active')->first();

        // Find the current product from local database using subscription_items
        $currentProduct = null;
        $isYearly = false;

        if ($currentSubscription) {
            /** @var \Laravel\Cashier\SubscriptionItem|null $subscriptionItem */
            $subscriptionItem = $currentSubscription->items()->first();

            if ($subscriptionItem) {
                $stripePriceId = $subscriptionItem->stripe_price;

                $currentProduct = $products->first(function (SubscriptionProduct $product) use ($stripePriceId, &$isYearly): bool {
                    if ($product->stripe_price_id === $stripePriceId) {
                        $isYearly = false;

                        return true;
                    }

                    if ($product->yearly_stripe_price_id === $stripePriceId) {
                        $isYearly = true;

                        return true;
                    }

                    return false;
                });
            }
        }

        $hasIncompletePayment = $currentSubscription !== null && $this->stripeService->hasIncompletePayment($user, $currentSubscription->type);

        $incompletePaymentUrl = null;
        if ($currentSubscription !== null && $hasIncompletePayment) {
            $incompletePaymentUrl = $this->stripeService->getIncompletePaymentUrl($currentSubscription);
        }

        return Inertia::render('checkout/show-subscription-product', [
            'products' => $products,
            'currentSubscription' => $currentSubscription ? [
                'id' => $currentSubscription->id,
                'type' => $currentSubscription->type,
                'type_display' => $user->subscriptionDisplayName(),
                'stripe_status' => $currentSubscription->stripe_status,
                'stripe_price' => $currentSubscription->stripe_price,
                'quantity' => $currentSubscription->quantity,
                'trial_ends_at' => $currentSubscription->trial_ends_at,
                'ends_at' => $currentSubscription->ends_at,
                'created_at' => $currentSubscription->created_at,
                'on_trial' => $currentSubscription->onTrial(),
                'cancelled' => $currentSubscription->canceled(),
                'on_grace_period' => $currentSubscription->onGracePeriod(),
                'active' => $currentSubscription->active(),
                'product_name' => $currentProduct?->name,
                'is_yearly' => $isYearly,
            ] : null,
            'billingPortalUrl' => $this->stripeService->getBillingPortalUrl($user, route('checkout.subscription')),
            'hasIncompletePayment' => $hasIncompletePayment,
            'incompletePaymentUrl' => $incompletePaymentUrl,
        ]);
    }
}
