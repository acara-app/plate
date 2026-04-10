<?php

declare(strict_types=1);

use App\Enums\HealthAggregationFunction;
use App\Http\Layouts\DiabetesLayout;
use App\Models\HealthDailyAggregate;
use App\Models\User;
use Carbon\CarbonImmutable;

it('computes the weighted cross-day mean for glucose, not the naive average of averages', function (): void {
    $user = User::factory()->create();

    HealthDailyAggregate::factory()->for($user)->bloodGlucose()->create([
        'local_date' => CarbonImmutable::now()->subDays(2)->toDateString(),
        'date' => CarbonImmutable::now()->subDays(2)->toDateString(),
        'value_avg' => 100.0,
        'value_min' => 95.0,
        'value_max' => 105.0,
        'value_last' => 100.0,
        'value_count' => 2,
        'value_sum' => 200.0,
        'value_sum_canonical' => 200.0,
        'aggregation_function' => HealthAggregationFunction::WeightedAvg->value,
    ]);

    HealthDailyAggregate::factory()->for($user)->bloodGlucose()->create([
        'local_date' => CarbonImmutable::now()->subDay()->toDateString(),
        'date' => CarbonImmutable::now()->subDay()->toDateString(),
        'value_avg' => 200.0,
        'value_min' => 150.0,
        'value_max' => 250.0,
        'value_last' => 200.0,
        'value_count' => 100,
        'value_sum' => 20000.0,
        'value_sum_canonical' => 20000.0,
        'aggregation_function' => HealthAggregationFunction::WeightedAvg->value,
    ]);

    $result = DiabetesLayout::dashboardDataFromAggregates($user, '7d');

    expect($result['summary']['glucoseStats']['count'])->toBe(102)
        ->and($result['summary']['glucoseStats']['avg'])->toBe(198.0);
});
