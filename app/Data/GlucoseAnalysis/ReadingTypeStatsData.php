<?php

declare(strict_types=1);

namespace App\Data\GlucoseAnalysis;

use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class ReadingTypeStatsData extends Data
{
    public function __construct(
        public int $count,
        public float $percentage,
        public ?float $average,
    ) {}
}
