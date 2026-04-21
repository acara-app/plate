<?php

declare(strict_types=1);

namespace Database\Factories;

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
            'platform' => 'mock',
            'platform_user_id' => (string) fake()->unique()->numerify('########'),
            'platform_chat_id' => null,
            'conversation_id' => null,
            'linking_token' => null,
            'token_expires_at' => null,
            'is_active' => true,
            'linked_at' => null,
        ];
    }

    public function forPlatform(string $platform): static
    {
        return $this->state(fn (array $attributes): array => [
            'platform' => $platform,
        ]);
    }

    public function telegram(): static
    {
        return $this->forPlatform('telegram');
    }

    public function linked(?User $user = null): static
    {
        return $this->state(function (array $attributes) use ($user): array {
            $state = [
                'linked_at' => now(),
                'is_active' => true,
                'linking_token' => null,
                'token_expires_at' => null,
            ];

            if ($user instanceof User) {
                $state['user_id'] = $user->id;
            }

            return $state;
        });
    }

    public function withToken(string $token = 'ABC123XY', int $expiresInHours = 24): static
    {
        return $this->state(fn (array $attributes): array => [
            'linking_token' => $token,
            'token_expires_at' => now()->addHours($expiresInHours),
        ]);
    }

    public function pending(?User $user = null): static
    {
        return $this->state(function (array $attributes) use ($user): array {
            $state = [
                'platform_user_id' => null,
                'linked_at' => null,
            ];

            if ($user instanceof User) {
                $state['user_id'] = $user->id;
            }

            return $state;
        })->withToken();
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
