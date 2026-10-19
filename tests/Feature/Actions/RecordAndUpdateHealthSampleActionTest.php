<?php

declare(strict_types=1);

use App\Actions\DeleteHealthSampleAction;
use App\Actions\UpdateHealthSampleAction;
use App\Data\HealthLogData;
use App\Enums\HealthEntrySource;
use App\Enums\HealthEntryType;
use App\Enums\HealthSyncType;
use App\Enums\MealType;
use App\Models\HealthSyncSample;
use App\Models\User;
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
    ));

    expect(HealthSyncSample::query()->where('group_id', $groupId)->pluck('value', 'type_identifier')->all())->toEqual([
        HealthSyncType::Carbohydrates->value => 50,
        HealthSyncType::BloodGlucose->value => 140,
    ]);
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

    $result = resolve(UpdateHealthSampleAction::class)->handle($sample, $data);

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
    ));

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
