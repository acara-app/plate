<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GlucoseReading>
 */
final class GlucoseReadingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $readingTypes = ['Fasting', 'PostMeal', 'Random', 'BeforeMeal'];

        return [
            'user_id' => \App\Models\User::factory(),
            'reading_value' => fake()->randomFloat(1, 70, 180), // Normal range: 70-180 mg/dL
            'reading_type' => fake()->randomElement($readingTypes),
            'measured_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the reading is a fasting reading.
     */
    public function fasting(): static
    {
        return $this->state(fn (array $attributes): array => [
            'reading_type' => 'Fasting',
            'reading_value' => fake()->randomFloat(1, 70, 100), // Typical fasting range
        ]);
    }

    /**
     * Indicate that the reading is a post-meal reading.
     */
    public function postMeal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'reading_type' => 'PostMeal',
            'reading_value' => fake()->randomFloat(1, 100, 140), // Typical post-meal range
        ]);
    }

    /**
     * Indicate that the reading is elevated (potential concern).
     */
    public function elevated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'reading_value' => fake()->randomFloat(1, 180, 250),
        ]);
    }
}
