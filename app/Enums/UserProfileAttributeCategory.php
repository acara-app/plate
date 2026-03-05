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
