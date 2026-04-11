<?php

declare(strict_types=1);

use App\Data\MobileSync\BloodGlucoseMetadata;

covers(BloodGlucoseMetadata::class);

it('normalizes null metadata to default glucose reading type', function (): void {
    expect(BloodGlucoseMetadata::normalize(null))->toBe([
        'glucose_reading_type' => 'random',
    ]);
});

it('normalizes empty array to default glucose reading type', function (): void {
    expect(BloodGlucoseMetadata::normalize([]))->toBe([
        'glucose_reading_type' => 'random',
    ]);
});

it('drops unknown keys from raw metadata', function (): void {
    $result = BloodGlucoseMetadata::normalize(['someKey' => 'someValue']);

    expect($result)->toBe(['glucose_reading_type' => 'random']);
});

it('accepts provided camelCase glucose reading type', function (): void {
    $result = BloodGlucoseMetadata::normalize(['glucoseReadingType' => 'fasting']);

    expect($result)->toBe(['glucose_reading_type' => 'fasting']);
});
