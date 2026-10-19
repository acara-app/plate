<?php

declare(strict_types=1);

use App\Actions\RecordDailyCheckInAction;
use App\Enums\DailyCheckInMetric;
use App\Enums\HealthEntrySource;
use App\Http\Controllers\HealthEntry\StoreDailyCheckInController;
use App\Http\Requests\StoreDailyCheckInRequest;
use App\Jobs\AggregateUserDayJob;
use App\Models\HealthDailyAggregate;
use App\Models\HealthSyncSample;
use App\Models\SleepSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;

covers(StoreDailyCheckInController::class, StoreDailyCheckInRequest::class, RecordDailyCheckInAction::class, DailyCheckInMetric::class);

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 17:00:00', 'UTC'));
    $this->user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
});

/**
 * @return array<string, array{float, string, string, HealthEntrySource|null}>
 */
function storedCheckIns(User $user): array
{
    return HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->orderBy('type_identifier')
        ->get()
        ->mapWithKeys(fn (HealthSyncSample $sample): array => [$sample->type_identifier => [
            $sample->value,
            $sample->unit,
            $sample->measured_at->utc()->toDateTimeString(),
            $sample->entry_source,
        ]])
        ->all();
}

it('requires authentication to check in', function (): void {
    $this->post(route('health-entries.check-in.store'), ['date' => '2026-10-19', 'stress_level' => 1])
        ->assertRedirectToRoute('login');
});

it('saves sleep, sleep quality and stress for the chosen local day', function (): void {
    Queue::fake();
    $logbook = route('health-entries.index', ['date' => '2026-10-19']);

    $this->actingAs($this->user)
        ->from($logbook)
        ->post(route('health-entries.check-in.store'), [
            'date' => '2026-10-19',
            'sleep_hours' => 7.5,
            'sleep_quality' => 3,
            'stress_level' => 1,
        ])
        ->assertRedirect($logbook);

    expect(storedCheckIns($this->user))->toBe([
        'selfReportedSleep' => [7.5, 'hours', '2026-10-18 16:00:00', HealthEntrySource::Web],
        'sleepQuality' => [3.0, 'score', '2026-10-18 16:00:00', HealthEntrySource::Web],
        'stressLevel' => [1.0, 'score', '2026-10-18 16:00:00', HealthEntrySource::Web],
    ]);

    Queue::assertPushed(AggregateUserDayJob::class, fn (AggregateUserDayJob $job): bool => $job->uniqueId() === $this->user->id.':2026-10-18');
});

it('updates the day’s answers and keeps the ones skipped this time', function (): void {
    $this->actingAs($this->user)
        ->post(route('health-entries.check-in.store'), ['date' => '2026-10-19', 'sleep_hours' => 6, 'sleep_quality' => 1, 'stress_level' => 3]);

    $this->actingAs($this->user)
        ->post(route('health-entries.check-in.store'), ['date' => '2026-10-19', 'sleep_hours' => null, 'sleep_quality' => 3, 'stress_level' => null])
        ->assertSessionHasNoErrors();

    expect(storedCheckIns($this->user))->toBe([
        'selfReportedSleep' => [6.0, 'hours', '2026-10-18 16:00:00', HealthEntrySource::Web],
        'sleepQuality' => [3.0, 'score', '2026-10-18 16:00:00', HealthEntrySource::Web],
        'stressLevel' => [3.0, 'score', '2026-10-18 16:00:00', HealthEntrySource::Web],
    ]);
});

it('keeps logged sleep in the daily totals when a synced nap lands on the same day', function (): void {
    $user = User::factory()->create(['timezone' => 'UTC']);
    SleepSession::factory()->create([
        'user_id' => $user->id,
        'stage' => SleepSession::STAGE_ASLEEP_CORE,
        'started_at' => '2026-10-18 14:00:00',
        'ended_at' => '2026-10-18 15:00:00',
    ]);

    $this->actingAs($user)
        ->post(route('health-entries.check-in.store'), ['date' => '2026-10-18', 'sleep_hours' => 7.5])
        ->assertSessionHasNoErrors();

    expect(HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where(HealthDailyAggregate::UTC_DAY_COLUMN, '2026-10-18')
        ->whereIn('type_identifier', ['selfReportedSleep', 'timeAsleep'])
        ->orderBy('type_identifier')
        ->pluck('value_last', 'type_identifier')
        ->all())->toBe(['selfReportedSleep' => 7.5, 'timeAsleep' => 1.0]);
});

/**
 * @return list<array{string, string, float}>
 */
function checkInTotals(User $user): array
{
    return HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->whereIn('type_identifier', array_column(DailyCheckInMetric::cases(), 'value'))
        ->orderBy('type_identifier')
        ->get()
        ->map(fn (HealthDailyAggregate $aggregate): array => [
            $aggregate->type_identifier,
            (string) $aggregate->local_date?->toDateString(),
            (float) $aggregate->value_last,
        ])
        ->all();
}

it('keeps one answer per question for the day after the timezone changes', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-19 12:00:00', 'UTC'));
    $user = User::factory()->create(['timezone' => 'UTC']);

    $this->actingAs($user)
        ->post(route('health-entries.check-in.store'), ['date' => '2026-10-19', 'sleep_hours' => 6, 'sleep_quality' => 1, 'stress_level' => 3])
        ->assertSessionHasNoErrors();

    $user->update(['timezone' => 'Asia/Ulaanbaatar']);

    $this->post(route('health-entries.check-in.store'), ['date' => '2026-10-19', 'sleep_quality' => 3])
        ->assertSessionHasNoErrors();

    expect(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(3)
        ->and(storedCheckIns($user))->toBe([
            'selfReportedSleep' => [6.0, 'hours', '2026-10-18 16:00:00', HealthEntrySource::Web],
            'sleepQuality' => [3.0, 'score', '2026-10-18 16:00:00', HealthEntrySource::Web],
            'stressLevel' => [3.0, 'score', '2026-10-18 16:00:00', HealthEntrySource::Web],
        ])
        ->and(checkInTotals($user))->toBe([
            ['selfReportedSleep', '2026-10-18', 6.0],
            ['sleepQuality', '2026-10-18', 3.0],
            ['stressLevel', '2026-10-18', 3.0],
        ]);

    $this->get(route('health-entries.index', ['date' => '2026-10-19']))
        ->assertInertia(fn ($page) => $page
            ->where('wellness.sleep_hours', 6)
            ->where('wellness.sleep_quality', 3)
            ->where('wellness.stress_level', 3));
});

it('shows the most recently saved answer when a day already holds duplicates', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-19 12:00:00', 'UTC'));
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
    $stale = HealthSyncSample::factory()->fromWeb()->create([
        'user_id' => $user->id, 'type_identifier' => 'sleepQuality', 'value' => 1, 'unit' => 'score', 'measured_at' => '2026-10-19 00:00:00',
    ]);
    $stale->forceFill(['updated_at' => '2026-10-19 08:00:00'])->save();
    $latest = HealthSyncSample::factory()->fromWeb()->create([
        'user_id' => $user->id, 'type_identifier' => 'sleepQuality', 'value' => 3, 'unit' => 'score', 'measured_at' => '2026-10-18 16:00:00',
    ]);
    $latest->forceFill(['updated_at' => '2026-10-19 10:00:00'])->save();

    $this->actingAs($user)
        ->get(route('health-entries.index', ['date' => '2026-10-19']))
        ->assertInertia(fn ($page) => $page->where('wellness.sleep_quality', 3));

    $this->post(route('health-entries.check-in.store'), ['date' => '2026-10-19', 'stress_level' => 2])
        ->assertSessionHasNoErrors();

    expect(storedCheckIns($user))->toBe([
        'sleepQuality' => [3.0, 'score', '2026-10-18 16:00:00', HealthEntrySource::Web],
        'stressLevel' => [2.0, 'score', '2026-10-18 16:00:00', HealthEntrySource::Web],
    ])->and(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(2);
});

it('leaves other days and synced data alone when saving a check-in', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-19 12:00:00', 'UTC'));
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
    $yesterday = HealthSyncSample::factory()->fromWeb()->create([
        'user_id' => $user->id, 'type_identifier' => 'stressLevel', 'value' => 3, 'unit' => 'score', 'measured_at' => '2026-10-17 16:00:00',
    ]);
    $synced = HealthSyncSample::factory()->fromMobileSync()->create([
        'user_id' => $user->id, 'type_identifier' => 'sleepQuality', 'value' => 2, 'unit' => 'score', 'measured_at' => '2026-10-18 20:00:00',
    ]);

    $this->actingAs($user)
        ->post(route('health-entries.check-in.store'), ['date' => '2026-10-19', 'stress_level' => 1])
        ->assertSessionHasNoErrors();

    expect($yesterday->fresh()?->value)->toBe(3.0)
        ->and($synced->fresh()?->value)->toBe(2.0);
});

it('rejects check-ins it cannot save', function (array $payload, string $field): void {
    $this->actingAs($this->user)
        ->post(route('health-entries.check-in.store'), $payload)
        ->assertSessionHasErrors($field);

    expect(storedCheckIns($this->user))->toBe([]);
})->with([
    'nothing answered' => [['date' => '2026-10-19'], 'sleep_hours'],
    'a day that has not started yet' => [['date' => '2026-10-20', 'stress_level' => 2], 'date'],
    'more hours than a day has' => [['date' => '2026-10-19', 'sleep_hours' => 25], 'sleep_hours'],
    'an unknown stress level' => [['date' => '2026-10-19', 'stress_level' => 4], 'stress_level'],
    'an unknown sleep quality' => [['date' => '2026-10-19', 'sleep_quality' => 0], 'sleep_quality'],
]);
