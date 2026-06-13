<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Benchmark\TruthSource;
use App\Models\BenchmarkMeal;
use App\Models\BenchmarkMealItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BenchmarkMealItem>
 */
final class BenchmarkMealItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'benchmark_meal_id' => BenchmarkMeal::factory(),
            'position' => fake()->unique()->numberBetween(1, 250),
            'name' => fake()->words(2, true),
            'visible' => true,
            'weight_g' => fake()->randomFloat(2, 5, 400),
            'kcal_per_100g' => fake()->randomFloat(2, 10, 600),
            'carbs_per_100g' => fake()->randomFloat(2, 0, 80),
            'protein_per_100g' => fake()->randomFloat(2, 0, 30),
            'fat_per_100g' => fake()->randomFloat(2, 0, 60),
            'truth_source' => TruthSource::Reference,
        ];
    }

    public function hidden(): self
    {
        return $this->state(fn (): array => ['visible' => false]);
    }
}
