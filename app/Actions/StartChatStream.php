<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Billing\EnforceAiUsageLimit;
use App\Contracts\Memory\DispatchesMemoryExtraction;
use App\Data\ChatStreamTurn;
use App\Http\Requests\StreamChatRequest;
use App\Jobs\GenerateConversationTitleJob;
use App\Jobs\ProcessChatStream;
use App\Jobs\SummarizeConversationJob;
use App\Models\Conversation;
use App\Models\User;
use App\Services\StreamEventStore;
use App\Utilities\ConfigHelper;
use Laravel\Ai\Files\Base64Image;

final readonly class StartChatStream
{
    public function __construct(
        private EnforceAiUsageLimit $enforceAiUsageLimit,
        private DispatchesMemoryExtraction $memoryExtraction,
        private StreamEventStore $events,
        private CreatePendingChatStreamTurn $pendingTurn,
    ) {}

    public function handle(StreamChatRequest $request, User $user, Conversation $conversation, string $channel = 'web'): ChatStreamTurn
    {
        $modelName = $request->modelName();
        $this->enforceAiUsageLimit->handle($user, $modelName);

        $messageCount = $conversation->messages()->count();

        $this->dispatchSummarizationIfNeeded($conversation, $messageCount);
        $this->memoryExtraction->dispatchIfEligible($user->id);
        $this->events->clear($conversation->id);

        $attachments = $this->serializeAttachments($request->userAttachments());
        $turn = $this->pendingTurn->handle($conversation, $user, $request->userMessage(), $attachments, $channel);

        if ($messageCount === 0) {
            dispatch(new GenerateConversationTitleJob($conversation->withoutRelations()));
        }

        dispatch(new ProcessChatStream(
            userId: $user->id,
            conversationId: $conversation->id,
            modelName: $modelName->value,
            channel: $channel,
            streamId: $turn->streamId,
            userMessageId: $turn->userMessageId,
            assistantMessageId: $turn->assistantMessageId,
        ));

        return $turn;
    }

    /**
     * @param  list<Base64Image>  $images
     * @return list<array{type: string, name: ?string, base64: string, mime: ?string}>
     */
    private function serializeAttachments(array $images): array
    {
        return array_map(
            fn (Base64Image $image): array => [
                'type' => 'base64-image',
                'name' => $image->name(),
                'base64' => $image->base64,
                'mime' => $image->mime,
            ],
            $images,
        );
    }

    private function dispatchSummarizationIfNeeded(Conversation $conversation, int $messageCount): void
    {
        if ($conversation->summarization_dispatched_at?->isAfter(now()->subMinutes(5))) {
            return;
        }

        $buffer = ConfigHelper::int('altani.summarization.buffer', 25);
        $threshold = ConfigHelper::int('altani.summarization.threshold', 20);

        if ($messageCount < ($buffer + $threshold)) {
            return;
        }

        $conversation->update(['summarization_dispatched_at' => now()]);

        dispatch(new SummarizeConversationJob($conversation->withoutRelations()));
    }
}
