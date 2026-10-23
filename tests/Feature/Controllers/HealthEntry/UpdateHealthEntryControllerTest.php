<?php

declare(strict_types=1);

use App\Http\Controllers\HealthEntry\UpdateHealthEntryController;
use App\Jobs\AggregateUserDayJob;
use App\Models\HealthSyncSample;
use App\Models\MealPhoto;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

covers(UpdateHealthEntryController::class);

it('dispatches daily aggregate refresh for old and new dates after updating a health entry', function (): void {
    Queue::fake();
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create([
        'user_id' => $user->id,
        'measured_at' => now()->subDay(),
    ]);
    $previousUtcDate = $sample->measured_at->copy()->utc()->toDateString();

    $newMeasuredAt = now();

    $response = $this->actingAs($user)->put(route('health-entries.update', $sample), [
        'log_type' => 'glucose',
        'glucose_value' => 7.2,
        'glucose_reading_type' => 'fasting',
        'measured_at' => $newMeasuredAt->toDateTimeString(),
    ]);

    $response->assertRedirect();

    Queue::assertPushed(AggregateUserDayJob::class, fn (AggregateUserDayJob $job): bool => str_contains($job->uniqueId(), $user->id.':'.$previousUtcDate));

    Queue::assertPushed(AggregateUserDayJob::class, fn (AggregateUserDayJob $job): bool => str_contains($job->uniqueId(), $user->id.':'.$newMeasuredAt->copy()->utc()->toDateString()));
});

it('can update own diabetes log', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['user_id' => $user->id]);

    $data = [
        'log_type' => 'glucose',
        'glucose_value' => 7.2,
        'glucose_reading_type' => 'fasting',
        'measured_at' => now()->toDateTimeString(),
        'notes' => 'Updated notes',
    ];

    $response = $this->actingAs($user)
        ->put(route('health-entries.update', $sample), $data);

    $response->assertRedirect();

    $this->assertDatabaseHas('health_sync_samples', [
        'id' => $sample->id,
        'type_identifier' => 'bloodGlucose',
        'value' => 130,
        'notes' => 'Updated notes',
    ]);
});

it('cannot update another user diabetes log', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $sample = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['user_id' => $otherUser->id]);

    $data = [
        'log_type' => 'glucose',
        'glucose_value' => 7.2,
        'glucose_reading_type' => 'fasting',
        'measured_at' => now()->toDateTimeString(),
    ];

    $response = $this->actingAs($user)
        ->put(route('health-entries.update', $sample), $data);

    $response->assertForbidden();
});

it('prevents empty vitals log submission when updating', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->weight()->fromWeb()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->put(route('health-entries.update', $sample), [
            'log_type' => 'vitals',
            'measured_at' => now()->toDateTimeString(),
        ]);

    $response->assertSessionHasErrors(['vitals']);
});

/**
 * @return array{0: HealthSyncSample, 1: string, 2: MealPhoto}
 */
function mealWithGlucoseReading(User $user): array
{
    $groupId = (string) Str::uuid();
    $glucose = HealthSyncSample::factory()->bloodGlucose()->fromWeb()->create(['user_id' => $user->id, 'group_id' => $groupId, 'value' => 120, 'notes' => 'Bread']);
    $metadata = ['snap_to_track' => [
        'source' => 'web_snap_to_track',
        'confidence' => 82,
        'items' => [['name' => 'Bread', 'portion' => '2 slices', 'calories' => 160, 'protein' => 6, 'carbs' => 30, 'fat' => 2, 'provenance' => 'model']],
    ]];

    foreach (['carbohydrates' => 30, 'protein' => 6, 'totalFat' => 2, 'dietaryEnergy' => 160] as $type => $value) {
        HealthSyncSample::factory()->fromWeb()->create(['user_id' => $user->id, 'group_id' => $groupId, 'type_identifier' => $type, 'value' => $value, 'metadata' => $metadata, 'notes' => 'Bread']);
    }

    $photo = MealPhoto::factory()->create(['user_id' => $user->id, 'health_group_id' => $groupId]);

    return [$glucose, $groupId, $photo];
}

it('saves edited ingredients and meal type without losing the reading, photo or AI details logged with the meal', function (): void {
    $user = User::factory()->create();
    [$glucose, $groupId, $photo] = mealWithGlucoseReading($user);

    $this->actingAs($user)->put(route('health-entries.update', $glucose), [
        'log_type' => 'food',
        'meal_type' => 'breakfast',
        'notes' => null,
        'measured_at' => now()->toIso8601String(),
        'items' => [['name' => 'Rye bread', 'portion' => '2 slices', 'calories' => 170, 'protein' => 7, 'carbs' => 31, 'fat' => 2, 'provenance' => 'user']],
    ])->assertSessionHasNoErrors()->assertRedirect();

    $members = HealthSyncSample::query()->where('group_id', $groupId)->get();
    $calories = $members->firstWhere('type_identifier', 'dietaryEnergy');

    expect($members)->toHaveCount(5)
        ->and($members->firstWhere('type_identifier', 'bloodGlucose')->id)->toBe($glucose->id)
        ->and($calories->value)->toBe(170.0)
        ->and($calories->metadata['meal_type'])->toBe('breakfast')
        ->and($calories->metadata['snap_to_track']['confidence'])->toBe(82)
        ->and($calories->metadata['snap_to_track']['items'][0]['name'])->toBe('Rye bread')
        ->and($members->pluck('notes')->unique()->all())->toBe(['Rye bread'])
        ->and($photo->fresh())->not->toBeNull();
});

it('saves an edited reading without losing the meal and photo it was logged with', function (): void {
    $user = User::factory()->create();
    [$glucose, $groupId, $photo] = mealWithGlucoseReading($user);

    $this->actingAs($user)->put(route('health-entries.update', $glucose), [
        'log_type' => 'glucose',
        'glucose_value' => 7.5,
        'glucose_reading_type' => 'post-meal',
        'notes' => 'Bread',
        'measured_at' => now()->toIso8601String(),
    ])->assertSessionHasNoErrors()->assertRedirect();

    $members = HealthSyncSample::query()->where('group_id', $groupId)->get();

    expect($members)->toHaveCount(5)
        ->and($members->firstWhere('type_identifier', 'dietaryEnergy')->metadata['snap_to_track']['items'][0]['name'])->toBe('Bread')
        ->and($photo->fresh())->not->toBeNull();
});

it('saves a synced meal that has calories but no carbohydrates', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->fromWeb()->create(['user_id' => $user->id, 'type_identifier' => 'dietaryEnergy', 'value' => 250, 'unit' => 'kcal']);

    $this->actingAs($user)->put(route('health-entries.update', $sample), [
        'log_type' => 'food',
        'calories' => 260,
        'meal_type' => 'snack',
        'measured_at' => now()->toIso8601String(),
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect($sample->refresh()->value)->toBe(260.0)
        ->and($sample->metadata['meal_type'])->toBe('snack');
});

it('does not turn samples that are not logbook entries into one', function (): void {
    $user = User::factory()->create();
    $water = HealthSyncSample::factory()->create(['user_id' => $user->id, 'type_identifier' => 'water', 'value' => 250, 'unit' => 'mL']);

    $this->actingAs($user)->put(route('health-entries.update', $water), [
        'log_type' => 'vitals',
        'weight' => 70,
        'measured_at' => now()->toIso8601String(),
    ])->assertNotFound();

    expect($water->refresh()->type_identifier)->toBe('water')
        ->and($water->value)->toBe(250.0)
        ->and(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('keeps an edited synced workout in the logbook and out of the synced exercise total', function (): void {
    Queue::fake();
    $user = User::factory()->create(['timezone' => 'UTC']);
    $day = CarbonImmutable::parse('2026-10-21', 'UTC');
    foreach (range(1, 10) as $minute) {
        HealthSyncSample::factory()->exercise()->fromMobileSync()->create([
            'user_id' => $user->id, 'source' => 'Apple Watch', 'value' => 1,
            'measured_at' => $day->setTime(9, $minute), 'ended_at' => $day->setTime(9, $minute + 1),
        ]);
    }

    $workout = HealthSyncSample::factory()->exercise()->fromMobileSync()->create([
        'user_id' => $user->id, 'type_identifier' => 'workouts', 'source' => 'Apple Watch', 'value' => 10,
        'measured_at' => $day->setTime(9, 0, 20), 'ended_at' => $day->setTime(9, 10, 20),
    ]);

    $this->actingAs($user)->put(route('health-entries.update', $workout), [
        'log_type' => 'exercise',
        'exercise_type' => 'walking',
        'exercise_duration_minutes' => 10,
        'measured_at' => $workout->measured_at->toIso8601String(),
        'notes' => 'Evening walk',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $this->get(route('health-entries.index', ['date' => '2026-10-21']))
        ->assertInertia(fn ($page) => $page
            ->where('wellness.synced_exercise_minutes', 10)
            ->where('entries', fn ($entries): bool => collect($entries)->pluck('id')->all() === [$workout->id])
            ->where('entries.0.notes', 'Evening walk')
            ->where('entries.0.source', 'web'));

    expect($workout->refresh()->type_identifier)->toBe('workouts');
});
