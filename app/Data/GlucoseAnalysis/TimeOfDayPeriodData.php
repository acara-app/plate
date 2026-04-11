<?php

declare(strict_types=1);

namespace App\Data\GlucoseAnalysis;

use Spatie\LaravelData\Data;

/** @codeCoverageIgnore */
final class TimeOfDayPeriodData extends Data
{
    public function __construct(
        public int $count,
        public ?float $average,
    ) {}
}
