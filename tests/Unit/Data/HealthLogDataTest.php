<?php

declare(strict_types=1);

use App\Data\HealthLogData;
use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\HealthEntryType;
use App\Enums\InsulinType;

covers(HealthLogData::class);

it('formats glucose log correctly', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Glucose,
        glucoseValue: 120.0,
        glucoseReadingType: GlucoseReadingType::Fasting,
        glucoseUnit: GlucoseUnit::MgDl,
    );

    expect($data->formatForDisplay())->toBe('Glucose 120 mg/dL (Fasting)');
});

it('formats glucose log with defaults', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Glucose,
        glucoseValue: 120.0,
    );

    expect($data->formatForDisplay())->toBe('Glucose 120 mg/dL (Random)');
});

it('formats food log correctly', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Food,
        carbsGrams: 50.0,
    );

    expect($data->formatForDisplay())->toBe('Food - 50g carbs');
});

it('formats food log with all macros', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Food,
        carbsGrams: 50.0,
        proteinGrams: 20.0,
        fatGrams: 15.0,
        calories: 400,
        notes: 'tsuivan',
    );

    expect($data->formatForDisplay())->toContain('tsuivan')
        ->toContain('50g carbs')
        ->toContain('20g protein')
        ->toContain('15g fat')
        ->toContain('400 kcal');
});

it('formats insulin log correctly', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Insulin,
        insulinUnits: 10.0,
        insulinType: InsulinType::Basal,
    );

    expect($data->formatForDisplay())->toBe('Insulin 10 units (Basal)');
});

it('formats insulin log with Mixed type', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Insulin,
        insulinUnits: 10.0,
        insulinType: InsulinType::Mixed,
    );

    expect($data->formatForDisplay())->toBe('Insulin 10 units (Mixed)');
});

it('formats insulin log with defaults', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Insulin,
        insulinUnits: 10.0,
    );

    expect($data->formatForDisplay())->toBe('Insulin 10 units (Bolus)');
});

it('formats meds log correctly', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Meds,
        medicationName: 'Metformin',
        medicationDosage: '500mg',
    );

    expect($data->formatForDisplay())->toBe('Medication - Metformin 500mg');
});

it('formats meds log without dosage', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Meds,
        medicationName: 'Metformin',
    );

    expect($data->formatForDisplay())->toBe('Medication - Metformin');
});

it('formats vitals log for weight', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Vitals,
        weight: 75.5,
    );

    expect($data->formatForDisplay())->toBe('Weight 75.5 kg');
});

it('formats vitals log for blood pressure', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Vitals,
        bpSystolic: 120,
        bpDiastolic: 80,
    );

    expect($data->formatForDisplay())->toBe('Blood Pressure 120/80');
});

it('formats vitals log fallback', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Vitals,
    );

    expect($data->formatForDisplay())->toBe('Vitals');
});

it('formats exercise log correctly', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Exercise,
        exerciseType: 'Running',
        exerciseDurationMinutes: 30,
    );

    expect($data->formatForDisplay())->toBe('Exercise - 30 min Running');
});

it('exports to glucose record array', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Glucose,
        glucoseValue: 120.0,
        glucoseReadingType: GlucoseReadingType::Fasting,
    );

    expect($data->toRecordArray())->toBe([
        'glucose_value' => 120.0,
        'glucose_reading_type' => GlucoseReadingType::Fasting->value,
    ]);
});

it('exports to food record array', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Food,
        carbsGrams: 50.0,
    );

    expect($data->toRecordArray())->toBe([
        'carbs_grams' => 50.0,
        'protein_grams' => null,
        'fat_grams' => null,
        'calories' => null,
        'notes' => null,
    ]);
});

it('exports to insulin record array', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Insulin,
        insulinUnits: 10.0,
        insulinType: InsulinType::Basal,
    );

    expect($data->toRecordArray())->toBe([
        'insulin_units' => 10.0,
        'insulin_type' => InsulinType::Basal->value,
    ]);
});

it('exports to meds record array', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Meds,
        medicationName: 'Metformin',
        medicationDosage: '500mg',
    );

    expect($data->toRecordArray())->toBe([
        'medication_name' => 'Metformin',
        'medication_dosage' => '500mg',
    ]);
});

it('exports to vitals record array', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Vitals,
        weight: 75.5,
        bpSystolic: 120,
        bpDiastolic: 80,
    );

    expect($data->toRecordArray())->toBe([
        'weight' => 75.5,
        'blood_pressure_systolic' => 120,
        'blood_pressure_diastolic' => 80,
        'a1c_value' => null,
    ]);
});

it('exports to exercise record array', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Exercise,
        exerciseType: 'Running',
        exerciseDurationMinutes: 30,
    );

    expect($data->toRecordArray())->toBe([
        'exercise_type' => 'Running',
        'exercise_duration_minutes' => 30,
    ]);
});

test('fromParsedArray converts valid data correctly', function (): void {
    $data = HealthLogData::fromParsedArray([
        'is_health_data' => true,
        'log_type' => 'glucose',
        'glucose_value' => 120,
        'glucose_reading_type' => 'fasting',
        'glucose_unit' => 'mg/dL',
        'measured_at' => '2023-01-01 10:00:00',
    ]);

    expect($data)
        ->isHealthData->toBeTrue()
        ->logType->toBe(HealthEntryType::Glucose)
        ->glucoseValue->toBe(120.0)
        ->glucoseReadingType->toBe(GlucoseReadingType::Fasting)
        ->glucoseUnit->toBe(GlucoseUnit::MgDl)
        ->measuredAt->not->toBeNull();
});

test('fromParsedArray handles string numbers correctly', function (): void {
    $data = HealthLogData::fromParsedArray([
        'is_health_data' => true,
        'log_type' => 'glucose',
        'glucose_value' => '120.5',
        'carbs_grams' => '45',
        'insulin_units' => '5.5',
        'weight' => '80',
        'bp_systolic' => '120',
        'bp_diastolic' => '80',
        'exercise_duration_minutes' => '30',
    ]);

    expect($data)
        ->glucoseValue->toBe(120.5)
        ->carbsGrams->toBe(45.0)
        ->insulinUnits->toBe(5.5)
        ->weight->toBe(80.0)
        ->bpSystolic->toBe(120)
        ->bpDiastolic->toBe(80)
        ->exerciseDurationMinutes->toBe(30);
});

test('fromParsedArray handles null and invalid values gracefully', function (): void {
    $data = HealthLogData::fromParsedArray([
        'is_health_data' => 'not-bool',
        'log_type' => 'invalid-type',
        'glucose_value' => 'not-numeric',
        'glucose_reading_type' => 'invalid-enum',
        'measured_at' => null,
    ]);

    expect($data)
        ->isHealthData->toBeTrue()
        ->logType->toBe(HealthEntryType::Glucose)
        ->glucoseValue->toBeNull()
        ->glucoseReadingType->toBeNull()
        ->measuredAt->toBeNull();
});

test('fromParsedArray handles empty array', function (): void {
    $data = HealthLogData::fromParsedArray([]);

    expect($data)
        ->isHealthData->toBeFalse()
        ->logType->toBe(HealthEntryType::Glucose)
        ->glucoseValue->toBeNull()
        ->notes->toBeNull();
});

test('toSampleArrays returns food samples with all macros', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Food,
        carbsGrams: 50.0,
        proteinGrams: 25.0,
        fatGrams: 10.0,
        calories: 400,
    );

    $samples = $data->toSampleArrays();

    expect($samples)->toHaveCount(4)
        ->and($samples[0]['type_identifier'])->toBe('carbohydrates')
        ->and($samples[0]['value'])->toBe(50.0)
        ->and($samples[1]['type_identifier'])->toBe('protein')
        ->and($samples[1]['value'])->toBe(25.0)
        ->and($samples[2]['type_identifier'])->toBe('totalFat')
        ->and($samples[2]['value'])->toBe(10.0)
        ->and($samples[3]['type_identifier'])->toBe('dietaryEnergy')
        ->and($samples[3]['value'])->toBe(400);
});

test('toSampleArrays returns vitals samples with all fields', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Vitals,
        weight: 75.5,
        bpSystolic: 120,
        bpDiastolic: 80,
        a1cValue: 6.5,
    );

    $samples = $data->toSampleArrays();

    expect($samples)->toHaveCount(4)
        ->and($samples[0]['type_identifier'])->toBe('weight')
        ->and($samples[0]['value'])->toBe(75.5)
        ->and($samples[1]['type_identifier'])->toBe('bloodPressureSystolic')
        ->and($samples[1]['value'])->toBe(120)
        ->and($samples[2]['type_identifier'])->toBe('bloodPressureDiastolic')
        ->and($samples[2]['value'])->toBe(80)
        ->and($samples[3]['type_identifier'])->toBe('a1c')
        ->and($samples[3]['value'])->toBe(6.5);
});

test('toSampleArrays returns insulin samples', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Insulin,
        insulinUnits: 10.0,
        insulinType: InsulinType::Basal,
    );

    $samples = $data->toSampleArrays();

    expect($samples)->toHaveCount(1)
        ->and($samples[0]['type_identifier'])->toBe('insulin')
        ->and($samples[0]['value'])->toBe(10.0)
        ->and($samples[0]['metadata'])->toBe(['insulin_type' => 'basal']);
});

test('toSampleArrays returns meds samples', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Meds,
        medicationName: 'Metformin',
        medicationDosage: '500mg',
    );

    $samples = $data->toSampleArrays();

    expect($samples)->toHaveCount(1)
        ->and($samples[0]['type_identifier'])->toBe('medication')
        ->and($samples[0]['value'])->toBe(1)
        ->and($samples[0]['metadata'])->toBe(['medication_name' => 'Metformin', 'medication_dosage' => '500mg']);
});

test('toSampleArrays returns exercise samples', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Exercise,
        exerciseType: 'Running',
        exerciseDurationMinutes: 30,
    );

    $samples = $data->toSampleArrays();

    expect($samples)->toHaveCount(1)
        ->and($samples[0]['type_identifier'])->toBe('exerciseMinutes')
        ->and($samples[0]['value'])->toBe(30)
        ->and($samples[0]['metadata'])->toBe(['exercise_type' => 'Running']);
});

test('toSampleArrays returns glucose samples', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Glucose,
        glucoseValue: 120.0,
        glucoseReadingType: GlucoseReadingType::Fasting,
    );

    $samples = $data->toSampleArrays();

    expect($samples)->toHaveCount(1)
        ->and($samples[0]['type_identifier'])->toBe('bloodGlucose')
        ->and($samples[0]['value'])->toBe(120.0)
        ->and($samples[0]['metadata'])->toBe(['glucose_reading_type' => 'fasting']);
});

test('fromParsedArray maps blood_pressure_systolic key to bpSystolic', function (): void {
    $data = HealthLogData::fromParsedArray([
        'is_health_data' => true,
        'log_type' => 'vitals',
        'blood_pressure_systolic' => '120',
        'blood_pressure_diastolic' => '80',
    ]);

    expect($data)
        ->bpSystolic->toBe(120)
        ->bpDiastolic->toBe(80);
});

test('fromParsedArray maps legacy bp_systolic key to bpSystolic', function (): void {
    $data = HealthLogData::fromParsedArray([
        'is_health_data' => true,
        'log_type' => 'vitals',
        'bp_systolic' => '130',
        'bp_diastolic' => '85',
    ]);

    expect($data)
        ->bpSystolic->toBe(130)
        ->bpDiastolic->toBe(85);
});

test('fromParsedArray prefers blood_pressure_systolic over bp_systolic', function (): void {
    $data = HealthLogData::fromParsedArray([
        'is_health_data' => true,
        'log_type' => 'vitals',
        'blood_pressure_systolic' => '120',
        'bp_systolic' => '999',
        'blood_pressure_diastolic' => '80',
        'bp_diastolic' => '999',
    ]);

    expect($data)
        ->bpSystolic->toBe(120)
        ->bpDiastolic->toBe(80);
});

test('toSampleArrays returns only BP samples when only blood pressure is provided', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Vitals,
        bpSystolic: 120,
        bpDiastolic: 80,
    );

    $samples = $data->toSampleArrays();

    expect($samples)->toHaveCount(2)
        ->and($samples[0]['type_identifier'])->toBe('bloodPressureSystolic')
        ->and($samples[0]['value'])->toBe(120)
        ->and($samples[1]['type_identifier'])->toBe('bloodPressureDiastolic')
        ->and($samples[1]['value'])->toBe(80);
});

test('toSampleArrays returns fallback dietary energy sample for food with no macros', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Food,
        notes: 'apple',
    );

    $samples = $data->toSampleArrays();

    expect($samples)->toHaveCount(1)
        ->and($samples[0]['type_identifier'])->toBe('dietaryEnergy')
        ->and($samples[0]['value'])->toBe(0);
});

test('toSampleArrays returns empty for vitals with no data', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Vitals,
    );

    expect($data->toSampleArrays())->toBe([]);
});

test('fromParsedArray handles null string values', function (): void {
    $data = HealthLogData::fromParsedArray([
        'is_health_data' => true,
        'log_type' => 'meds',
        'medication_name' => 'null',
        'medication_dosage' => '',
    ]);

    expect($data)
        ->medicationName->toBeNull()
        ->medicationDosage->toBeNull();
});
