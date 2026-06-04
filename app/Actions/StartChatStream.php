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
use App\Utilities\ConfigHelper;
use Illuminate\Http\JsonResponse;

final readonly class StartChatStream
{
    public function __construct(
        private EnforceAiUsageLimit $enforceAiUsageLimit,
        private DispatchesMemoryExtraction $memoryExtraction,
    ) {}

    public function handle(StreamChatRequest $request, User $user, Conversation $conversation, string $channel = 'web'): JsonResponse
    {
        $modelName = $request->modelName();
        $this->enforceAiUsageLimit->handle($user, $modelName);

        $this->dispatchSummarizationIfNeeded($conversation);
        $this->memoryExtraction->dispatchIfEligible($user->id);

        dispatch(new ProcessChatStream(userId: $user->id, conversationId: $conversation->id, content: $request->userMessage(), images: PersistPartialChatStream::serializeAttachments($request->userAttachments()), modelName: $modelName->value, channel: $channel));

        return response()->json([
            'status' => 'processing',
            'channel' => 'chat.'.$user->id,
            'conversationId' => $conversation->id,
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
