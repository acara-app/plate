<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Conversation>
 */
final class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the conversation belongs to a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the conversation has a specific title.
     */
    public function withTitle(string $title): static
    {
        return $this->state(fn (array $attributes): array => [
            'title' => $title,
        ]);
    }
}
