<?php

declare(strict_types=1);

use App\DataObjects\MealPlanContext\MedicationData;
use Illuminate\Support\Facades\Date;

it('creates medication data with all fields', function (): void {
    $startedAt = Date::parse('2024-01-15');

    $data = new MedicationData(
        name: 'Metformin',
        dosage: '500mg',
        frequency: 'Twice daily',
        purpose: 'Blood sugar control',
        startedAt: $startedAt,
    );

    expect($data->name)->toBe('Metformin')
        ->and($data->dosage)->toBe('500mg')
        ->and($data->frequency)->toBe('Twice daily')
        ->and($data->purpose)->toBe('Blood sugar control')
        ->and($data->startedAt)->toBe($startedAt);
});

it('creates medication data with null optional fields', function (): void {
    $data = new MedicationData(
        name: 'Aspirin',
        dosage: null,
        frequency: null,
        purpose: null,
        startedAt: null,
    );

    expect($data->name)->toBe('Aspirin')
        ->and($data->dosage)->toBeNull()
        ->and($data->frequency)->toBeNull()
        ->and($data->purpose)->toBeNull()
        ->and($data->startedAt)->toBeNull();
});

it('creates medication data with mixed null and non-null values', function (): void {
    $startedAt = Date::parse('2024-06-01');

    $data = new MedicationData(
        name: 'Insulin',
        dosage: '10 units',
        frequency: null,
        purpose: 'Diabetes management',
        startedAt: $startedAt,
    );

    expect($data->name)->toBe('Insulin')
        ->and($data->dosage)->toBe('10 units')
        ->and($data->frequency)->toBeNull()
        ->and($data->purpose)->toBe('Diabetes management')
        ->and($data->startedAt)->toBe($startedAt);
});
