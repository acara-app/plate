<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\GlucoseReadingType;
use App\Enums\HealthEntrySource;
use App\Enums\HealthSyncType;
use App\Enums\InsulinType;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthSyncSample>
 */
final class HealthSyncSampleFactory extends Factory
{
    protected $model = HealthSyncSample::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'mobile_sync_device_id' => null,
            'type_identifier' => fake()->randomElement([
                'heartRate',
                HealthSyncType::BloodGlucose->value,
                HealthSyncType::Weight->value,
                'stepCount',
                HealthSyncType::BloodPressureSystolic->value,
                HealthSyncType::BloodPressureDiastolic->value,
            ]),
            'value' => fake()->randomFloat(2, 0, 200),
            'unit' => fake()->randomElement(['bpm', 'mg/dL', 'kg', 'count', 'mmHg']),
            'measured_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'source' => fake()->randomElement(['Apple Watch', 'iPhone', null]),
            'entry_source' => null,
            'timezone' => null,
            'metadata' => null,
            'notes' => null,
            'group_id' => null,
        ];
    }

    public function bloodGlucose(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_identifier' => HealthSyncType::BloodGlucose->value,
            'value' => fake()->randomFloat(1, 70, 180),
            'unit' => HealthSyncType::BloodGlucose->unit(),
            'metadata' => ['glucose_reading_type' => collect(GlucoseReadingType::cases())->random()->value],
        ]);
    }

    public function fasting(): static
    {
        return $this->bloodGlucose()->state(fn (array $attributes): array => [
            'value' => fake()->randomFloat(1, 70, 100),
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::Fasting->value],
        ]);
    }

    public function postMeal(): static
    {
        return $this->bloodGlucose()->state(fn (array $attributes): array => [
            'value' => fake()->randomFloat(1, 100, 140),
            'metadata' => ['glucose_reading_type' => GlucoseReadingType::PostMeal->value],
        ]);
    }

    public function elevated(): static
    {
        return $this->bloodGlucose()->state(fn (array $attributes): array => [
            'value' => fake()->randomFloat(1, 180, 250),
        ]);
    }

    public function heartRate(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_identifier' => 'heartRate',
            'value' => fake()->numberBetween(60, 100),
            'unit' => 'bpm',
        ]);
    }

    public function stepCount(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_identifier' => 'stepCount',
            'value' => fake()->numberBetween(100, 5000),
            'unit' => 'steps',
        ]);
    }

    public function activeEnergy(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_identifier' => 'activeEnergy',
            'value' => fake()->randomFloat(1, 50, 500),
            'unit' => 'kcal',
        ]);
    }

    public function weight(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_identifier' => HealthSyncType::Weight->value,
            'value' => fake()->randomFloat(1, 60.0, 100.0),
            'unit' => HealthSyncType::Weight->unit(),
        ]);
    }

    public function insulin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_identifier' => HealthSyncType::Insulin->value,
            'value' => fake()->randomFloat(2, 1, 50),
            'unit' => HealthSyncType::Insulin->unit(),
            'metadata' => ['insulin_type' => collect(InsulinType::cases())->random()->value],
        ]);
    }

    public function medication(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_identifier' => HealthSyncType::Medication->value,
            'value' => 1,
            'unit' => HealthSyncType::Medication->unit(),
            'metadata' => [
                'medication_name' => fake()->word(),
                'medication_dosage' => fake()->numberBetween(100, 1000).'mg',
            ],
        ]);
    }

    public function bloodPressure(int $systolic = 0, int $diastolic = 0): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_identifier' => HealthSyncType::BloodPressureSystolic->value,
            'value' => $systolic ?: fake()->numberBetween(90, 180),
            'unit' => HealthSyncType::BloodPressureSystolic->unit(),
        ]);
    }

    public function a1c(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_identifier' => HealthSyncType::A1c->value,
            'value' => fake()->randomFloat(1, 4.0, 10.0),
            'unit' => HealthSyncType::A1c->unit(),
        ]);
    }

    public function carbohydrates(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_identifier' => HealthSyncType::Carbohydrates->value,
            'value' => fake()->randomFloat(2, 5, 200),
            'unit' => HealthSyncType::Carbohydrates->unit(),
        ]);
    }

    public function exercise(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_identifier' => HealthSyncType::ExerciseMinutes->value,
            'value' => fake()->numberBetween(15, 90),
            'unit' => HealthSyncType::ExerciseMinutes->unit(),
            'metadata' => ['exercise_type' => fake()->randomElement(['walking', 'running', 'cycling', 'swimming', 'yoga'])],
        ]);
    }

    public function fromWeb(): static
    {
        return $this->state(fn (array $attributes): array => [
            'entry_source' => HealthEntrySource::Web,
            'source' => null,
        ]);
    }

    public function fromChat(): static
    {
        return $this->state(fn (array $attributes): array => [
            'entry_source' => HealthEntrySource::Chat,
            'source' => null,
        ]);
    }

    public function fromMobileSync(): static
    {
        return $this->state(fn (array $attributes): array => [
            'entry_source' => HealthEntrySource::MobileSync,
        ]);
    }
}
