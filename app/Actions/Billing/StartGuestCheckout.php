<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Contracts\Billing\ManagesGuestCheckout;
use App\Contracts\Billing\OffersSubscriptions;
use App\Data\Billing\GuestCheckoutSession;
use App\Enums\PhotoPlan;
use App\Enums\SnapFunnelStage;
use App\Models\PlanPurchase;
use App\Models\SubscriptionProduct;
use App\Services\Billing\PhotoPlanCatalog;
use App\Services\Billing\SnapFunnel;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\IdempotencyException;
use Throwable;

final readonly class StartGuestCheckout
{
    public function __construct(
        private PhotoPlanCatalog $catalog,
        private ManagesGuestCheckout $checkout,
        private SnapFunnel $funnel,
        private Session $session,
        private OffersSubscriptions $offers,
    ) {}

    public function handle(string $guestId, string $placement, ?int $freeScansUsed = null, ?SubscriptionProduct $product = null): string
    {
        $guestHash = hash('sha256', $guestId);
        $lock = Cache::lock('guest-checkout:'.$guestHash, 15);
        $lock->block(10);

        try {
            $latest = PlanPurchase::query()->with('product')->where('guest_hash', $guestHash)->whereNull('cancelled_at')->latest('id')->first();

            $url = $this->resume($latest, $product, $freeScansUsed);

            if ($url !== null) {
                return $url;
            }

            $product ??= $this->catalog->product(PhotoPlan::Starter->productName());
            throw_unless($product instanceof SubscriptionProduct && $product->price > 0 && filled($product->stripe_price_id) && $this->offers->available($product), RuntimeException::class, 'This plan is not on sale.');

            if ($latest instanceof PlanPurchase && ! $this->expireUnpaidCheckout($latest)) {
                return route('snap-to-track.purchase');
            }

            return $this->openSession($this->createPurchase($guestHash, $placement, $product), $freeScansUsed);
        } finally {
            $lock->release();
        }
    }

    private function resume(?PlanPurchase $purchase, ?SubscriptionProduct $product, ?int $freeScansUsed): ?string
    {
        if (! $purchase instanceof PlanPurchase) {
            return null;
        }

        $this->session->put('snap_to_track.purchase', $purchase->id);

        if ($purchase->isPaid() || $purchase->isAwaitingPayment()) {
            return route('snap-to-track.purchase');
        }

        $sameProduct = $product === null ? $purchase->product->name === PhotoPlan::Starter->productName() : $purchase->subscription_product_id === $product->id;

        if (! $sameProduct) {
            return null;
        }

        return match (true) {
            $purchase->canReopenCheckout() => $purchase->checkout_url,
            $purchase->isOpeningCheckout() => $this->openSession($purchase, $freeScansUsed),
            default => null,
        };
    }

    private function expireUnpaidCheckout(PlanPurchase $purchase): bool
    {
        if ($purchase->isOpeningCheckout()) {
            $this->openSession($purchase, null);
        }

        if ($purchase->stripe_checkout_session_id !== null && ! $this->checkout->expireIfOpen($purchase->stripe_checkout_session_id)) {
            $purchase->update(['checkout_returned_at' => $purchase->checkout_returned_at ?? now()]);

            return false;
        }

        $cancelled = PlanPurchase::query()->whereKey($purchase->id)->whereNull('paid_at')->whereNull('cancelled_at')->update(['cancelled_at' => now()]);
        throw_unless($cancelled === 1, RuntimeException::class, 'The checkout has changed. Please try again.');

        return true;
    }

    private function createPurchase(string $guestHash, string $placement, SubscriptionProduct $product): PlanPurchase
    {
        $limiter = 'guest-checkout:'.request()->ip();

        throw_if(RateLimiter::tooManyAttempts($limiter, 10), RuntimeException::class, 'Too many guest checkouts.');
        RateLimiter::hit($limiter);

        return PlanPurchase::query()->create([
            'subscription_product_id' => $product->id,
            'stripe_price_id' => $product->stripe_price_id,
            'snap_journey_id' => $this->funnel->checkoutMetadata(null)['snap_journey'] ?? null,
            'guest_hash' => $guestHash,
            'placement' => $placement,
        ]);
    }

    private function openSession(PlanPurchase $purchase, ?int $freeScansUsed): string
    {
        $product = $purchase->product;
        $type = Str::slug($product->name);
        $returnParameters = $purchase->placement === 'pricing' ? ['purchase' => $purchase->id] : [];

        try {
            $session = $this->checkout->open(
                $purchase->checkoutIdempotencyKey(),
                (string) $purchase->stripe_price_id,
                $purchase->id,
                route('snap-to-track.purchase', ['checkout' => 'success', ...$returnParameters]),
                route('snap-to-track.purchase', ['checkout' => 'cancelled', ...$returnParameters]),
                array_filter(['plan_purchase' => $purchase->id, 'product_name' => $product->name, 'snap_journey' => $purchase->snap_journey_id, 'name' => $type, 'type' => $type]),
                "We'll email your activation link to the address above. Please make sure it's correct.",
            );
        } catch (ApiErrorException $apiErrorException) {
            $session = $this->recover($purchase, $apiErrorException);
        }

        $purchase->update([
            'stripe_checkout_session_id' => $session->id,
            'checkout_url' => $session->url,
            'checkout_expires_at' => $session->expiresAt,
        ]);
        throw_if($purchase->refresh()->isCancelled(), RuntimeException::class, 'The checkout has changed. Please try again.');
        $this->session->put('snap_to_track.purchase', $purchase->id);
        $this->funnel->record(SnapFunnelStage::CheckoutStarted, $purchase->id, [
            'plan' => SnapFunnel::plan($product->name),
            'interval' => 'monthly',
            'placement' => $purchase->placement,
            'free_scans_used' => $freeScansUsed,
        ]);

        return $session->url;
    }

    private function recover(PlanPurchase $purchase, ApiErrorException $exception): GuestCheckoutSession
    {
        throw_unless($exception instanceof IdempotencyException || $exception->getHttpStatus() >= 500, $exception);

        $this->sameAttempt($purchase)->whereNull('checkout_attempt_failed_at')->update(['checkout_attempt_failed_at' => now()]);

        try {
            $session = $this->checkout->findOpenSession($purchase->id, $purchase->created_at);
        } catch (Throwable $throwable) {
            report($throwable);

            throw $exception;
        }

        if ($session instanceof GuestCheckoutSession) {
            return $session;
        }

        if ($purchase->failedCheckoutAttemptHasSettled()) {
            $this->sameAttempt($purchase)->update(['checkout_attempt' => $purchase->checkout_attempt + 1, 'checkout_attempt_failed_at' => null]);
        }

        throw $exception;
    }

    /**
     * @return Builder<PlanPurchase>
     */
    private function sameAttempt(PlanPurchase $purchase): Builder
    {
        return PlanPurchase::query()->whereKey($purchase->id)->where('checkout_attempt', $purchase->checkout_attempt)->whereNull('stripe_checkout_session_id');
    }
}
