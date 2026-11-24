<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Contracts\Billing\ManagesGuestCheckout;
use App\Enums\PhotoPlan;
use App\Enums\SnapFunnelStage;
use App\Models\PlanPurchase;
use App\Models\SubscriptionProduct;
use App\Services\Billing\PhotoPlanCatalog;
use App\Services\Billing\SnapFunnel;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class StartGuestCheckout
{
    public function __construct(
        private PhotoPlanCatalog $catalog,
        private ManagesGuestCheckout $checkout,
        private SnapFunnel $funnel,
        private Session $session,
    ) {}

    public function handle(string $guestId, string $placement): string
    {
        $guestHash = hash('sha256', $guestId);
        $lock = Cache::lock('guest-checkout:'.$guestHash, 15);
        $lock->block(10);

        try {
            return $this->resume($guestHash) ?? $this->open($guestHash, $placement);
        } finally {
            $lock->release();
        }
    }

    private function resume(string $guestHash): ?string
    {
        $latest = PlanPurchase::query()->where('guest_hash', $guestHash)->whereNull('cancelled_at')->latest('id')->first();

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

    private function open(string $guestHash, string $placement): string
    {
        $product = $this->catalog->product(PhotoPlan::Starter->productName());
        $limiter = 'guest-checkout:'.request()->ip();

        throw_unless($product instanceof SubscriptionProduct && filled($product->stripe_price_id), RuntimeException::class, 'Starter is not on sale.');
        throw_if(RateLimiter::tooManyAttempts($limiter, 10), RuntimeException::class, 'Too many guest checkouts.');
        RateLimiter::hit($limiter);

        $journeyId = $this->funnel->checkoutMetadata(null)['snap_journey'] ?? null;
        $purchase = PlanPurchase::query()->create([
            'subscription_product_id' => $product->id,
            'snap_journey_id' => $journeyId,
            'guest_hash' => $guestHash,
            'placement' => $placement,
        ]);
        $type = Str::slug($product->name);

        $session = $this->checkout->open(
            (string) $product->stripe_price_id,
            $purchase->id,
            route('snap-to-track.purchase', ['checkout' => 'success']),
            route('snap-to-track.purchase', ['checkout' => 'cancelled']),
            array_filter(['plan_purchase' => $purchase->id, 'product_name' => $product->name, 'snap_journey' => $journeyId, 'name' => $type, 'type' => $type]),
        );

        $purchase->update([
            'stripe_checkout_session_id' => $session->id,
            'checkout_url' => $session->url,
            'checkout_expires_at' => $session->expiresAt,
        ]);
        $this->session->put('snap_to_track.purchase', $purchase->id);
        $this->funnel->record(SnapFunnelStage::CheckoutStarted, $purchase->id, [
            'plan' => SnapFunnel::plan($product->name),
            'interval' => 'monthly',
            'placement' => $placement,
        ]);

        return $session->url;
    }
}
