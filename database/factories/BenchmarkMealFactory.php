<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Benchmark\CameraAngle;
use App\Enums\Benchmark\DishType;
use App\Enums\Benchmark\Lighting;
use App\Enums\Benchmark\Tranche;
use App\Enums\Benchmark\TruthScope;
use App\Enums\Benchmark\TruthSource;
use App\Models\BenchmarkMeal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BenchmarkMeal>
 */
final class BenchmarkMealFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = sprintf('m%04d', fake()->unique()->numberBetween(1, 9999));

        return [
            'code' => $code,
            'tranche' => Tranche::Hand,
            'collected_on' => now()->toDateString(),
            'cuisine' => fake()->randomElement(['western', 'mongolian', 'japanese', 'indian']),
            'dish_type' => DishType::Whole,
            'lighting' => Lighting::Bright,
            'angle' => CameraAngle::Angled,
            'truth_scope' => TruthScope::PerItem,
            'total_weight_g' => fake()->randomFloat(2, 150, 900),
            'photo_disk' => 'local',
            'photo_path' => sprintf('%s/%s.jpg', BenchmarkMeal::PHOTO_DIRECTORY, $code),
        ];
    }

    public function mealOnly(): self
    {
        return $this->state(fn (): array => [
            'truth_scope' => TruthScope::MealOnly,
            'total_kcal' => fake()->randomFloat(2, 200, 900),
            'total_carbs_g' => fake()->randomFloat(2, 10, 120),
            'total_protein_g' => fake()->randomFloat(2, 5, 60),
            'total_fat_g' => fake()->randomFloat(2, 5, 60),
            'truth_source' => TruthSource::Label,
            'truth_ref' => fake()->words(3, true),
        ]);
    }
}
