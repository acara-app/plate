<?php

declare(strict_types=1);

namespace App\Enums;

enum GlucoseUnit: string
{
    case MgDl = 'mg/dL';
    case MmolL = 'mmol/L';

    public static function mgDlToMmolL(float $value): float
    {
        return round($value / 18.0182, 1);
    }

    public static function mmolLToMgDl(float $value): float
    {
        return round($value * 18.0182, 0);
    }

    public function placeholder(): string
    {
        return match ($this) {
            self::MgDl => 'e.g., 120',
            self::MmolL => 'e.g., 6.7',
        };
    }

    public function label(): string
    {
        return $this->value;
    }

    /**
     * @return array{min: float, max: float}
     */
    public function validationRange(): array
    {
        return match ($this) {
            self::MgDl => ['min' => 20, 'max' => 600],
            self::MmolL => ['min' => 1.1, 'max' => 33.3],
        };
    }
}
