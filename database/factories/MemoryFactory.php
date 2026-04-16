<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Memory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Memory>
 */
final class MemoryFactory extends Factory
{
    protected $model = Memory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $vector = array_map(
            static fn (): float => fake()->randomFloat(6, -1, 1),
            range(1, 8),
        );

        return [
            'user_id' => User::factory(),
            'content' => fake()->sentence(),
            'metadata' => ['source' => 'test'],
            'categories' => ['fact'],
            'importance' => 5,
            'source' => 'test',
            'is_archived' => false,
            'access_count' => 0,
            'consolidation_generation' => 0,
            'embedding' => '['.implode(',', $vector).']',
        ];
    }

    public function archived(): self
    {
        return $this->state(fn (): array => ['is_archived' => true]);
    }

    /**
     * @param  array<int, float>  $vector
     */
    public function withVector(array $vector): self
    {
        return $this->state(fn (): array => [
            'embedding' => '['.implode(',', $vector).']',
        ]);
    }

    public function withImportance(int $importance): self
    {
        return $this->state(fn (): array => ['importance' => $importance]);
    }

    /**
     * @param  array<int, string>  $categories
     */
    public function withCategories(array $categories): self
    {
        return $this->state(fn (): array => ['categories' => $categories]);
    }

    public function expired(): self
    {
        return $this->state(fn (): array => ['expires_at' => now()->subDay()]);
    }
}
