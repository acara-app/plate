<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MobileTwoFactorChallenge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MobileTwoFactorChallenge>
 */
final class MobileTwoFactorChallengeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token_hash' => hash('sha256', Str::random(64)),
            'user_id' => User::factory(),
            'device_identifier' => fake()->uuid(),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
        ];
    }

    public function expired(): self
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }
}
