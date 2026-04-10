<?php

declare(strict_types=1);

use App\Actions\AggregateHealthDailySamplesAction;
use App\Enums\HealthEntrySource;
use App\Models\HealthDailyAggregate;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonImmutable;

covers(AggregateHealthDailySamplesAction::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->action = resolve(AggregateHealthDailySamplesAction::class);
    $this->date = CarbonImmutable::parse('2026-04-05');
});

it('prefers Apple Watch over iPhone within the same 5-minute window', function (): void {
    HealthSyncSample::factory()->for($this->user)->stepCount()->create([
        'value' => 1090,
        'source' => "Tuvshinjargal's Apple Watch",
        'measured_at' => $this->date->copy()->setTime(1, 1, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    HealthSyncSample::factory()->for($this->user)->stepCount()->create([
        'value' => 666,
        'source' => "Tuvshinjargal's iPhone",
        'measured_at' => $this->date->copy()->setTime(1, 3, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->action->handle($this->user, $this->date);

    $aggregate = HealthDailyAggregate::query()
        ->where('user_id', $this->user->id)
        ->where('type_identifier', 'stepCount')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and((float) $aggregate->value_sum)->toBe(1090.0)
        ->and($aggregate->source_primary)->toBe("Tuvshinjargal's Apple Watch");
});

it('includes iPhone samples in windows where the Watch has no data', function (): void {
    foreach (range(0, 10) as $hour) {
        HealthSyncSample::factory()->for($this->user)->stepCount()->create([
            'value' => 50,
            'source' => "Tuvshinjargal's Apple Watch",
            'measured_at' => $this->date->copy()->setTime($hour, 0, 0),
            'entry_source' => HealthEntrySource::MobileSync,
        ]);
    }

    foreach (range(12, 15) as $hour) {
        HealthSyncSample::factory()->for($this->user)->stepCount()->create([
            'value' => 80,
            'source' => "Tuvshinjargal's iPhone",
            'measured_at' => $this->date->copy()->setTime($hour, 0, 0),
            'entry_source' => HealthEntrySource::MobileSync,
        ]);
    }

    $this->action->handle($this->user, $this->date);

    $aggregate = HealthDailyAggregate::query()
        ->where('user_id', $this->user->id)
        ->where('type_identifier', 'stepCount')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and((float) $aggregate->value_sum)->toBe(870.0)
        ->and($aggregate->value_count)->toBe(15);
});

it('aggregates heart rate from all sources (no interval dedup for instantaneous metrics)', function (): void {
    HealthSyncSample::factory()->for($this->user)->heartRate()->create([
        'value' => 70,
        'source' => "Tuvshinjargal's Apple Watch",
        'measured_at' => $this->date->copy()->setTime(1, 0, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    HealthSyncSample::factory()->for($this->user)->heartRate()->create([
        'value' => 80,
        'source' => "Tuvshinjargal's iPhone",
        'measured_at' => $this->date->copy()->setTime(1, 2, 0),
        'entry_source' => HealthEntrySource::MobileSync,
    ]);

    $this->action->handle($this->user, $this->date);

    $aggregate = HealthDailyAggregate::query()
        ->where('user_id', $this->user->id)
        ->where('type_identifier', 'heartRate')
        ->first();

    expect($aggregate)->not->toBeNull()
        ->and($aggregate->value_count)->toBe(2)
        ->and((float) $aggregate->value_avg)->toBe(75.0);
});
