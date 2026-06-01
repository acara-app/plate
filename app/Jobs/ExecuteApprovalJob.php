<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\ExecutesApprovedTool;
use App\Enums\AgentApprovalStatus;
use App\Events\AgentApprovalResolved;
use App\Models\AgentApproval;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\MaxExceptions;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Throwable;

#[MaxExceptions(3)]
#[Timeout(120)]
final class ExecuteApprovalJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $approvalId,
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping($this->approvalId),
        ];
    }

    public function uniqueId(): string
    {
        return $this->approvalId;
    }

    public function handle(): void
    {
        $approval = $this->claim();

        if (! $approval instanceof AgentApproval) {
            return;
        }

        $executorClass = config('plate.approvals.executors.'.$approval->tool_name);

        if (! is_string($executorClass)) {
            $approval->update([
                'status' => AgentApprovalStatus::Failed,
                'error' => sprintf('No executor registered for tool [%s].', $approval->tool_name),
            ]);

            event(new AgentApprovalResolved($approval->id));

            return;
        }

        try {
            /** @var ExecutesApprovedTool $executor */
            $executor = resolve($executorClass);

            $approval->update([
                'status' => AgentApprovalStatus::Executed,
                'result' => $executor->handle($approval),
                'executed_at' => now(),
            ]);
        } catch (Throwable $throwable) {
            report($throwable);

            $approval->update([
                'status' => AgentApprovalStatus::Failed,
                'error' => $throwable->getMessage(),
            ]);
        }

        event(new AgentApprovalResolved($approval->id));
    }

    public function failed(Throwable $exception): void
    {
        $transitioned = AgentApproval::query()
            ->whereKey($this->approvalId)
            ->whereIn('status', [
                AgentApprovalStatus::Approved->value,
                AgentApprovalStatus::Executing->value,
            ])
            ->update([
                'status' => AgentApprovalStatus::Failed->value,
                'error' => $exception->getMessage(),
            ]);

        if ($transitioned > 0) {
            event(new AgentApprovalResolved($this->approvalId));
        }
    }

    private function claim(): ?AgentApproval
    {
        return DB::transaction(function (): ?AgentApproval {
            $approval = AgentApproval::query()
                ->whereKey($this->approvalId)
                ->lockForUpdate()
                ->first();

            if (! $approval instanceof AgentApproval || $approval->status !== AgentApprovalStatus::Approved) {
                return null;
            }

            $approval->update(['status' => AgentApprovalStatus::Executing]);

            return $approval;
        });
    }
}
