<?php

declare(strict_types=1);

use App\Actions\DeleteHealthSampleAction;
use App\Actions\UpdateHealthSampleAction;
use App\Data\HealthLogData;
use App\Data\ReviewedMealData;
use App\Enums\HealthEntrySource;
use App\Enums\HealthEntryType;
use App\Enums\HealthSyncType;
use App\Enums\MealType;
use App\Models\HealthSyncSample;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

covers(UpdateHealthSampleAction::class, DeleteHealthSampleAction::class);

it('replaces only the edited kind of reading and keeps the rest of the group', function (bool $boundToEditedKind): void {
    $user = User::factory()->create();
    $groupId = (string) Str::uuid();

    $glucose = HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::BloodGlucose->value,
        'value' => 100,
        'group_id' => $groupId,
        'entry_source' => HealthEntrySource::Web,
    ]);
    $carbs = HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::Carbohydrates->value,
        'value' => 50,
        'group_id' => $groupId,
        'entry_source' => HealthEntrySource::Web,
    ]);
    $protein = HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::Protein->value,
        'value' => 20,
        'group_id' => $groupId,
        'entry_source' => HealthEntrySource::Web,
    ]);

    $result = resolve(UpdateHealthSampleAction::class)->handle(
        $boundToEditedKind ? $carbs : $glucose,
        new HealthLogData(isHealthData: true, logType: HealthEntryType::Food, carbsGrams: 60.0, measuredAt: now()->subHour()->toImmutable(), notes: 'Pasta'),
        HealthEntrySource::Web,
    );

    $members = HealthSyncSample::query()->where('group_id', $groupId)->get();

    expect($result->id)->toBe($boundToEditedKind ? $carbs->id : $glucose->id)
        ->and($members->pluck('value', 'type_identifier')->all())->toEqual([
            HealthSyncType::BloodGlucose->value => 100,
            HealthSyncType::Carbohydrates->value => 60,
        ])
        ->and($members->pluck('notes')->unique()->all())->toBe(['Pasta'])
        ->and(HealthSyncSample::query()->find($protein->id))->toBeNull();
})->with(['bound to the edited reading' => [true], 'bound to a kept reading' => [false]]);

it('keeps a meal when only the reading logged with it is edited', function (): void {
    $user = User::factory()->create();
    $groupId = (string) Str::uuid();
    $carbs = HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::Carbohydrates->value,
        'value' => 50,
        'group_id' => $groupId,
    ]);
    HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::BloodGlucose->value,
        'value' => 100,
        'group_id' => $groupId,
    ]);

    resolve(UpdateHealthSampleAction::class)->handle($carbs, new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Glucose,
        glucoseValue: 140.0,
    ), HealthEntrySource::Web);

    expect(HealthSyncSample::query()->where('group_id', $groupId)->pluck('value', 'type_identifier')->all())->toEqual([
        HealthSyncType::Carbohydrates->value => 50,
        HealthSyncType::BloodGlucose->value => 140,
    ]);
});

it("replaces or removes a meal's fiber when its ingredients are edited", function (?float $fiber, ?float $stored): void {
    $user = User::factory()->create();
    $groupId = (string) Str::uuid();
    $sample = HealthSyncSample::factory()->for($user)->create(['type_identifier' => HealthSyncType::Carbohydrates->value, 'value' => 40, 'group_id' => $groupId]);
    HealthSyncSample::factory()->for($user)->create(['type_identifier' => HealthSyncType::Fiber->value, 'value' => 6, 'group_id' => $groupId]);

    resolve(UpdateHealthSampleAction::class)->handle($sample, new ReviewedMealData(
        [['name' => 'Oats', 'calories' => 300, 'carbs' => 54, 'fiber' => $fiber]],
        now()->toImmutable(),
    )->toHealthLogDataWithMetadata([]), HealthEntrySource::Web);

    expect(HealthSyncSample::query()->where('group_id', $groupId)->where('type_identifier', 'fiber')->value('value'))->toBe($stored);
})->with([
    'new fiber value' => [8.0, 8.0],
    'fiber removed' => [null, null],
]);

it('clears fiber when a totals-only update explicitly sends none', function (): void {
    $user = User::factory()->create();
    $groupId = (string) Str::uuid();
    $items = [['name' => 'Oats', 'calories' => 300, 'protein' => 10, 'carbs' => 54, 'fat' => 5, 'fiber' => 8, 'provenance' => 'model']];
    $sample = HealthSyncSample::factory()->for($user)->create(['type_identifier' => HealthSyncType::Carbohydrates->value, 'value' => 54, 'group_id' => $groupId, 'metadata' => ['snap_to_track' => ['items' => $items]]]);
    HealthSyncSample::factory()->for($user)->create(['type_identifier' => HealthSyncType::Fiber->value, 'value' => 8, 'group_id' => $groupId, 'metadata' => ['snap_to_track' => ['items' => $items]]]);

    resolve(UpdateHealthSampleAction::class)->handle($sample, HealthLogData::fromParsedArray([
        'log_type' => 'food', 'carbs_grams' => 54, 'protein_grams' => 10, 'fat_grams' => 5, 'calories' => 300, 'fiber_grams' => null,
    ]), HealthEntrySource::Web);

    expect(HealthSyncSample::query()->whereBelongsTo($user)->where('type_identifier', 'fiber')->exists())->toBeFalse();
});

it('creates additional group members when updating to a multi-sample entry', function (): void {
    $user = User::factory()->create();

    $sample = HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::Carbohydrates->value,
        'value' => 40,
        'group_id' => null,
        'entry_source' => HealthEntrySource::Web,
    ]);

    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Food,
        carbsGrams: 50.0,
        proteinGrams: 20.0,
    );

    $result = resolve(UpdateHealthSampleAction::class)->handle($sample, $data, HealthEntrySource::Web);

    expect($result->id)->toBe($sample->id)
        ->and($result->group_id)->not->toBeNull();

    $groupMembers = HealthSyncSample::query()->where('group_id', $result->group_id)->get();
    expect($groupMembers)->toHaveCount(2)
        ->and($groupMembers->pluck('type_identifier')->sort()->values()->all())->toBe([
            HealthSyncType::Carbohydrates->value,
            HealthSyncType::Protein->value,
        ]);
});

it('keeps the stored meal type when an edit does not choose one', function (?MealType $chosen, string $expected): void {
    $user = User::factory()->create();
    $groupId = (string) Str::uuid();
    $sample = HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::Carbohydrates->value,
        'value' => 40,
        'group_id' => $groupId,
        'metadata' => ['meal_type' => MealType::Dinner->value],
    ]);
    HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::DietaryEnergy->value,
        'value' => 300,
        'group_id' => $groupId,
        'metadata' => ['meal_type' => MealType::Dinner->value],
    ]);

    resolve(UpdateHealthSampleAction::class)->handle($sample, new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Food,
        carbsGrams: 45.0,
        calories: 320,
        mealType: $chosen,
    ), HealthEntrySource::Web);

    expect(HealthSyncSample::query()->where('group_id', $groupId)->pluck('metadata')->pluck('meal_type')->unique()->all())
        ->toBe([$expected]);
})->with([
    'not chosen' => [null, 'dinner'],
    'changed' => [MealType::Snack, 'snack'],
]);

it('deletes all group members when sample has a group_id', function (): void {
    $user = User::factory()->create();
    $groupId = (string) Str::uuid();

    $primary = HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::Carbohydrates->value,
        'group_id' => $groupId,
    ]);

    $sibling = HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::Protein->value,
        'group_id' => $groupId,
    ]);

    $unrelated = HealthSyncSample::factory()->for($user)->bloodGlucose()->create();

    $action = resolve(DeleteHealthSampleAction::class);
    $action->handle($primary);

    expect(HealthSyncSample::query()->find($primary->id))->toBeNull()
        ->and(HealthSyncSample::query()->find($sibling->id))->toBeNull()
        ->and(HealthSyncSample::query()->find($unrelated->id))->not->toBeNull();
});

it('keeps an edited synced exercise reading’s kind and moves its recorded span with it', function (string $type): void {
    $sample = HealthSyncSample::factory()->exercise()->fromMobileSync()->create([
        'type_identifier' => $type,
        'value' => 10,
        'measured_at' => '2026-10-21 09:00:20',
        'ended_at' => '2026-10-21 09:10:20',
    ]);

    $updated = resolve(UpdateHealthSampleAction::class)->handle($sample, new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Exercise,
        exerciseType: 'yoga',
        exerciseDurationMinutes: 30,
        measuredAt: CarbonImmutable::parse('2026-10-21 18:00:00'),
        notes: 'Class',
    ), HealthEntrySource::Web);

    expect($updated->type_identifier)->toBe($type)
        ->and($updated->value)->toBe(30.0)
        ->and($updated->measured_at->toDateTimeString())->toBe('2026-10-21 18:00:00')
        ->and($updated->ended_at?->toDateTimeString())->toBe('2026-10-21 18:10:00');
})->with([
    'exercise minutes' => [HealthSyncType::ExerciseMinutes->value],
    'workout' => [HealthSyncType::Workouts->value],
]);

it('makes an edited synced reading the user’s own and keeps where other readings were logged', function (?HealthEntrySource $loggedFrom, HealthEntrySource $editedFrom, ?HealthEntrySource $expected): void {
    $user = User::factory()->create();
    $groupId = (string) Str::uuid();
    $glucose = HealthSyncSample::factory()->for($user)->bloodGlucose()->create(['entry_source' => $loggedFrom, 'group_id' => $groupId]);
    HealthSyncSample::factory()->for($user)->exercise()->create(['entry_source' => $loggedFrom, 'group_id' => $groupId]);

    resolve(UpdateHealthSampleAction::class)->handle($glucose, new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Glucose,
        glucoseValue: 140.0,
    ), $editedFrom);

    expect(HealthSyncSample::query()->where('group_id', $groupId)->get()->pluck('entry_source')->unique()->all())->toBe([$expected]);
})->with([
    'synced, edited on the web' => [HealthEntrySource::MobileSync, HealthEntrySource::Web, HealthEntrySource::Web],
    'synced, edited in the app' => [HealthEntrySource::MobileSync, HealthEntrySource::Mobile, HealthEntrySource::Mobile],
    'logged in chat' => [HealthEntrySource::Chat, HealthEntrySource::Web, HealthEntrySource::Chat],
    'no recorded source' => [null, HealthEntrySource::Mobile, null],
]);
