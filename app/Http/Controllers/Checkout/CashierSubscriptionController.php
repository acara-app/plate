<?php

declare(strict_types=1);

namespace App\Http\Controllers\Checkout;

use App\Actions\Billing\StartGuestCheckout;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Contracts\Billing\OffersSubscriptions;
use App\Contracts\Services\StripeServiceContract;
use App\Data\Billing\PhotoAnalysisContext;
use App\Enums\SnapFunnelStage;
use App\Http\Requests\CreateSubscriptionRequest;
use App\Models\SubscriptionProduct;
use App\Services\Billing\SnapFunnel;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final readonly class CashierSubscriptionController
{
    public function __construct(private StripeServiceContract $stripeService, private SnapFunnel $funnel, private StartGuestCheckout $guestCheckout) {}

    public function __invoke(CreateSubscriptionRequest $request): RedirectResponse|Response
    {
        $product = SubscriptionProduct::query()->findOrFail($request->integer('product_id'));
        $user = $request->user();
        $available = $product->price > 0 && resolve(OffersSubscriptions::class)->available($product);

        if ($user === null && (! $available || ! $product->purchasable || $product->coming_soon || blank($product->stripe_price_id))) {
            throw ValidationException::withMessages(['product_id' => __('common.checkout_subscription.photo_plan.unavailable')]);
        }

        abort_unless($available, 422, 'This plan is no longer offered.');

        $attempt = (string) Str::uuid();
        $checkout = ['plan' => SnapFunnel::plan($product->name), 'interval' => 'monthly'];
        $this->funnel->record(SnapFunnelStage::CheckoutClicked, $attempt, $checkout);

        try {
            if ($user === null) {
                $guestId = PhotoAnalysisContext::guestId($request) ?? $request->session()->get('snap_to_track.analysis_guest_id');
                if (! is_string($guestId)) {
                    $guestId = (string) Str::uuid();
                    $request->session()->put('snap_to_track.analysis_guest_id', $guestId);
                }

                $freeScansUsed = resolve(ManagesPhotoAnalyses::class)->entitlement(null, $guestId)->freeScansUsed();

                return Inertia::location($this->guestCheckout->handle($guestId, 'pricing', $freeScansUsed, $product));
            }

            if ($this->stripeService->hasActiveSubscription($user)) {
                $this->funnel->record(SnapFunnelStage::CheckoutFailed, $attempt, ['reason' => 'already_subscribed']);
                Inertia::flash('toast', [
                    'type' => 'error',
                    'message' => 'You already have an active subscription. Use the billing portal to manage it.',
                ]);

                return to_route('checkout.subscription');
            }

            $selection = "{$user->id}:{$product->id}:monthly";
            $openCheckout = $request->session()->get('checkout.open');

            if (is_array($openCheckout) && ($openCheckout['selection'] ?? null) === $selection && ($openCheckout['expires_at'] ?? 0) > now()->getTimestamp() && is_string($openCheckout['url'] ?? null)) {
                $request->session()->put('checkout.started', $openCheckout['attempt'] ?? $attempt);

                return Inertia::location($openCheckout['url']);
            }

            $lookupKey = $product->stripe_lookup_key;
            throw_unless($lookupKey, Exception::class, "No monthly lookup key configured for product: {$product->name}");

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
                    'billing_interval' => 'monthly',
                    ...$this->funnel->checkoutMetadata($user),
                ],
                $trialDays
            );

            $request->session()->put('checkout.started', $attempt);
            $request->session()->put('checkout.open', [
                'selection' => $selection,
                'url' => $checkoutUrl,
                'attempt' => $attempt,
                'expires_at' => now()->addHours(23)->getTimestamp(),
            ]);
            $this->funnel->record(SnapFunnelStage::CheckoutStarted, $attempt, $checkout);

            return Inertia::location($checkoutUrl);
        } catch (Exception $exception) {
            report($exception);
            $this->funnel->record(SnapFunnelStage::CheckoutFailed, $attempt, ['reason' => 'error']);
            if ($user === null) {
                return to_route('checkout.subscription')->withErrors(['checkout' => __('common.checkout_subscription.photo_plan.checkout_error')]);
            }

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Failed to initiate subscription. Please try again.',
            ]);

            return to_route('checkout.subscription');
        }
    }
}
