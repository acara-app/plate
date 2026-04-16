<?php

declare(strict_types=1);

namespace App\Actions;

use Acara\PlateCore\Contracts\Memory\DispatchesMemoryExtraction;
use App\Ai\AgentPayload;
use App\Ai\Agents\AgentRunner;
use App\Http\Requests\StreamChatRequest;
use App\Jobs\SummarizeConversationJob;
use App\Models\Conversation;
use App\Models\User;
use App\Utilities\ConfigHelper;
use Laravel\Ai\Responses\StreamableAgentResponse;

final readonly class BuildAssistantAgentAction
{
    public function __construct(
        private AgentRunner $agentRunner,
        private DispatchesMemoryExtraction $memoryExtraction,
    ) {}

    public function handle(StreamChatRequest $request, User $user, string $conversationId): StreamableAgentResponse
    {
        $agentPayload = new AgentPayload(
            userId: $user->id,
            message: $request->userMessage(),
            images: $request->userAttachments(),
            mode: $request->mode(),
            modelName: $request->modelName(),
            conversationId: $conversationId,
        );

        $this->dispatchSummarizationIfNeeded($conversationId);
        $this->memoryExtraction->dispatchIfEligible($user->id);

        return $this->agentRunner->runWithConversation($agentPayload, $user, $conversationId);
    }

    private function dispatchSummarizationIfNeeded(string $conversationId): void
    {
        $conversation = Conversation::query()->find($conversationId);

        if (! $conversation instanceof Conversation) {
            return;
        }

        if ($conversation->summarization_dispatched_at?->isAfter(now()->subMinutes(5))) {
            return;
        }

        $buffer = ConfigHelper::int('altani.summarization.buffer', 25);
        $threshold = ConfigHelper::int('altani.summarization.threshold', 20);

        if ($conversation->messages()->count() < ($buffer + $threshold)) {
            return;
        }

        $conversation->update(['summarization_dispatched_at' => now()]);

        dispatch(new SummarizeConversationJob($conversation));
    }
}
