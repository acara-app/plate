<?php

declare(strict_types=1);

use App\DataObjects\Memory\MemoryValidationResultData;

it('can be created from array using from method', function (): void {
    $data = [
        'isValid' => true,
        'confidence' => 0.95,
        'reason' => 'Content verified against current data.',
        'suggestedUpdate' => null,
    ];

    $result = MemoryValidationResultData::from($data);

    expect($result)
        ->isValid->toBeTrue()
        ->confidence->toBe(0.95)
        ->reason->toBe('Content verified against current data.')
        ->suggestedUpdate->toBeNull();
});

it('can be created directly with constructor', function (): void {
    $result = new MemoryValidationResultData(
        isValid: false,
        confidence: 0.82,
        reason: 'Information is outdated.',
        suggestedUpdate: 'The user now prefers tea over coffee.',
    );

    expect($result)
        ->isValid->toBeFalse()
        ->confidence->toBe(0.82)
        ->reason->toBe('Information is outdated.')
        ->suggestedUpdate->toBe('The user now prefers tea over coffee.');
});

it('can be converted to array', function (): void {
    $result = new MemoryValidationResultData(
        isValid: true,
        confidence: 0.99,
    );

    $array = $result->toArray();

    expect($array)
        ->toBeArray()
        ->toHaveKeys(['is_valid', 'confidence', 'reason', 'suggested_update'])
        ->and($array['is_valid'])->toBeTrue()
        ->and($array['confidence'])->toBe(0.99);
});

it('handles optional fields correctly', function (): void {
    $result = new MemoryValidationResultData(
        isValid: true,
        confidence: 0.75,
    );

    expect($result)
        ->reason->toBeNull()
        ->suggestedUpdate->toBeNull();
});

it('accepts confidence values between 0 and 1', function (float $confidence): void {
    $result = new MemoryValidationResultData(
        isValid: true,
        confidence: $confidence,
    );

    expect($result->confidence)->toBe($confidence);
})->with([
    'zero' => 0.0,
    'quarter' => 0.25,
    'half' => 0.5,
    'three quarters' => 0.75,
    'one' => 1.0,
]);
