<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AgentApprovalStatus;
use App\Models\AgentApproval;
use Illuminate\Console\Command;

final class ExpireStaleAgentApprovalsCommand extends Command
{
    protected $signature = 'approvals:expire-stale';

    protected $description = 'Expire pending agent approvals that have passed their TTL.';

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
