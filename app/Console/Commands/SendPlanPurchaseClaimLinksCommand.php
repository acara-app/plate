<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendPlanPurchaseClaimLink;
use App\Models\PlanPurchase;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Description('Queue activation emails that paid guest purchases have not received yet, or report the purchases still waiting for one')]
#[Signature('plan-purchases:claim-links {--report : Report paid purchases still waiting for their activation email instead of queueing them}')]
final class SendPlanPurchaseClaimLinksCommand extends Command
{
    public function handle(): int
    {
        return $this->option('report') ? $this->reportWaiting() : $this->queueMissing();
    }

    private function queueMissing(): int
    {
        $purchases = PlanPurchase::query()->awaitingClaimLink()->whereNotNull('email')
            ->whereBetween('paid_at', [now()->subDays(3), now()->subMinutes(5)])
            ->get(['id']);

        foreach ($purchases as $purchase) {
            dispatch(new SendPlanPurchaseClaimLink($purchase->id));
        }

        $this->info(sprintf('Queued %d activation email(s).', $purchases->count()));

        return self::SUCCESS;
    }

    private function reportWaiting(): int
    {
        $references = PlanPurchase::query()->awaitingClaimLink()->where('paid_at', '<=', now()->subHour())->get()
            ->map(fn (PlanPurchase $purchase): string => $purchase->reference());

        report_if($references->isNotEmpty(), new RuntimeException('Paid plan purchases are still waiting for their activation email: '.$references->implode(', ')));

        $this->info(sprintf('%d paid purchase(s) still waiting for an activation email.', $references->count()));

        return self::SUCCESS;
    }
}
