<?php

declare(strict_types=1);

use App\Actions\RecordDailyCheckInAction;
use App\Enums\DailyCheckInMetric;
use App\Enums\HealthEntrySource;
use App\Http\Controllers\HealthEntry\StoreDailyCheckInController;
use App\Http\Requests\StoreDailyCheckInRequest;
use App\Jobs\AggregateUserDayJob;
use App\Models\HealthSyncSample;
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
        'sleepQuality' => [3.0, 'score', '2026-10-18 16:00:00', HealthEntrySource::Web],
        'stressLevel' => [1.0, 'score', '2026-10-18 16:00:00', HealthEntrySource::Web],
        'timeAsleep' => [7.5, 'hours', '2026-10-18 16:00:00', HealthEntrySource::Web],
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
        'sleepQuality' => [3.0, 'score', '2026-10-18 16:00:00', HealthEntrySource::Web],
        'stressLevel' => [3.0, 'score', '2026-10-18 16:00:00', HealthEntrySource::Web],
        'timeAsleep' => [6.0, 'hours', '2026-10-18 16:00:00', HealthEntrySource::Web],
    ]);
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
