<?php

declare(strict_types=1);

namespace App\Http\Controllers\Checkout;

use App\Contracts\Billing\OffersSubscriptions;
use App\Contracts\Services\StripeServiceContract;
use App\Enums\SnapFunnelStage;
use App\Http\Requests\CreateSubscriptionRequest;
use App\Models\SubscriptionProduct;
use App\Services\Billing\SnapFunnel;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final readonly class CashierSubscriptionController
{
    public function __construct(private StripeServiceContract $stripeService, private SnapFunnel $funnel) {}

    public function __invoke(CreateSubscriptionRequest $request): RedirectResponse|Response
    {
        /** @var array{product_id: int, billing_interval: string} $data */
        $data = $request->validated();

        /** @var SubscriptionProduct $product */
        $product = SubscriptionProduct::query()->findOrFail($data['product_id']);

        abort_unless(resolve(OffersSubscriptions::class)->available($product), 422, 'This plan is no longer offered.');
        abort_if($data['billing_interval'] === 'yearly' && ! $product->yearly_stripe_lookup_key, 422, 'This plan is monthly only.');

        $attempt = (string) Str::uuid();
        $checkout = ['plan' => SnapFunnel::plan($product->name), 'interval' => $data['billing_interval']];
        $this->funnel->record(SnapFunnelStage::CheckoutClicked, $attempt, $checkout);

        try {
            $user = $request->user();

            if ($user === null) {
                abort(401); // @codeCoverageIgnore
            }

            if ($this->stripeService->hasActiveSubscription($user)) {
                $this->funnel->record(SnapFunnelStage::CheckoutFailed, $attempt, ['reason' => 'already_subscribed']);
                Inertia::flash('toast', [
                    'type' => 'error',
                    'message' => 'You already have an active subscription. Use the billing portal to manage it.',
                ]);

                return to_route('checkout.subscription');
            }

            $openCheckout = $request->session()->get('checkout.open');

            if (is_array($openCheckout) && ($openCheckout['selection'] ?? null) === "{$product->id}:{$data['billing_interval']}" && ($openCheckout['expires_at'] ?? 0) > now()->getTimestamp() && is_string($openCheckout['url'] ?? null)) {
                $request->session()->put('checkout.started', $openCheckout['attempt'] ?? $attempt);

                return Inertia::location($openCheckout['url']);
            }

            $billingInterval = $data['billing_interval'];
            $lookupKey = $billingInterval === 'yearly'
                ? $product->yearly_stripe_lookup_key
                : $product->stripe_lookup_key;

            throw_unless($lookupKey, Exception::class, sprintf('No %s lookup key configured for product: %s', $billingInterval, $product->name));

            $actualPriceId = $this->stripeService->getPriceIdFromLookupKey($lookupKey);

            throw_unless($actualPriceId, Exception::class, "No Stripe price found with lookup_key: {$lookupKey}");

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
                    ...$this->funnel->checkoutMetadata($user),
                ],
                $trialDays
            );

            $request->session()->put('checkout.started', $attempt);
            $request->session()->put('checkout.open', [
                'selection' => "{$product->id}:{$billingInterval}",
                'url' => $checkoutUrl,
                'attempt' => $attempt,
                'expires_at' => now()->addHours(23)->getTimestamp(),
            ]);
            $this->funnel->record(SnapFunnelStage::CheckoutStarted, $attempt, $checkout);

            return Inertia::location($checkoutUrl);
        } catch (Exception) {
            $this->funnel->record(SnapFunnelStage::CheckoutFailed, $attempt, ['reason' => 'error']);
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Failed to initiate subscription. Please try again.',
            ]);

            return to_route('checkout.subscription');
        }
    }
}
