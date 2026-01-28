<?php

declare(strict_types=1);

use App\Enums\DietaryPreferenceType;

it('has correct values', function (): void {
    expect(DietaryPreferenceType::Allergy->value)->toBe('allergy')
        ->and(DietaryPreferenceType::Intolerance->value)->toBe('intolerance')
        ->and(DietaryPreferenceType::Pattern->value)->toBe('pattern')
        ->and(DietaryPreferenceType::Dislike->value)->toBe('dislike')
        ->and(DietaryPreferenceType::Restriction->value)->toBe('restriction');
});

it('returns correct labels', function (): void {
    expect(DietaryPreferenceType::Allergy->label())->toBe('Allergy')
        ->and(DietaryPreferenceType::Intolerance->label())->toBe('Intolerance')
        ->and(DietaryPreferenceType::Pattern->label())->toBe('Dietary Pattern')
        ->and(DietaryPreferenceType::Dislike->label())->toBe('Dislike')
        ->and(DietaryPreferenceType::Restriction->label())->toBe('Religious/Cultural Restriction');
});
