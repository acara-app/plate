<?php

declare(strict_types=1);

namespace App\Enums;

enum HeightUnit: string
{
    case Cm = 'cm';
    case In = 'in';
    case M = 'm';

    public static function fromInput(mixed $value): ?self
    {
        if (! is_string($value)) {
            return null;
        }

        return match (mb_strtolower(mb_trim($value))) {
            'cm', 'cms', 'centimeter', 'centimeters', 'centimetre', 'centimetres' => self::Cm,
            'in', 'inch', 'inches' => self::In,
            'm', 'meter', 'meters', 'metre', 'metres' => self::M,
            default => null,
        };
    }

    public function toCentimeters(float $value): float
    {
        return match ($this) {
            self::Cm => $value,
            self::In => $value * 2.54,
            self::M => $value * 100,
        };
    }

    public function fromCentimeters(float $centimeters): float
    {
        return match ($this) {
            self::Cm => $centimeters,
            self::In => $centimeters / 2.54,
            self::M => $centimeters / 100,
        };
    }
}
