<?php

declare(strict_types=1);

use App\Enums\HealthSyncType;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Services\HealthEntryAssembler;
use Illuminate\Support\Str;

covers(HealthEntryAssembler::class);

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

it('assembles iOS medication samples that store the name under the HealthKit "name" key', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->medication()->for($user)->create([
        'metadata' => ['name' => 'Melatonin', 'form' => 'capsule'],
    ]);

    $assembler = new HealthEntryAssembler;
    $result = $assembler->assemble(collect([$sample]));

    expect($result->first()['medication_name'])->toBe('Melatonin')
        ->and($result->first()['medication_dosage'])->toBe('1 capsule');
});

it('composes a medication dose from value and unit when the device omits an explicit dosage', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->for($user)->create([
        'type_identifier' => HealthSyncType::MedicationDoseEvent->value,
        'value' => 1,
        'unit' => 'mL',
        'metadata' => ['medication_name' => 'Cough syrup'],
    ]);

    $assembler = new HealthEntryAssembler;
    $result = $assembler->assemble(collect([$sample]));

    expect($result->first()['medication_name'])->toBe('Cough syrup')
        ->and($result->first()['medication_dosage'])->toBe('1 mL');
});

it('leaves medication dosage blank when there is no dosage, form, or device-specific unit', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->medication()->for($user)->create([
        'metadata' => ['medication_name' => 'Aspirin'],
    ]);

    $assembler = new HealthEntryAssembler;
    $result = $assembler->assemble(collect([$sample]));

    expect($result->first()['medication_dosage'])->toBeNull();
});

it('keeps the dose count for dose-counted medications without an explicit dosage', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->medication()->for($user)->create([
        'value' => 2,
        'metadata' => ['medication_name' => 'Vitamin D'],
    ]);

    $assembler = new HealthEntryAssembler;
    $result = $assembler->assemble(collect([$sample]));

    expect($result->first()['medication_dosage'])->toBe('2 doses');
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

it('assembles exercise sample without metadata falls back to the type label', function (): void {
    $user = User::factory()->create();
    $sample = HealthSyncSample::factory()->exercise()->for($user)->create([
        'value' => 15,
        'metadata' => null,
    ]);

    $assembler = new HealthEntryAssembler;
    $result = $assembler->assemble(collect([$sample]));

    expect($result->first()['exercise_type'])->toBe(HealthSyncType::ExerciseMinutes->label());
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

it('labels an unlabelled meal by the local time it was eaten', function (string $utcTime, string $mealType): void {
    $sample = HealthSyncSample::factory()->carbohydrates()->for(User::factory())->create([
        'measured_at' => '2026-10-18 '.$utcTime,
    ]);

    expect((new HealthEntryAssembler)->assemble(collect([$sample]), 'Asia/Ulaanbaatar')->first()['meal_type'])->toBe($mealType);
})->with([
    'breakfast from 04:00' => ['20:00:00', 'breakfast'],
    'breakfast until 10:59' => ['02:59:00', 'breakfast'],
    'lunch from 11:00' => ['03:00:00', 'lunch'],
    'dinner until 21:59' => ['13:59:00', 'dinner'],
    'snack from 22:00' => ['14:00:00', 'snack'],
    'snack until 03:59' => ['19:59:00', 'snack'],
]);

it('keeps a chosen meal type and gives readings none', function (): void {
    $user = User::factory()->create();
    $meal = HealthSyncSample::factory()->carbohydrates()->for($user)->create([
        'measured_at' => '2026-10-18 01:00:00',
        'metadata' => ['meal_type' => 'snack'],
    ]);
    $reading = HealthSyncSample::factory()->bloodGlucose()->for($user)->create(['measured_at' => '2026-10-18 01:00:00']);

    $entries = (new HealthEntryAssembler)->assemble(collect([$meal, $reading]));

    expect($entries->pluck('meal_type', 'id')->all())->toBe([$meal->id => 'snack', $reading->id => null]);
});
