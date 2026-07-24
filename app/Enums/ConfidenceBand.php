<?php

declare(strict_types=1);

namespace App\Enums;

enum ConfidenceBand: string
{
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 80 => self::High,
            $score >= 50 => self::Medium,
            default => self::Low,
        };
    }
}
