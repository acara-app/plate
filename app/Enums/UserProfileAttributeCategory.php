<?php

declare(strict_types=1);

namespace App\Enums;

enum UserProfileAttributeCategory: string
{
    case Allergy = 'allergy';
    case Intolerance = 'intolerance';
    case DietaryPattern = 'dietary_pattern';
    case Dislike = 'dislike';
    case Restriction = 'restriction';
    case HealthCondition = 'health_condition';
    case Medication = 'medication';

    /**
     * @return array<int, self>
     */
    public static function dietaryPreferences(): array
    {
        return [
            self::Allergy,
            self::Intolerance,
            self::DietaryPattern,
            self::Dislike,
            self::Restriction,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function dietaryPreferenceValues(): array
    {
        return array_map(
            fn (self $category): string => $category->value,
            self::dietaryPreferences(),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Allergy => 'Allergy',
            self::Intolerance => 'Intolerance',
            self::DietaryPattern => 'Dietary Pattern',
            self::Dislike => 'Dislike',
            self::Restriction => 'Religious/Cultural Restriction',
            self::HealthCondition => 'Health Condition',
            self::Medication => 'Medication',
        };
    }
}
