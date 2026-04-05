<?php

declare(strict_types=1);

use App\Enums\HealthSyncType;

it('returns correct units for all types', function (HealthSyncType $type, string $expectedUnit): void {
    expect($type->unit())->toBe($expectedUnit);
})->with([
    [HealthSyncType::BloodGlucose, 'mg/dL'],
    [HealthSyncType::BloodPressureSystolic, 'mmHg'],
    [HealthSyncType::BloodPressureDiastolic, 'mmHg'],
    [HealthSyncType::BloodPressure, 'mmHg'],
    [HealthSyncType::Weight, 'kg'],
    [HealthSyncType::Carbohydrates, 'g'],
    [HealthSyncType::Protein, 'g'],
    [HealthSyncType::TotalFat, 'g'],
    [HealthSyncType::DietaryEnergy, 'kcal'],
    [HealthSyncType::ExerciseMinutes, 'min'],
    [HealthSyncType::Workouts, 'min'],
    [HealthSyncType::A1c, '%'],
    [HealthSyncType::Insulin, 'IU'],
    [HealthSyncType::Medication, 'dose'],
    [HealthSyncType::BiologicalSex, ''],
    [HealthSyncType::DateOfBirth, ''],
    [HealthSyncType::BloodType, ''],
]);

it('returns correct labels for all types', function (HealthSyncType $type, string $expectedLabel): void {
    expect($type->label())->toBe($expectedLabel);
})->with([
    [HealthSyncType::BloodGlucose, 'Blood Glucose'],
    [HealthSyncType::BloodPressureSystolic, 'Blood Pressure (Systolic)'],
    [HealthSyncType::BloodPressureDiastolic, 'Blood Pressure (Diastolic)'],
    [HealthSyncType::BloodPressure, 'Blood Pressure'],
    [HealthSyncType::Weight, 'Weight'],
    [HealthSyncType::Carbohydrates, 'Carbohydrates'],
    [HealthSyncType::Protein, 'Protein'],
    [HealthSyncType::TotalFat, 'Total Fat'],
    [HealthSyncType::DietaryEnergy, 'Calories'],
    [HealthSyncType::ExerciseMinutes, 'Exercise'],
    [HealthSyncType::Workouts, 'Workouts'],
    [HealthSyncType::A1c, 'A1C'],
    [HealthSyncType::Insulin, 'Insulin'],
    [HealthSyncType::Medication, 'Medication'],
    [HealthSyncType::BiologicalSex, 'Biological Sex'],
    [HealthSyncType::DateOfBirth, 'Date of Birth'],
    [HealthSyncType::BloodType, 'Blood Type'],
]);

it('returns entry type values with all syncable types', function (): void {
    $values = HealthSyncType::entryTypeValues();

    expect($values)
        ->toContain(HealthSyncType::BloodGlucose->value)
        ->toContain(HealthSyncType::Medication->value)
        ->not->toContain(HealthSyncType::BiologicalSex->value)
        ->not->toContain(HealthSyncType::DateOfBirth->value)
        ->not->toContain(HealthSyncType::BloodType->value)
        ->not->toContain(HealthSyncType::BloodPressure->value);

    foreach (HealthSyncType::cases() as $case) {
        if ($case->isSyncable()) {
            expect($values)->toContain($case->value);
        }
    }
});

it('returns user characteristic values', function (): void {
    $values = HealthSyncType::userCharacteristicValues();

    expect($values)
        ->toContain(HealthSyncType::BiologicalSex->value)
        ->toContain(HealthSyncType::DateOfBirth->value)
        ->toContain(HealthSyncType::BloodType->value)
        ->not->toContain(HealthSyncType::BloodGlucose->value)
        ->not->toContain(HealthSyncType::Weight->value);
});

it('identifies syncable types correctly', function (): void {
    expect(HealthSyncType::BloodGlucose->isSyncable())->toBeTrue()
        ->and(HealthSyncType::Weight->isSyncable())->toBeTrue()
        ->and(HealthSyncType::BiologicalSex->isSyncable())->toBeFalse()
        ->and(HealthSyncType::BloodPressure->isSyncable())->toBeFalse();
});

it('returns correct category for each type', function (HealthSyncType $type, string $expectedCategory): void {
    expect($type->category())->toBe($expectedCategory);
})->with([
    [HealthSyncType::BloodGlucose, 'glucose'],
    [HealthSyncType::Weight, 'vitals'],
    [HealthSyncType::Carbohydrates, 'food'],
    [HealthSyncType::Insulin, 'medication'],
    [HealthSyncType::ExerciseMinutes, 'exercise'],
    [HealthSyncType::BiologicalSex, 'profile'],
    [HealthSyncType::BloodPressure, 'vitals'],
]);
