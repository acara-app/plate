<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\RecordedApprovalDecisions;
use App\Models\Conversation;
use App\Models\History;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Exceptions\ApprovalMismatchException;

final readonly class RecordApprovalDecisions
{
    /**
     * @param  array<string, array{action: string, result?: string|null}>  $decisions  keyed by tool call ID
     *
     * @throws ApprovalMismatchException when the decisions do not match the conversation's paused turn
     */
    public function handle(Conversation $conversation, array $decisions): RecordedApprovalDecisions
    {
        return DB::transaction(function () use ($conversation, $decisions): RecordedApprovalDecisions {
            $paused = $this->lockPausedTurn($conversation);

            $pending = $paused->pendingApprovals();
            $unknown = array_diff(array_keys($decisions), array_keys($pending));

            if ($unknown !== []) {
                throw new ApprovalMismatchException(
                    'The approval decisions do not match a paused conversation turn.',
                    $this->pendingApprovalsOn($paused),
                );
            }

            $recorded = [...$paused->recordedApprovalDecisions(), ...$decisions];

            $paused->forceFill(['meta' => $this->withRecordedDecisions($paused, $recorded)])->save();

            return new RecordedApprovalDecisions(
                decisions: $recorded,
                awaiting: array_values(array_diff(array_keys($pending), array_keys($recorded))),
            );
        });
    }

    public function pausedTurnFor(Conversation $conversation): ?History
    {
        return $conversation->pausedApprovalTurn();
    }

    private function lockPausedTurn(Conversation $conversation): History
    {
        $paused = $conversation->messages()
            ->whereNotNull('approval_state')
            ->reorder('id', 'desc')
            ->lockForUpdate()
            ->get()
            ->first(fn (History $message): bool => $message->hasPendingApprovals());

        if (! $paused instanceof History) {
            throw new ApprovalMismatchException('This conversation has no tool call awaiting approval.', new Collection);
        }

        return $paused;
    }

    /**
     * @param  array<string, array{action: string, result?: string|null}>  $recorded
     * @return array<string, mixed>
     */
    private function withRecordedDecisions(History $paused, array $recorded): array
    {
        $meta = $paused->meta ?? [];

        $meta[History::STREAM_META_KEY] = [
            ...$paused->chatStreamMeta(),
            'approval_decisions' => $recorded,
        ];

        return $meta;
    }

    /**
     * @return Collection<int, PendingApproval>
     */
    private function pendingApprovalsOn(History $paused): Collection
    {
        $tools = [];
        $arguments = [];

        foreach ($paused->tool_calls ?? [] as $toolCall) {
            $tools[$toolCall['id']] = $toolCall['name'];
            $arguments[$toolCall['id']] = $toolCall['arguments'] ?? [];
        }

        return (new Collection($paused->pendingApprovals()))
            ->map(fn (?string $reason, string $toolCallId): PendingApproval => new PendingApproval(
                id: $toolCallId,
                tool: $tools[$toolCallId] ?? '',
                arguments: $arguments[$toolCallId] ?? [],
                reason: $reason,
            ))
            ->values();
    }
}
