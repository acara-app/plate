<?php

declare(strict_types=1);

namespace App\Enums;

enum SpikeRiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'LOW',
            self::Medium => 'MEDIUM',
            self::High => 'HIGH',
        };
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::Low => 'text-emerald-500',
            self::Medium => 'text-amber-500',
            self::High => 'text-red-500',
        };
    }

    public function gaugePercentage(): int
    {
        return match ($this) {
            self::Low => 25,
            self::Medium => 55,
            self::High => 85,
        };
    }
}
