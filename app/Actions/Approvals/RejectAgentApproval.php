<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Enums\AgentApprovalStatus;
use App\Models\AgentApproval;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class RejectAgentApproval
{
    public function handle(AgentApproval $approval, User $user): AgentApproval
    {
        DB::transaction(function () use ($approval, $user): void {
            $locked = AgentApproval::query()
                ->whereKey($approval->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            throw_unless($locked->user_id === $user->id, AuthorizationException::class);

            if (! $locked->status->canReject()) {
                return;
            }

            $locked->update([
                'status' => AgentApprovalStatus::Rejected,
                'resolved_at' => now(),
            ]);
        });

        return $approval->fresh() ?? $approval;
    }
}
