<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Billing\EnforceAiUsageLimit;
use App\Ai\AgentPayload;
use App\Ai\Agents\AgentRunner;
use App\Contracts\Memory\DispatchesMemoryExtraction;
use App\Enums\AgentStreamStatus;
use App\Http\Requests\StreamChatRequest;
use App\Jobs\StreamAgentRunJob;
use App\Jobs\SummarizeConversationJob;
use App\Models\AgentStreamRun;
use App\Models\Conversation;
use App\Models\User;
use App\Utilities\ConfigHelper;
use Illuminate\Support\Facades\Context;

final readonly class StartAgentStreamRunAction
{
    public function __construct(
        private EnforceAiUsageLimit $enforceAiUsageLimit,
        private DispatchesMemoryExtraction $memoryExtraction,
    ) {}

    public function handle(StreamChatRequest $request, User $user, string $conversationId, string $channel = 'web'): AgentStreamRun
    {
        Context::add('chat.channel', $channel);
        Context::add('chat.conversation_id', $conversationId);

        $modelName = $request->modelName();

        $this->enforceAiUsageLimit->handle($user, $modelName);

        $payload = new AgentPayload(
            userId: $user->id,
            message: $request->userMessage(),
            images: $request->userAttachments(),
            modelName: $modelName,
            conversationId: $conversationId,
        );

        $this->dispatchSummarizationIfNeeded($conversationId);
        $this->memoryExtraction->dispatchIfEligible($user->id);

        $run = AgentStreamRun::query()->create([
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'agent' => AgentRunner::class,
            'channel' => $channel,
            'model' => $modelName->value,
            'prompt' => $payload->message,
            'status' => AgentStreamStatus::Queued,
            'expires_at' => now()->addMinutes(ConfigHelper::int('altani.stream.run_ttl_minutes', 30)),
        ]);

        dispatch(new StreamAgentRunJob($run->id, $payload))->afterCommit();

        return $run;
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
