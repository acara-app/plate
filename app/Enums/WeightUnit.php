<?php

declare(strict_types=1);

namespace App\Enums;

enum WeightUnit: string
{
    case Kg = 'kg';
    case Lb = 'lb';

    public static function fromInput(mixed $value): ?self
    {
        if (! is_string($value)) {
            return null;
        }

        return match (mb_strtolower(mb_trim($value))) {
            'kg', 'kgs', 'kilo', 'kilos', 'kilogram', 'kilograms' => self::Kg,
            'lb', 'lbs', 'pound', 'pounds' => self::Lb,
            default => null,
        };
    }

    public function toKg(float $value): float
    {
        return match ($this) {
            self::Kg => $value,
            self::Lb => $value * 0.45359237,
        };
    }

    public function fromKg(float $kilograms): float
    {
        return match ($this) {
            self::Kg => $kilograms,
            self::Lb => $kilograms / 0.45359237,
        };
    }
}
