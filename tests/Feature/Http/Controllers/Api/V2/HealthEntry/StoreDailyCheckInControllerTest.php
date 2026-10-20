<?php

declare(strict_types=1);

use App\Enums\HealthEntrySource;
use App\Http\Controllers\Api\V2\HealthEntry\StoreDailyCheckInController;
use App\Jobs\AggregateUserDayJob;
use App\Models\HealthSyncSample;
use App\Models\SleepSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

covers(StoreDailyCheckInController::class);

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 17:00:00', 'UTC'));
    $this->user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
});

it('requires a bearer token', function (): void {
    $this->postJson(route('api.v2.health-entries.check-in.store'), ['date' => '2026-10-19', 'stress_level' => 1])->assertUnauthorized();
});

it('saves sleep, sleep quality and stress for the chosen local day', function (): void {
    Queue::fake();
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.health-entries.check-in.store'), ['date' => '2026-10-19', 'sleep_hours' => 7.5, 'sleep_quality' => 3, 'stress_level' => 1])
        ->assertOk()
        ->assertJsonPath('wellness.sleep_hours', 7.5)
        ->assertJsonPath('wellness.sleep_synced', false)
        ->assertJsonPath('wellness.sleep_quality', 3)
        ->assertJsonPath('wellness.stress_level', 1);

    expect(HealthSyncSample::query()->where('user_id', $this->user->id)->pluck('entry_source')->unique()->all())->toBe([HealthEntrySource::Web]);
    Queue::assertPushed(AggregateUserDayJob::class, fn (AggregateUserDayJob $job): bool => $job->uniqueId() === $this->user->id.':2026-10-18');
});

it('keeps the answers skipped this time', function (): void {
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.health-entries.check-in.store'), ['date' => '2026-10-19', 'sleep_hours' => 6, 'sleep_quality' => 1, 'stress_level' => 3])->assertOk();

    $this->postJson(route('api.v2.health-entries.check-in.store'), ['date' => '2026-10-19', 'sleep_quality' => 3])
        ->assertOk()
        ->assertJsonPath('wellness.sleep_hours', 6)
        ->assertJsonPath('wellness.sleep_quality', 3)
        ->assertJsonPath('wellness.stress_level', 3);
});

it('prefers sleep synced from Apple Health over logged hours', function (): void {
    SleepSession::factory()->create([
        'user_id' => $this->user->id,
        'stage' => SleepSession::STAGE_ASLEEP_CORE,
        'started_at' => '2026-10-18 15:00:00',
        'ended_at' => '2026-10-18 23:00:00',
    ]);
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.health-entries.check-in.store'), ['date' => '2026-10-19', 'sleep_quality' => 2])
        ->assertOk()
        ->assertJsonPath('wellness.sleep_hours', 8)
        ->assertJsonPath('wellness.sleep_synced', true)
        ->assertJsonPath('wellness.sleep_quality', 2);
});

it('keeps one answer per question after the app moves the account to the device timezone', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-19 12:00:00', 'UTC'));
    $user = User::factory()->create(['timezone' => 'UTC']);
    Sanctum::actingAs($user, ['chat:converse']);

    $this->postJson(route('api.v2.health-entries.check-in.store'), ['date' => '2026-10-19', 'sleep_quality' => 1, 'stress_level' => 3])->assertOk();
    $this->putJson(route('api.v2.account.timezone'), ['timezone' => 'Asia/Ulaanbaatar'])->assertNoContent();

    Queue::fake();

    $this->postJson(route('api.v2.health-entries.check-in.store'), ['date' => '2026-10-19', 'sleep_quality' => 3])
        ->assertOk()
        ->assertJsonPath('wellness.sleep_quality', 3)
        ->assertJsonPath('wellness.stress_level', 3);

    expect(HealthSyncSample::query()->where('user_id', $user->id)->orderBy('type_identifier')->get()
        ->map(fn (HealthSyncSample $sample): array => [$sample->type_identifier, $sample->value, $sample->measured_at->utc()->toDateTimeString()])
        ->all())->toBe([
            ['sleepQuality', 3.0, '2026-10-18 16:00:00'],
            ['stressLevel', 3.0, '2026-10-18 16:00:00'],
        ]);

    Queue::assertPushed(AggregateUserDayJob::class, fn (AggregateUserDayJob $job): bool => $job->uniqueId() === $user->id.':2026-10-18');
    Queue::assertPushed(AggregateUserDayJob::class, fn (AggregateUserDayJob $job): bool => $job->uniqueId() === $user->id.':2026-10-19');
});

it('rejects check-ins it cannot save', function (array $payload, string $field): void {
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.health-entries.check-in.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    expect(HealthSyncSample::query()->count())->toBe(0);
})->with([
    'nothing answered' => [['date' => '2026-10-19'], 'sleep_hours'],
    'a day that has not started yet' => [['date' => '2026-10-20', 'stress_level' => 2], 'date'],
    'more hours than a day has' => [['date' => '2026-10-19', 'sleep_hours' => 25], 'sleep_hours'],
    'an unknown stress level' => [['date' => '2026-10-19', 'stress_level' => 4], 'stress_level'],
]);
