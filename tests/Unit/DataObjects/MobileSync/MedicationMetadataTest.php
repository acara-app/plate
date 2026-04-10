<?php

declare(strict_types=1);

use App\DataObjects\MobileSync\MedicationMetadata;

covers(MedicationMetadata::class);

it('returns null for null metadata', function (): void {
    expect(MedicationMetadata::normalize(null))->toBeNull();
});

it('returns null for empty metadata', function (): void {
    expect(MedicationMetadata::normalize([]))->toBeNull();
});

it('maps camelCase displayText to snake_case display_text', function (): void {
    $result = MedicationMetadata::normalize(['displayText' => 'Metformin Hydrochloride 500 mg']);

    expect($result)
        ->toHaveKey('display_text', 'Metformin Hydrochloride 500 mg')
        ->not->toHaveKey('displayText');
});

it('maps camelCase hasSchedule to snake_case has_schedule', function (): void {
    $result = MedicationMetadata::normalize(['hasSchedule' => 'true']);

    expect($result)
        ->toHaveKey('has_schedule', 'true')
        ->not->toHaveKey('hasSchedule');
});

it('maps camelCase isArchived to snake_case is_archived', function (): void {
    $result = MedicationMetadata::normalize(['isArchived' => 'false']);

    expect($result)
        ->toHaveKey('is_archived', 'false')
        ->not->toHaveKey('isArchived');
});

it('passes name and form through unchanged', function (): void {
    $result = MedicationMetadata::normalize([
        'name' => 'Metformin 500mg',
        'form' => 'pill',
    ]);

    expect($result)->toBe([
        'name' => 'Metformin 500mg',
        'form' => 'pill',
    ]);
});

it('maps all iOS medication library fields', function (): void {
    $result = MedicationMetadata::normalize([
        'name' => 'Metformin 500mg',
        'displayText' => 'Metformin Hydrochloride 500 mg',
        'form' => 'pill',
        'hasSchedule' => 'true',
        'isArchived' => 'false',
    ]);

    expect($result)->toBe([
        'name' => 'Metformin 500mg',
        'display_text' => 'Metformin Hydrochloride 500 mg',
        'form' => 'pill',
        'has_schedule' => 'true',
        'is_archived' => 'false',
    ]);
});

it('filters null values from output', function (): void {
    $result = MedicationMetadata::normalize([
        'displayText' => 'Aspirin 81 mg',
    ]);

    expect($result)
        ->toHaveKey('display_text', 'Aspirin 81 mg')
        ->not->toHaveKey('name')
        ->not->toHaveKey('form')
        ->not->toHaveKey('has_schedule')
        ->not->toHaveKey('is_archived');
});

it('drops unknown keys from raw input', function (): void {
    $result = MedicationMetadata::normalize([
        'displayText' => 'Lisinopril 10 mg',
        'form' => 'tablet',
        'someOtherKey' => 'value',
    ]);

    expect($result)->toBe([
        'display_text' => 'Lisinopril 10 mg',
        'form' => 'tablet',
    ]);
});
