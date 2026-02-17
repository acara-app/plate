<?php

declare(strict_types=1);

use App\Ai\Agents\HealthDataParserAgent;
use App\DataObjects\HealthLogData;
use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\HealthEntryType;
use Tests\Helpers\TestJsonSchema;

it('parses health log data correctly', function (): void {
    HealthDataParserAgent::fake([
        [
            'is_health_data' => true,
            'log_type' => 'glucose',
            'glucose_value' => 120,
            'glucose_reading_type' => 'fasting',
            'measured_at' => '2023-01-01T10:00:00',
        ],
    ]);

    $agent = resolve(HealthDataParserAgent::class);
    $result = $agent->parse('My glucose was 120 fasting');

    expect($result)
        ->toBeInstanceOf(HealthLogData::class)
        ->isHealthData->toBeTrue()
        ->logType->toBe(HealthEntryType::Glucose)
        ->glucoseValue->toBe(120.0)
        ->glucoseReadingType->toBe(GlucoseReadingType::Fasting);
});

it('handles non-health intent', function (): void {
    HealthDataParserAgent::fake([
        [
            'is_health_data' => false,
            'log_type' => 'question',
        ],
    ]);

    $agent = resolve(HealthDataParserAgent::class);
    $result = $agent->parse('How are you?');

    expect($result)
        ->toBeInstanceOf(HealthLogData::class)
        ->isHealthData->toBeFalse();
});

it('defines correct schema', function (): void {
    $agent = resolve(HealthDataParserAgent::class);
    $jsonSchema = new TestJsonSchema;

    $schema = $agent->schema($jsonSchema);

    expect($schema)
        ->toHaveKey('is_health_data')
        ->toHaveKey('log_type')
        ->toHaveKey('glucose_value');
});

it('includes instructions', function (): void {
    $agent = resolve(HealthDataParserAgent::class);
    $instructions = $agent->instructions();

    expect($instructions)
        ->toContain('You are a health data parser')
        ->toContain('INTENT DETECTION');
});

it('parses glucose with valid reading types', function (): void {
    HealthDataParserAgent::fake([
        [
            'is_health_data' => true,
            'log_type' => 'glucose',
            'glucose_value' => 110,
            'glucose_reading_type' => 'before-meal',
            'glucose_unit' => 'mg/dL',
        ],
    ]);

    $agent = resolve(HealthDataParserAgent::class);
    $result = $agent->parse('Glucose before meal 110');

    expect($result->glucoseReadingType)->toBe(GlucoseReadingType::BeforeMeal);
    expect($result->glucoseUnit)->toBe(GlucoseUnit::MgDl);
});

it('handles invalid glucose reading type gracefully', function (): void {
    HealthDataParserAgent::fake([
        [
            'is_health_data' => true,
            'log_type' => 'glucose',
            'glucose_value' => 120,
            'glucose_reading_type' => 'invalid-type',
            'glucose_unit' => 'invalid-unit',
        ],
    ]);

    $agent = resolve(HealthDataParserAgent::class);
    $result = $agent->parse('My glucose is 120');

    expect($result->glucoseReadingType)->toBeNull();
    expect($result->glucoseUnit)->toBeNull();
});

it('handles insulin data with invalid type', function (): void {
    HealthDataParserAgent::fake([
        [
            'is_health_data' => true,
            'log_type' => 'insulin',
            'insulin_units' => 5,
            'insulin_type' => 'invalid-type',
        ],
    ]);

    $agent = resolve(HealthDataParserAgent::class);
    $result = $agent->parse('Took 5 units insulin');

    expect($result->insulinType)->toBeNull();
    expect($result->insulinUnits)->toBe(5.0);
});

it('handles medication data with null values', function (): void {
    HealthDataParserAgent::fake([
        [
            'is_health_data' => true,
            'log_type' => 'meds',
            'medication_name' => 'null',
            'medication_dosage' => '',
        ],
    ]);

    $agent = resolve(HealthDataParserAgent::class);
    $result = $agent->parse('Took my medication');

    expect($result->medicationName)->toBeNull();
    expect($result->medicationDosage)->toBeNull();
});

it('handles numeric values as null strings', function (): void {
    HealthDataParserAgent::fake([
        [
            'is_health_data' => true,
            'log_type' => 'glucose',
            'glucose_value' => 'null',
            'carbs_grams' => 'null',
            'insulin_units' => 'null',
            'weight' => 'null',
            'bp_systolic' => 'null',
            'bp_diastolic' => 'null',
            'exercise_duration_minutes' => 'null',
        ],
    ]);

    $agent = resolve(HealthDataParserAgent::class);
    $result = $agent->parse('My glucose reading');

    expect($result->glucoseValue)->toBeNull();
    expect($result->carbsGrams)->toBeNull();
    expect($result->insulinUnits)->toBeNull();
    expect($result->weight)->toBeNull();
    expect($result->bpSystolic)->toBeNull();
    expect($result->bpDiastolic)->toBeNull();
    expect($result->exerciseDurationMinutes)->toBeNull();
});
