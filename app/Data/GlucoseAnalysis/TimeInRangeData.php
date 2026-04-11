<?php

declare(strict_types=1);

namespace App\Data\GlucoseAnalysis;

use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class TimeInRangeData extends Data
{
    public function __construct(
        public float $percentage,
        public float $abovePercentage,
        public float $belowPercentage,
        public int $inRangeCount,
        public int $aboveRangeCount,
        public int $belowRangeCount,
    ) {}
}
