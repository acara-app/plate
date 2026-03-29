<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DeletedUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeletedUser>
 */
final class DeletedUserFactory extends Factory
{
    protected $model = DeletedUser::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'email' => fake()->unique()->safeEmail(),
            'deleted_at' => now(),
        ];
    }

    public function deletedDaysAgo(int $days): static
    {
        return $this->state(fn (array $attributes): array => [
            'deleted_at' => now()->subDays($days),
        ]);
    }
}
