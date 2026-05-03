<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\TierEntitlement;
use App\Enums\SubscriptionTier;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Laravel\Cashier\Subscription;

final readonly class SubscriptionTierResolver implements ResolvesUserTier
{
    public function resolve(User $user): TierEntitlement
    {
        if (! enable_premium_upgrades_for($user)) {
            return TierEntitlement::unrestricted();
        }

        $subscription = $this->findRelevantSubscription($user);

        if (! $subscription instanceof Subscription) {
            return TierEntitlement::free();
        }

        if ($subscription->incomplete()) {
            return TierEntitlement::paymentPending();
        }

        $tier = $this->matchTier($subscription);

        if (! $tier instanceof SubscriptionTier || $tier === SubscriptionTier::Free) {
            return TierEntitlement::free();
        }

        return new TierEntitlement(
            tier: $tier,
            gracePeriodEndsAt: $this->resolveGracePeriodEndsAt($subscription),
        );
    }

    private function findRelevantSubscription(User $user): ?Subscription
    {
        /** @var Collection<int, Subscription> $subscriptions */
        $subscriptions = $user->subscriptions()->get(); // @phpstan-ignore-line

        $valid = $subscriptions->first(fn (Subscription $subscription): bool => $subscription->valid());

        if ($valid instanceof Subscription) {
            return $valid;
        }

        return $subscriptions->first(fn (Subscription $subscription): bool => $subscription->incomplete());
    }

    private function matchTier(Subscription $subscription): ?SubscriptionTier
    {
        $stripePrice = $subscription->stripe_price ?? $subscription->items()->value('stripe_price');

        if ($stripePrice === null || $stripePrice === '') {
            return null; // @codeCoverageIgnore
        }

        $product = SubscriptionProduct::query()
            ->where('stripe_price_id', $stripePrice)
            ->orWhere('yearly_stripe_price_id', $stripePrice)
            ->first();

        if (! $product instanceof SubscriptionProduct) {
            return null;
        }

        return SubscriptionTier::fromProductName($product->name);
    }

    private function resolveGracePeriodEndsAt(Subscription $subscription): ?CarbonInterface
    {
        if (! $subscription->onGracePeriod()) {
            return null;
        }

        $endsAt = $subscription->ends_at;

        return $endsAt instanceof CarbonInterface ? $endsAt : null;
    }
}
