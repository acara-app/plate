<?php

declare(strict_types=1);

use App\Actions\DeleteHealthSampleAction;
use App\Actions\UpdateHealthSampleAction;
use App\DataObjects\HealthLogData;
use App\Enums\HealthEntrySource;
use App\Enums\HealthEntryType;
use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Support\Str;

it('deletes group siblings and recreates when updating a grouped entry', function (): void {
    $user = User::factory()->create();
    $groupId = (string) Str::uuid();

    $primary = HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::Carbohydrates->value,
        'value' => 50,
        'group_id' => $groupId,
        'entry_source' => HealthEntrySource::Web,
    ]);

    $sibling = HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::Protein->value,
        'value' => 20,
        'group_id' => $groupId,
        'entry_source' => HealthEntrySource::Web,
    ]);

    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Glucose,
        glucoseValue: 120.0,
    );

    $action = resolve(UpdateHealthSampleAction::class);
    $result = $action->handle($primary, $data);

    expect($result->type_identifier)->toBe(HealthSyncType::BloodGlucose->value)
        ->and($result->group_id)->toBeNull()
        ->and(HealthSyncSample::query()->find($sibling->id))->toBeNull();
});

it('creates additional group members when updating to a multi-sample entry', function (): void {
    $user = User::factory()->create();

    $sample = HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::BloodGlucose->value,
        'value' => 100,
        'group_id' => null,
        'entry_source' => HealthEntrySource::Web,
    ]);

    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Food,
        carbsGrams: 50.0,
        proteinGrams: 20.0,
    );

    $action = resolve(UpdateHealthSampleAction::class);
    $result = $action->handle($sample, $data);

    expect($result->group_id)->not->toBeNull();

    $groupMembers = HealthSyncSample::query()->where('group_id', $result->group_id)->get();
    expect($groupMembers)->toHaveCount(2)
        ->and($groupMembers->pluck('type_identifier')->sort()->values()->all())->toBe([
            HealthSyncType::Carbohydrates->value,
            HealthSyncType::Protein->value,
        ]);
});

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
