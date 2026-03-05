<?php

declare(strict_types=1);

use App\Enums\UserProfileAttributeCategory;

it('has all expected cases', function (): void {
    expect(UserProfileAttributeCategory::cases())->toHaveCount(7);
});

it('has correct string values', function (UserProfileAttributeCategory $case, string $expected): void {
    expect($case->value)->toBe($expected);
})->with([
    [UserProfileAttributeCategory::Allergy, 'allergy'],
    [UserProfileAttributeCategory::Intolerance, 'intolerance'],
    [UserProfileAttributeCategory::DietaryPattern, 'dietary_pattern'],
    [UserProfileAttributeCategory::Dislike, 'dislike'],
    [UserProfileAttributeCategory::Restriction, 'restriction'],
    [UserProfileAttributeCategory::HealthCondition, 'health_condition'],
    [UserProfileAttributeCategory::Medication, 'medication'],
]);

it('has labels for all cases', function (): void {
    foreach (UserProfileAttributeCategory::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
    }
});

it('can be created from string value', function (): void {
    expect(UserProfileAttributeCategory::from('allergy'))->toBe(UserProfileAttributeCategory::Allergy)
        ->and(UserProfileAttributeCategory::from('health_condition'))->toBe(UserProfileAttributeCategory::HealthCondition)
        ->and(UserProfileAttributeCategory::from('medication'))->toBe(UserProfileAttributeCategory::Medication);
});
