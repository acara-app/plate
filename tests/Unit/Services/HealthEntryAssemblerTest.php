<?php

declare(strict_types=1);

use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Services\HealthEntryAssembler;
use Illuminate\Support\Str;

it('assembles insulin samples', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->insulin()->for($user)->create([
        'metadata' => ['insulin_type' => 'bolus'],
    ]);

    $assembler = new HealthEntryAssembler;
    $result = $assembler->assemble(collect([$sample]));

    expect($result->first())
        ->toBeArray()
        ->and($result->first()['insulin_units'])->toBe($sample->value)
        ->and($result->first()['insulin_type'])->toBe('bolus');
});

it('assembles medication samples', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->medication()->for($user)->create([
        'metadata' => ['medication_name' => 'Metformin', 'medication_dosage' => '500mg'],
    ]);

    $assembler = new HealthEntryAssembler;
    $result = $assembler->assemble(collect([$sample]));

    expect($result->first()['medication_name'])->toBe('Metformin')
        ->and($result->first()['medication_dosage'])->toBe('500mg');
});

it('assembles exercise samples', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->exercise()->for($user)->create([
        'value' => 30,
        'metadata' => ['exercise_type' => 'Running'],
    ]);

    $assembler = new HealthEntryAssembler;
    $result = $assembler->assemble(collect([$sample]));

    expect($result->first()['exercise_duration_minutes'])->toBe(30)
        ->and($result->first()['exercise_type'])->toBe('Running');
});

it('assembles exercise sample without metadata falls back to type_identifier', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->exercise()->for($user)->create([
        'value' => 15,
        'metadata' => null,
    ]);

    $assembler = new HealthEntryAssembler;
    $result = $assembler->assemble(collect([$sample]));

    expect($result->first()['exercise_type'])->toBe($sample->type_identifier);
});

it('ignores unknown type identifiers gracefully', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => 'unknownType',
        'value' => 42,
    ]);

    $assembler = new HealthEntryAssembler;
    $result = $assembler->assemble(collect([$sample]));

    expect($result->first()['glucose_value'])->toBeNull()
        ->and($result->first()['weight'])->toBeNull();
});

it('assembles grouped food samples into a single entry', function (): void {
    $user = User::factory()->create();
    $groupId = (string) Str::uuid();
    $measuredAt = now();

    HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::Carbohydrates->value,
        'value' => 50,
        'unit' => 'g',
        'group_id' => $groupId,
        'measured_at' => $measuredAt,
        'notes' => 'Rice bowl',
    ]);

    HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::Protein->value,
        'value' => 20,
        'unit' => 'g',
        'group_id' => $groupId,
        'measured_at' => $measuredAt,
    ]);

    HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::TotalFat->value,
        'value' => 10,
        'unit' => 'g',
        'group_id' => $groupId,
        'measured_at' => $measuredAt,
    ]);

    HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::DietaryEnergy->value,
        'value' => 400,
        'unit' => 'kcal',
        'group_id' => $groupId,
        'measured_at' => $measuredAt,
    ]);

    $samples = HealthSyncSample::query()->where('group_id', $groupId)->get();
    $assembler = new HealthEntryAssembler;
    $result = $assembler->assemble($samples);

    expect($result)->toHaveCount(1)
        ->and($result->first()['carbs_grams'])->toBe(50.0)
        ->and($result->first()['protein_grams'])->toBe(20.0)
        ->and($result->first()['fat_grams'])->toBe(10.0)
        ->and($result->first()['calories'])->toBe(400)
        ->and($result->first()['notes'])->toBe('Rice bowl');
});
