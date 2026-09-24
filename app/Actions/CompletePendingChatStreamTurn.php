<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\ChatStreamResult;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use App\Services\Ai\PlateConversationStore;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Enums\MessageStatus;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Responses\Data\Step;

/**
 * @codeCoverageIgnore
 *
 * @phpstan-import-type TStoredStep from PlateConversationStore
 */
final readonly class CompletePendingChatStreamTurn
{
    public function __construct(
        private PlateConversationStore $conversationStore,
    ) {}

    /**
     * @param  Collection<int, Step>  $steps
     */
    public function handle(
        string $conversationId,
        User $user,
        ?string $userMessageId,
        string $assistantMessageId,
        ChatStreamResult $result,
        string $status,
        Collection $steps = new Collection,
        ?string $provider = null,
    ): void {
        if ($status !== History::STREAM_STATUS_COMPLETED) {
            $result = $result->withoutPendingApprovals();
        }

        DB::transaction(function () use ($conversationId, $user, $userMessageId, $assistantMessageId, $result, $status, $steps, $provider): void {
            $now = now();

            $conversation = Conversation::query()
                ->whereKey($conversationId)
                ->forUser($user)
                ->lockForUpdate()
                ->firstOrFail();

            $userMessage = $userMessageId === null
                ? null
                : $this->lockMessage($userMessageId, $conversationId, $user, MessageRole::User);

            $assistantMessage = $this->lockMessage($assistantMessageId, $conversationId, $user, MessageRole::Assistant);

            if ($userMessage instanceof History) {
                $this->markUserMessage($userMessage, $status, $now);
            }

            if (! $result->hasAssistantContent() && $status !== History::STREAM_STATUS_COMPLETED) {
                $assistantMessage->delete();
                $conversation->forceFill(['updated_at' => $now])->save();

                return;
            }

            $assistantMessage->forceFill([
                'content' => $result->text,
                'steps' => $this->steps($result, $steps),
                'usage' => $result->usage,
                'meta' => $this->assistantMeta($assistantMessage, $result, $status, $provider),
                'status' => $this->messageStatus($result, $status),
                'updated_at' => $now,
            ])->save();

            $conversation->forceFill(['updated_at' => $now])->save();
        });
    }

    /**
     * @param  Collection<int, Step>  $steps
     * @return list<TStoredStep>
     */
    private function steps(ChatStreamResult $result, Collection $steps): array
    {
        if ($steps->isEmpty()) {
            return [$this->conversationStore->storableStep($result->text, $result->toolCalls, $result->toolResults, $result->pendingApprovals)];
        }

        return $this->conversationStore->storableSteps($steps, $result->pendingApprovals);
    }

    private function messageStatus(ChatStreamResult $result, string $status): MessageStatus
    {
        return match (true) {
            $status === History::STREAM_STATUS_FAILED => MessageStatus::Failed,
            $result->hasPendingApprovals() => MessageStatus::Paused,
            default => MessageStatus::Completed,
        };
    }

    private function lockMessage(string $messageId, string $conversationId, User $user, MessageRole $role): History
    {
        return History::query()
            ->whereKey($messageId)
            ->where('conversation_id', $conversationId)
            ->whereMorphedTo('participant', $user)
            ->where('role', $role->value)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function markUserMessage(History $message, string $status, CarbonInterface $now): void
    {
        $message->forceFill([
            'meta' => $this->mergeStreamMeta($message, [
                'status' => $status,
            ]),
            'updated_at' => $now,
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function assistantMeta(
        History $message,
        ChatStreamResult $result,
        string $status,
        ?string $provider,
    ): array {
        $meta = $this->mergeStreamMeta($message, [
            'status' => $status,
            'provider_tools' => $result->providerTools,
            'citations' => $result->citations,
            'errors' => $result->errors,
            ...$result->hasPendingApprovals() ? ['approvals' => $result->pendingApprovals] : [],
        ]);

        if ($provider !== null) {
            $meta['provider'] = $provider;
        }

        return $meta;
    }

    /**
     * @param  array<string, mixed>  $streamMeta
     * @return array<string, mixed>
     */
    private function mergeStreamMeta(History $message, array $streamMeta): array
    {
        $meta = $message->meta ?? [];
        $existing = $message->chatStreamMeta();

        $meta[History::STREAM_META_KEY] = [
            ...$existing,
            ...$streamMeta,
        ];

        return $meta;
    }
}
