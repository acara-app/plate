<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2\HealthEntry\UpdateHealthEntryController;
use App\Jobs\AggregateUserDayJob;
use App\Models\HealthSyncSample;
use App\Models\MealPhoto;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

covers(UpdateHealthEntryController::class);

/**
 * @return array{0: HealthSyncSample, 1: string, 2: MealPhoto}
 */
function apiLogbookMealWithReading(User $user): array
{
    $groupId = (string) Str::uuid();
    $glucose = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['user_id' => $user->id, 'group_id' => $groupId, 'value' => 120, 'notes' => 'Bread']);
    $metadata = ['meal_type' => 'lunch', 'snap_to_track' => [
        'source' => 'mobile_snap_to_track',
        'confidence' => 82,
        'items' => [['name' => 'Bread', 'portion' => '2 slices', 'calories' => 160, 'protein' => 6, 'carbs' => 30, 'fat' => 2, 'fiber' => 3, 'provenance' => 'model']],
    ]];

    foreach (['carbohydrates' => 30, 'protein' => 6, 'totalFat' => 2, 'dietaryEnergy' => 160, 'fiber' => 3] as $type => $value) {
        HealthSyncSample::factory()->fromWeb()->create(['user_id' => $user->id, 'group_id' => $groupId, 'type_identifier' => $type, 'value' => $value, 'metadata' => $metadata, 'notes' => 'Bread']);
    }

    $photo = MealPhoto::factory()->create(['user_id' => $user->id, 'health_group_id' => $groupId]);

    return [$glucose, $groupId, $photo];
}

beforeEach(function (): void {
    $this->user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
});

it('requires a bearer token', function (): void {
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create();

    $this->putJson(route('api.v2.health-entries.update', $sample), [])->assertUnauthorized();
});

it('saves an edited reading and returns it with its new local day', function (): void {
    Queue::fake();
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['user_id' => $this->user->id, 'measured_at' => '2026-10-10 03:00:00']);
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->putJson(route('api.v2.health-entries.update', $sample), [
        'log_type' => 'glucose',
        'glucose_value' => 7.2,
        'glucose_reading_type' => 'fasting',
        'measured_at' => '2026-10-12T17:00:00Z',
        'notes' => 'Updated notes',
    ])
        ->assertOk()
        ->assertJsonPath('date', '2026-10-13')
        ->assertJsonPath('glucose_unit', 'mmol/L')
        ->assertJsonPath('entry.id', $sample->id)
        ->assertJsonPath('entry.glucose_value', 130)
        ->assertJsonPath('entry.notes', 'Updated notes')
        ->assertJsonPath('entry.source', 'web');

    Queue::assertPushed(AggregateUserDayJob::class, fn (AggregateUserDayJob $job): bool => $job->uniqueId() === $this->user->id.':2026-10-10');
    Queue::assertPushed(AggregateUserDayJob::class, fn (AggregateUserDayJob $job): bool => $job->uniqueId() === $this->user->id.':2026-10-12');
});

it('saves edited ingredients and meal type without losing the reading, photo or AI details logged with the meal', function (): void {
    [$glucose, $groupId, $photo] = apiLogbookMealWithReading($this->user);
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->putJson(route('api.v2.health-entries.update', $glucose), [
        'log_type' => 'food',
        'meal_type' => 'breakfast',
        'notes' => null,
        'measured_at' => now()->toIso8601String(),
        'items' => [['name' => 'Rye bread', 'portion' => '2 slices', 'grams' => null, 'calories' => 170, 'protein' => 7, 'carbs' => 31, 'fat' => 2, 'fiber' => null, 'provenance' => 'user']],
    ])
        ->assertOk()
        ->assertJsonPath('entry.calories', 170)
        ->assertJsonPath('entry.fiber_grams', null)
        ->assertJsonPath('entry.glucose_value', 120)
        ->assertJsonPath('entry.meal_type', 'breakfast')
        ->assertJsonPath('entry.food_analysis.confidence', 82)
        ->assertJsonPath('entry.food_analysis.items.0.name', 'Rye bread')
        ->assertJsonPath('entry.photo_url', '/api/v2/health-entries/'.$groupId.'/photo');

    expect(HealthSyncSample::query()->where('group_id', $groupId)->count())->toBe(5)
        ->and($photo->fresh())->not->toBeNull();
});

it('saves an edited reading without losing the meal it was logged with', function (): void {
    [$glucose, $groupId] = apiLogbookMealWithReading($this->user);
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->putJson(route('api.v2.health-entries.update', $glucose), [
        'log_type' => 'glucose',
        'glucose_value' => 7.5,
        'glucose_reading_type' => 'post-meal',
        'notes' => 'Bread',
        'measured_at' => now()->toIso8601String(),
    ])
        ->assertOk()
        ->assertJsonPath('entry.glucose_value', 135)
        ->assertJsonPath('entry.food_analysis.items.0.name', 'Bread')
        ->assertJsonPath('entry.fiber_grams', 3);

    expect(HealthSyncSample::query()->where('group_id', $groupId)->count())->toBe(6);
});

it('keeps the ingredient breakdown when unchanged totals are saved without items', function (bool $sendFiber): void {
    [$glucose, $groupId] = apiLogbookMealWithReading($this->user);
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->putJson(route('api.v2.health-entries.update', $glucose), [
        'log_type' => 'food',
        'meal_type' => 'lunch',
        'measured_at' => now()->toIso8601String(),
        'notes' => 'Bread',
        'calories' => 160,
        'protein_grams' => 6,
        'carbs_grams' => 30,
        'fat_grams' => 2,
        ...$sendFiber ? ['fiber_grams' => 3] : [],
    ])
        ->assertOk()
        ->assertJsonPath('entry.fiber_grams', 3)
        ->assertJsonPath('entry.food_analysis.items.0.name', 'Bread');

    expect(HealthSyncSample::query()->where('group_id', $groupId)->where('type_identifier', 'fiber')->count())->toBe(1);
})->with(['fiber left out' => [false], 'fiber unchanged' => [true]]);

it('clears fiber on a manually logged meal when it is sent empty', function (): void {
    $groupId = (string) Str::uuid();
    $sample = HealthSyncSample::factory()->carbohydrates()->create(['user_id' => $this->user->id, 'group_id' => $groupId, 'value' => 40, 'entry_source' => 'mobile']);
    HealthSyncSample::factory()->create(['user_id' => $this->user->id, 'group_id' => $groupId, 'type_identifier' => 'fiber', 'value' => 5, 'unit' => 'g', 'entry_source' => 'mobile']);
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->putJson(route('api.v2.health-entries.update', $sample), [
        'log_type' => 'food',
        'meal_type' => 'snack',
        'measured_at' => now()->toIso8601String(),
        'notes' => null,
        'calories' => null,
        'protein_grams' => null,
        'carbs_grams' => 42,
        'fat_grams' => null,
        'fiber_grams' => null,
    ])
        ->assertOk()
        ->assertJsonPath('entry.carbs_grams', 42)
        ->assertJsonPath('entry.fiber_grams', null)
        ->assertJsonPath('entry.source', 'mobile');

    expect(HealthSyncSample::query()->where('user_id', $this->user->id)->pluck('type_identifier')->all())->toBe(['carbohydrates']);
});

it('cannot update another user’s entry', function (): void {
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['value' => 100]);
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->putJson(route('api.v2.health-entries.update', $sample), [
        'log_type' => 'glucose',
        'glucose_value' => 7.2,
        'glucose_reading_type' => 'fasting',
        'measured_at' => now()->toIso8601String(),
    ])->assertForbidden();

    expect($sample->fresh()->value)->toBe(100.0);
});

it('does not edit samples that are not logbook entries', function (): void {
    $sample = HealthSyncSample::factory()->fromWeb()->create(['user_id' => $this->user->id, 'type_identifier' => 'water', 'value' => 500, 'unit' => 'mL']);
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->putJson(route('api.v2.health-entries.update', $sample), [
        'log_type' => 'vitals',
        'weight' => 70,
        'measured_at' => now()->toIso8601String(),
    ])->assertNotFound();

    expect($sample->fresh()->type_identifier)->toBe('water');
});

it('asks for at least one vital sign', function (): void {
    $sample = HealthSyncSample::factory()->weight()->fromWeb()->create(['user_id' => $this->user->id]);
    Sanctum::actingAs($this->user, ['chat:converse']);

    $this->putJson(route('api.v2.health-entries.update', $sample), [
        'log_type' => 'vitals',
        'measured_at' => now()->toIso8601String(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('vitals');
});
