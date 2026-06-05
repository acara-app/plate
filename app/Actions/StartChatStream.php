<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Billing\EnforceAiUsageLimit;
use App\Contracts\Memory\DispatchesMemoryExtraction;
use App\Http\Requests\StreamChatRequest;
use App\Jobs\ProcessChatStream;
use App\Jobs\SummarizeConversationJob;
use App\Models\Conversation;
use App\Models\User;
use App\Services\StreamEventStore;
use App\Utilities\ConfigHelper;
use Illuminate\Http\JsonResponse;

final readonly class StartChatStream
{
    public function __construct(
        private EnforceAiUsageLimit $enforceAiUsageLimit,
        private DispatchesMemoryExtraction $memoryExtraction,
        private StreamEventStore $events,
        private CreatePendingChatStreamTurn $pendingTurn,
    ) {}

    public function handle(StreamChatRequest $request, User $user, Conversation $conversation, string $channel = 'web'): JsonResponse
    {
        $modelName = $request->modelName();
        $this->enforceAiUsageLimit->handle($user, $modelName);

        $this->dispatchSummarizationIfNeeded($conversation);
        $this->memoryExtraction->dispatchIfEligible($user->id);
        $this->events->clear($conversation->id);

        $attachments = PersistPartialChatStream::serializeAttachments($request->userAttachments());
        $turn = $this->pendingTurn->handle($conversation, $user, $request->userMessage(), $attachments, $channel);

        dispatch(new ProcessChatStream(
            userId: $user->id,
            conversationId: $conversation->id,
            content: $request->userMessage(),
            images: $attachments,
            modelName: $modelName->value,
            channel: $channel,
            streamId: $turn->streamId,
            userMessageId: $turn->userMessageId,
            assistantMessageId: $turn->assistantMessageId,
        ));

        return response()->json([
            'status' => 'processing',
            'channel' => 'chat.'.$user->id,
            'conversationId' => $conversation->id,
            'userMessageId' => $turn->userMessageId,
            'assistantMessageId' => $turn->assistantMessageId,
        ], 202);
    }

    private function dispatchSummarizationIfNeeded(Conversation $conversation): void
    {
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
