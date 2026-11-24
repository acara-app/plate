<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Models\User;
use Illuminate\Support\Facades\Date;
use Laravel\Cashier\Subscription;
use Stripe\Subscription as StripeSubscription;

final readonly class SyncStripeSubscription
{
    public function handle(User $user, StripeSubscription $stripeSubscription): void
    {
        $metadata = $stripeSubscription->metadata->toArray();
        $type = $metadata['type'] ?? $metadata['name'] ?? null;
        $items = $stripeSubscription->items->data;
        $singleItem = count($items) === 1 ? $items[0] : null;
        $endsAt = $stripeSubscription->cancel_at ?? $stripeSubscription->canceled_at;

        $subscription = Subscription::query()->updateOrCreate(['stripe_id' => $stripeSubscription->id], [
            'user_id' => $user->id,
            'type' => is_string($type) ? $type : 'default',
            'stripe_status' => $stripeSubscription->status,
            'stripe_price' => $singleItem?->price->id,
            'quantity' => $singleItem?->quantity,
            'trial_ends_at' => $stripeSubscription->trial_end === null ? null : Date::createFromTimestamp($stripeSubscription->trial_end),
            'ends_at' => $endsAt === null ? null : Date::createFromTimestamp($endsAt),
        ]);

        foreach ($items as $item) {
            $subscription->items()->updateOrCreate(['stripe_id' => $item->id], [
                'stripe_product' => is_string($item->price->product) ? $item->price->product : $item->price->product->id,
                'stripe_price' => $item->price->id,
                'quantity' => $item->quantity,
            ]);
        }
    }
}
