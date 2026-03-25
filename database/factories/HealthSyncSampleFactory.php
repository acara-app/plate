<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthSyncSample>
 */
final class HealthSyncSampleFactory extends Factory
{
    protected $model = HealthSyncSample::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'mobile_sync_device_id' => null,
            'type_identifier' => fake()->randomElement([
                'heartRate',
                'bloodGlucose',
                'weight',
                'stepCount',
                'bloodPressureSystolic',
                'bloodPressureDiastolic',
            ]),
            'value' => fake()->randomFloat(2, 0, 200),
            'unit' => fake()->randomElement(['bpm', 'mmol/L', 'kg', 'count', 'mmHg']),
            'measured_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'source' => fake()->randomElement(['Apple Watch', 'iPhone', null]),
            'metadata' => null,
        ];
    }

    public function bloodGlucose(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_identifier' => 'bloodGlucose',
            'value' => fake()->randomFloat(1, 4.0, 10.0),
            'unit' => 'mmol/L',
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

    public function weight(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_identifier' => 'weight',
            'value' => fake()->randomFloat(1, 60.0, 100.0),
            'unit' => 'kg',
        ]);
    }
}
