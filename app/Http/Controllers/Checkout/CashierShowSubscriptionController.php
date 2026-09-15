<?php

declare(strict_types=1);

namespace App\Http\Controllers\Checkout;

use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Contracts\Billing\OffersSubscriptions;
use App\Contracts\Services\StripeServiceContract;
use App\Data\Billing\PhotoAnalysisContext;
use App\Models\SubscriptionProduct;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\SubscriptionItem;

final readonly class CashierShowSubscriptionController
{
    public function __construct(private StripeServiceContract $stripeService)
    {
        //
    }

    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $products = SubscriptionProduct::all();

        /** @var Subscription|null $currentSubscription */
        /** @phpstan-ignore-next-line argument.type */
        $currentSubscription = $user?->subscriptions()->get()->first(fn (Subscription $subscription): bool => $subscription->valid());

        $currentProduct = null;
        $isYearly = false;

        if ($currentSubscription) {
            /** @var SubscriptionItem|null $subscriptionItem */
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

        $hasIncompletePayment = $user !== null && $currentSubscription !== null && $this->stripeService->hasIncompletePayment($user, $currentSubscription->type);

        $incompletePaymentUrl = null;
        if ($currentSubscription !== null && $hasIncompletePayment) {
            $incompletePaymentUrl = $this->stripeService->getIncompletePaymentUrl($currentSubscription);
        }

        $offers = resolve(OffersSubscriptions::class);
        $availableProducts = $products->filter($offers->available(...))->map($offers->present(...))->values();

        $allowance = resolve(ManagesPhotoAnalyses::class)->entitlement($user, PhotoAnalysisContext::guestId($request));
        $returningFromCheckout = $request->routeIs('checkout.success');

        if ($returningFromCheckout && $allowance->mode === 'premium' && $request->session()->pull('checkout.started')) {
            Inertia::flash('analytics', ['name' => 'snap_to_track_payment_verified', 'properties' => ['source' => 'checkout']]);
        }

        $premiumNotYetActive = $allowance->enabled ? $allowance->mode !== 'premium' : $currentSubscription === null;
        $paymentPending = $returningFromCheckout && $request->session()->has('checkout.started') && $premiumNotYetActive;

        return Inertia::render('checkout/show-subscription-product', [
            'products' => $availableProducts,
            'isGuest' => $user === null,
            'paymentPending' => $paymentPending,
            'selectedProductId' => $request->session()->get('checkout.selected_product'),
            'photoAllowance' => $allowance->toArray(),
            'upgradeDraft' => session('snap_to_track.upgrade_draft'),
            'currentSubscription' => $currentSubscription ? [
                'id' => $currentSubscription->id,
                'type' => $currentSubscription->type,
                'type_display' => $user?->subscriptionDisplayName(),
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
            'billingPortalUrl' => $user?->stripe_id ? route('billing.portal') : null,
            'hasIncompletePayment' => $hasIncompletePayment,
            'incompletePaymentUrl' => $incompletePaymentUrl,
        ]);
    }
}
