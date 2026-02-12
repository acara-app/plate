<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Glucose unit preference enum for localization.
 * mg/dL: Used in USA
 * mmol/L: Used in Canada, UK, and most other countries
 */
enum GlucoseUnit: string
{
    case MgDl = 'mg/dL';
    case MmolL = 'mmol/L';

    /**
     * Convert a value from mg/dL to mmol/L
     */
    public static function mgDlToMmolL(float $value): float
    {
        return round($value / 18.0182, 1);
    }

    /**
     * Convert a value from mmol/L to mg/dL
     */
    public static function mmolLToMgDl(float $value): float
    {
        return round($value * 18.0182, 0);
    }

    /**
     * Get the placeholder value for the glucose input.
     */
    public function placeholder(): string
    {
        return match ($this) {
            self::MgDl => 'e.g., 120',
            self::MmolL => 'e.g., 6.7',
        };
    }

    /**
     * Get the label for display.
     */
    public function label(): string
    {
        return $this->value;
    }

    /**
     * Get the valid glucose value range for this unit.
     *
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
