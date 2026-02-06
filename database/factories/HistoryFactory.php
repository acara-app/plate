<?php

declare(strict_types=1);

namespace Database\Factories;

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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'conversation_id' => Conversation::factory(),
            'user_id' => User::factory(),
            'agent' => \App\Ai\Agents\NutritionAdvisor::class,
            'role' => fake()->randomElement([MessageRole::User, MessageRole::Assistant]),
            'content' => fake()->paragraph(),
            'attachments' => [],
            'tool_calls' => [],
            'tool_results' => [],
            'usage' => [],
            'meta' => [],
        ];
    }

    /**
     * Indicate that the message is from a user.
     */
    public function userMessage(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => MessageRole::User,
            'tool_calls' => [],
            'tool_results' => [],
            'usage' => [],
        ]);
    }

    /**
     * Indicate that the message is from an assistant.
     */
    public function assistantMessage(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => MessageRole::Assistant,
            'attachments' => [],
        ]);
    }

    /**
     * Indicate that the message belongs to a specific conversation.
     */
    public function forConversation(Conversation $conversation): static
    {
        return $this->state(fn (array $attributes): array => [
            'conversation_id' => $conversation->id,
            'user_id' => $conversation->user_id,
        ]);
    }

    /**
     * Indicate that the message belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the message uses a specific agent.
     */
    public function withAgent(string $agentClass): static
    {
        return $this->state(fn (array $attributes): array => [
            'agent' => $agentClass,
        ]);
    }
}
