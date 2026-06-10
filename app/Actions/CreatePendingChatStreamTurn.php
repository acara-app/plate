<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\AgentRunner;
use App\Data\ChatStreamTurn;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Messages\MessageRole;

final readonly class CreatePendingChatStreamTurn
{
    /**
     * @param  list<array{type: string, name: ?string, base64: string, mime: ?string}>  $attachments
     */
    public function handle(
        Conversation $conversation,
        User $user,
        string $prompt,
        array $attachments,
        string $channel,
    ): ChatStreamTurn {
        return DB::transaction(function () use ($conversation, $user, $prompt, $attachments, $channel): ChatStreamTurn {
            $streamId = (string) Str::uuid7();
            $userMessageId = (string) Str::uuid7();
            $assistantMessageId = (string) Str::uuid7();
            $now = now();

            $conversation = Conversation::query()
                ->whereKey($conversation->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $conversation->messages()->create([
                'id' => $userMessageId,
                'user_id' => $user->id,
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

            $conversation->messages()->create([
                'id' => $assistantMessageId,
                'user_id' => $user->id,
                'agent' => AgentRunner::class,
                'role' => MessageRole::Assistant,
                'content' => '',
                'attachments' => [],
                'tool_calls' => [],
                'tool_results' => [],
                'usage' => [],
                'meta' => History::streamMeta($streamId, History::STREAM_STATUS_PENDING, [
                    'channel' => $channel,
                    'user_message_id' => $userMessageId,
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $conversation->forceFill(['updated_at' => $now])->save();

            return new ChatStreamTurn(
                streamId: $streamId,
                userMessageId: $userMessageId,
                assistantMessageId: $assistantMessageId,
            );
        });
    }
}
