<?php

declare(strict_types=1);

use App\DataObjects\HealthLogData;
use App\DataObjects\HealthParserResult;
use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\HealthEntryType;

it('converts valid data correctly', function (): void {
    $result = new HealthParserResult(
        is_health_data: true,
        log_type: 'glucose',
        glucose_value: 120,
        glucose_reading_type: 'fasting',
        glucose_unit: 'mg/dL',
        measured_at: '2023-01-01 10:00:00',
    );

    $logData = $result->toHealthLogData();

    expect($logData)
        ->toBeInstanceOf(HealthLogData::class)
        ->isHealthData->toBeTrue()
        ->logType->toBe(HealthEntryType::Glucose)
        ->glucoseValue->toBe(120.0)
        ->glucoseReadingType->toBe(GlucoseReadingType::Fasting)
        ->glucoseUnit->toBe(GlucoseUnit::MgDl)
        ->measuredAt->not->toBeNull();
});

it('handles string numbers correctly', function (): void {
    $result = new HealthParserResult(
        glucose_value: '120.5',
        carbs_grams: '45',
        insulin_units: '5.5',
        weight: '80',
        bp_systolic: '120',
        bp_diastolic: '80',
        exercise_duration_minutes: '30',
    );

    $logData = $result->toHealthLogData();

    expect($logData)
        ->glucoseValue->toBe(120.5)
        ->carbsGrams->toBe(45)
        ->insulinUnits->toBe(5.5)
        ->weight->toBe(80.0)
        ->bpSystolic->toBe(120)
        ->bpDiastolic->toBe(80)
        ->exerciseDurationMinutes->toBe(30);
});

it('handles null and invalid values gracefully', function (): void {
    $result = new HealthParserResult(
        is_health_data: 'not-bool', // Should default to false
        log_type: 'invalid-type', // Should default to Glucose
        glucose_value: 'not-numeric',
        glucose_reading_type: 'invalid-enum',
        measured_at: null,
    );

    $logData = $result->toHealthLogData();

    expect($logData)
        ->isHealthData->toBeFalse()
        ->logType->toBe(HealthEntryType::Glucose)
        ->glucoseValue->toBeNull()
        ->glucoseReadingType->toBeNull()
        ->measuredAt->toBeNull();
});

it('handles explicitly null string values "null"', function (): void {
    $result = new HealthParserResult(
        glucose_value: 'null',
        glucose_reading_type: 'null',
        medication_name: 'null',
    );

    $logData = $result->toHealthLogData();

    expect($logData)
        ->glucoseValue->toBeNull()
        ->glucoseReadingType->toBeNull()
        ->medicationName->toBeNull();
});

it('handles empty strings correctly', function (): void {
    $result = new HealthParserResult(
        medication_dosage: '',
    );

    $logData = $result->toHealthLogData();

    expect($logData->medicationDosage)->toBeNull();
});

it('converts boolean values correctly', function (): void {
    $trueResult = new HealthParserResult(is_health_data: true);
    expect($trueResult->toHealthLogData()->isHealthData)->toBeTrue();

    $falseResult = new HealthParserResult(is_health_data: false);
    expect($falseResult->toHealthLogData()->isHealthData)->toBeFalse();
});
