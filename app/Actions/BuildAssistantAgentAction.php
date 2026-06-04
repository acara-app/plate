<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\AgentRequest;
use App\Ai\Agents\AgentRunner;
use App\Contracts\Memory\DispatchesMemoryExtraction;
use App\Http\Requests\StreamChatRequest;
use App\Jobs\SummarizeConversationJob;
use App\Models\Conversation;
use App\Models\User;
use App\Utilities\ConfigHelper;
use Illuminate\Support\Facades\Context;
use Laravel\Ai\Responses\StreamableAgentResponse;

final readonly class BuildAssistantAgentAction
{
    public function __construct(
        private AgentRunner $agentRunner,
        private DispatchesMemoryExtraction $memoryExtraction,
    ) {}

    public function handle(StreamChatRequest $request, User $user, string $conversationId, string $channel = 'web'): StreamableAgentResponse
    {
        Context::add('chat.channel', $channel);
        Context::add('chat.conversation_id', $conversationId);

        $agentRequest = new AgentRequest(
            message: $request->userMessage(),
            images: $request->userAttachments(),
            modelName: $request->modelName(),
            conversationId: $conversationId,
        );

        $this->dispatchSummarizationIfNeeded($conversationId);
        $this->memoryExtraction->dispatchIfEligible($user->id);

        return $this->agentRunner->run($agentRequest, $user);
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
