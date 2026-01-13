<?php

declare(strict_types=1);

namespace App\Enums;

enum AllergySeverity: string
{
    case Mild = 'mild';
    case Moderate = 'moderate';
    case Severe = 'severe';

    public function label(): string
    {
        return match ($this) {
            self::Mild => 'Mild',
            self::Moderate => 'Moderate',
            self::Severe => 'Severe (Anaphylactic)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Mild => 'Minor discomfort, can tolerate small amounts',
            self::Moderate => 'Noticeable reaction, should avoid',
            self::Severe => 'Life-threatening, must strictly avoid',
        };
    }
}
