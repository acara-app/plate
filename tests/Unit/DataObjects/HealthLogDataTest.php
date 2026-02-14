<?php

declare(strict_types=1);

use App\DataObjects\HealthLogData;
use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\HealthEntryType;
use App\Enums\InsulinType;

beforeEach(function (): void {
    // Setup if needed
});

test('it formats glucose log correctly', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Glucose,
        glucoseValue: 120.0,
        glucoseReadingType: GlucoseReadingType::Fasting,
        glucoseUnit: GlucoseUnit::MgDl,
    );

    expect($data->formatForDisplay())->toBe('Glucose 120 mg/dL (Fasting)');
});

test('it formats glucose log with defaults', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Glucose,
        glucoseValue: 120.0,
    );

    expect($data->formatForDisplay())->toBe('Glucose 120 mg/dL (Random)');
});

test('it formats food log correctly', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Food,
        carbsGrams: 50,
    );

    expect($data->formatForDisplay())->toBe('Food - 50g carbs');
});

test('it formats insulin log correctly', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Insulin,
        insulinUnits: 10.0,
        insulinType: InsulinType::Basal,
    );

    expect($data->formatForDisplay())->toBe('Insulin 10 units (Basal)');
});

test('it formats insulin log with defaults', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Insulin,
        insulinUnits: 10.0,
    );

    expect($data->formatForDisplay())->toBe('Insulin 10 units (Bolus)');
});

test('it formats meds log correctly', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Meds,
        medicationName: 'Metformin',
        medicationDosage: '500mg',
    );

    expect($data->formatForDisplay())->toBe('Medication - Metformin 500mg');
});

test('it formats meds log without dosage', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Meds,
        medicationName: 'Metformin',
    );

    expect($data->formatForDisplay())->toBe('Medication - Metformin');
});

test('it formats vitals log for weight', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Vitals,
        weight: 75.5,
    );

    expect($data->formatForDisplay())->toBe('Weight 75.5 kg');
});

test('it formats vitals log for blood pressure', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Vitals,
        bpSystolic: 120,
        bpDiastolic: 80,
    );

    expect($data->formatForDisplay())->toBe('Blood Pressure 120/80');
});

test('it formats vitals log fallback', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Vitals,
    );

    expect($data->formatForDisplay())->toBe('Vitals');
});

test('it formats exercise log correctly', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Exercise,
        exerciseType: 'Running',
        exerciseDurationMinutes: 30,
    );

    expect($data->formatForDisplay())->toBe('Exercise - 30 min Running');
});

test('it exports to glucose record array', function (): void {
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

test('it exports to food record array', function (): void {
    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Food,
        carbsGrams: 50,
    );

    expect($data->toRecordArray())->toBe([
        'carbs_grams' => 50,
    ]);
});

test('it exports to insulin record array', function (): void {
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

test('it exports to meds record array', function (): void {
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

test('it exports to vitals record array', function (): void {
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
    ]);
});

test('it exports to exercise record array', function (): void {
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
