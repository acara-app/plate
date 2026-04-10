<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\HealthAggregationFunction;
use App\Models\HealthDailyAggregate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthDailyAggregate>
 */
final class HealthDailyAggregateFactory extends Factory
{
    protected $model = HealthDailyAggregate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = CarbonImmutable::parse(fake()->date());

        return [
            'user_id' => User::factory(),
            'date' => $date,
            'local_date' => $date,
            'timezone' => 'UTC',
            'type_identifier' => fake()->randomElement([
                'heartRate',
                'stepCount',
                'bloodGlucose',
                'weight',
                'activeEnergy',
                'carbohydrates',
            ]),
            'value_sum' => null,
            'value_sum_canonical' => null,
            'value_avg' => fake()->randomFloat(2, 60, 200),
            'value_min' => fake()->randomFloat(2, 40, 80),
            'value_max' => fake()->randomFloat(2, 100, 250),
            'value_last' => fake()->randomFloat(2, 60, 180),
            'value_count' => fake()->numberBetween(1, 50),
            'source_primary' => fake()->randomElement(['Apple Watch', 'iPhone', 'Bluetooth Device']),
            'unit' => null,
            'canonical_unit' => null,
            'aggregation_function' => HealthAggregationFunction::Avg->value,
            'aggregation_version' => 1,
            'metadata' => null,
        ];
    }

    public function stepCount(int $steps = 0): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_identifier' => 'stepCount',
            'value_sum' => $steps ?: fake()->numberBetween(3000, 15000),
            'value_sum_canonical' => $steps ?: fake()->numberBetween(3000, 15000),
            'value_avg' => null,
            'value_min' => null,
            'value_max' => null,
            'value_last' => null,
            'value_count' => fake()->numberBetween(5, 30),
            'unit' => 'steps',
            'canonical_unit' => 'count',
            'aggregation_function' => HealthAggregationFunction::Sum->value,
        ]);
    }

    public function heartRate(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_identifier' => 'heartRate',
            'value_sum' => null,
            'value_sum_canonical' => null,
            'value_avg' => fake()->randomFloat(1, 60, 100),
            'value_min' => fake()->randomFloat(1, 48, 65),
            'value_max' => fake()->randomFloat(1, 90, 180),
            'value_last' => fake()->randomFloat(1, 60, 95),
            'value_count' => fake()->numberBetween(10, 100),
            'unit' => 'bpm',
            'canonical_unit' => 'bpm',
            'aggregation_function' => HealthAggregationFunction::Avg->value,
        ]);
    }

    public function bloodGlucose(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type_identifier' => 'bloodGlucose',
            'value_sum' => null,
            'value_sum_canonical' => null,
            'value_avg' => fake()->randomFloat(1, 80, 150),
            'value_min' => fake()->randomFloat(1, 60, 90),
            'value_max' => fake()->randomFloat(1, 120, 250),
            'value_last' => fake()->randomFloat(1, 80, 140),
            'value_count' => fake()->numberBetween(1, 10),
            'unit' => 'mg/dL',
            'canonical_unit' => 'mg/dL',
            'aggregation_function' => HealthAggregationFunction::WeightedAvg->value,
        ]);
    }
}
