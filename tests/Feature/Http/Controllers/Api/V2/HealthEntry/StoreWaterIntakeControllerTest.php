<?php

declare(strict_types=1);

use App\Enums\HealthEntrySource;
use App\Http\Controllers\Api\V2\HealthEntry\StoreWaterIntakeController;
use App\Jobs\AggregateUserDayJob;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

covers(StoreWaterIntakeController::class);

/**
 * @return list<array{float, string, HealthEntrySource|null}>
 */
function apiLogbookStoredWater(User $user): array
{
    return HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', 'water')
        ->orderBy('id')
        ->get()
        ->map(fn (HealthSyncSample $sample): array => [$sample->value, $sample->measured_at->utc()->toDateTimeString(), $sample->entry_source])
        ->all();
}

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-18 17:00:00', 'UTC'));
    $this->user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
});

it('requires a bearer token', function (): void {
    $this->postJson(route('api.v2.health-entries.water.store'), ['date' => '2026-10-19', 'amount_ml' => 250])->assertUnauthorized();
});

it('saves the water drunk on the chosen local day and returns the refreshed wellness', function (): void {
    Queue::fake();
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.health-entries.water.store'), ['date' => '2026-10-19', 'amount_ml' => 750])
        ->assertOk()
        ->assertJsonPath('wellness.water_ml', 750)
        ->assertJsonPath('wellness.water_synced_ml', 0)
        ->assertJsonPath('wellness.water_goal_ml', 2000);

    expect(apiLogbookStoredWater($this->user))->toBe([[750.0, '2026-10-18 16:00:00', HealthEntrySource::Web]]);
    Queue::assertPushed(AggregateUserDayJob::class, fn (AggregateUserDayJob $job): bool => $job->uniqueId() === $this->user->id.':2026-10-18');
});

it('replaces the day’s amount and clears it when every cup is emptied', function (): void {
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.health-entries.water.store'), ['date' => '2026-10-19', 'amount_ml' => 1000])->assertOk();
    $this->postJson(route('api.v2.health-entries.water.store'), ['date' => '2026-10-19', 'amount_ml' => 1250])->assertOk();

    expect(apiLogbookStoredWater($this->user))->toBe([[1250.0, '2026-10-18 16:00:00', HealthEntrySource::Web]]);

    $this->postJson(route('api.v2.health-entries.water.store'), ['date' => '2026-10-19', 'amount_ml' => 0])
        ->assertOk()
        ->assertJsonPath('wellness.water_ml', 0);

    expect(apiLogbookStoredWater($this->user))->toBe([]);
});

it('shares one logged amount with the web logbook', function (): void {
    $this->actingAs($this->user)
        ->post(route('health-entries.water.store'), ['date' => '2026-10-19', 'amount_ml' => 500])
        ->assertSessionHasNoErrors();

    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.health-entries.water.store'), ['date' => '2026-10-19', 'amount_ml' => 750])
        ->assertOk()
        ->assertJsonPath('wellness.water_ml', 750);

    expect(apiLogbookStoredWater($this->user))->toBe([[750.0, '2026-10-18 16:00:00', HealthEntrySource::Web]]);
});

it('reports water synced from Apple Health separately from logged water', function (): void {
    HealthSyncSample::factory()->fromMobileSync()->create(['user_id' => $this->user->id, 'type_identifier' => 'water', 'value' => 300, 'unit' => 'mL', 'measured_at' => '2026-10-18 17:00:00']);
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.health-entries.water.store'), ['date' => '2026-10-19', 'amount_ml' => 200])
        ->assertOk()
        ->assertJsonPath('wellness.water_ml', 500)
        ->assertJsonPath('wellness.water_synced_ml', 300);
});

it('rejects water it cannot save', function (array $payload, string $field): void {
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->postJson(route('api.v2.health-entries.water.store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    expect(apiLogbookStoredWater($this->user))->toBe([]);
})->with([
    'no amount' => [['date' => '2026-10-19'], 'amount_ml'],
    'no day' => [['amount_ml' => 250], 'date'],
    'a day that has not started yet' => [['date' => '2026-10-20', 'amount_ml' => 250], 'date'],
    'a negative amount' => [['date' => '2026-10-19', 'amount_ml' => -250], 'amount_ml'],
    'more than five litres' => [['date' => '2026-10-19', 'amount_ml' => 5250], 'amount_ml'],
    'a fraction of a millilitre' => [['date' => '2026-10-19', 'amount_ml' => 250.5], 'amount_ml'],
]);
