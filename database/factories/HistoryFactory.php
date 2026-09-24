<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Ai\Agents\AgentRunner;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Laravel\Ai\Enums\MessageStatus;
use Laravel\Ai\Messages\MessageRole;

/**
 * @extends Factory<History>
 */
final class HistoryFactory extends Factory
{
    protected $model = History::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'conversation_id' => Conversation::factory(),
            'participant_type' => 'user',
            'participant_id' => User::factory(),
            'agent' => AgentRunner::class,
            'role' => fake()->randomElement([MessageRole::User, MessageRole::Assistant]),
            'content' => fake()->paragraph(),
            'attachments' => [],
            'steps' => [],
            'usage' => [],
            'meta' => [],
        ];
    }

    public function userMessage(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => MessageRole::User,
            'steps' => [],
            'usage' => [],
        ]);
    }

    public function assistantMessage(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => MessageRole::Assistant,
            'attachments' => [],
        ]);
    }

    public function pendingStream(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => MessageRole::Assistant,
            'meta' => History::streamMeta((string) Str::uuid7(), History::STREAM_STATUS_PENDING),
        ]);
    }

    public function forConversation(Conversation $conversation): static
    {
        return $this->state(fn (array $attributes): array => [
            'conversation_id' => $conversation->id,
            'participant_type' => $conversation->participant_type,
            'participant_id' => $conversation->participant_id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => Conversation::participantAttributes($user));
    }

    /**
     * @param  array<string, string|null>  $pending
     * @param  array<string, mixed>  $arguments
     * @param  list<array<string, mixed>>  $replayBlocks
     */
    public function awaitingApproval(array $pending, array $arguments = [], array $replayBlocks = []): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => MessageRole::Assistant,
            'status' => MessageStatus::Paused,
            'steps' => [[
                'content' => '',
                'tool_calls' => array_map(
                    fn (string $id, ?string $reason): array => ['id' => $id, 'name' => 'log_health_entry', 'arguments' => $arguments, 'approval_reason' => $reason],
                    array_keys($pending),
                    $pending,
                ),
                'reasoning' => '',
                'replay_blocks' => $replayBlocks,
                'provider_tool_calls' => [],
            ]],
        ]);
    }

    public function withAgent(string $agentClass): static
    {
        return $this->state(fn (array $attributes): array => [
            'agent' => $agentClass,
        ]);
    }
}
