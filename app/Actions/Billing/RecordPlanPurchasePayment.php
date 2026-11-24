<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Jobs\SendPlanPurchaseClaimLink;
use App\Models\PlanPurchase;
use Illuminate\Support\Str;

final readonly class RecordPlanPurchasePayment
{
    /** @param array<array-key, mixed> $invoice */
    public function handle(array $invoice): void
    {
        $metadata = data_get($invoice, 'parent.subscription_details.metadata', data_get($invoice, 'subscription_details.metadata'));
        $purchaseId = is_array($metadata) ? ($metadata['plan_purchase'] ?? null) : null;
        $subscription = data_get($invoice, 'parent.subscription_details.subscription', data_get($invoice, 'subscription'));
        $customer = data_get($invoice, 'customer');
        $email = data_get($invoice, 'customer_email');
        $amount = data_get($invoice, 'amount_paid');

        if (! is_string($purchaseId) || ! is_string($subscription) || ! is_string($customer) || ! is_int($amount) || $amount <= 0
            || data_get($invoice, 'status') !== 'paid' || data_get($invoice, 'billing_reason') !== 'subscription_create') {
            return;
        }

        $recorded = PlanPurchase::query()->whereKey($purchaseId)->whereNull('paid_at')->update([
            'stripe_customer_id' => $customer,
            'stripe_subscription_id' => $subscription,
            'email' => is_string($email) ? Str::lower(mb_trim($email)) : null,
            'paid_at' => now(),
        ]);

        if ($recorded === 1) {
            dispatch(new SendPlanPurchaseClaimLink($purchaseId))->afterCommit();
        }
    }

    public function cancel(string $subscriptionId): void
    {
        PlanPurchase::query()->where('stripe_subscription_id', $subscriptionId)
            ->whereNull('claimed_at')->whereNull('cancelled_at')
            ->update(['cancelled_at' => now()]);
    }
}
