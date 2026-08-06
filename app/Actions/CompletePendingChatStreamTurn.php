<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\ChatStreamResult;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Messages\MessageRole;

/** @codeCoverageIgnore */
final readonly class CompletePendingChatStreamTurn
{
    /**
     * @param  list<array<string, mixed>>  $providerContentBlocks
     */
    public function handle(
        string $conversationId,
        User $user,
        ?string $userMessageId,
        string $assistantMessageId,
        ChatStreamResult $result,
        string $status,
        array $providerContentBlocks = [],
        ?string $provider = null,
    ): void {
        if ($status !== History::STREAM_STATUS_COMPLETED) {
            $result = $result->withoutPendingApprovals();
        }

        DB::transaction(function () use ($conversationId, $user, $userMessageId, $assistantMessageId, $result, $status, $providerContentBlocks, $provider): void {
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
                'tool_calls' => $result->toolCalls,
                'tool_results' => $this->toolResults($conversationId, $assistantMessageId, $result),
                'usage' => $result->usage,
                'meta' => $this->assistantMeta($assistantMessage, $result, $status, $providerContentBlocks, $provider),
                'approval_state' => $result->hasPendingApprovals()
                    ? ['pending' => $result->pendingApprovals]
                    : null,
                'updated_at' => $now,
            ])->save();

            $conversation->forceFill(['updated_at' => $now])->save();
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function toolResults(string $conversationId, string $assistantMessageId, ChatStreamResult $result): array
    {
        if ($result->toolResults === []) {
            return [];
        }

        $recorded = History::query()
            ->where('conversation_id', $conversationId)
            ->whereKeyNot($assistantMessageId)
            ->where('role', MessageRole::Assistant->value)
            ->whereNotNull('approval_state')
            ->get(['id', 'tool_results'])
            ->flatMap(fn (History $message): Collection => collect($message->tool_results ?? [])->pluck('id'))
            ->filter()
            ->all();

        if ($recorded === []) {
            return $result->toolResults;
        }

        return array_values(array_filter(
            $result->toolResults,
            fn (array $toolResult): bool => ! in_array($toolResult['id'] ?? null, $recorded, true),
        ));
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
     * @param  list<array<string, mixed>>  $providerContentBlocks
     * @return array<string, mixed>
     */
    private function assistantMeta(
        History $message,
        ChatStreamResult $result,
        string $status,
        array $providerContentBlocks,
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

        if ($providerContentBlocks !== []) {
            $meta['provider_content_blocks'] = $providerContentBlocks;
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
