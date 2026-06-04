<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\AgentRunner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Files\Base64Image;

final readonly class PersistPartialChatStream
{
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
    ): void {
        DB::transaction(function () use ($conversationId, $userId, $prompt, $attachments, $assistantText, $toolCalls, $toolResults): void {
            $now = now();

            $userMessageExists = DB::table('agent_conversation_messages')
                ->where('conversation_id', $conversationId)
                ->where('user_id', $userId)
                ->where('role', 'user')
                ->where('content', $prompt)
                ->exists();

            if (! $userMessageExists) {
                DB::table('agent_conversation_messages')->insert([
                    'id' => (string) Str::uuid7(),
                    'conversation_id' => $conversationId,
                    'user_id' => $userId,
                    'agent' => AgentRunner::class,
                    'role' => 'user',
                    'content' => $prompt,
                    'attachments' => json_encode($attachments, JSON_THROW_ON_ERROR),
                    'tool_calls' => '[]',
                    'tool_results' => '[]',
                    'usage' => '[]',
                    'meta' => '[]',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if (mb_trim($assistantText) !== '' || $toolCalls !== [] || $toolResults !== []) {
                DB::table('agent_conversation_messages')->insert([
                    'id' => (string) Str::uuid7(),
                    'conversation_id' => $conversationId,
                    'user_id' => $userId,
                    'agent' => AgentRunner::class,
                    'role' => 'assistant',
                    'content' => $assistantText,
                    'attachments' => '[]',
                    'tool_calls' => json_encode($toolCalls, JSON_THROW_ON_ERROR),
                    'tool_results' => json_encode($toolResults, JSON_THROW_ON_ERROR),
                    'usage' => '[]',
                    'meta' => '[]',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('agent_conversations')
                ->where('id', $conversationId)
                ->where('user_id', $userId)
                ->update(['updated_at' => $now]);
        });
    }
}
