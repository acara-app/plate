<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\AgentRunner;
use App\Data\ChatStreamTurn;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Messages\MessageRole;

final readonly class CreatePendingChatStreamTurn
{
    public function __construct(
        private AbandonPendingApprovals $abandonPendingApprovals,
    ) {}

    /**
     * @param  list<array{type: string, name: ?string, base64: string, mime: ?string}>  $attachments
     */
    public function handle(
        Conversation $conversation,
        User $user,
        string $prompt,
        array $attachments,
        string $channel,
        ?string $model = null,
    ): ChatStreamTurn {
        return DB::transaction(function () use ($conversation, $user, $prompt, $attachments, $channel, $model): ChatStreamTurn {
            $streamId = (string) Str::uuid7();
            $userMessageId = (string) Str::uuid7();
            $now = now();

            $conversation = $this->lockConversation($conversation, $user);

            $this->abandonPendingApprovals->handle($conversation->id);

            $conversation->messages()->create([
                'id' => $userMessageId,
                ...Conversation::participantAttributes($user),
                'agent' => AgentRunner::class,
                'role' => MessageRole::User,
                'content' => $prompt,
                'attachments' => $attachments,
                'tool_calls' => [],
                'tool_results' => [],
                'usage' => [],
                'meta' => History::streamMeta($streamId, History::STREAM_STATUS_SUBMITTED, [
                    'channel' => $channel,
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $assistantMessageId = $this->createAssistantPlaceholder($conversation, $user, $streamId, $channel, $model, $userMessageId, $now);

            $conversation->forceFill(['updated_at' => $now])->save();

            return new ChatStreamTurn(
                streamId: $streamId,
                userMessageId: $userMessageId,
                assistantMessageId: $assistantMessageId,
            );
        });
    }

    public function forResume(
        Conversation $conversation,
        User $user,
        string $channel,
        ?string $model = null,
    ): ChatStreamTurn {
        return DB::transaction(function () use ($conversation, $user, $channel, $model): ChatStreamTurn {
            $streamId = (string) Str::uuid7();
            $now = now();

            $conversation = $this->lockConversation($conversation, $user);

            $assistantMessageId = $this->createAssistantPlaceholder($conversation, $user, $streamId, $channel, $model, null, $now);

            $conversation->forceFill(['updated_at' => $now])->save();

            return new ChatStreamTurn(
                streamId: $streamId,
                userMessageId: null,
                assistantMessageId: $assistantMessageId,
            );
        });
    }

    private function lockConversation(Conversation $conversation, User $user): Conversation
    {
        return Conversation::query()
            ->whereKey($conversation->id)
            ->forUser($user)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function createAssistantPlaceholder(
        Conversation $conversation,
        User $user,
        string $streamId,
        string $channel,
        ?string $model,
        ?string $userMessageId,
        CarbonInterface $now,
    ): string {
        $assistantMessageId = (string) Str::uuid7();

        $conversation->messages()->create([
            'id' => $assistantMessageId,
            ...Conversation::participantAttributes($user),
            'agent' => AgentRunner::class,
            'role' => MessageRole::Assistant,
            'content' => '',
            'attachments' => [],
            'tool_calls' => [],
            'tool_results' => [],
            'usage' => [],
            'meta' => History::streamMeta($streamId, History::STREAM_STATUS_PENDING, array_filter([
                'channel' => $channel,
                'model' => $model,
                'user_message_id' => $userMessageId,
            ], fn (mixed $value): bool => $value !== null)),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $assistantMessageId;
    }
}
