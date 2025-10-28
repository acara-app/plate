<?php

declare(strict_types=1);

namespace App\Http\Controllers\Checkout;

use App\Models\SubscriptionProduct;
use App\Services\Contracts\StripeServiceInterface;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;

final readonly class CashierSubscriptionController
{
    public function __construct(private StripeServiceInterface $stripeService)
    {
        //
    }

    public function __invoke(Request $request): \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        /** @var array{product_id: int, billing_interval: string} $data */
        $data = $request->validate([
            'product_id' => ['required', 'exists:subscription_products,id'],
            'billing_interval' => ['required', 'in:monthly,yearly'],
        ]);

        /** @var SubscriptionProduct $product */
        $product = SubscriptionProduct::query()->findOrFail($data['product_id']);

        try {
            $user = $request->user();

            if ($user === null) {
                abort(401); // @codeCoverageIgnore
            }

            if ($this->stripeService->hasActiveSubscription($user)) {
                return to_route('checkout.subscription')
                    ->with('error', 'You already have an active subscription. Use the billing portal to manage it.');
            }

            // Get the appropriate price ID based on billing interval
            $stripePriceId = $data['billing_interval'] === 'yearly'
                ? $product->yearly_stripe_price_id
                : $product->stripe_price_id;

            $billingInterval = $data['billing_interval'];
            throw_unless($stripePriceId, Exception::class, "No {$billingInterval} price ID configured for product: {$product->name}");

            $actualPriceId = $this->stripeService->getPriceIdFromLookupKey($stripePriceId);

            throw_unless($actualPriceId, Exception::class, "No price found with lookup_key: {$stripePriceId}");

            // Use product name as subscription type for better UX
            $subscriptionType = str($product->name)->slug()->toString();

            $checkoutUrl = $this->stripeService->createSubscriptionCheckout(
                $user,
                $subscriptionType,
                $actualPriceId,
                route('checkout.success').'?success=1',
                route('checkout.cancel').'?cancelled=1',
                [
                    'product_id' => (string) $product->id,
                    'product_name' => $product->name,
                    'user_id' => (string) $user->id,
                    'billing_interval' => $data['billing_interval'],
                ]
            );

            return Inertia::location($checkoutUrl);

        } catch (Exception) {

            return to_route('checkout.subscription')
                ->with('error', 'Failed to initiate subscription. Please try again.');
        }
    }
}
