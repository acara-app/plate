<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Contracts\Billing\ManagesGuestCheckout;
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
    ) {}

    public function handle(string $guestId, string $placement, ?int $freeScansUsed = null): string
    {
        $guestHash = hash('sha256', $guestId);
        $lock = Cache::lock('guest-checkout:'.$guestHash, 15);
        $lock->block(10);

        try {
            $latest = PlanPurchase::query()->where('guest_hash', $guestHash)->whereNull('cancelled_at')->latest('id')->first();

            return $this->resume($latest) ?? $this->openSession($latest?->isOpeningCheckout() === true ? $latest : $this->createPurchase($guestHash, $placement), $freeScansUsed);
        } finally {
            $lock->release();
        }
    }

    private function resume(?PlanPurchase $latest): ?string
    {
        if (! $latest instanceof PlanPurchase) {
            return null;
        }

        $this->session->put('snap_to_track.purchase', $latest->id);

        return match (true) {
            $latest->isPaid(), $latest->isAwaitingPayment() => route('snap-to-track.purchase'),
            $latest->canReopenCheckout() => $latest->checkout_url,
            default => null,
        };
    }

    private function createPurchase(string $guestHash, string $placement): PlanPurchase
    {
        $product = $this->catalog->product(PhotoPlan::Starter->productName());
        $limiter = 'guest-checkout:'.request()->ip();

        throw_unless($product instanceof SubscriptionProduct && filled($product->stripe_price_id), RuntimeException::class, 'Starter is not on sale.');
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

        try {
            $session = $this->checkout->open(
                $purchase->checkoutIdempotencyKey(),
                (string) $purchase->stripe_price_id,
                $purchase->id,
                route('snap-to-track.purchase', ['checkout' => 'success']),
                route('snap-to-track.purchase', ['checkout' => 'cancelled']),
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
