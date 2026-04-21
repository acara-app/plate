<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ChatPlatform;
use App\Models\User;
use App\Models\UserChatPlatformLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserChatPlatformLink>
 */
final class UserChatPlatformLinkFactory extends Factory
{
    protected $model = UserChatPlatformLink::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'platform' => ChatPlatform::Telegram,
            'platform_user_id' => null,
            'platform_chat_id' => null,
            'conversation_id' => null,
            'linking_token' => null,
            'token_expires_at' => null,
            'is_active' => true,
            'linked_at' => null,
        ];
    }

    public function forPlatform(ChatPlatform $platform): static
    {
        return $this->state(fn (): array => ['platform' => $platform]);
    }

    public function withToken(): static
    {
        return $this->state(fn (): array => [
            'linking_token' => $this->generateToken(),
            'token_expires_at' => now()->addHours(24),
        ]);
    }

    public function pendingLink(): static
    {
        return $this->state(fn (): array => [
            'user_id' => User::factory(),
            'linking_token' => $this->generateToken(),
            'token_expires_at' => now()->addHours(24),
            'linked_at' => null,
            'platform_user_id' => null,
        ]);
    }

    public function linked(?User $user = null): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user instanceof User ? $user->id : User::factory(),
            'platform_user_id' => (string) random_int(10_000_000, 99_999_999),
            'linked_at' => now(),
            'is_active' => true,
            'linking_token' => null,
            'token_expires_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    private function generateToken(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $token = '';

        for ($i = 0; $i < 8; $i++) {
            $token .= $alphabet[random_int(0, 35)];
        }

        return $token;
    }
}
