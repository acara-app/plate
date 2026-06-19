<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\ChatStreamResult;
use App\Models\Conversation;
use App\Models\History;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Messages\MessageRole;

/** @codeCoverageIgnore */
final readonly class CompletePendingChatStreamTurn
{
    public function handle(
        string $conversationId,
        int $userId,
        string $userMessageId,
        string $assistantMessageId,
        ChatStreamResult $result,
        string $status,
    ): void {
        DB::transaction(function () use ($conversationId, $userId, $userMessageId, $assistantMessageId, $result, $status): void {
            $now = now();

            $conversation = Conversation::query()
                ->whereKey($conversationId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            $userMessage = $this->lockMessage($userMessageId, $conversationId, $userId, MessageRole::User);
            $assistantMessage = $this->lockMessage($assistantMessageId, $conversationId, $userId, MessageRole::Assistant);

            $this->markUserMessage($userMessage, $status, $now);

            if (! $result->hasAssistantContent() && $status !== History::STREAM_STATUS_COMPLETED) {
                $assistantMessage->delete();
                $conversation->forceFill(['updated_at' => $now])->save();

                return;
            }

            $assistantMessage->forceFill([
                'content' => $result->text,
                'tool_calls' => $result->toolCalls,
                'tool_results' => $result->toolResults,
                'usage' => $result->usage,
                'meta' => $this->assistantMeta($assistantMessage, $result, $status),
                'updated_at' => $now,
            ])->save();

            $conversation->forceFill(['updated_at' => $now])->save();
        });
    }

    private function lockMessage(string $messageId, string $conversationId, int $userId, MessageRole $role): History
    {
        return History::query()
            ->whereKey($messageId)
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId)
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
    private function assistantMeta(History $message, ChatStreamResult $result, string $status): array
    {
        return $this->mergeStreamMeta($message, [
            'status' => $status,
            'provider_tools' => $result->providerTools,
            'citations' => $result->citations,
            'errors' => $result->errors,
        ]);
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
