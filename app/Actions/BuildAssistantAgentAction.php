<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\AgentPayload;
use App\Ai\Agents\AgentRunner;
use App\Http\Requests\StreamChatRequest;
use App\Jobs\Memory\ExtractUserMemoriesJob;
use App\Jobs\SummarizeConversationJob;
use App\Models\Conversation;
use App\Models\MemoryExtractionCheckpoint;
use App\Models\User;
use App\Services\Memory\MemoryExtractor;
use App\Utilities\ConfigHelper;
use Laravel\Ai\Responses\StreamableAgentResponse;

final readonly class BuildAssistantAgentAction
{
    public function __construct(
        private AgentRunner $agentRunner,
        private MemoryExtractor $memoryExtractor,
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
        $this->dispatchMemoryExtractionIfNeeded($user->id);

        return $this->agentRunner->runWithConversation($agentPayload, $user, $conversationId);
    }

    private function dispatchMemoryExtractionIfNeeded(int $userId): void
    {
        $checkpoint = MemoryExtractionCheckpoint::query()->where('user_id', $userId)->first();
        $cooldownMinutes = ConfigHelper::int('memory.extraction.cooldown_minutes', 5);

        if ($checkpoint?->last_extracted_at?->isAfter(now()->subMinutes($cooldownMinutes))) {
            return;
        }

        if (! $this->memoryExtractor->shouldExtract($userId)) {
            return;
        }

        MemoryExtractionCheckpoint::query()->updateOrCreate(
            ['user_id' => $userId],
            ['last_extracted_at' => now()],
        );

        dispatch(new ExtractUserMemoriesJob($userId));
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
