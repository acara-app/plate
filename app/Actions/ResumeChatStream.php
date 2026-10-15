<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Billing\EnforceAiUsageLimit;
use App\Ai\Agents\AgentRunner;
use App\Data\Ai\AgentModelData;
use App\Data\ApprovalDecisionResult;
use App\Jobs\ProcessChatStream;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Ai\ConfiguredAgentModels;
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
        $paused = $this->recordApprovalDecisions->pausedTurnFor($conversation);
        $agentModels = resolve(ConfiguredAgentModels::class)->forTurn($paused, $user);
        $modelName = AgentModelData::from($agentModels[AgentRunner::class]);
        $this->enforceAiUsageLimit->handle($user, $modelName);

        $recorded = $this->recordApprovalDecisions->handle($conversation, $decisions);

        if (! $recorded->complete()) {
            return new ApprovalDecisionResult(turn: null, awaiting: $recorded->awaiting);
        }

        $this->events->clear($conversation->id);

        $turn = $this->pendingTurn->forResume($conversation, $user, $channel, $modelName->model, $agentModels);

        dispatch(new ProcessChatStream(
            userId: $user->id,
            conversationId: $conversation->id,
            modelName: $modelName->model,
            channel: $channel,
            streamId: $turn->streamId,
            userMessageId: null,
            assistantMessageId: $turn->assistantMessageId,
            locale: $locale,
            decisions: $recorded->toQueuePayload(),
            agentModels: $agentModels,
        ));

        return new ApprovalDecisionResult(turn: $turn, awaiting: []);
    }
}
