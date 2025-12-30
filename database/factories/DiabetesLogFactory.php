<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GlucoseReadingType;
use App\Enums\InsulinType;
use App\Models\DiabetesLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiabetesLog>
 */
final class DiabetesLogFactory extends Factory
{
    protected $model = DiabetesLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'glucose_value' => fake()->randomFloat(1, 70, 180),
            'glucose_reading_type' => fake()->randomElement(GlucoseReadingType::cases()),
            'measured_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'notes' => fake()->optional(0.3)->sentence(),
            'insulin_units' => null,
            'insulin_type' => null,
            'medication_name' => null,
            'medication_dosage' => null,
            'weight' => null,
            'blood_pressure_systolic' => null,
            'blood_pressure_diastolic' => null,
            'a1c_value' => null,
            'carbs_grams' => null,
            'exercise_type' => null,
            'exercise_duration_minutes' => null,
        ];
    }

    /**
     * Indicate that the reading is a fasting reading.
     */
    public function fasting(): static
    {
        return $this->state(fn (array $attributes): array => [
            'glucose_reading_type' => GlucoseReadingType::Fasting,
            'glucose_value' => fake()->randomFloat(1, 70, 100),
        ]);
    }

    /**
     * Indicate that the reading is a post-meal reading.
     */
    public function postMeal(): static
    {
        return $this->state(fn (array $attributes): array => [
            'glucose_reading_type' => GlucoseReadingType::PostMeal,
            'glucose_value' => fake()->randomFloat(1, 100, 140),
        ]);
    }

    /**
     * Indicate that the reading is elevated.
     */
    public function elevated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'glucose_value' => fake()->randomFloat(1, 180, 250),
        ]);
    }

    /**
     * Include insulin tracking data.
     */
    public function withInsulin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'insulin_units' => fake()->randomFloat(2, 1, 50),
            'insulin_type' => fake()->randomElement(InsulinType::cases()),
        ]);
    }

    /**
     * Include blood pressure data.
     */
    public function withBloodPressure(): static
    {
        return $this->state(fn (array $attributes): array => [
            'blood_pressure_systolic' => fake()->numberBetween(90, 180),
            'blood_pressure_diastolic' => fake()->numberBetween(60, 120),
        ]);
    }

    /**
     * Include exercise data.
     */
    public function withExercise(): static
    {
        return $this->state(fn (array $attributes): array => [
            'exercise_type' => fake()->randomElement(['walking', 'running', 'cycling', 'swimming', 'yoga']),
            'exercise_duration_minutes' => fake()->numberBetween(15, 90),
        ]);
    }
}
