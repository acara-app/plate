<?php

declare(strict_types=1);

namespace App\Http\Controllers\Checkout;

use App\Contracts\Services\StripeServiceContract;
use App\Http\Requests\CreateSubscriptionRequest;
use App\Models\SubscriptionProduct;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final readonly class CashierSubscriptionController
{
    public function __construct(private StripeServiceContract $stripeService) {}

    public function __invoke(CreateSubscriptionRequest $request): RedirectResponse|Response
    {
        /** @var array{product_id: int, billing_interval: string} $data */
        $data = $request->validated();

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

            $billingInterval = $data['billing_interval'];
            $lookupKey = $billingInterval === 'yearly'
                ? $product->yearly_stripe_lookup_key
                : $product->stripe_lookup_key;

            throw_unless($lookupKey, Exception::class, sprintf('No %s lookup key configured for product: %s', $billingInterval, $product->name));

            $actualPriceId = $this->stripeService->getPriceIdFromLookupKey($lookupKey);

            throw_unless($actualPriceId, Exception::class, 'No Stripe price found with lookup_key: '.$lookupKey);

            $subscriptionType = str($product->name)->slug()->toString();

            $trialDays = $product->product_group === 'trial' ? 7 : null;

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
                ],
                $trialDays
            );

            return Inertia::location($checkoutUrl);

        } catch (Exception) {

            return to_route('checkout.subscription')
                ->with('error', 'Failed to initiate subscription. Please try again.');
        }
    }
}
