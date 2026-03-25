<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MobileSyncDevice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MobileSyncDevice>
 */
final class MobileSyncDeviceFactory extends Factory
{
    protected $model = MobileSyncDevice::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'device_name' => null,
            'device_identifier' => null,
            'linking_token' => null,
            'token_expires_at' => null,
            'is_active' => true,
            'paired_at' => null,
            'last_synced_at' => null,
        ];
    }

    public function paired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'device_name' => fake()->randomElement(['iPhone 15 Pro', 'iPhone 16', 'iPad Air']),
            'device_identifier' => fake()->uuid(),
            'paired_at' => now(),
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
