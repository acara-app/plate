<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\AgentRunner;
use App\Models\Conversation;
use App\Models\History;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Messages\MessageRole;

final readonly class PersistPartialChatStream
{
    private const string PARTIAL_STREAM_ID = 'partial_stream_id';

    /**
     * @param  list<Base64Image>  $images
     * @return list<array{type: string, name: ?string, base64: string, mime: ?string}>
     */
    public static function serializeAttachments(array $images): array
    {
        return array_values(array_map(
            fn (Base64Image $image): array => $image->toArray(),
            $images,
        ));
    }

    /**
     * @param  list<array{type: string, name: ?string, base64: string, mime: ?string}>  $attachments
     * @param  list<array<string, mixed>>  $toolCalls
     * @param  list<array<string, mixed>>  $toolResults
     */
    public function handle(
        string $conversationId,
        int $userId,
        string $prompt,
        array $attachments,
        string $assistantText,
        array $toolCalls = [],
        array $toolResults = [],
        string $streamId = '',
    ): void {
        $streamId = $streamId === '' ? (string) Str::uuid7() : $streamId;

        DB::transaction(function () use ($conversationId, $userId, $prompt, $attachments, $assistantText, $toolCalls, $toolResults, $streamId): void {
            $now = now();

            $conversation = Conversation::query()
                ->whereKey($conversationId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Collection<int, History> $messages */
            $messages = $conversation->messages()
                ->where('user_id', $userId)
                ->where('agent', AgentRunner::class)
                ->lockForUpdate()
                ->get();

            $userMessage = $this->partialUserMessage($messages, $streamId)
                ?? $this->pendingUserMessage($messages, $prompt)
                ?? $conversation->messages()->create([
                    'id' => (string) Str::uuid7(),
                    'user_id' => $userId,
                    'agent' => AgentRunner::class,
                    'role' => MessageRole::User,
                    'content' => $prompt,
                    'attachments' => $attachments,
                    'tool_calls' => [],
                    'tool_results' => [],
                    'usage' => [],
                    'meta' => [self::PARTIAL_STREAM_ID => $streamId],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($this->hasAssistantContent($assistantText, $toolCalls, $toolResults)) {
                $this->persistAssistantMessage(
                    conversation: $conversation,
                    messages: $messages,
                    userMessage: $userMessage,
                    userId: $userId,
                    assistantText: $assistantText,
                    toolCalls: $toolCalls,
                    toolResults: $toolResults,
                    streamId: $streamId,
                    now: $now,
                );
            }

            $conversation->forceFill(['updated_at' => $now])->save();
        });
    }

    /**
     * @param  Collection<int, History>  $messages
     */
    private function partialUserMessage(Collection $messages, string $streamId): ?History
    {
        /** @var History|null $message */
        $message = $messages
            ->reverse()
            ->first(fn (History $message): bool => $message->role === MessageRole::User
                && ($message->meta[self::PARTIAL_STREAM_ID] ?? null) === $streamId);

        return $message;
    }

    /**
     * @param  Collection<int, History>  $messages
     */
    private function pendingUserMessage(Collection $messages, string $prompt): ?History
    {
        $message = $messages->last();

        if (! $message instanceof History) {
            return null;
        }

        if ($message->role !== MessageRole::User) {
            return null;
        }

        if ($message->content !== $prompt) {
            return null;
        }

        return $message;
    }

    /**
     * @param  list<array<string, mixed>>  $toolCalls
     * @param  list<array<string, mixed>>  $toolResults
     */
    private function hasAssistantContent(string $assistantText, array $toolCalls, array $toolResults): bool
    {
        return mb_trim($assistantText) !== ''
            || $toolCalls !== []
            || $toolResults !== [];
    }

    /**
     * @param  Collection<int, History>  $messages
     * @param  list<array<string, mixed>>  $toolCalls
     * @param  list<array<string, mixed>>  $toolResults
     */
    private function persistAssistantMessage(
        Conversation $conversation,
        Collection $messages,
        History $userMessage,
        int $userId,
        string $assistantText,
        array $toolCalls,
        array $toolResults,
        string $streamId,
        CarbonInterface $now,
    ): void {
        $assistantMessage = $this->partialAssistantMessageAfter($messages, $userMessage, $streamId);

        if (! $assistantMessage instanceof History) {
            $conversation->messages()->create([
                'id' => (string) Str::uuid7(),
                'user_id' => $userId,
                'agent' => AgentRunner::class,
                'role' => MessageRole::Assistant,
                'content' => $assistantText,
                'attachments' => [],
                'tool_calls' => $toolCalls,
                'tool_results' => $toolResults,
                'usage' => [],
                'meta' => [self::PARTIAL_STREAM_ID => $streamId],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return;
        }

        $assistantMessage->forceFill([
            'content' => $assistantText,
            'tool_calls' => $toolCalls,
            'tool_results' => $toolResults,
            'updated_at' => $now,
        ])->save();
    }

    /**
     * @param  Collection<int, History>  $messages
     */
    private function partialAssistantMessageAfter(Collection $messages, History $userMessage, string $streamId): ?History
    {
        /** @var History|null $message */
        $message = $this->messagesAfter($messages, $userMessage)
            ->first(fn (History $message): bool => $message->role === MessageRole::Assistant
                && ($message->meta[self::PARTIAL_STREAM_ID] ?? null) === $streamId);

        return $message;
    }

    /**
     * @param  Collection<int, History>  $messages
     * @return Collection<int, History>
     */
    private function messagesAfter(Collection $messages, History $userMessage): Collection
    {
        $afterUserMessage = false;

        return $messages
            ->filter(function (History $message) use ($userMessage, &$afterUserMessage): bool {
                if ($message->id === $userMessage->id) {
                    $afterUserMessage = true;

                    return false;
                }

                return $afterUserMessage;
            })
            ->values();
    }
}
