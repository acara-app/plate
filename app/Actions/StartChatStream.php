<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Billing\EnforceAiUsageLimit;
use App\Contracts\Memory\DispatchesMemoryExtraction;
use App\Http\Requests\StreamChatRequest;
use App\Jobs\GenerateConversationTitleJob;
use App\Jobs\ProcessChatStream;
use App\Jobs\SummarizeConversationJob;
use App\Models\Conversation;
use App\Models\User;
use App\Services\StreamEventStore;
use App\Utilities\ConfigHelper;
use Illuminate\Http\JsonResponse;
use Laravel\Ai\Files\Base64Image;

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

        $isFirstTurn = $conversation->messages()->doesntExist();

        $attachments = $this->serializeAttachments($request->userAttachments());
        $turn = $this->pendingTurn->handle($conversation, $user, $request->userMessage(), $attachments, $channel);

        $this->dispatchTitleGenerationIfNeeded($conversation, $isFirstTurn);

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

    /**
     * @param  list<Base64Image>  $images
     * @return list<array{type: string, name: ?string, base64: string, mime: ?string}>
     */
    private function serializeAttachments(array $images): array
    {
        return array_values(array_map(
            fn (Base64Image $image): array => $image->toArray(),
            $images,
        ));
    }

    private function dispatchTitleGenerationIfNeeded(Conversation $conversation, bool $isFirstTurn): void
    {
        if (! $isFirstTurn) {
            return;
        }

        dispatch(new GenerateConversationTitleJob($conversation));
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
