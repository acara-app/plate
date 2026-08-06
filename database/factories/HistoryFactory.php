<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Ai\Agents\AgentRunner;
use App\Models\Conversation;
use App\Models\History;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
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
            'tool_calls' => [],
            'tool_results' => [],
            'usage' => [],
            'meta' => [],
        ];
    }

    public function userMessage(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => MessageRole::User,
            'tool_calls' => [],
            'tool_results' => [],
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
     */
    public function awaitingApproval(array $pending): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => MessageRole::Assistant,
            'approval_state' => ['pending' => $pending],
        ]);
    }

    public function withAgent(string $agentClass): static
    {
        return $this->state(fn (array $attributes): array => [
            'agent' => $agentClass,
        ]);
    }
}
