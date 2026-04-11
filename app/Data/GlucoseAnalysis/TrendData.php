<?php

declare(strict_types=1);

namespace App\Data\GlucoseAnalysis;

use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class TrendData extends Data
{
    public function __construct(
        public ?float $slopePerDay,
        public ?float $slopePerWeek,
        public ?string $direction,
        public ?float $firstValue,
        public ?float $lastValue,
    ) {}
}
