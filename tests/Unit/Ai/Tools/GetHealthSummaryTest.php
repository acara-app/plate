<?php

declare(strict_types=1);

use App\Actions\AggregateHealthDailySamplesAction;
use App\Ai\Tools\GetHealthSummary;
use App\Data\VisualDataset;
use App\Enums\GlucoseUnit;
use App\Models\HealthDailyAggregate;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\TurnDataRegistry;
use Carbon\CarbonImmutable;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

covers(GetHealthSummary::class);

beforeEach(function (): void {
    $this->tool = new GetHealthSummary;
});

it('has correct name and description', function (): void {
    expect($this->tool->name())->toBe('get_health_summary')
        ->and($this->tool->description())->toContain('aggregated daily summaries');
});

it('has valid schema', function (): void {
    $schema = new TestJsonSchema;
    $result = $this->tool->schema($schema);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['type', 'days', 'date']);
});

it('returns error if user is not authenticated', function (): void {
    $request = new Request(['type' => 'steps']);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('error', 'User not authenticated');
});

it('returns step count aggregate rows with compatibility and metadata fields', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $day = CarbonImmutable::parse('2026-04-10');

    HealthDailyAggregate::factory()->for($user)->stepCount(8000)->create([
        'local_date' => $day,
        'date' => $day,
        'value_count' => 2,
        'source_primary' => 'Apple Watch',
    ]);

    $request = new Request(['type' => 'steps', 'days' => 1, 'date' => $day->toDateString()]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['summaries'])->toHaveCount(1)
        ->and($json['summaries'][0]['type'])->toBe('stepCount')
        ->and((float) $json['summaries'][0]['total'])->toBe(8000.0)
        ->and($json['summaries'][0]['count'])->toBe(2)
        ->and($json['summaries'][0]['aggregation_function'])->toBe('sum')
        ->and((float) $json['summaries'][0]['primary_value'])->toBe(8000.0)
        ->and($json['summaries'][0]['canonical_unit'])->toBe('count')
        ->and($json['summaries'][0]['source_primary'])->toBe('Apple Watch');
});

it('offers each metric it returns as a daily dataset only while a reply collects data', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    foreach (['2026-11-16' => 6200, '2026-11-18' => 0] as $day => $steps) {
        HealthDailyAggregate::factory()->for($user)->stepCount()->create(['local_date' => $day, 'date' => $day, 'value_sum' => $steps]);
    }

    HealthDailyAggregate::factory()->for($user)->heartRate()->create(['local_date' => '2026-11-17', 'date' => '2026-11-17', 'value_avg' => 72.25]);

    $request = new Request(['type' => 'all', 'days' => 3, 'date' => '2026-11-18']);
    $datasets = resolve(TurnDataRegistry::class);

    $this->tool->handle($request);
    $outsideAReply = $datasets->datasets();
    $collected = $datasets->collect(function () use ($datasets, $request): array {
        $this->tool->handle($request);

        return $datasets->datasets();
    });

    expect($outsideAReply)->toBe([])
        ->and(array_map(fn (VisualDataset $dataset): array => [$dataset->label, $dataset->measure, $dataset->unit, $dataset->rows], $collected))->toBe([
            'step_count' => ['Steps', 'daily total', 'steps', [
                ['day' => '2026-11-16', 'value' => 6200],
                ['day' => '2026-11-17', 'value' => null],
                ['day' => '2026-11-18', 'value' => 0],
            ]],
            'heart_rate' => ['Heart Rate', 'daily average', 'bpm', [
                ['day' => '2026-11-16', 'value' => null],
                ['day' => '2026-11-17', 'value' => 72.3],
                ['day' => '2026-11-18', 'value' => null],
            ]],
        ]);
});

it("reports workout minutes, dose counts and glucose in the person's unit, and charts no event that is not a measurement", function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['units_preference' => GlucoseUnit::MmolL]);
    $this->actingAs($user);
    $day = CarbonImmutable::parse('2026-11-18');
    HealthSyncSample::factory()->for($user)->createMany([
        ['type_identifier' => 'workouts', 'value' => 30, 'unit' => 'min', 'measured_at' => $day->setTime(8, 0)],
        ['type_identifier' => 'workouts', 'value' => 30, 'unit' => 'min', 'measured_at' => $day->setTime(18, 0)],
        ['type_identifier' => 'medicationDoseEvent', 'value' => 2, 'unit' => 'tablet', 'measured_at' => $day->setTime(9, 0)],
        ['type_identifier' => 'medicationDoseEvent', 'value' => 2, 'unit' => 'tablet', 'measured_at' => $day->setTime(21, 0)],
        ['type_identifier' => 'menstrualFlow', 'value' => 2, 'unit' => 'event', 'measured_at' => $day->setTime(9, 0)],
    ]);
    HealthSyncSample::factory()->for($user)->bloodGlucose()->createMany([
        ['value' => 90, 'measured_at' => $day->setTime(7, 0)],
        ['value' => 108, 'measured_at' => $day->setTime(12, 0)],
        ['value' => 180, 'measured_at' => $day->setTime(19, 0)],
    ]);
    resolve(AggregateHealthDailySamplesAction::class)->handle($user, $day);
    $datasets = resolve(TurnDataRegistry::class);

    [$summaries, $charted] = $datasets->collect(function () use ($datasets): array {
        $summary = json_decode((string) $this->tool->handle(new Request(['type' => 'all', 'days' => 1, 'date' => '2026-11-18'])), true);

        return [array_column($summary['summaries'], null, 'type'), $datasets->datasets()];
    });

    expect(array_map(fn (VisualDataset $dataset): array => [$dataset->measure, $dataset->unit, $dataset->rows[0]['value']], $charted))->toEqual([
        'blood_glucose' => ['daily average', 'mmol/L', 7],
        'medication_dose_event' => ['daily total', 'dose', 2],
        'workouts' => ['daily total', 'min', 60],
    ]);
    expect($summaries['workouts'])->toMatchArray(['unit' => 'min', 'total' => 60, 'count' => 2, 'primary_value' => 60]);
    expect($summaries['medicationDoseEvent'])->toMatchArray(['unit' => 'dose', 'count' => 2, 'primary_value' => 2]);
    expect($summaries['bloodGlucose'])->toMatchArray(['unit' => 'mmol/L', 'avg' => 7, 'min' => 5, 'max' => 10, 'primary_value' => 7]);
});

it('returns heart rate with avg min max from aggregates', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $day = CarbonImmutable::parse('2026-04-10');

    HealthDailyAggregate::factory()->for($user)->heartRate()->create([
        'local_date' => $day,
        'date' => $day,
        'value_sum' => 240,
        'value_avg' => 80,
        'value_min' => 60,
        'value_max' => 100,
        'value_count' => 3,
    ]);

    $request = new Request(['type' => 'heart_rate', 'days' => 1, 'date' => $day->toDateString()]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['summaries'])->toHaveCount(1)
        ->and((float) $json['summaries'][0]['avg'])->toBe(80.0)
        ->and((float) $json['summaries'][0]['min'])->toBe(60.0)
        ->and((float) $json['summaries'][0]['max'])->toBe(100.0)
        ->and((float) $json['summaries'][0]['total'])->toBe(240.0)
        ->and((float) $json['summaries'][0]['primary_value'])->toBe(80.0)
        ->and($json['summaries'][0]['aggregation_function'])->toBe('avg');
});

it('filters by date range correctly', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $day = CarbonImmutable::parse('2026-04-10');

    HealthDailyAggregate::factory()->for($user)->stepCount(5000)->create([
        'local_date' => $day->subDays(2),
        'date' => $day->subDays(2),
    ]);

    HealthDailyAggregate::factory()->for($user)->stepCount(8000)->create([
        'local_date' => $day,
        'date' => $day,
    ]);

    HealthDailyAggregate::factory()->for($user)->stepCount(10000)->create([
        'local_date' => $day->subDays(10),
        'date' => $day->subDays(10),
    ]);

    $request = new Request(['type' => 'steps', 'days' => 3, 'date' => $day->toDateString()]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['summaries'])->toHaveCount(2);
});

it('returns empty result when no aggregate data exists', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $request = new Request(['type' => 'steps', 'days' => 7]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json)->toHaveKey('success', true)
        ->and($json['summaries'])->toBeEmpty();
});

it('returns all types when type is all', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $day = CarbonImmutable::parse('2026-04-10');

    HealthDailyAggregate::factory()->for($user)->stepCount(5000)->create([
        'local_date' => $day,
        'date' => $day,
    ]);

    HealthDailyAggregate::factory()->for($user)->bloodGlucose()->create([
        'local_date' => $day,
        'date' => $day,
    ]);

    $request = new Request(['type' => 'all', 'days' => 1, 'date' => $day->toDateString()]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    $types = array_column($json['summaries'], 'type');

    expect($types)->toContain('stepCount')
        ->and($types)->toContain('bloodGlucose');
});

it('returns primary value for each aggregation function', function (string $aggregationFunction, string $valueField, mixed $expectedValue): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $day = CarbonImmutable::parse('2026-04-10');

    HealthDailyAggregate::factory()->for($user)->create([
        'type_identifier' => 'testMetric',
        'local_date' => $day,
        'date' => $day,
        'aggregation_function' => $aggregationFunction,
        'value_sum' => 100.0,
        'value_avg' => 50.0,
        'value_min' => 10.0,
        'value_max' => 90.0,
        'value_last' => 75.0,
        'value_count' => 5,
    ]);

    $request = new Request(['type' => 'testMetric', 'days' => 1, 'date' => $day->toDateString()]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['summaries'])->toHaveCount(1)
        ->and((float) $json['summaries'][0]['primary_value'])->toBe($expectedValue);
})->with([
    'min' => ['min', 'value_min', 10.0],
    'max' => ['max', 'value_max', 90.0],
    'last' => ['last', 'value_last', 75.0],
    'count' => ['count', 'value_count', 5.0],
]);

it('delegates to model primaryValue when aggregation function is none', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $day = CarbonImmutable::parse('2026-04-10');

    HealthDailyAggregate::factory()->for($user)->stepCount(5000)->create([
        'local_date' => $day,
        'date' => $day,
        'aggregation_function' => 'none',
    ]);

    $request = new Request(['type' => 'stepCount', 'days' => 1, 'date' => $day->toDateString()]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['summaries'])->toHaveCount(1)
        ->and($json['summaries'][0]['aggregation_function'])->toBe('none');
});

it('delegates to model primaryValue when aggregation function is null', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $day = CarbonImmutable::parse('2026-04-10');

    HealthDailyAggregate::factory()->for($user)->heartRate()->create([
        'local_date' => $day,
        'date' => $day,
        'aggregation_function' => null,
    ]);

    $request = new Request(['type' => 'heartRate', 'days' => 1, 'date' => $day->toDateString()]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['summaries'])->toHaveCount(1)
        ->and($json['summaries'][0]['primary_value'])->not->toBeNull();
});

it('returns null primary value when value field is null', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $day = CarbonImmutable::parse('2026-04-10');

    HealthDailyAggregate::factory()->for($user)->create([
        'type_identifier' => 'testMetric',
        'local_date' => $day,
        'date' => $day,
        'aggregation_function' => 'min',
        'value_min' => null,
    ]);

    $request = new Request(['type' => 'testMetric', 'days' => 1, 'date' => $day->toDateString()]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['summaries'])->toHaveCount(1)
        ->and($json['summaries'][0]['primary_value'])->toBeNull();
});

it('does not return data from other users', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);
    $day = CarbonImmutable::parse('2026-04-10');

    HealthDailyAggregate::factory()->for($otherUser)->stepCount(9000)->create([
        'local_date' => $day,
        'date' => $day,
    ]);

    $request = new Request(['type' => 'steps', 'days' => 1, 'date' => $day->toDateString()]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['summaries'])->toBeEmpty();
});

it('defaults to 7 days', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $day = CarbonImmutable::parse('2026-04-10');

    HealthDailyAggregate::factory()->for($user)->stepCount(5000)->create([
        'local_date' => $day->subDays(5),
        'date' => $day->subDays(5),
    ]);

    $request = new Request(['type' => 'steps', 'date' => $day->toDateString()]);
    $result = $this->tool->handle($request);
    $json = json_decode((string) $result, true);

    expect($json['summaries'])->toHaveCount(1)
        ->and($json['date_range']['from'])->toBe($day->subDays(6)->toDateString());
});

it('surfaces dietary nutrients under the food category filter', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $day = CarbonImmutable::parse('2026-04-10');

    foreach (['dietaryEnergy', 'vitaminD', 'fiber', 'sodium'] as $nutrient) {
        HealthDailyAggregate::factory()->for($user)->create([
            'type_identifier' => $nutrient,
            'local_date' => $day,
            'date' => $day,
            'aggregation_function' => 'sum',
            'value_sum' => 12.0,
            'value_count' => 1,
        ]);
    }

    HealthDailyAggregate::factory()->for($user)->stepCount()->create([
        'local_date' => $day,
        'date' => $day,
    ]);

    $request = new Request(['type' => 'food', 'days' => 1, 'date' => $day->toDateString()]);
    $json = json_decode((string) $this->tool->handle($request), true);
    $types = array_column($json['summaries'], 'type');

    expect($types)
        ->toContain('dietaryEnergy')
        ->toContain('vitaminD')
        ->toContain('fiber')
        ->toContain('sodium')
        ->not->toContain('stepCount');
});
