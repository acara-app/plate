<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Contracts\Billing\ManagesGuestCheckout;
use App\Enums\PlanPurchaseClaim;
use App\Models\PlanPurchase;
use App\Models\User;
use App\Services\Billing\SnapFunnel;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Subscription;
use LogicException;
use Stripe\Subscription as StripeSubscription;

final readonly class ClaimPlanPurchase
{
    public function __construct(
        private ManagesGuestCheckout $checkout,
        private SyncStripeSubscription $syncSubscription,
        private SnapFunnel $funnel,
    ) {}

    public function handle(PlanPurchase $purchase, User $user, string $method): PlanPurchaseClaim
    {
        if ($purchase->isClaimed()) {
            return $purchase->user_id === $user->id ? PlanPurchaseClaim::Claimed : PlanPurchaseClaim::ClaimedElsewhere;
        }

        if ($purchase->isCancelled()) {
            return PlanPurchaseClaim::Cancelled;
        }

        $user->refresh();
        $customerId = $purchase->stripe_customer_id;
        $subscriptionId = $purchase->stripe_subscription_id;

        throw_unless($purchase->isPaid() && $customerId !== null && $subscriptionId !== null, LogicException::class, 'Only paid purchases can be claimed.');

        $conflict = $this->conflict($user, $customerId, $subscriptionId);

        if ($conflict instanceof PlanPurchaseClaim) {
            $purchase->update(['claim_conflict' => $conflict->value]);

            return $conflict;
        }

        $stripeSubscription = $this->checkout->subscription($subscriptionId);

        if (in_array($stripeSubscription->status, [StripeSubscription::STATUS_CANCELED, StripeSubscription::STATUS_INCOMPLETE_EXPIRED], true)) {
            $purchase->update(['cancelled_at' => now()]);

            return PlanPurchaseClaim::Cancelled;
        }

        $outcome = DB::transaction(function () use ($purchase, $user, $method, $customerId, $subscriptionId, $stripeSubscription): ?PlanPurchaseClaim {
            $account = User::query()->lockForUpdate()->findOrFail($user->id);
            $conflict = $this->conflict($account, $customerId, $subscriptionId);

            if ($conflict instanceof PlanPurchaseClaim) {
                return $conflict;
            }

            $updated = PlanPurchase::query()->whereKey($purchase->id)->whereNull('claimed_at')->update([
                'user_id' => $account->id,
                'claimed_at' => now(),
                'claim_method' => $method,
                'claim_conflict' => null,
            ]);

            if ($updated === 0) {
                return null;
            }

            $account->forceFill(['stripe_id' => $customerId])->save();
            $this->syncSubscription->handle($account, $stripeSubscription);

            return PlanPurchaseClaim::Claimed;
        });

        if ($outcome === null) {
            return $purchase->refresh()->user_id === $user->id ? PlanPurchaseClaim::Claimed : PlanPurchaseClaim::ClaimedElsewhere;
        }

        if ($outcome->needsSupport()) {
            $purchase->update(['claim_conflict' => $outcome->value]);

            return $outcome;
        }

        $user->refresh();
        $this->funnel->recordClaim($purchase, $user, $method);

        return PlanPurchaseClaim::Claimed;
    }

    private function conflict(User $user, string $customerId, string $subscriptionId): ?PlanPurchaseClaim
    {
        $hasOtherActivePlan = Subscription::query()->whereBelongsTo($user, 'owner')->where('stripe_id', '!=', $subscriptionId)->get()
            ->contains(fn (Subscription $subscription): bool => $subscription->valid());

        if ($hasOtherActivePlan) {
            return PlanPurchaseClaim::ActivePlan;
        }

        return $user->stripe_id !== null && $user->stripe_id !== $customerId ? PlanPurchaseClaim::OtherCustomer : null;
    }
}
