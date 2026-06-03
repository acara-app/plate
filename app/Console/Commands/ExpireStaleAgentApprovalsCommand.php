<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AgentApprovalStatus;
use App\Models\AgentApproval;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Expire pending agent approvals that have passed their TTL.')]
#[Signature('approvals:expire-stale')]
final class ExpireStaleAgentApprovalsCommand extends Command
{
    public function handle(): int
    {
        $expired = AgentApproval::query()
            ->stale()
            ->update([
                'status' => AgentApprovalStatus::Expired->value,
                'resolved_at' => now(),
            ]);

        $this->info(sprintf('Expired %d stale approval(s).', $expired));

        return self::SUCCESS;
    }
}
