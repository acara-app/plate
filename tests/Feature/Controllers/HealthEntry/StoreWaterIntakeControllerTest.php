<?php

declare(strict_types=1);

use App\Actions\RecordWaterIntakeAction;
use App\Enums\HealthEntrySource;
use App\Http\Controllers\HealthEntry\StoreWaterIntakeController;
use App\Http\Requests\StoreWaterIntakeRequest;
use App\Jobs\AggregateUserDayJob;
use App\Models\HealthDailyAggregate;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;

covers(StoreWaterIntakeController::class, StoreWaterIntakeRequest::class, RecordWaterIntakeAction::class);

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 17:00:00', 'UTC'));
    $this->user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
});

/**
 * @return list<array{float, string, string, HealthEntrySource|null}>
 */
function storedWater(User $user): array
{
    return HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'water')
        ->get()
        ->map(fn (HealthSyncSample $sample): array => [
            $sample->value,
            $sample->unit,
            $sample->measured_at->utc()->toDateTimeString(),
            $sample->entry_source,
        ])
        ->all();
}

/**
 * @return array<string, float|null>
 */
function waterTotals(User $user): array
{
    return HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'water')
        ->oldest(HealthDailyAggregate::UTC_DAY_COLUMN)
        ->get()
        ->mapWithKeys(fn (HealthDailyAggregate $aggregate): array => [(string) $aggregate->local_date?->toDateString() => $aggregate->value_sum])
        ->all();
}

it('requires authentication to log water', function (): void {
    $this->post(route('health-entries.water.store'), ['date' => '2026-10-19', 'amount_ml' => 250])
        ->assertRedirectToRoute('login');
});

it('saves the water drunk on the chosen local day', function (): void {
    Queue::fake();
    $logbook = route('health-entries.index', ['date' => '2026-10-19']);

    $this->actingAs($this->user)
        ->from($logbook)
        ->post(route('health-entries.water.store'), ['date' => '2026-10-19', 'amount_ml' => 750])
        ->assertRedirect($logbook)
        ->assertSessionMissing('success');

    expect(storedWater($this->user))->toBe([[750.0, 'mL', '2026-10-18 16:00:00', HealthEntrySource::Web]]);

    Queue::assertPushed(AggregateUserDayJob::class, fn (AggregateUserDayJob $job): bool => $job->uniqueId() === $this->user->id.':2026-10-18');
});

it('replaces the day’s amount when another cup is tapped and clears it when every cup is emptied', function (): void {
    $this->actingAs($this->user)
        ->post(route('health-entries.water.store'), ['date' => '2026-10-19', 'amount_ml' => 1000]);

    $this->actingAs($this->user)
        ->post(route('health-entries.water.store'), ['date' => '2026-10-19', 'amount_ml' => 1250])
        ->assertSessionHasNoErrors();

    expect(storedWater($this->user))->toBe([[1250.0, 'mL', '2026-10-18 16:00:00', HealthEntrySource::Web]]);

    $this->actingAs($this->user)
        ->post(route('health-entries.water.store'), ['date' => '2026-10-19', 'amount_ml' => 0])
        ->assertSessionHasNoErrors();

    expect(storedWater($this->user))->toBe([]);
});

it('counts logged water in the day’s totals next to water synced from Apple Health', function (): void {
    $user = User::factory()->create(['timezone' => 'UTC']);
    HealthSyncSample::factory()->fromMobileSync()->create([
        'user_id' => $user->id,
        'type_identifier' => 'water',
        'value' => 300,
        'unit' => 'mL',
        'measured_at' => '2026-10-18 09:00:00',
    ]);

    $this->actingAs($user)
        ->post(route('health-entries.water.store'), ['date' => '2026-10-18', 'amount_ml' => 500])
        ->assertSessionHasNoErrors();

    expect(HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where(HealthDailyAggregate::UTC_DAY_COLUMN, '2026-10-18')
        ->where('type_identifier', 'water')
        ->value('value_sum'))->toEqual(800.0);
});

it('forgets the day’s logged water total once every cup is emptied', function (?float $syncedMl, array $totals): void {
    $user = User::factory()->create(['timezone' => 'UTC']);

    if ($syncedMl !== null) {
        HealthSyncSample::factory()->fromMobileSync()->create([
            'user_id' => $user->id,
            'type_identifier' => 'water',
            'value' => $syncedMl,
            'unit' => 'mL',
            'measured_at' => '2026-10-18 09:00:00',
        ]);
    }

    $this->actingAs($user)
        ->post(route('health-entries.water.store'), ['date' => '2026-10-18', 'amount_ml' => 250]);

    $this->actingAs($user)
        ->post(route('health-entries.water.store'), ['date' => '2026-10-18', 'amount_ml' => 0])
        ->assertSessionHasNoErrors();

    expect(waterTotals($user))->toBe($totals);
})->with([
    'nothing synced' => [null, []],
    'water synced from Apple Health' => [300.0, ['2026-10-18' => 300.0]],
]);

it('keeps a single logged amount for the day after the timezone changes', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-19 12:00:00', 'UTC'));
    $user = User::factory()->create(['timezone' => 'UTC']);

    $this->actingAs($user)
        ->post(route('health-entries.water.store'), ['date' => '2026-10-19', 'amount_ml' => 500]);

    $user->update(['timezone' => 'Asia/Ulaanbaatar']);

    $this->actingAs($user)
        ->post(route('health-entries.water.store'), ['date' => '2026-10-19', 'amount_ml' => 750])
        ->assertSessionHasNoErrors();

    expect(storedWater($user))->toBe([[750.0, 'mL', '2026-10-18 16:00:00', HealthEntrySource::Web]])
        ->and(waterTotals($user))->toBe(['2026-10-18' => 750.0]);

    $this->actingAs($user)
        ->post(route('health-entries.water.store'), ['date' => '2026-10-19', 'amount_ml' => 0])
        ->assertSessionHasNoErrors();

    expect(storedWater($user))->toBe([])
        ->and(waterTotals($user))->toBe([]);
});

it('rejects water it cannot save', function (array $payload, string $field): void {
    $this->actingAs($this->user)
        ->post(route('health-entries.water.store'), $payload)
        ->assertSessionHasErrors($field);

    expect(storedWater($this->user))->toBe([]);
})->with([
    'no amount' => [['date' => '2026-10-19'], 'amount_ml'],
    'a day that has not started yet' => [['date' => '2026-10-20', 'amount_ml' => 250], 'date'],
    'a negative amount' => [['date' => '2026-10-19', 'amount_ml' => -250], 'amount_ml'],
    'more than five litres' => [['date' => '2026-10-19', 'amount_ml' => 5250], 'amount_ml'],
    'a fraction of a millilitre' => [['date' => '2026-10-19', 'amount_ml' => 250.5], 'amount_ml'],
]);
