<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Billing\EnforceAiUsageLimit;
use App\Data\ApprovalDecisionResult;
use App\Enums\ModelName;
use App\Jobs\ProcessChatStream;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use App\Services\StreamEventStore;
use Laravel\Ai\Exceptions\ApprovalMismatchException;

final readonly class ResumeChatStream
{
    public function __construct(
        private EnforceAiUsageLimit $enforceAiUsageLimit,
        private StreamEventStore $events,
        private CreatePendingChatStreamTurn $pendingTurn,
        private RecordApprovalDecisions $recordApprovalDecisions,
    ) {}

    /**
     * @param  array<string, array{action: string, result?: string|null}>  $decisions  keyed by tool call ID
     *
     * @throws ApprovalMismatchException when the decisions do not match the conversation's paused turn
     */
    public function handle(Conversation $conversation, User $user, array $decisions, string $channel = 'web', ?string $locale = null): ApprovalDecisionResult
    {
        $modelName = $this->modelFor($this->recordApprovalDecisions->pausedTurnFor($conversation));
        $this->enforceAiUsageLimit->handle($user, $modelName);

        $recorded = $this->recordApprovalDecisions->handle($conversation, $decisions);

        if (! $recorded->complete()) {
            return new ApprovalDecisionResult(turn: null, awaiting: $recorded->awaiting);
        }

        $this->events->clear($conversation->id);

        $turn = $this->pendingTurn->forResume($conversation, $user, $channel, $modelName->value);

        dispatch(new ProcessChatStream(
            userId: $user->id,
            conversationId: $conversation->id,
            modelName: $modelName->value,
            channel: $channel,
            streamId: $turn->streamId,
            userMessageId: null,
            assistantMessageId: $turn->assistantMessageId,
            locale: $locale,
            decisions: $recorded->toQueuePayload(),
        ));

        return new ApprovalDecisionResult(turn: $turn, awaiting: []);
    }

    private function modelFor(?History $paused): ModelName
    {
        $model = $paused?->chatStreamMeta()['model'] ?? null;

        return (is_string($model) ? ModelName::tryFrom($model) : null) ?? ModelName::default();
    }
}
