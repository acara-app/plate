<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MobileAuthNonce;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MobileAuthNonce>
 */
final class MobileAuthNonceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nonce_id' => fake()->uuid(),
            'nonce' => bin2hex(random_bytes(32)),
            'device_identifier' => fake()->uuid(),
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
