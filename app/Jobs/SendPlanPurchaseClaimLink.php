<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\PlanPurchase;
use App\Notifications\PlanPurchaseClaimLinkNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Notification;

#[Tries(3)]
final class SendPlanPurchaseClaimLink implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $purchaseId) {}

    public function handle(): void
    {
        $purchase = PlanPurchase::query()->find($this->purchaseId);

        if (! $purchase instanceof PlanPurchase || $purchase->email === null || $purchase->isClaimed() || $purchase->isCancelled()) {
            return;
        }

        $link = route('snap-to-track.activate.open', ['token' => $purchase->issueClaimToken()]);

        Notification::route('mail', $purchase->email)->notifyNow(new PlanPurchaseClaimLinkNotification($link));
    }
}
