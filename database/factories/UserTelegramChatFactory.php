<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserTelegramChat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserTelegramChat>
 */
final class UserTelegramChatFactory extends Factory
{
    protected $model = UserTelegramChat::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'telegraph_chat_id' => null,
            'linking_token' => null,
            'token_expires_at' => null,
            'is_active' => true,
            'linked_at' => null,
        ];
    }

    public function linked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'linked_at' => now(),
        ]);
    }

    public function withToken(): static
    {
        return $this->state(fn (array $attributes): array => [
            'linking_token' => mb_strtoupper(mb_substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8)),
            'token_expires_at' => now()->addHours(24),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
