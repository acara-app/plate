<?php

declare(strict_types=1);

use App\Actions\AggregateHealthDailySamplesAction;
use App\Enums\HealthAggregationFunction;
use App\Enums\HealthEntrySource;
use App\Models\HealthDailyAggregate;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->date = CarbonImmutable::parse('2026-04-05');
    $this->action = resolve(AggregateHealthDailySamplesAction::class);
});

it('sums cumulative step count with Watch winning overlapping 5-minute windows and iPhone filling gaps', function (): void {
    HealthSyncSample::factory()->for($this->user)->stepCount()->create([
        'value' => 1090,
        'source' => "Tuvshinjargal's Apple Watch",
        'measured_at' => $this->date->copy()->setTime(2, 8, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);
    HealthSyncSample::factory()->for($this->user)->stepCount()->create([
        'value' => 666,
        'source' => "Tuvshinjargal's iPhone",
        'measured_at' => $this->date->copy()->setTime(2, 9, 30),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    HealthSyncSample::factory()->for($this->user)->stepCount()->create([
        'value' => 500,
        'source' => "Tuvshinjargal's Apple Watch",
        'measured_at' => $this->date->copy()->setTime(10, 0, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    HealthSyncSample::factory()->for($this->user)->stepCount()->create([
        'value' => 300,
        'source' => "Tuvshinjargal's iPhone",
        'measured_at' => $this->date->copy()->setTime(14, 30, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->action->handle($this->user, $this->date);

    $aggregate = HealthDailyAggregate::query()
        ->where('user_id', $this->user->id)
        ->where('type_identifier', 'stepCount')
        ->where('local_date', $this->date->toDateString())
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and((float) $aggregate->value_sum)->toBe(1890.0)
        ->and((float) $aggregate->value_sum_canonical)->toBe(1890.0)
        ->and($aggregate->value_count)->toBe(3)
        ->and($aggregate->aggregation_function)->toBe(HealthAggregationFunction::Sum->value);
});

it('falls back to iPhone when Watch has no data at all for cumulative metrics', function (): void {
    HealthSyncSample::factory()->for($this->user)->stepCount()->create([
        'value' => 300,
        'source' => "Tuvshinjargal's iPhone",
        'measured_at' => $this->date->copy()->setTime(1, 0, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->action->handle($this->user, $this->date);

    $aggregate = HealthDailyAggregate::query()->where('user_id', $this->user->id)
        ->where('type_identifier', 'stepCount')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and((float) $aggregate->value_sum)->toBe(300.0)
        ->and($aggregate->source_primary)->toBe("Tuvshinjargal's iPhone");
});

it('averages heart rate across all sources without interval dedup', function (): void {
    $rates = [72, 75, 68, 80, 71];

    foreach ($rates as $i => $val) {
        HealthSyncSample::factory()->for($this->user)->heartRate()->create([
            'value' => $val,
            'source' => $i < 3 ? "Tuvshinjargal's Apple Watch" : 'Bluetooth Device',
            'measured_at' => $this->date->copy()->setTime($i + 1, 0, 0),
            'entry_source' => HealthEntrySource::MobileSync,
        ]);
    }

    $this->action->handle($this->user, $this->date);

    $aggregate = HealthDailyAggregate::query()->where('user_id', $this->user->id)
        ->where('type_identifier', 'heartRate')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and((float) $aggregate->value_avg)->toBe(73.2)
        ->and((float) $aggregate->value_min)->toBe(68.0)
        ->and((float) $aggregate->value_max)->toBe(80.0)
        ->and($aggregate->value_count)->toBe(5)
        ->and($aggregate->aggregation_function)->toBe(HealthAggregationFunction::Avg->value);
});

it('stores value_sum and value_sum_canonical for slow-changing glucose so weighted averages work across days', function (): void {
    HealthSyncSample::factory()->for($this->user)->bloodGlucose()->create([
        'value' => 95,
        'unit' => 'mg/dL',
        'measured_at' => $this->date->copy()->setTime(8, 0, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    HealthSyncSample::factory()->for($this->user)->bloodGlucose()->create([
        'value' => 140,
        'unit' => 'mg/dL',
        'measured_at' => $this->date->copy()->setTime(14, 0, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->action->handle($this->user, $this->date);

    $aggregate = HealthDailyAggregate::query()->where('user_id', $this->user->id)
        ->where('type_identifier', 'bloodGlucose')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and((float) $aggregate->value_last)->toBe(140.0)
        ->and((float) $aggregate->value_avg)->toBe(117.5)
        ->and((float) $aggregate->value_min)->toBe(95.0)
        ->and((float) $aggregate->value_max)->toBe(140.0)
        ->and((float) $aggregate->value_sum)->toBe(235.0)
        ->and((float) $aggregate->value_sum_canonical)->toBe(235.0)
        ->and($aggregate->aggregation_function)->toBe(HealthAggregationFunction::WeightedAvg->value);
});

it('counts medication event metrics and preserves per-event metadata', function (): void {
    HealthSyncSample::factory()->for($this->user)->medication()->create([
        'measured_at' => $this->date->copy()->setTime(8, 0, 0),
        'entry_source' => HealthEntrySource::MobileSync,
        'metadata' => ['medication_name' => 'Metformin', 'medication_dosage' => '500mg'],
    ]);

    HealthSyncSample::factory()->for($this->user)->medication()->create([
        'measured_at' => $this->date->copy()->setTime(20, 0, 0),
        'entry_source' => HealthEntrySource::MobileSync,
        'metadata' => ['medication_name' => 'Metformin', 'medication_dosage' => '500mg'],
    ]);

    $this->action->handle($this->user, $this->date);

    $aggregate = HealthDailyAggregate::query()->where('user_id', $this->user->id)
        ->where('type_identifier', 'medication')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and($aggregate->value_count)->toBe(2)
        ->and($aggregate->aggregation_function)->toBe(HealthAggregationFunction::Count->value)
        ->and($aggregate->metadata)->toHaveCount(2);
});

it('stores weight aggregates with value_last and value_sum_canonical populated', function (): void {
    HealthSyncSample::factory()->for($this->user)->weight()->create([
        'value' => 72.5,
        'unit' => 'kg',
        'measured_at' => $this->date->copy()->setTime(8, 0, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->action->handle($this->user, $this->date);

    $aggregate = HealthDailyAggregate::query()->where('user_id', $this->user->id)
        ->where('type_identifier', 'weight')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and((float) $aggregate->value_last)->toBe(72.5)
        ->and((float) $aggregate->value_sum_canonical)->toBe(72.5);
});

it('returns zero when user has no samples for a date', function (): void {
    $result = $this->action->handle($this->user, $this->date);

    expect($result)->toBe(0);
    expect(HealthDailyAggregate::query()->where('user_id', $this->user->id)->count())->toBe(0);
});

it('upserts existing aggregates idempotently on re-run', function (): void {
    HealthSyncSample::factory()->for($this->user)->heartRate()->create([
        'value' => 72,
        'measured_at' => $this->date->copy()->setTime(1, 0, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->action->handle($this->user, $this->date);

    HealthSyncSample::factory()->for($this->user)->heartRate()->create([
        'value' => 80,
        'measured_at' => $this->date->copy()->setTime(2, 0, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->action->handle($this->user, $this->date);

    $aggregates = HealthDailyAggregate::query()->where('user_id', $this->user->id)
        ->where('type_identifier', 'heartRate')
        ->get();

    expect($aggregates)->toHaveCount(1)
        ->and($aggregates->first())->not->toBeNull()
        ->and($aggregates->first()->value_count)->toBe(2)
        ->and((float) $aggregates->first()->value_avg)->toBe(76.0);
});

it('handles date range aggregation', function (): void {
    $date1 = CarbonImmutable::parse('2026-04-01');
    $date2 = CarbonImmutable::parse('2026-04-02');

    HealthSyncSample::factory()->for($this->user)->stepCount()->create([
        'value' => 5000,
        'source' => "Tuvshinjargal's Apple Watch",
        'measured_at' => $date1->copy()->setTime(1, 0, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    HealthSyncSample::factory()->for($this->user)->stepCount()->create([
        'value' => 7000,
        'source' => "Tuvshinjargal's Apple Watch",
        'measured_at' => $date2->copy()->setTime(1, 0, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $total = $this->action->handleDateRange($this->user, $date1, $date2);

    expect($total)->toBe(2);

    $agg1 = HealthDailyAggregate::query()->where('local_date', $date1->toDateString())->first();
    $agg2 = HealthDailyAggregate::query()->where('local_date', $date2->toDateString())->first();

    expect($agg1)->not->toBeNull()
        ->and((float) $agg1->value_sum)->toBe(5000.0)
        ->and($agg2)->not->toBeNull()
        ->and((float) $agg2->value_sum)->toBe(7000.0);
});

it('stores unknown type identifiers with count only and no bogus averages', function (): void {
    HealthSyncSample::factory()->for($this->user)->create([
        'type_identifier' => 'someBrandNewHealthKitTypeWeveNeverSeen',
        'value' => 42.0,
        'unit' => 'units',
        'measured_at' => $this->date->copy()->setTime(1, 0, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->action->handle($this->user, $this->date);

    $aggregate = HealthDailyAggregate::query()->where('user_id', $this->user->id)
        ->where('type_identifier', 'someBrandNewHealthKitTypeWeveNeverSeen')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and($aggregate->value_count)->toBe(1)
        ->and($aggregate->value_avg)->toBeNull()
        ->and($aggregate->value_sum)->toBeNull()
        ->and($aggregate->aggregation_function)->toBe(HealthAggregationFunction::None->value);
});

it('does not mix data across users', function (): void {
    $otherUser = User::factory()->create();

    HealthSyncSample::factory()->for($this->user)->heartRate()->create([
        'value' => 72,
        'measured_at' => $this->date->copy()->setTime(1, 0, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->action->handle($this->user, $this->date);

    expect(HealthDailyAggregate::query()->where('user_id', $otherUser->id)->count())->toBe(0);
});

it('does not mix data across dates', function (): void {
    $yesterday = $this->date->subDay();

    HealthSyncSample::factory()->for($this->user)->heartRate()->create([
        'value' => 72,
        'measured_at' => $yesterday->copy()->setTime(1, 0, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    HealthSyncSample::factory()->for($this->user)->heartRate()->create([
        'value' => 80,
        'measured_at' => $this->date->copy()->setTime(1, 0, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->action->handle($this->user, $yesterday);
    $this->action->handle($this->user, $this->date);

    $aggYesterday = HealthDailyAggregate::query()->where('local_date', $yesterday->toDateString())->first();
    $aggToday = HealthDailyAggregate::query()->where('local_date', $this->date->toDateString())->first();

    expect($aggYesterday)->not->toBeNull()
        ->and((float) $aggYesterday->value_avg)->toBe(72.0)
        ->and($aggToday)->not->toBeNull()
        ->and((float) $aggToday->value_avg)->toBe(80.0);
});
