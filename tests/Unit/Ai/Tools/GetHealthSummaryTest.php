<?php

declare(strict_types=1);

use App\Ai\Tools\GetHealthSummary;
use App\Models\HealthDailyAggregate;
use App\Models\User;
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
