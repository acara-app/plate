<?php

declare(strict_types=1);

namespace App\Enums;

enum DietaryPreferenceType: string
{
    case Allergy = 'allergy';
    case Intolerance = 'intolerance';
    case Pattern = 'pattern';
    case Dislike = 'dislike';
    case Restriction = 'restriction';

    public function label(): string
    {
        return match ($this) {
            self::Allergy => 'Allergy',
            self::Intolerance => 'Intolerance',
            self::Pattern => 'Dietary Pattern',
            self::Dislike => 'Dislike',
            self::Restriction => 'Religious/Cultural Restriction',
        };
    }
}
