<?php

declare(strict_types=1);

use App\DataObjects\MobileSync\MedicationDoseEventMetadata;

covers(MedicationDoseEventMetadata::class);

it('returns null for null metadata', function (): void {
    expect(MedicationDoseEventMetadata::normalize(null))->toBeNull();
});

it('returns null for empty metadata', function (): void {
    expect(MedicationDoseEventMetadata::normalize([]))->toBeNull();
});

it('maps camelCase medicationName to snake_case', function (): void {
    $result = MedicationDoseEventMetadata::normalize(['medicationName' => 'Metformin']);

    expect($result)
        ->toHaveKey('medication_name', 'Metformin')
        ->not->toHaveKey('medicationName');
});

it('maps camelCase logStatus to snake_case', function (): void {
    $result = MedicationDoseEventMetadata::normalize(['logStatus' => 'taken']);

    expect($result)
        ->toHaveKey('log_status', 'taken')
        ->not->toHaveKey('logStatus');
});

it('maps both fields together', function (): void {
    $result = MedicationDoseEventMetadata::normalize([
        'medicationName' => 'Aspirin',
        'logStatus' => 'taken',
    ]);

    expect($result)->toBe([
        'medication_name' => 'Aspirin',
        'log_status' => 'taken',
    ]);
});

it('filters null values from output', function (): void {
    $result = MedicationDoseEventMetadata::normalize([
        'medicationName' => 'Metformin',
    ]);

    expect($result)
        ->toHaveKey('medication_name', 'Metformin')
        ->not->toHaveKey('log_status');
});

it('drops unknown keys from raw input', function (): void {
    $result = MedicationDoseEventMetadata::normalize([
        'medicationName' => 'Aspirin',
        'logStatus' => 'taken',
        'someOtherKey' => 'value',
    ]);

    expect($result)
        ->toBe([
            'medication_name' => 'Aspirin',
            'log_status' => 'taken',
        ]);
});
